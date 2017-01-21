<?php
class Chat
{
	/*
	* $dbh resource: database (mysqli) connection resource resource
	*/
	private $dbh = null;
	
	/*
	* $tables_prefix string prefix of database tables
	*/
	private $tables_prefix = '';
	
	/*
	* $chat_id int id of this chat in the chat database table
	*/
	private $chat_id;
	
	
	/*
	* Can take 1, 2 or 5 arguments:
	*
	* @param $chat_id int : id of the chat in the chat table 
	*
	* OR
	* @param $dbh resource: database (mysqli) connection resource resource
	* @param $tp string prefix of database tables
	*
	* OR
	* @param $server string database server
	* @param $username string database user
	* @param $password string database user password
	* @param $database string database name
	* @param $prefix   string prefix of database tables defaults to empty string
	*/
	public function __construct($id='') 
	{
		if( !empty($id) ) {
			$this->set_id( $id );
		}
	}
	
	public function connect($server, $user, $pass, $db, $tp='')
	{
		try {
			$this->set_dbh( new mysqli($server, $user, $pass, $db) );
			$this->set_tables_prefix( $tp );
		}
		catch(Exception $e) {
			$this->set_dbh(null);
		}
	}
	
	public function post($message)
	{
		$dbh     = $this->get_dbh();
		$tp      = $this->get_tables_prefix();
		$message = trim($message);
		$sql     = "INSERT INTO {$tp}chat (`message`, `status`, `date`) VALUES ( ?, 'pending', UTC_TIMESTAMP() )";
		
		try {
			$stmt = $dbh->prepare($sql);
			$stmt->bind_param("s", $message);
			$stmt->execute();
			
			if($stmt->affected_rows) {
				$chat_id = $stmt->insert_id;
			}
			
			$stmt->close();
			$this->set_id($chat_id);
			
			return $chat_id;
		}
		catch(Exception $e) {
			return null;
		}
	}

	/*
	* update_data array with possible members:
	* message 
	* status
	* meta data e.g other values not in the chat table
	* meta data should be added as array in format: meta_key =>array('value'=>your_value, 'overwrite'=>boolean
	* e.g update( array(
	* 'message' => 'updated message',
	* 'status' => 'new status',
	* 'sender_id' => array('value'=>3, 'overwrite'=>true),
	* 'fontcolor' => array('value'=>'blue', 'overwrite'=>true),
	* ...
	* ) )
	* The meta data 'overwrite' parameter is optional, defaults to false
	*/
	public function update($update_data)
	{
		$chat_id   = $this->get_id();
		$udata     = array();
		$meta_data = array();
		$where     = array( 'id'=>$chat_id );
		
		foreach( $update_data AS $key => $value ) {
			if( ($key != 'id') && ($key != 'date') ) {

				if( is_array($value) ) {
					$meta_data[$key] = $value;
				}
				else {
					$udata[$key] = $value;
				}
			}
		}
		
		if( !empty($meta_data) ){
			$this->update_meta_data($meta_data); 
		}
		if( !empty($udata) ){
			$this->update_table( $this->get_tables_prefix(). "chat", $udata, $where );
		}
	}
	
	public function get($data='')
	{
		$chat_id = $this->get_id();
		$tp      = $this->get_tables_prefix();
		$dbh     = $this->get_dbh();
		
		if( empty($data) ) {
			$sel_str = "*";
		}
		else {
			if( is_string($data) ) {
				$sel_str = "`$data`";
			}
			else if( is_array($data) ) {
				$sel_str = '';
				foreach($data AS $data_key) {
					$sel_str .= "`$data_key`, ";
				}
				$sel_str = rtrim( trim($sel_str), ',' );
			}
		}
		
		try {
			$stmt = $dbh->prepare( "SELECT $sel_str FROM  {$tp}chat WHERE `id` = ". $chat_id );
			
			$stmt->execute();
			
			if( empty($data) || is_array($data) ) {
				$result = $stmt->get_result();
				return $result->fetch_assoc();
			}
			else if( is_string($data) ) {
				$stmt->store_result(); //solves the problem of Allowed memory size of 134217728 bytes exhausted. credits http://stackoverflow.com/questions/5052870/mysqli-bind-result-allocates-too-much-memory
				$stmt->bind_result($$data);
				$stmt->fetch();
				return $$data;
			}
		}
		catch(Exception $e) {
			return '';
		}
	}
	
