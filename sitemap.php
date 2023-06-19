<?php
header('Content-Type: text/html; charset=utf-8');
foreach ($Api->UserJSON->pages as $key => $page) {
	if ($page->visible) {
		echo $Page->site->url . "/" . str_replace(".md", "", $key) . "\n";
	}
}
if ($EJ) {
	echo $Page->site->url . "/shop\n";
	foreach ($EJ->products as $product) {
		echo $Page->site->url . "/shop/product/" . $product->number . "\n";
	}
}