<?php
require_once dirname(__DIR__). '/config.php';
require_once __DIR__. '/request-validator.php';
require_once dirname(__DIR__). '/classes/chat.class.php';

$response_data = array();

if( isset($_POST['post-message']) ) {
	$message = $_POST['message'];
	
	$chat = new Chat();
	$chat->connect($db_server, $db_user, $user_pass, $db_name, $tables_prefix);
	
	if( $chat_id = $chat->post($message) ) {
		
		$message_data = array();
		$ignore_data  = array('message', 'p', 'post-message');
		
		foreach($_POST AS $key => $value) {
			if( !in_array($key, $ignore_data) ) {
				$message_data[$key] = array( 'value'=>$value, 'overwrite'=>true );
			}
		}
		
		$chat->update($message_data);
		$response_data = array('success'=>true, 'messageID'=>$chat_id);
	}
	else {
		$response_data = array('error'=>true, 'message'=>'');
	}
}

else if( isset($_GET['get-messages']) ) {
	
	$last_message_id = $_GET['last-message-id'];
	$where_data      = isset($_GET['where-data']) ? $_GET['where-data'] : array();
	$where_data      = ( is_array($where_data) ? $where_data : json_decode($where_data, true) );
	$limit           = $_GET['limit'];
	$order           = array();
	
	$main_chat   = new Chat();
	$main_chat->connect($db_server, $db_user, $user_pass, $db_name, $tables_prefix);
	$message_ids = $main_chat->get_messages($last_message_id, $where_data, $order, $limit);
	//$message_ids = $main_chat->get_messages($last_message_id, $initial_limit);
	
	//foreach($message_ids AS $message_id) {
	for($i = 0; $i < count($message_ids); $i++) {
		
		$chat = new Chat( $message_ids[$i] );
		
		$chat->connect($db_server, $db_user, $user_pass, $db_name, $tables_prefix);
		$chat_data = $chat->get();
		$chat_meta = $chat->get_meta();
		$response_data[$i] = array(
			'id'      => $chat_data['id'], 
			'status'  => $chat_data['status'], 
			'message' => nl2br( $chat_data['message'] ), 
			'date'    => $chat_data['date']
		);
		
		foreach($chat_meta AS $meta_data) {
			$meta_key  = $meta_data['meta_key'];
			$meta_value = $meta_data['meta_value'];
			$response_data[$i][$meta_key] = $meta_value;
		}
	}
}

if( isset($_POST['set-message-as-read']) ) {
	$message_id = $_POST['message-id'];
	
	$chat = new Chat($message_id);
	$chat->connect($db_server, $db_user, $user_pass, $db_name, $tables_prefix);
	
	$chat->update( array('status'=>'read') );
	$response_data = array('success'=>true, 'messageID'=>$chat_id);
}
echo json_encode($response_data);
exit;