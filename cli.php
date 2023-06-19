<?php

	(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('cli only');

	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	function getUserInput(){
		$h = fopen ("php://stdin","r");
		$i = fgets($h);
		fclose($h);	
		if(trim($i) == "") return getUserInput();
		else return trim($i);
	}

	$username = null;
	$password = null;
	$url = null;

	echo "-----------------------------------------------------\n";
	echo "                     E-junkie IO 					   \n";
	echo "-----------------------------------------------------\n";
	echo "\nCreating new user and website\n";
	echo "\nEnter username (used for login and indexing) : "; $username = getUserInput();
	echo "\nEnter password (used for login) : "; $password = getUserInput();
	echo "\nEnter url for site (http://localhost or http://xyz.com or https://www.xyz.com/) : "; $url = getUserInput();
	echo "\nCreating website......\n\n";

	if($username && $password && $url){
		/*		
		1. First create a entry in websites.json index
		2. Then create a file with username.json in Users/username.json
		3. Copy basic pages and templates into required folders
		*/
		$website = json_decode(file_get_contents("./websites.json"));
		$url = str_replace(" ", "", $url);
		$orgUrl = $url;
		$url = str_replace("https://", "", $url);
		$url = str_replace("http://", "", $url);
		if(strpos($url, "www.") !== FALSE){
            $url = explode(".",$url);
            $url = $url[1].".".$url[2];
        }
		if(substr($url,"-1") == "/") $url = substr($url, 0, strlen($url)-1);

		//create entry in websites index
		if(gettype($website) != "object") $website = new stdClass();
		$website->{$url} = new stdClass();
		$website->{$url}->user = $username;
		file_put_contents("./websites.json", json_encode($website));
		
		//create user json in Users folder
		$userjson = new stdClass();
		$userjson->username = $username;
		$userjson->name = "";
		$userjson->email = "";
		$userjson->password = md5($password);
		$userjson->metadata = new stdClass();
		$userjson->metadata->created_at = date('Y-m-d H:i:s');
		$userjson->metadata->updated_at = date('Y-m-d H:i:s');
		$userjson->login = new stdClass();
		$userjson->login->ip = null;
		$userjson->login->timestamp = null;

		$userjson->website = new stdClass();
	    $userjson->website->domain = "$url";
	    $userjson->website->ssl = (strpos($orgUrl, "https://") !== FALSE ? true : false);
	    $userjson->website->title = "";
	    $userjson->website->author = "";
	    $userjson->website->created_at = date('Y-m-d H:i:s');
	    $userjson->website->updated_at = date('Y-m-d H:i:s');
	    $userjson->website->description = "";
	    $userjson->website->social = array();
	    $userjson->website->logo = "";
	    $userjson->website->keywords = "";
	    $userjson->website->home = "home";
	    $userjson->website->theme = "default";

	    $userjson->integrations = new stdClass();
	    $userjson->integrations->ejunkie = new stdClass();
	    $userjson->integrations->ejunkie->enabled = false;
	    $userjson->integrations->ejunkie->clientId = null;
		$userjson->integrations->ejunkie->shop ="shop";
		$userjson->integrations->ejunkie->product ="product";
		$userjson->integrations->ejunkie->maxRelated = 5;
		$userjson->integrations->ejunkie->pref = json_decode('{"pinned": [],"pinned_down": [],"hide_out_of_stock": false}');

    	mkdir("./Users", 0777, true);
	    mkdir("./UsersPages/$username", 0777, true);
	    mkdir("./UsersTemplates/$username/default", 0777, true);
	    mkdir("./static/$username", 0777, true);

		$userjson->pages = new stdClass();
		$userjson->pages->{"home.md"} = new stdClass();
      	$userjson->pages->{"home.md"}->created_at = date('Y-m-d H:i:s');
      	$userjson->pages->{"home.md"}->updated_at = date('Y-m-d H:i:s');
      	$userjson->pages->{"home.md"}->title = "Hello EJIO";
      	$userjson->pages->{"home.md"}->visible = true;

      	$userjson->pages->{"error.md"} = new stdClass();
      	$userjson->pages->{"error.md"}->created_at = date('Y-m-d H:i:s');
      	$userjson->pages->{"error.md"}->updated_at = date('Y-m-d H:i:s');
      	$userjson->pages->{"error.md"}->title = "Oops..Page not found";
      	$userjson->pages->{"error.md"}->visible = true;

		$userjson->folders = array();	  
		file_put_contents("./Users/$username.json", json_encode($userjson));

		//copy example pages to pages folder
		file_put_contents("./UsersPages/$username/home.md", file_get_contents("./EJIO/Examples/pages/home.md"));
		file_put_contents("./UsersPages/$username/error.md", file_get_contents("./EJIO/Examples/pages/error.md"));

		//copy example templates to theme/templates foler
		file_put_contents("./UsersTemplates/$username/default/footer.ej", file_get_contents("./EJIO/Examples/templates/footer.ej"));
		file_put_contents("./UsersTemplates/$username/default/header.ej", file_get_contents("./EJIO/Examples/templates/header.ej"));
		file_put_contents("./UsersTemplates/$username/default/static.ej", file_get_contents("./EJIO/Examples/templates/static.ej"));
		file_put_contents("./UsersTemplates/$username/default/shop.ej", file_get_contents("./EJIO/Examples/templates/shop.ej"));
		file_put_contents("./UsersTemplates/$username/default/product.ej", file_get_contents("./EJIO/Examples/templates/product.ej"));
	}

	echo "\nYour website has been created. Below is the link to your admin panel. We have created basic pages and templates to get started.";
	echo "\nAdmin : $orgUrl/admin"; echo "\nWebsite : $orgUrl";
	echo "\n\n\n!!!Make sure you have the following enabled";
	echo "\n1. mod_rewrite is enabled. Otherwise AltoRouter will not work.";
	echo "\n2. You should have a php server running.";
	echo "\n3. File permissions should be 777 and file ownership must be the same user:group as of server";
	echo "\n";
?>
