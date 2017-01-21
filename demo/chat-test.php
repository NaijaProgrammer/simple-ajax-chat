<html>
 <head>
  <title>Chat Test</title>
  <script src="../js/jquery-2.2.1.min.js"></script>
  <script src="../js/js.php"></script>
  <script src="../js/chat.js"></script>
 </head>
 <body>
 
  <fieldset>
   <legend>Chat test</legend>
   <div style="float:left;">
    <textarea id="send-message-field" style="width: 500px; height: 250px; display: block; margin-bottom:5px;"></textarea>
    <button id="send-message-button">Send</button>
   </div>
   <div id="incoming-messages" style="float:right; width:350px; height:500px; overflow-y:scroll; border:1px solid #aaa; "></div>
  </fieldset>
 
<script>
(function doChat(){
	var chat = new Chat();
	(function sendMessage(){
		$('#send-message-button').on('click', function(e){
			e.preventDefault();
			var message   = $('#send-message-field').val();
			var msgConfig = {
				'font-weight' : 'bold',
				'font-size'   : '16px',
				'color'       : 'blue',
				'type'        : 'public', //types: public: general room; protected: particular room; private: individual
				'room-id'     : 1,
				'room-name'   : 'General',
			};
			var success = function(data){
				data = JSON.parse(data);
				console.log(data);
			}
			
			chat.postMessage(message, msgConfig, success);
			resetMessageField();
			
		});

		function resetMessageField()
		{
			$('#send-message-field').val('');
		}
	})();
	(function getMessages() {
		var msgIDS = []; 
		var lastMessageID = 0;
		var whereData     = {'type':'public', 'room-id':1, 'room-name':'General'};
		var limit         = 0;
		var error         = function(){};
		var success       = function(data) {
			console.log(data);
			data = JSON.parse(data);
			
			/*
			* without this check, we pass empty msgIDS array to the setLastMessageID function
			* and this returns -Infinity as the lastMessageID (the maxNumber of the messageIDS array)
			* leading to error on the server side
			*/
			if(data.length < 1) {
				return; 
			}
			
			for(var i = 0; i < data.length; i++) {
				var currentChat = data[i];
				msgIDS.push(currentChat.id);
				displayMessage(currentChat);
			}
			setLastMessageID(msgIDS);
			resetMessageIDS();
		}

		setInterval(function(){
			//chat.getMessages(lastMessageID, initialLimit, error, success);
			chat.getMessages(lastMessageID, whereData, limit, error, success);
		}, 1000);

		function displayMessage(msgData)
		{
			var msgID   = msgData.id;
			var message = msgData.message;
			var status  = msgData.status;
			var date    = msgData.date;
			
			var color      = msgData.color;
			var fontSize   = msgData['font-size'];
			var fontWeight = msgData['font-weight'];
			var msgString  = '<div style="font-size:' + fontSize + '; font-weight:' + fontWeight + '; color:' + color + ';">' + message + '</div>';
			
			$('#incoming-messages').append(msgString);
		}

		function setLastMessageID(msgIDS)
		{
			lastMessageID = getMaxNumber(msgIDS);
			
			function getMaxNumber(numArr)
			{
				return Math.max.apply(null, numArr);
			}
		}

		function resetMessageIDS()
		{
			msgIDS = [];
		}
	})();
})();
</script>
 </body>
</html>
