<?php
function create_db_tables($server, $user, $pass, $db_name, $tables_prefix) 
{
	$tp         = $tables_prefix;
	$db_conn    = new mysqli($server, $user, $pass, $db_name);
	$tables     = array('chat', 'chat_meta');
	$tables_len = count($tables);

	for($i=0; $i < $tables_len; $i++){

		if($tables[$i] == 'chat') {
			$db_conn->query("CREATE TABLE IF NOT EXISTS {$tp}chat (
			`id`      int NOT NULL auto_increment PRIMARY KEY,
			`status`  varchar(255),
			`message` text NOT NULL,
			`date`    datetime NOT NULL
		   )"); //end sql command
		}
		
		else if($tables[$i] == 'chat_meta') {
			$db_conn->query("CREATE TABLE IF NOT EXISTS {$tp}chat_meta (
			`id`         int NOT NULL auto_increment PRIMARY KEY,
			`chat_id`    int NOT NULL,
			`meta_key`   varchar(255),
			`meta_value` varchar(255),
			KEY `chat_id` (`chat_id`),
			KEY `meta_key` (`meta_key`)
		   )"); //end sql command
		}
	}
}

include dirname(__DIR__). '/config.php';
create_db_tables($db_server, $db_user, $user_pass, $db_name, $tables_prefix);
echo "{$db_name} {$tables_prefix}chat tables created";