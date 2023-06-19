<?php
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
	die();
}
$endpoint = $match['params']['endpoint'];
$params = (isset($match['params']['param']) ? $match['params']['param'] : null);
if ($endpoint == "assets" && $params == "save") {
	//element vuejs sends POST data, not json
	$_POST = $_POST;
} else {
	$_POST = json_decode(file_get_contents('php://input'), true);
}
require_once './EJIO/Scripts/Api.php';

$user = $_SESSION['EJIO_user'];

if ($params == "save")
	$updateCall = true;
else
	$updateCall = false;
$api = new Api($user, $updateCall);
if (isset($_GET['pretty']))
	$pretty = JSON_PRETTY_PRINT;
else
	$pretty = null;

function sendResponse($response)
{
	global $pretty;
	echo json_encode($response, $pretty);
	die();
}

switch ($endpoint) {

	case "":
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "session":
		sendResponse(array("status" => true, "ts" => time()));
		break;
	case "user":
		sendResponse($api->getUserMetadata());
		break;
	case "website":
		sendResponse($api->getWebsiteMetadata());
		break;
	case "pages":
		sendResponse($api->getPages());
		break;
	case "folders":
		sendResponse($api->getFolders());
		break;
	case "websiteStats":
		sendResponse($api->getWebsiteStats());
		break;
	case "page":
		if ($params == null)
			sendResponse($api->getPage($_POST['key']));
		if ($params == "visibility")
			sendResponse($api->setPageVisibility($_POST['key'], $_POST['page']));
		if ($params == "delete")
			sendResponse($api->deletePage($_POST['key']));
		if ($params == "save")
			sendResponse($api->savePage($_POST['key'], $_POST['page']));
		if ($params == "addfolder")
			sendResponse($api->addFolder($_POST['key']));
		if ($params == "deletefolder")
			sendResponse($api->deleteFolder($_POST['key']));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "templates":
		sendResponse($api->getTemplates());
		break;
	case "template":
		if ($params == null)
			sendResponse($api->getTemplate($_POST['key']));
		if ($params == "delete")
			sendResponse($api->deleteTemplate($_POST['key']));
		if ($params == "save")
			sendResponse($api->saveTemplate($_POST['key'], $_POST['template']));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "themes":
		sendResponse($api->getThemes());
		break;
	case "theme":
		if ($params == "delete")
			sendResponse($api->deleteTheme($_POST['key']));
		if ($params == "save")
			sendResponse($api->saveTheme($_POST['key']));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "assets":
		if ($params == null)
			sendResponse($api->getAssets());
		if ($params == "delete")
			sendResponse($api->deleteAsset($_POST['key']));
		if ($params == "save")
			sendResponse($api->saveAssets($_REQUEST));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "integrations":
		if ($params == null)
			sendResponse($api->getIntegrations());
		if ($params == "save")
			sendResponse($api->saveIntegration($_POST['integrations']));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	case "settings":
		if ($params == null)
			sendResponse($api->getSettings());
		if ($params == "name")
			sendResponse($api->getSettings('name'));
		if ($params == "save")
			sendResponse($api->saveSettings($_POST['settings']));
		$api->invalidRequest("Endpoint not found", 404);
		break;
	default:
		$api->invalidRequest("Endpoint not found", 404);
}
die();