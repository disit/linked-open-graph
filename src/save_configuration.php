<?php
/* Linked Open Graph
   Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as
   published by the Free Software Foundation, either version 3 of the
   License, or (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/* Save a graph configuration.
	The code receive a post value from axrelations.js with the email of the user and the configuration of the graph in json.
	Create the link to the graph with an univocal code. 
	The function return true if save success.
	*/
	
	require_once("config.php");
	
	//counts the number of access to this page and treats them as number of save.
	// fopen("counter.har","at");
	// $counter= (file_get_contents("counter.har"));
	// $counter++;//the code
	// $fp=fopen("counter.har","w");
	// fwrite($fp,($counter));
	// fclose($fp);	
	
	// $sender="info@disit.org";
	$sender=$email_sender;
	$response="not saved";
	$email=$_POST['email'];
	$result;
	if($email!=null){		
		//saves the configuration in the DB.
		//Access to the database.
		$username = $db_username;
		$password = $db_psw;
		$hostname = $db_host; 
		$schema = $db_schema;
		// connects to the database
		$dbhandle = mysqli_connect($hostname, $username, $password, $schema) or die("Unable to connect to MySQL");
		$parent_id=null;
		$title=db_escape($_POST['title'],$dbhandle);
		$desc=db_escape($_POST['desc'],$dbhandle);//TODO insert a control for the length of the description based on the dimension on the database.
		//Receives the POST of the configuration of the graph. This is made for save the user own graph. 
		$json_conf=db_escape($_POST['save'], $dbhandle);
		//Saves configuration in a file wich has the same name as the univocal code.
		// $f=fopen('save/save_'.$counter .'.json','w');
		// fwrite($f,stripslashes($json_conf));
		// fclose($f);

		if($_POST['parent_id']!=null){$parent_id=db_escape($_POST['parent_id'],$dbhandle);}
		$eps_list = db_escape($_POST['EPs'],$dbhandle);
		$status = db_escape($_POST['status'],$dbhandle);
		$endpoint = db_escape($_POST['endpoint'],$dbhandle);
		$initial_uri = db_escape($_POST['initial_uri'],$dbhandle);
		
		if($_POST['update']=="true" && $parent_id!=null){//Update case.
			$query="UPDATE graph SET config='".$json_conf ."', timestamp=".time() .",list_endpoints='". $eps_list ."',status='".$status."',title='".$title ."' ,description='".$desc ."' WHERE readwrite_id='".$parent_id ."' ";
			$conf_id=$parent_id;
		}
		else{
			$conf_id="".$email .time();
			$conf_id_r=$conf_id;
			$conf_id_rw=$conf_id ."write";
			$conf_id_r=md5($conf_id_r);
			$conf_id_rw=md5($conf_id_rw);
			$query="INSERT INTO graph (id,mail, sparql_endpoint,initial_uri,config,parent_id,timestamp,readwrite_id,list_endpoints,status,title,description) VALUES ('".$conf_id_r ."','".$email ."','".$endpoint ."','".$initial_uri."','".$json_conf."','".$parent_id ."',".time() .",'".$conf_id_rw ."','".$eps_list."','".$status."','".$title."','".$desc."')";
		}
		if (!mysqli_query($dbhandle,$query)){
			die('Error: ' . mysqli_error($dbhandle) . "\n------\n".$query);
		}
		mysqli_close($dbhandle);		
		$response="save";//if the configuration is saved.
		
		//Creates and send the mail with the link.
		if($_POST['update']!="true"){
			$title_edit=str_replace("\\","",$title);
			$desc_edit=str_replace("\\","",$desc);
			$link='http://'.$_SERVER['HTTP_HOST'].''.substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'],'/')). '?graph=';
			$link_r=$link. $conf_id_r;
			$link_rw=$link. $conf_id_rw;
			//Sends the email 
			$message = "<html><head><title>LOG graph</title></head>
			<body><p>Thanks a lot to have used Linked Open Graph by DISIT at <a href=\"http://log.disit.org\">http://log.disit.org</a></p>
			<p>Your graph:$title_edit has been saved. </p>
			<p>Description:</br>$desc_edit</p>
			<p>You can access to the Linked Open Graph you produced by clicking on these links: </p>
			link for read only: <a href='$link_r'>'$link_r'</a></br>
			link for overwrite: <a href='$link_rw '>'$link_rw'</a></br>
			<p>or copy paste it on your browser. </p>
			<p>You can share the link with your friends.</p>
			<p>Best regards<br>
			 LOG.disit.org team<br>
			 You can contact us at info@disit.org or visit our web page at <a href=\"http://www.disit.dinfo.unifi.it\">http://www.disit.dinfo.unifi.it</a>
			</body>
			</html>	";
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

			// Additional headers
			$headers .= 'To: '.$email . "\r\n";
			$headers .= 'From: Linked Open Graph <'.$sender .'>' . "\r\n"
			.'Reply-To: '.$sender . "\r\n" .'X-Mailer: PHP/' . phpversion();
			$to      = $email;
			$subject = "Your link to Linked Open Graph - $title_edit ";
			mail($to, $subject, $message, $headers);
			$result->overwride_code=$conf_id_rw;
		}
	}
	$result->email=$email;
	$result->response=$response;
	$result->save_r=$conf_id_r;
	$result->save_rw=$conf_id_rw;
	echo JSON_ENCODE($result);

function db_escape($string, $link) {
	if(get_magic_quotes_gpc())
		$string = stripslashes($string);
	return mysqli_real_escape_string($link,$string);
}
?>