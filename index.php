<?php
$TmpRequestURI = explode("?", $_SERVER['REQUEST_URI'])[0];
$TmpQueryString = ($_SERVER['QUERY_STRING'] == "" ? "" : "?" . $_SERVER['QUERY_STRING']);

if (substr($TmpRequestURI, -1) == "/" && $_SERVER['REQUEST_METHOD'] == "GET" && $TmpRequestURI != "/" && $TmpRequestURI != "/admin/") {
	$TURL = "https://" . $_SERVER['HTTP_HOST'] . substr($TmpRequestURI, 0, -1);
	header("Location: $TURL$TmpQueryString", true, 301);
	die();
}

if (isset($_GET['p'])) {
	header('Location: https://' . $_SERVER['HTTP_HOST'] . "/" . $_GET['p'], true, 302);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// Uncomment the below lines only for dev
// header("Access-Control-Allow-Origin: http://localhost:8080");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
// header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Expose-Headers: Access-Control-Allow-Origin");	

function show404()
{
	global $EJT;
	header("HTTP/1.1 404 Not Found");
	$EJT->page->name = "error";
	$EJT->generateStatic();
	die();
}

require_once './EJIO/Scripts/AltoRouter.php';
require_once './EJIO/Scripts/EJParser.php';
require_once './EJIO/Scripts/EJTemplate.php';
require_once './EJIO/Scripts/Api.php';

$WebsitesAvailable = json_decode(file_get_contents("websites.json"));
$tmpWebsiteName = $_SERVER['HTTP_HOST'];
if (strpos($tmpWebsiteName, "www.") !== FALSE) {
	//remove www from the website name, as we treat it as an alias
	$tmpWebsiteName = explode(".", $tmpWebsiteName);
	$tmpWebsiteName = $tmpWebsiteName[1] . "." . $tmpWebsiteName[2];
}
$WebsiteRequested = $tmpWebsiteName;

$Website = null;
if ($WebsitesAvailable->{$WebsiteRequested}) {
	$Website = $WebsitesAvailable->{$WebsiteRequested}->user;
	$Website = json_decode(file_get_contents("./Users/$Website.json"));
}

if ($Website == null)
	die("Website not found");

if ($Website) {
	$Page = new stdClass();
	$Page->url = "/"; //base url or sub-folder name
	$Page->user = $Website->username;
	$Page->theme = strtolower($Website->website->theme != "" ? $Website->website->theme : "default");
	$Page->location->templates = "./UsersTemplates/" . $Page->user . "/" . $Page->theme;
	$Page->location->pages = "./UsersPages/" . $Page->user;

	$Page->site = $Website->website;
	$Page->site->url = ($Page->site->ssl ? "https://" : "http://") . $_SERVER['HTTP_HOST']; // robin

	if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == "http" && $Page->site->ssl == true) {
		$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $redirect);
		exit();
	}

	$Page->EJ = null;
	if ($Website->integrations->ejunkie->enabled === true) {
		$Page->EJ = $Website->integrations->ejunkie;
		$Page->EJ->selectedCategory = null;
		$Page->EJ->selectedProduct = null;
		$Page->EJ->showTags = true;
		$Page->EJ->showSearch = true;
	}

	$router = new AltoRouter();
	$router->setBasePath("");

	if ($Page->EJ) {
		$router->map('GET', "/" . $Page->EJ->shop . "/[i:page]?", null, 'shop');
		$router->map('GET', "/" . $Page->EJ->shop, null, 'shopP');
		$router->map('GET', "/" . $Page->EJ->shop . "/tags/[*:tag]", null, 'productTag');
		$router->map('GET', "/" . $Page->EJ->shop . "/cat/[*:tag1]/[*:tag2]?/[*:tag3]?/[*:tag4]?/[*:tag5]?", null, 'tags');
		$router->map('GET', "/" . $Page->EJ->shop . "/" . $Page->EJ->product . "/[*:item]/[*:slug]?", null, 'product');
	}

	$router->map('GET', '/', null, 'home');
	$router->map('GET|POST', '/admin/[*:param]?', null, 'admin');
	$router->map('POST|OPTIONS', '/api/[:endpoint]?/[:param]?', null, 'api');
	$router->map('POST|OPTIONS', '/api', null, 'api2');
	$router->map('GET', '/[*:page]', null, 'static');

	$match = $router->match();

	if ($match)
		require 'routeHandler.php';
	else
		show404();
}