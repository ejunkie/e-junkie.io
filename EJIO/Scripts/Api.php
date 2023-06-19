<?php
	class Api{
		var $User = null;
		var $UserJSON = null;
		var $updateCall = false;
		var $responseCodes = [
			"400" => "Bad Request",
			"401" => "Unauthorized",
			"403" => "Forbidden",
			"404" => "Not Found",
			"405" => "Method Not Allowed",
		];
	
		public function __construct($User, $updateCall){
			$this->User = $User;
			if($this->User == "" || !$this->User) $this->invalidRequest("Invalid User Passed", 400); 
			$this->updateCall = $updateCall;
			$this->prepareUser();
		}

		public function prepareUser(){
			$this->UserJSON = json_decode(file_get_contents('./Users/'.$this->User.".json"));
			if($this->UserJSON->username == null || $this->UserJSON->username == "") die();
			if($this->updateCall)
				file_put_contents('./Users/'.$this->User.".bk.json", file_get_contents('./Users/'.$this->User.".json"));
		}

		public function getPagesInsideDirectory($loc){
			$pages = array();
			$tempPages = scandir($loc);
			foreach($tempPages as $p){
				if($p == "." || $p == "..") continue;
				if(is_dir($loc.$p)){
					$pages[][$p] = $this->getPagesInsideDirectory($loc.$p); 
				}else{
					$pages[] = $p;
				}
			}
			return $pages;
		}

		public function getPages(){
			return array("Pages"=>$this->UserJSON->pages, "Folders"=>$this->UserJSON->folders);
		}


		public function getFolders(){
			return $this->UserJSON->folders;
		}

		public function getUserMetadata(){
			$arr = [];
			$arr['username'] = $this->UserJSON->username;
			$arr['name'] = $this->UserJSON->name;	
			$arr['email'] = $this->UserJSON->email;
			$arr['metadata'] = $this->UserJSON->metadata;
			return (object) $arr;
		}
		
		public function getWebsiteMetadata(){
			return $this->UserJSON->website;
		}

		public function getWebsiteStats(){
			$arr = [];
			$arr['totalPages'] = count( (array)$this->UserJSON->pages);
			$last_edit = 0;
			$last_edited_page = null;
			$recently_addedTs = new DateTime();
			$recently_addedTs->modify('-10 day');
			$recently_addedPages = [];
			$recently_editedPages = [];
			foreach($this->UserJSON->pages as $k=>$page){
				$updatedAt = strtotime($page->updated_at);
				if($last_edit < $updatedAt){
					$last_edit = $updatedAt;
					$last_edited_page = $page;
				}
				if(strtotime($page->created_at) > $recently_addedTs->getTimeStamp() && count($recently_addedPages) <3){
					$page->created_at = $this->getDaysTimestamp(strtotime($page->created_at));
					$recently_addedPages[$k] = $page;
				}
				if(strtotime($page->updated_at) > $recently_addedTs->getTimeStamp() && count($recently_editedPages) <3){
					$page->updated_at = $this->getDaysTimestamp(strtotime($page->updated_at));
					$recently_editedPages[$k] = $page;
				}
			}
			if($arr['totalPages'] != 0){
				$arr['last_edit'] = array('page' => $last_edited_page ,'timestamp' => $this->getDaysTimestamp($last_edit));
			}else{
				$arr['last_edit'] = array('page' => null ,'timestamp' => null);
			}
			$arr['recently_added_pages'] = $recently_addedPages;
			$arr['recently_edited_pages'] = $recently_addedPages;
			return (object) $arr;
		}

		public function invalidRequest($error, $code){
			if(!$code) $code = "404";
			header("HTTP/1.1 $code ".$this->responseCodes[$code]);
			if($error == "") $error = "Invlaid Request";
			echo json_encode(["status"=>false, "error"=>$error]);
			die();
		}

		public function getDaysTimestamp($t){
			$dtF = new DateTime();
		    $dtT = new DateTime();
		    $dtT->setTimestamp($t);
			return $dtF->diff($dtT)->format('%a days, %h hours ago');
		}

		public function getPage($key, $lite = false){
			$temp_page = file_get_contents("./UsersPages/".$this->User."/".$key);
			$page = null;
			if($temp_page == ""){
				if($lite) return false;
				else return array("status"=>false);	
			} 
			else{
				require_once 'Spyc.php';	
				$yaml = new stdClass();
				$yaml->title = "";
				$yaml->description = "";
				$yaml->author = "";
				$yaml->keywords = "";
				$yaml->image = "";
				$yaml->template = "";
				$yaml->folder = null;
				if(substr($temp_page, 0, 3) == "---"){
					$tyaml = explode('---', $temp_page)[1];
					$temp_page = explode('---', $temp_page, 3)[2];
					$tyaml = (object) Spyc::YAMLLoad($tyaml);
					$yaml->title = $tyaml->title;
					$yaml->description = $tyaml->description;
					$yaml->author = $tyaml->author;
					$yaml->keywords = $tyaml->keywords;
					$yaml->template = $tyaml->template;
					$yaml->folder = $tyaml->folder;
					$yaml->image = $tyaml->image;
				} 
				if($yaml->template == "") $yaml->template = "static";
				$page['yaml'] = $yaml;
				$page['key'] = $key;
				$page['content'] = trim($temp_page);
				if(!$lite){
					require_once 'EJTemplate.php';

					$Page = new stdClass();
					$Page->url = "/"; //base url or sub-folder name
					$Page->user = $this->UserJSON->username;
					$Page->location->templates = "./UsersTemplates/".$Page->user."/".$this->UserJSON->website->theme;
					$Page->location->pages = "./UsersPages/".$Page->user;
					$Page->location->static = "./UsersTemplates/".$Page->user;
					$Page->site = $this->UserJSON->website;
					$Page->site->url = ($Page->ssl ? "https://" : "http://").$Page->site->domain;
					$Page->EJ = null;
					if($this->UserJSON->integrations->ejunkie->enabled === true){
						$Page->EJ = $Website->integrations->ejunkie;
						$Page->EJ->selectedCategory = null;
						$Page->EJ->selectedProduct = null;
					}
					$Page->name = str_replace('.md', '', $key);
					if($Page->url != "/"){
						$Page->url = "/".$Page->url;
						$Page->url = substr($Page->url, 0, -1);
					}
					$Page->editing = true;
					$EJT = new EJTemplate($Page, $this, null);
					ob_start();
					$EJT->generateStatic();
					$outputP = ob_get_contents();
					ob_end_clean();
					$page['page'] = $outputP;
				}
			}
			return $page;
		}

		public function getPageMetadata($name){
			foreach($this->UserJSON->pages as $pageN=>$pageM){
				if($pageN == $name)
					return $pageM;
			}
			return null;
		}

		public function savePage($key, $page){
			$page = (object) $page;
			$yaml = (object) $page->yaml;
			$isFolder = false;
			$key = explode("/", $key);
			$key = str_replace(" ", "-", $key);
			if(count($key) == 0)
				$key = $key[0];
			else
				$key = $key[count($key)-1];
			foreach($this->UserJSON->folders as $n=>$m){
				if($n == $yaml->folder){
					$isFolder = true;
					$key = $yaml->folder."/".$key;
				}
			}
			$finalContent = "";
			$finalContent .= "---\n";
			$finalContent .= "title: $yaml->title\n";
			$finalContent .= "description: $yaml->description\n";
			$finalContent .= "author: $yaml->author\n";
			$finalContent .= "keywords: $yaml->keywords\n";
			$finalContent .= "image: $yaml->image\n";
			if($yaml->template == "")
				$finalContent .= "template: static\n";
			else
				$finalContent .= "template: $yaml->template\n";
			$finalContent .= "folder: ".($isFolder ? $yaml->folder : null)."\n";
			$finalContent .= "---\n";
			$finalContent .= $page->content;
			$temp_page = file_put_contents("./UsersPages/".$this->User."/".$key, $finalContent);
			if($this->UserJSON->pages->{$key}){
				$this->UserJSON->pages->{$key}->updated_at = date('Y-m-d H:i:s');
				$this->UserJSON->pages->{$key}->title = $yaml->title;
			}else{
				$this->UserJSON->pages->{$key}->created_at = date('Y-m-d H:i:s');
				$this->UserJSON->pages->{$key}->updated_at = date('Y-m-d H:i:s');
				$this->UserJSON->pages->{$key}->title = $yaml->title;
				$this->UserJSON->pages->{$key}->visible = true;
			}
			if($isFolder){
				$foundInFolders = false;
				$tempFolders = array();
				foreach($this->UserJSON->folders->{$yaml->folder} as $fo){
					if($fo->key == $key){
						$fo = array("key"=>$key, "yaml"=>$yaml);
						$foundInFolders = true;
					}
					$tempFolders[] = $fo; 
				}
				$this->UserJSON->folders->{$yaml->folder} = $tempFolders;
				if($foundInFolders == false)
					$this->UserJSON->folders->{$yaml->folder}[] = array("key"=>$key, "yaml"=>$yaml);
			}
			$this->saveJSON();			
			return $this->getPage($key);
		}

		public function setPageVisibility($c, $o){
			if($this->UserJSON->pages->{$c}){
				$o = (object) $o;
				if($o->visible)
					$this->UserJSON->pages->{$c}->visible = true;
				else
					$this->UserJSON->pages->{$c}->visible = false;
				$this->UserJSON->pages->{$c}->updated_at = date('Y-m-d H:i:s');
				$this->saveJSON();			
				return $this->getPages();
			}
			return array("status"=>false);
		}

		public function deletePage($c){
			$newPages = array();
			foreach($this->UserJSON->pages as $pageN=>$pageM){
				if($pageN == $c){
					unlink("./UsersPages/".$this->User."/".$c);
					$pageT = (object) $this->getPage($c, true);
					if($pageT->yaml->folder != ""){
						$tempFolders = array();
						foreach($this->UserJSON->folders->{$pageT->yaml->folder} as $fo){
							if($fo->key == $c);
							else $tempFolders[] = $fo;
						}
						$this->UserJSON->folders->{$pageT->yaml->folder} = $tempFolders;
					}	
				} 
				else $newPages[$pageN] = $pageM;
			}
			$this->UserJSON->pages = $newPages;
			$this->saveJSON();
			return $this->getPages();
		}

		public function addFolder($key){
			if(strpos($key,".") === FALSE && strpos($key,"/") === FALSE);
			else return array("status"=>false, "error"=>"Invalid Folder Name");
			mkdir("./UsersPages/".$this->User."/".$key, 0777, true);
			if($this->UserJSON->folders);
			else $this->UserJSON->folders = new stdClass();
			$this->UserJSON->folders->{$key} = [];
			$this->saveJSON();			
			return $this->getPages();
		}

		public function deleteFolder($c){
			if(strpos($c,".") === FALSE && strpos($c,"/") === FALSE);
			else return array("status"=>false, "error"=>"Invalid Folder Name");
		
			$dir = "./UsersPages/".$this->User."/".$c;
			foreach($this->UserJSON->folders->{$c} as $pp){
				$this->deletePage($pp->key);
			}
			rmdir($dir);
			$newFolders = array();
			foreach($this->UserJSON->folders as $pageN=>$pageM){
				if($pageN == $c);
				else $newFolders[$pageN] = $pageM;
			}
			$this->UserJSON->folders = $newFolders;
			$this->saveJSON();
			return $this->getPages();
		}

		public function getChildPages($Parent = null, $Size = 10, $Pagination = false, $Order = "DESC", $Page = 1){
			if(!$Parent) return false;
			if($Order == "") $Order = "DESC";
			if($Order == "DESC") $Order = -1*$Size;
			else $Order = 0;
			if($Page == "") $Page = 1;
			$Page--;
			if($Pagination){
				$childPages = array_slice($this->UserJSON->folders->{$Parent}, $Order+($Size*$Page), $Size);
				if($Order == 0) return $childPages;
				else return array_reverse($childPages);
			}else{
				$childPages = array_slice($this->UserJSON->folders->{$Parent}, $Order, $Size);
				if($Order == 0) return $childPages;
				else return array_reverse($childPages);
			}
		}

		public function getTemplates(){
			$templates = scandir('./UsersTemplates/'.$this->User."/".$this->UserJSON->website->theme."/");
			$newArr = array();
			foreach($templates as $template)
				if($template != "." && $template != ".." && $template != "init.json")
					$newArr[] = str_replace(".ej", "", $template);
			return $newArr;
		}

		public function getTemplate($key){
			$template = file_get_contents('./UsersTemplates/'.$this->User."/".$this->UserJSON->website->theme."/".$key.".ej");
			return array('key'=>$key, 'template'=>$template);
		}

		public function saveTemplate($key, $template){
			file_put_contents('./UsersTemplates/'.$this->User."/".$this->UserJSON->website->theme."/".$key.".ej", $template);
			return array("Template" => array("key"=>$key, "template"=>$template), "Templates" => $this->getTemplates());
		}

		public function deleteTemplate($key){
			if($key == "" || $key == "." || $key == "..") return $this->getTemplates();
			unlink('./UsersTemplates/'.$this->User."/".$this->UserJSON->website->theme."/".$key.".ej");
			return $this->getTemplates();
		}

		public function scan_dir($dir) {
		    $ignored = array('.', '..', '.svn', '.htaccess');

		    $files = array();    
		    foreach (scandir($dir) as $file) {
		        if (in_array($file, $ignored)) continue;
		        $files[$file] = filemtime($dir . '/' . $file);
		    }

		    arsort($files);
		    $files = array_keys($files);

		    return ($files) ? $files : false;
		}

		public function getAssets(){
			$assets = $this->scan_dir("./static/".$this->User."/");
			$newArr = array();
			foreach($assets as $asset)
				if($asset != "." && $asset != ".."){
					$newArr[] = array(
						"key" => $asset,
						"asset" => $asset,
						"url" => "/static/".$this->User."/$asset"
					);
				}
			return $newArr;
		}

		public function getThemes(){
			$themes = glob('./UsersTemplates/'.$this->User."/*", GLOB_ONLYDIR);
			$newArr = array();
			foreach($themes as $theme){
				$prop = json_decode(file_get_contents($theme."/init.json"));
				if($prop);
				else{
					$prop = new stdClass();
					$prop->name = end(explode("/",$theme));
					$prop->author = null;
					$prop->thumbnail = null;
					$prop->demo = null;
					$prop->homepage = null;
				}
				$prop->key = end(explode("/",$theme));
				$newArr[] = $prop;
			}
			return ["themes"=>$newArr, "selectedTheme"=>$this->UserJSON->website->theme];
		}

		public function saveTheme($key){
			if($key != ""){
				$key = str_replace(".", "", $key);
				$key = str_replace("/", "", $key);
				$key = str_replace("..", "", $key);
				$key = str_replace("\\", "", $key);
				$this->UserJSON->website->theme = str_replace(" ", "", $key);
			}
			$this->saveJSON();
			return $this->getThemes();
		}

		public function deleteTheme($key){
			if($key == "" || $key == "." || $key == ".." && ($key == $this->UserJSON->website->theme)) return $this->getThemes();
			$templates = scandir('./UsersTemplates/'.$this->User."/".$key."/");
			foreach($templates as $template){
				if($template != "." && $template != "..")
					unlink('./UsersTemplates/'.$this->User."/".$key."/".$template);
			}
			rmdir("./UsersTemplates/".$this->User."/".$key);
			return $this->getThemes();
		}

		public function saveAssets($request){
			$allowed =  array('gif','png' ,'jpg', 'jpeg', 'mp4', 'webm', '3gp', 'js', 'css', 'svg', 'jps', 'webp', 'pdf');
			foreach($_FILES as $file){
				$filename = $file['name'];
				$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				$filesize = (($file['size']/1024)/1024);
				if(!in_array($ext,$allowed))
			    	return array("status"=>false, "error"=>"Invalid File Type : $filename");
			    if($filesize > 5)
			    	return array("status"=>false, "error"=>"File Size greater than 5 MB : $filename");
			    $tmpFilePath = $file['tmp_name'];
				if ($tmpFilePath != ""){
				    // $newFilePath = "./static/".$this->User."/".time()."---Contenture---".$file['name'];
				    $newFilePath = "./static/".$this->User."/".$file['name'];
				    if(move_uploaded_file($tmpFilePath, $newFilePath))
			    		return $this->getAssets();
			    	else
			    		return array("status"=>false, "error"=>"Failed to upload file : $filename");
			   }
			}
			return array("status"=>false, "error"=>"Invalid Call");
		}

		public function deleteAsset($key){
			if($key == "" || $key == "." || $key == "..") return $this->getAssets();
			unlink("./static/".$this->User."/".$key);
			return $this->getAssets();
		}

		public function saveJSON(){
			file_put_contents('./Users/'.$this->User.".json", json_encode($this->UserJSON));
			return array("status"=>true);
		}

		public function getIntegrations(){
			/*
			Available Integrations:
				1. E-junkie
			*/
			//get EJ Products if client id is present
			if($this->UserJSON->integrations->ejunkie->clientId){
				require_once 'EJParser.php';
				$EJ = new EJParser($this->UserJSON->integrations->ejunkie->clientId, null, null, null, $this->UserJSON->integrations->ejunkie->apiKey, true);
				$this->UserJSON->integrations->ejunkie->products = $EJ->products;
			}
			return $this->UserJSON->integrations;
		}

		public function saveIntegration($integration){
			foreach($integration as $name=>$val){
				/* if $name == "ejunkie" */
				if($name == "ejunkie"){
					$val = (object) $val;
					$this->UserJSON->integrations->ejunkie->clientId = $val->clientId;
					if($this->UserJSON->integrations->ejunkie->clientId != "" && $this->UserJSON->integrations->ejunkie->clientId != null)
						$this->UserJSON->integrations->ejunkie->enabled = ($val->enabled === true ? true : false);
					else
						$this->UserJSON->integrations->ejunkie->enabled = false;
					$this->UserJSON->integrations->ejunkie->apiKey = $val->apiKey;
					$this->UserJSON->integrations->ejunkie->maxRelated = $val->maxRelated;
					$this->UserJSON->integrations->ejunkie->shop = ($val->shop == "" ? "shop" : $val->shop);
					$this->UserJSON->integrations->ejunkie->product = ($val->product == "" ? "product" : $val->product);
					$this->UserJSON->integrations->ejunkie->pref = $val->pref;
				}
			}
			$this->saveJSON();
			return $this->getIntegrations();
		}

		public function getSettings($key = ""){
			if($key == ""){
				$tmpUser = new stdClass();
				$tmpUser->username = $this->UserJSON->username;
				$tmpUser->name = $this->UserJSON->name;
				$tmpUser->email = $this->UserJSON->email;
				$tmpUser->password = "";

				$tmpWebsite = $this->UserJSON->website;
				return array("account"=>$tmpUser, "website"=>$tmpWebsite);
			}else if($key != "password"){
				return $this->UserJSON->{$key};
			}
		}

		public function saveSettings($settings){
			foreach($settings as $name=>$val){
				/* if $name == "ejunkie" */
				$val = (object) $val;
				if($name == "account"){
					$this->UserJSON->name = $val->name;
					$this->UserJSON->email = $val->email;
					if($val->password != "")
						$this->UserJSON->password = md5($val->password);
				}
				if($name == "website"){
					if($val->domain != "")
						$this->UserJSON->website->domain = $val->domain;
				    $this->UserJSON->website->ssl = $val->ssl;
				    $this->UserJSON->website->title = $val->title;
				    $this->UserJSON->website->author = $val->author;
				    $this->UserJSON->website->updated_at = date('Y-m-d H:i:s');
				    $this->UserJSON->website->description = $val->description;
				    $this->UserJSON->website->social = $val->social;
				    $this->UserJSON->website->logo = $val->logo;
				    $this->UserJSON->website->keywords = $val->keywords;
					if($val->home != "")
				    	$this->UserJSON->website->home = $val->home;
				}
			}
			$this->saveJSON();
			return $this->getSettings();
		}
	}
?>
