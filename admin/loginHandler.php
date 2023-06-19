<?php

	$AuthenticationErrors = array();

	if($_POST['csrf'] != $_SESSION['csrf']){
		$AuthenticationErrors[] = "Invalid token received";
	}else if(isset($_POST['login'])){

		$username = $_POST['username'];
		$password = $_POST['password'];

		if($username == "") $AuthenticationErrors[] = "Invalid Username";
		else if($password == "") $AuthenticationErrors[] = "Invalid Password";
		else{
			//check credentials and login
			$password = md5($password);
			$json = json_decode(file_get_contents("./Users/$username.json"));
			if($json == null)
				$AuthenticationErrors[] = "No User found";
			//if no errors found then only do password check
			if(count($AuthenticationErrors) == 0){
				if($password == $json->password){
					//successful login
					$json->login->ip = $_SERVER['REMOTE_ADDR'];
					$json->login->timestamp = date('Y-m-d H:i:s');
					file_put_contents("./Users/$username.json", json_encode($json));
					$_SESSION['EJIO_user'] = $json->username;
					include_once 'index.html';
					die();
				}
				else $AuthenticationErrors[] = "Wrong password";
			}
		}
	}else if(isset($_POST['register'])){

	}
?>
