<?php
date_default_timezone_set("Asia/Kolkata"); 
require_once('../serverConfig/serverInIt.php');
$config=parse_ini_file("../serverConfig/server.ini",true);
$envType=$config['environment']['type'];
if($envType==''||$envType==null){
    http_response_code(500);
    $reportTo=$config['error']['errorMailsTo'];
  mail($reportTo,'Some error Occured','Hi, Some error encountered while Fetching evn config file authenticate.php/9');
    
    error_log(date("d-m-Y h:i:sa").' Environtment.type missing it should be set to development for development activities and production for service activities.'."\n",3,"../authenticate/error.log");
    echo json_encode("Server cannot take request at this moment. Contact Application ownner.");
     
    exit;
}
if($envType=='Development'){
    //echo('Application pointed to Development Region');
    require_once('../dataBaseConnection/DevDbConnect.php');
}
if($envType=='Production'){
    //echo('Application pointed to Production Region');
    require_once('../dataBaseConnection/ProdDbConnect.php');
}

class AttendanceHandler {

	function send($message) {
		global $clientSocketArray;
		$messageLength = strlen($message);
		foreach($clientSocketArray as $clientSocket)
		{
			@socket_write($clientSocket,$message,$messageLength);
		}

		return true;
	}

	function unseal($socketData) {
		$length = ord($socketData[1]) & 127;
		if($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		$socketData = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i%4];
		}
		return $socketData;
	}

	function seal($socketData) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$socketData;
	}

	function doHandshake($received_header,$client_socket_resource, $host_name, $port) {
		$headers = array();
		$lines = preg_split("/\r\n/", $received_header);
		foreach($lines as $line)
		{
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$buffer  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $host_name\r\n" .
		"WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_socket_resource,$buffer,strlen($buffer));
	}
	
	function newConnectionACK($client_ip_address) {
		try{
			$config=parse_ini_file("../serverConfig/server.ini",true);
			$server_config=new ServerConfiguration();
			if( $server_config->_environment['type']=='Development'){
			   $dbHandeler=DevDataBase::connect();
			   
			}
			else if( $server_config->_environment['type']=='Production'){
			   $dbHandeler=ProdDataBase::connect();
			  
		   }
		   else{
			   $data=Array("serverMessage"=>"No environtment found");
		http_response_code(404);
			   echo json_encode($data);
			   exit;
		   }
				 
			//$dbHandeler=DevDataBase::connect();
		  }
		  catch(PDOException $exp){
		  http_response_code(500);
		  $reportTo=$config['error']['errorMailsTo'];
		  mail($reportTo,'Some error Occured','Hi, Some error encountered while Connecting Data Base this is authenticate.php/33');
			echo json_encode("Internal server error");
			  exit;
		  }
		  $tokenHard = openssl_random_pseudo_bytes(32);
		  $tokenHard = bin2hex($tokenHard);
		  $query=$dbHandeler->prepare('DELETE FROM currentQR');
		  $query->execute(); 
		  $sql='insert into currentQR (qr,time) values(:token,:time)';
		  $query=$dbHandeler->prepare($sql);
		  $date_now = date('Y-m-d H:i:s');
		  $query->bindValue(':time',$date_now);
		  $query->bindValue(':token',$tokenHard);

		  $query->execute();
		

		$message = 'System connected to Falcon Centralized Data Base ';
		$messageArray = array('message'=>$message,'message_type'=>'chat-connection-ack','token'=>$tokenHard);
		$ACK = $this->seal(json_encode($messageArray));
		return $ACK;
	}
	
	function connectionDisconnectACK($client_ip_address) {
		$message = '';
		$messageArray = array('message'=>$message,'message_type'=>'chat-connection-ack');
		$ACK = $this->seal(json_encode($messageArray));
		return $ACK;
	}
	
	function createChatBoxMessage($chat_user,$token) {
		try{
			$config=parse_ini_file("../serverConfig/server.ini",true);
			$server_config=new ServerConfiguration();
			if( $server_config->_environment['type']=='Development'){
			   $dbHandeler=DevDataBase::connect();
			   
			}
			else if( $server_config->_environment['type']=='Production'){
			   $dbHandeler=ProdDataBase::connect();
			  
		   }
		   else{
			   $data=Array("serverMessage"=>"No environtment found");
		http_response_code(404);
			   echo json_encode($data);
			   exit;
		   }
				 
			//$dbHandeler=DevDataBase::connect();
		  }
		  catch(PDOException $exp){
		  http_response_code(500);
		  $reportTo=$config['error']['errorMailsTo'];
		  mail($reportTo,'Some error Occured','Hi, Some error encountered while Connecting Data Base this is authenticate.php/33');
			echo json_encode("Internal server error");
			  exit;
		  }
		  if($chat_user==="system" && $token=="expired"){
			$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
			$messageArray = array('message_type'=>'reset','token'=>$tokenHard);
			$chatMessage = $this->seal(json_encode($messageArray));
			return $chatMessage;
		  }
		$sql="Select * from adminTable where fid=:dev";
		$query=$dbHandeler->prepare($sql);
		$query->bindValue(':dev',$chat_user);
		$query->execute();
		
		if($query->rowCount()>0){
			$emp=$query->fetch(PDO::FETCH_ASSOC);
			$sql="select * from currentQR where qr=:token";
			$query=$dbHandeler->prepare($sql);
		$query->bindValue(':token',$token);
		$query->execute();
		$tokenDetails=$query->fetch(PDO::FETCH_ASSOC);
		if($query->rowCount()>0){
			$sql='SELECT * from qrAttendanceTable  where email=:email';
			$query=$dbHandeler->prepare($sql);
			$query->bindValue(':email',strtolower($emp['email']));
			$query->execute();
			$date_now = date('Y-m-d');
			while($test=$query->fetch(PDO::FETCH_ASSOC)){
				$dt = new DateTime($test['date']);
				$dt = $dt->format('Y-m-d');
				if($dt==$date_now){
					$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
		$messageArray = array('message'=>'Hi '.$test['name'].', Your attendance is registered for today.','message_type'=>'system','token'=>$tokenHard);
		$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;
				}

			}
			$time_now = date('H:i:s');
			$dt = new DateTime($tokenDetails['time']);
			$dt = $dt->format('H:i:s');
			if($time_now-$dt<=30){
			
			$sql='insert into qrAttendanceTable  (email,name,device,qrCode,date) values(:email,:name,:device,:token,:date)';
			$query=$dbHandeler->prepare($sql);
			$query->bindValue(':email',strtolower($emp['email']));
			$query->bindValue(':name',($emp['name']));
			$query->bindValue(':device',strtolower($emp['device']));
			$query->bindValue(':token',$token);
			$date_now = date('Y-m-d H:m:s');
			$query->bindValue(':date',$date_now);
			$query->execute();
		
			$sql='SELECT * from qrAttendanceTable  where email=:email and name=:name and device=:device and qrCode=:token';
			$query=$dbHandeler->prepare($sql);
			$query->bindValue(':email',strtolower($emp['email']));
			$query->bindValue(':name',($emp['name']));
			$query->bindValue(':device',($emp['device']));
			$query->bindValue(':token',$token);
			$query->execute();
			$stamp=$query->fetch(PDO::FETCH_ASSOC);
			$tab="<tr>
			<td>".$stamp['name']."</td>
			<td>".$stamp['email']."</td>
			<td>".$stamp['date']."</td>
			</tr>";
			//$msg="Attendance Captured for ".$stamp['date']." By System. Thank you.";
			//$message =  $emp['name']  . "! <div style='color: green;' class='chat-box-message'>" . $msg . " </div>";
			
			$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
		$messageArray = array('message'=>$tab,'message_type'=>'done','token'=>$tokenHard);
		$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;
			}
			else{
				$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
		$messageArray = array('message'=>'Do not try to mock the System Using Last QR. Your location device and Id are tested before your attendance is loaded into the system.','message_type'=>'system','token'=>$tokenHard);
		$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;
			}
		}
		else{
			
				
			
			
			$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
				$msg="This QR Code is not Valid One. Please Scan From valid source.";
				$emp['name']="System Manager";
			
			$message =  $emp['name']  . "! <div style='color: red;' class='chat-box-message'>" . $msg . " </div>";
			$messageArray = array('message'=>$message,'message_type'=>'system','token'=>$tokenHard);
			$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;
			
		
			
		}
		}
		else{
			$tokenHard = openssl_random_pseudo_bytes(32);
			$tokenHard = bin2hex($tokenHard);
			$query=$dbHandeler->prepare('DELETE FROM currentQR');
			$query->execute(); 
			$sql='insert into currentQR (qr,time) values(:token,:time)';
			$query=$dbHandeler->prepare($sql);
			$date_now = date('Y-m-d H:i:s');
			$query->bindValue(':time',$date_now);
			$query->bindValue(':token',$tokenHard);

			$query->execute();
			$msg="Could not Capture attendance, Use your Correct Falcon FID.";
			error_log("Some one tried capturing attendance using device ".$chat_user."\n",3,'../attendance/issueInAttendanceCapture.log');
			$emp['name']="System Manager";
			$message =  $emp['name']  . "! <div style='color: red;' class='chat-box-message'>" . $msg . "</div>";
			$messageArray = array('message'=>$message,'message_type'=>'system','token'=>$tokenHard);
			$chatMessage = $this->seal(json_encode($messageArray));
			return $chatMessage;
		}

		
		
	}
}
?>