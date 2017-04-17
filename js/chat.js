function Chat()
{
	function postMessage(message, messageConfig, success)
	{
		messageConfig = messageConfig || {};
		
		var url = messageConfig.url || getRequestBaseUrl() + '/ajax/index.php';
		var data = messageConfig;
		
		data.message = message;
		data.p = 'messages';
		data['post-message'] = true;
		
		makeRequest(url, {
			'method'  : 'POST',
			'data'    : data,
			'error'   : function(){},
			'success' : success
		});
	}
	
	/*
	* whereData JSON key/value pair object in format: {'room-id':1, 'font-size':'24px', 'color':'blue'}, 
	* ]
	*/
	function getMessages(lastMessageID, whereData, limit, error, success)
	{
		console.log(lastMessageID, 'the last id bendr');
		var url       = getRequestBaseUrl() + '/ajax/index.php';
		var emptyFnx  = function(){};
		
		lastMessageID = lastMessageID || 0;
		limit         = limit  || 0;
		whereData     = whereData || {};
		error         = ( typeof error   == 'function' ? error   : emptyFnx );
		success       = ( typeof success == 'function' ? success : emptyFnx );
		
		makeRequest(url, {
			'method'  : 'GET',
			'data'    : { 'p':'messages', 'get-messages':true, 'last-message-id':lastMessageID, 'where-data':whereData, 'limit':limit },
			'error'   : error,
			'success' : success
		});
	}
	
	function setAsRead(msgID)
	{
		var url = getRequestBaseUrl() + '/ajax/index.php';
		makeRequest(url, {
			'method'  : 'POST',
			'data'    : { 'p':'messages', 'set-message-as-read':true, 'message-id':msgID },
			'error'   : function(){},
			'success' : function(){}
		});
	}
	
	function getRequestBaseUrl()
	{
		return getChatDirectoryUrl(); 
	}
	
	function makeRequest(url, options)
	{
		var requestMethod   = options.method || 'GET';
		var requestData     = options.data || {};
		var errorCallback   = options.error || function(){};
		var successCallback = options.success || function(data){};
		
		$.ajax(url, {
			method : requestMethod,
			cache  : false,
			data   : requestData,
			error : function(jqXHR, status, error) {
				errorCallback();
			},
			success  : function(data, status, jqXHR) {
				successCallback(data);
			},
			complete : function(jqXHR, status) {
				
			}
		});
	}

	return {
		'postMessage'       : postMessage,
		'getMessages'       : getMessages,
		'setAsRead'         : setAsRead,
		'makeRequest'       : makeRequest,
		'getRequestBaseUrl' : getRequestBaseUrl
	}
}