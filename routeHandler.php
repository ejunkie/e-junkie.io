<?php
function checkForRedirections($x)
{

	$redirections = [
	];

	if ($redirections[$x] != null) {
		if (strpos($redirections[$x], "http") !== FALSE) {
			header("Location: " . $redirections[$x], true, 301);
		} else {
			header("Location: /" . $redirections[$x], true, 301);
		}
		die();
	}
}

function createCSRFToken()
{
	if (isset($_SESSION['csrf']))
		;
	else
		$_SESSION['csrf'] = md5("__EJIO__" . time() . mt_rand(1, 1000));
}

if ($match['name'] == "api" || $match['name'] == "api2") {
	session_start();
	require_once 'apiHandler.php';
	die();
}

if ($match['name'] == "admin") {
	session_start();
	createCSRFToken();

	if ($match['params']['param'] == "logout")
		unset($_SESSION['EJIO_user']);

	if (isset($_SESSION['EJIO_user'])) //user is logged in
		require_once 'admin/index.html';
	else { //show login
		$LoginUsername = $Page->user;
		if (isset($_POST['login']) || isset($_POST['register']))
			require_once 'admin/loginHandler.php';
		require_once 'admin/login.php';
	}
	die();
}

/*incase we get /home in request, its better to forward it to / with 301 to avoid duplicate content*/
if ($match['params']['page'] == $Page->site->home) {
	header('Location: https://' . $_SERVER['HTTP_HOST'], true, 301);
}

$Api = new Api($Page->user, false);

if ($Page->url != "/") {
	$Page->url = "/" . $Page->url;
	$Page->url = substr($Page->url, 0, -1);
}
$Page->name = null;

switch ($match['name']) {
	case 'home':
		$Page->name = $Page->site->home;
		break;
	case 'static':
		$Page->name = $match['params']['page'];
		break;
	case 'shopP':
	case 'shop':
		$Page->name = "shop";
		$Page->type = "shop";
		break;
	case 'tags':
		$Page->name = "shop";
		$Page->type = "tags";
		$Page->EJ->selectedTags = array(
			'tag1' => urldecode($match['params']['tag1']),
			'tag2' => urldecode($match['params']['tag2']),
			'tag3' => urldecode($match['params']['tag3']),
			'tag4' => urldecode($match['params']['tag4']),
			'tag5' => urldecode($match['params']['tag5']),
		);
		break;
	case 'productTag':
		$Page->name = "shop";
		$Page->type = "productTag";
		$Page->EJ->selectedProductTag = urldecode($match['params']['tag']);
		break;
	case 'product':
		$Page->name = "product";
		$Page->type = "product";
		$Page->EJ->selectedProduct = $match['params']['item'];
		break;
	default:
		$Page->name = "404";
		break;
}

checkForRedirections($Page->name);

$EJ = null;
if (($Page->type == "shop" || $Page->type == "product" || $Page->type == "tags" || $Page->type == "productTag" || $match['name'] == "sitemap") && $Page->EJ != null) {
	if (intval($match['params']['page']) == 1) {
		header('Location: /' . $Page->EJ->shop . '/' . $TmpQueryString, true, 301);
		die();
	}
	if ($match['params']['page'] == null)
		$Page->pageNo = 1;
	else
		$Page->pageNo = intval($match['params']['page']);
	if ($Page->pageNo == 0)
		$Page->pageNo++;
	$EJ = new EJParser($Page->EJ->clientId, $Page->EJ->selectedTags, $Page->EJ->selectedProduct, $Page->EJ->selectedProductTag, $Page, $Page->EJ->apiKey);
}

if ($match["name"] == "sitemap") {
	require_once "sitemap.php";
	die();
}


$EJT = new EJTemplate($Page, $Api, $EJ);

if ($Page->name == "shop/product") {
	header('Location: /shop', true, 301);
	die();
}

if ($Page->name == "product" && $EJ->selectedProduct == null) {
	header('Location: /shop', true, 301);
	die();
}


if (($Page->type == "shop" || $Page->type == "product" || $Page->type == "tags" || $Page->type == "productTag") && $EJ != null) {
	if ($Page->type == 'shop') {
		$EJ->getProducts();
	} else if ($Page->type == 'tags') {
		$EJ->getTagProducts();
	} else if ($Page->type == 'productTag') {
		$EJ->getProductTagProducts();
	} else if ($Page->type == 'product') {
		$EJ->getProduct();
	} else {
		show404();
	}
	if (count($EJ->products) == 0)
		show404();
	else
		$EJT->generateShop();
} else {
	$EJT->generateStatic();
}