	public function get_meta($meta_key='', $limit = '')
	{
		$chat_id   = $this->get_id();
		$tp        = $this->get_tables_prefix();
		$meta_key  = trim($meta_key);
		$limit     = trim($limit);
		$values    = array();
		
		try {
			$dbh  = $this->get_dbh();
			
			if( empty($meta_key) ) {
				$str = "SELECT * FROM {$tp}chat_meta WHERE `chat_id` = $chat_id";
			}
			else {
				$str  = "SELECT `meta_value` FROM {$tp}chat_meta WHERE `chat_id` = $chat_id AND `meta_key` = ?";
			}
			
			$str .= ( !empty($limit)    ? " LIMIT ?" : "" );
			$stmt = $dbh->prepare( $str );
			
			if( !empty($meta_key) && !empty($limit) ) {
				$stmt->bind_param("ss", $meta_key, $limit);
			}
			else if( !empty($meta_key) ) {
				$stmt->bind_param("s", $meta_key);
			}
			
			$stmt->execute();
			
			//without this, $stmt->num_rows returns 0, even if there is some result set returned
			$stmt->store_result();
			
			$num_rows = $stmt->num_rows;
			
			//without this, we get: Warning: Course::get_meta(): Couldn't fetch mysqli_stmt in FILE_PATH 
			if($num_rows < 1){
				return '';
			}
			
			if( !empty($meta_key) ) {
				$stmt->bind_result($meta_value);
			
				while ($stmt->fetch()) {
					$values[] = $meta_value;
				}
			}
			else {
				$stmt->bind_result($meta_id, $chat_id, $meta_key, $meta_value);
				while ($stmt->fetch()) {
					$values[] = array('id'=>$meta_id, 'chat_id'=>$chat_id, 'meta_key'=>$meta_key, 'meta_value'=>$meta_value);
				}
			}
			
			$stmt->close();
			
			if( !empty($meta_key) ) {
				return ( $num_rows == 1 || $limit == '1' ? $values[0] : $values );
			}
			else {
				return $values;
			}
		}
		catch(Exception $e) {
			return null;
		}
	}
	
	/*
	* Retrieves new messages more recent than/by $chat_id 
	* - the $chat_id parameter (sent by the client)
	* represents the id of the last message received by the client. 
	*/
	public function get_messages( $chat_id=0, $where=array(), $order=array(), $limit = 0 )
	{
		$tp    = $this->get_tables_prefix();
		$dbh   = $this->get_dbh();
		$chats = array();
		
		/*
		// retrieve messages newer than $chat_id
		$sql = ( $ids_only ? "SELECT `id` " : "SELECT `id`,  `message`, `date` " ).  
            "FROM {$tp}chat ".
            "WHERE `id` > ". $chat_id." ".
            "AND `status` = 'pending' ". 
            "ORDER BY `id` ASC";
			
		if( ($chat_id == 0) ){
			$sql .= " LIMIT $initial_limit"; // on the first load only retrieve the last 50 messages from the server
		}
		*/
		$chat_table_data    = array('id', 'message', 'status', 'date');
		$meta_table_aliases = range('a', 'z');
		
		$sql      = "SELECT chat.id FROM {$tp}chat chat ";
		$counter1 = 0;
		$counter2 = 0;
		
		foreach($where AS $key => $value) {
			if( !in_array($key, $chat_table_data) ) {
				$meta_table_alias = $meta_table_aliases[$counter1];
				$sql .= "JOIN {$tp}chat_meta $meta_table_alias ON chat.id={$meta_table_alias}.chat_id AND {$meta_table_alias}.meta_key='$key' ";
				++$counter1;
			}
		}
		
		$sql .= "WHERE chat.id > $chat_id ";

		foreach($where AS $key => $value) {
			if( in_array($key, $chat_table_data) ) {
				$sql .= "AND chat.{$key} = '$value' ";
			}
			else {
				$meta_table_alias = $meta_table_aliases[$counter2];
				$sql .= "AND {$meta_table_alias}.meta_value = '$value' ";
				++$counter2;
			}
		}

		$sql .= "ORDER BY chat.id ASC";
		
		if( !empty($limit) ){
			$sql .= " LIMIT $limit";
		}
		
		try{
			$stmt = $dbh->prepare($sql);
			
			$stmt->execute();
			$stmt->bind_result($chat_id);
			
			while ($stmt->fetch()) {
				$chats[] = $chat_id;
			}
			
			return $chats;
		}
		catch(Exception $e) {
			return $chats;
		}
	}

	protected function insert($table, $data)
	{
		$dbh           = $this->get_dbh();
		$bind_str      = '';
		$fields        = '';
		$placeholders  = '';
		$values        = array();
		
		if ( empty($table) || empty($data) ) {
			return false;
		}
			
		foreach( $data AS $column => $value ) {
			$fields .= "`$column`,";
			$placeholders .= "?,";
			$values[] = $value;
		}
		
		foreach($values AS $value) {
			switch(gettype($value)) {
				case 'integer' : $bind_str .= 'i'; break;
				case 'double'  : $bind_str .= 'd'; break;
				case 'string'  : $bind_str .= 's'; break;
			}
		}
		
		array_unshift($values, $bind_str);
		
		$fields = rtrim( trim($fields), ',' );
		$placeholders = rtrim( trim($placeholders), ',' );
		
		$stmt = $dbh->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
		
		call_user_func_array( array($stmt, 'bind_param'), $this->reference_values($values) );
			
		$stmt->execute();
			
		return $stmt->affected_rows ?  $stmt->insert_id : false;
	}
	
	protected function update_table($table, $update_data, $where='')
	{
		$tp            = $this->get_tables_prefix();
		$dbh           = $this->get_dbh();
		$placeholders  = '';
		$where_clause  = '';
		$bind_str       = '';
		$update_values = array();
		$where_values  = array();
		$count         = 0;
		
		foreach( $update_data AS $column => $value ) {
			$placeholders .= "`$column` = ?, "; 
			$update_values[] = $value;
		}
		
		foreach ( $where as $field => $value ) {
			if ( $count > 0 ) {
				$where_clause .= " AND ";
			}

			$where_clause .= "`$field` = ?";
			$where_values[] = $value;
	
			$count++;
		}
		
		$placeholders = rtrim( trim($placeholders), ',' );
		$stmt = $dbh->prepare( "UPDATE {$table} SET {$placeholders} WHERE {$where_clause}" );
		$replacement_values = array_merge($update_values, $where_values);
		
		foreach($replacement_values AS $value) {
			switch(gettype($value)) {
				case 'integer' : $bind_str .= 'i'; break;
				case 'double'  : $bind_str .= 'd'; break;
				case 'string'  : $bind_str .= 's'; break;
			}
		}
		
		array_unshift($replacement_values, $bind_str);
		
		call_user_func_array( array($stmt, 'bind_param'), $this->reference_values($replacement_values) );
		
		$stmt->execute();
		$affected_rows = $stmt->affected_rows;
		$stmt->close();
		
		return $affected_rows;
	}
	
	protected function delete($table, $id)
	{
		$tp   = $this->get_tables_prefix();
		$dbh  = $this->get_dbh();
		$stmt = $dbh->prepare("DELETE FROM $table WHERE id = ?");
			
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$affected_rows = $stmt->affected_rows;
		$stmt->close();

		return $affected_rows;
	}
	
	private function reference_values($array)
	{
		$refs = array();
		foreach ($array as $key => $value) {
			$refs[$key] = &$array[$key]; 
		}
		
		return $refs; 
	}
	
	private function get_tables_prefix()
	{
		return $this->tables_prefix;
	}
	
	private function set_tables_prefix($prefix)
	{
		$this->tables_prefix = $prefix;
	}
	
	private function get_dbh()
	{
		return $this->dbh;
	}
	
	private function set_dbh($dbh)
	{
		$this->dbh = $dbh;
	}
	
	/*
	* $data_array associative array of associative array(s) 
	* format : array(
	* 	key => array('value'=>value, 'overwrite'=>boolean),
	*	key => array('value'=>value, 'overwrite'=>boolean),
	*	...
	* )
	* e.g array(
	*	'fontweight' => array('value'=>'bold', 'overwrite'=>true),
	*	'fontsize' => array('value'=>'18px', 'overwrite'=>true)
	*)
	*/
	private function update_meta_data($data_array)
	{
		$chat_id = $this->get_id();
		$tp      = $this->get_tables_prefix();
		
		foreach($data_array AS $meta_key => $val_array){
			if( $this->meta_exists($meta_key) && !empty($val_array['overwrite']) ){
				$where = array('chat_id'=>$chat_id, 'meta_key'=>$meta_key);
				$this->update_table( $this->get_tables_prefix(). "chat_meta", array('meta_value'=>$val_array['value']), $where);
			}
			else{
				$this->insert_meta_data($meta_key, $val_array['value']);
			}
		}
	}
	
	private function insert_meta_data($meta_key, $meta_value)
	{
		$chat_id = $this->get_id();
		$tp      = $this->get_tables_prefix();
		
		try {
			$dbh  = $this->get_dbh();
			$stmt = $dbh->prepare( "INSERT INTO {$tp}chat_meta (`chat_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)" );
			$stmt->bind_param("iss", $chat_id, $meta_key, $meta_value);
			$stmt->execute();
			
			if($stmt->affected_rows) {
				$meta_id = $stmt->insert_id;
			}
			
			$stmt->close();
			
			return $meta_id;
		}
		catch(Exception $e) {
			return null;
		}
	}
	
	private function meta_exists($meta_key)
	{
		$chat_id = $this->get_id();
		$tp      = $this->get_tables_prefix();
		$ids     = array();
		
		try {
			$dbh  = $this->get_dbh();
			$stmt = $dbh->prepare( "SELECT `id` FROM {$tp}chat_meta WHERE `chat_id` = $chat_id AND `meta_key` = ? LIMIT 1" );
			$stmt->bind_param("s", $meta_key);
			$stmt->execute();
			
			//without this, $stmt->num_rows returns 0, even if there is some result set returned
			$stmt->store_result();
			
			$num_rows = $stmt->num_rows;
			
			//without this, we get: Warning: Course::get_meta(): Couldn't fetch mysqli_stmt in FILE_PATH 
			if($num_rows < 1){
				return false;
			}
			
			$stmt->bind_result($meta_id);
			
			while ($stmt->fetch()) {
				$ids[] = $meta_id;
			}
			
			$stmt->close();
			return !empty($ids);
		}
		catch(Exception $e) {
			return null;
		}
	}
	
	private function get_id()
	{
		return $this->chat_id;
	}
	
	private function set_id($id)
	{
		$this->chat_id = $id;
	}
}