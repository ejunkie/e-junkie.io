<?php

class EJParser
{
	var $json_url = "https://s3.amazonaws.com/json.e-junkie.com/";
	var $api_url = "https://api.e-junkie.com/api/";
	var $clientId = null;
	var $products = array();
	var $availableTags = array();
	var $client = null;
	var $totalCount = 0;
	var $selectedTags = null;
	var $selectedProduct = null;
	var $selectedProductTag = null;
	var $validCall = false;
	var $relatedProducts = array();
	var $url = "";
	var $pref = null;
	var $totalPages = 1;
	var $currentPage = 1;
	var $size = 16;
	var $maxRelated = 0;
	var $apiKey = null;

	function __construct($client, $tags, $item, $productTag, $page, $apiKey = null, $lite = false)
	{
		$this->clientId = intval($client);
		$this->apiKey = $apiKey;
		if ($this->apiKey) {
			$this->api_url = $this->api_url . $this->clientId;
		} else {
			$this->json_url = $this->json_url . $this->clientId;
		}
		$this->selectedTags = $tags;
		if ($item != null)
			$this->selectedProduct = $item;
		if ($productTag != null)
			$this->selectedProductTag = $productTag;
		if ($page->url)
			$this->url = ($page->url == "/" ? "/" : $page->url . "/") . $page->EJ->shop;
		$this->pref = $page->EJ->pref;
		if ($page->pageNo)
			$this->currentPage = $page->pageNo - 1;
		$this->maxRelated = $page->EJ->maxRelated;
		if ($this->apiKey)
			$this->fetchAPI($lite);
		else
			$this->fetchJSON($lite);
	}

	function fetchAPI($lite = false)
	{
		if ($this->clientId == 0)
			return false;
		$postdata = http_build_query(array('key' => $this->apiKey));
		$opts = array(
			'http' =>
			array(
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			)
		);
		$context = stream_context_create($opts);
		if ($lite) {
			$data = file_get_contents($this->api_url . "/?lite", false, $context);
			$this->products = json_decode($data)->products;
			$this->validCall = true;
			return true;
		} else if ($this->selectedProduct) {
			$data = file_get_contents($this->api_url . "/" . $this->selectedProduct, false, $context);
		} else {
			$data = file_get_contents($this->api_url . "/?page=" . ($this->currentPage + 1), false, $context);
		}
		if ($data != "") {
			$data = json_decode($data);
			$this->client = $data->client;
			$this->totalCount = $data->totalCount;
			$this->totalPages = $data->totalPages;
			$this->size = $data->size;
			$temp_products = $data->products;
			$this->products = array();
			foreach ($temp_products as $product) {
				if ($this->pref) {
					if ($this->pref->hide_out_of_stock && $product->out_of_stock)
						continue;
				}
				$product->name = htmlspecialchars($product->name, ENT_QUOTES, "UTF-8");
				$product->tagline = htmlspecialchars($product->tagline, ENT_QUOTES, "UTF-8");
				$this->products[] = $product;
			}
			$this->validCall = true;
			return true;
		} else
			return false;
	}

	function fetchJSON($lite)
	{
		if ($this->clientId == 0)
			return false;
		$data = file_get_contents($this->json_url);
		if ($data != "") {
			$data = json_decode($data);
			if ($lite) {
				foreach ($data->items as $product) {
					$this->products[] = array(
						"id" => $product->id,
						"name" => $product->name,
						"number" => $product->number
					);
				}
				$this->validCall = true;
				return true;
			}
			$this->client = $data->client;
			$this->totalCount = $data->count;
			$temp_products = $data->items;
			$this->products = array();
			foreach ($temp_products as $product) {
				if ($this->pref) {
					if ($this->pref->hide_out_of_stock && $product->out_of_stock)
						continue;
				}
				$product->name = htmlspecialchars($product->name, ENT_QUOTES, "UTF-8");
				$product->tagline = htmlspecialchars($product->tagline, ENT_QUOTES, "UTF-8");
				$product->price = str_replace(".00", "", $product->price);
				$this->products[] = $product;
			}
			$temp_products = array();
			foreach ($this->products as $product) {
				if (in_array($product->id, ["1631204", "1631205", "1631206", "1630905"]))
					continue;
				else
					$temp_products[] = $product;
			}
			foreach ($this->products as $product)
				if ($product->id == "1631204")
					$temp_products[] = $product; foreach ($this->products as $product)
				if ($product->id == "1631205")
					$temp_products[] = $product; foreach ($this->products as $product)
				if ($product->id == "1631206")
					$temp_products[] = $product; foreach ($this->products as $product)
				if ($product->id == "1630905")
					$temp_products[] = $product;

			$this->products = $temp_products;

			$this->validCall = true;
			return true;
		} else
			return false;
	}

	function getSlug($str)
	{
		return str_replace("%2F", "-", urlencode(str_replace(' ', '-', $str)));
	}

	function getURL($product, $perma = false)
	{
		if ($perma)
			return $this->url . "/product/" . rawurlencode(utf8_encode($product->number));
		$url = $this->url . "/product/" . $product->number . "/" . $product->slug;
		return $url;
	}

	function getAvailableTags()
	{
		$allTags = array();

		if ($this->selectedProduct) { //product case
			foreach ($this->products[0]->tags as $tag)
				$allTags[] = $tag;
		} else if (
			$this->selectedTags['tag1'] != null || //tag case 
			$this->selectedTags['tag2'] != null ||
			$this->selectedTags['tag3'] != null ||
			$this->selectedTags['tag4'] != null ||
			$this->selectedTags['tag5'] != null
		) {

			$selectedTagLevel = 0;
			if ($this->selectedTags['tag5'] != null)
				$selectedTagLevel = 4;
			else if ($this->selectedTags['tag4'] != null)
				$selectedTagLevel = 2;
			else if ($this->selectedTags['tag3'] != null)
				$selectedTagLevel = 2;
			else if ($this->selectedTags['tag2'] != null)
				$selectedTagLevel = 1;
			else if ($this->selectedTags['tag1'] != null)
				$selectedTagLevel = 0;

			foreach ($this->products as $product) {
				if ($selectedTagLevel == 4)
					break;
				$allTags[] = $product->tags[$selectedTagLevel + 1];
			}
		} else { //shop case
			foreach ($this->products as $product)
				$allTags[] = $product->tags[0];
		}

		//sanitize all tags
		$url = "";
		$url .= ($this->selectedTags['tag1'] ? "/" . urlencode($this->selectedTags['tag1']) : "");
		$url .= ($this->selectedTags['tag2'] ? "/" . urlencode($this->selectedTags['tag2']) : "");
		$url .= ($this->selectedTags['tag3'] ? "/" . urlencode($this->selectedTags['tag3']) : "");
		$url .= ($this->selectedTags['tag4'] ? "/" . urlencode($this->selectedTags['tag4']) : "");
		$url .= ($this->selectedTags['tag5'] ? "/" . urlencode($this->selectedTags['tag5']) : "");

		$insertedTags = array();
		foreach ($allTags as $tag) {
			if (!$tag)
				continue;
			if (strtolower($tag) != "all products" && !in_array(strtolower($tag), $insertedTags)) {
				$insertedTags[] = strtolower($tag);
				$this->availableTags[] = array(
					'title' => $tag,
					'url' => $url . "/" . urlencode($tag),
				);
			}
		}
	}

	function getTagProducts()
	{
		if ($this->validCall) {
			$temp_array = array();
			$selectedTags = array();
			$insertedItems = array();

			if ($this->selectedTags['tag1'])
				$selectedTags[] = $this->selectedTags['tag1'];
			if ($this->selectedTags['tag2'])
				$selectedTags[] = $this->selectedTags['tag2'];
			if ($this->selectedTags['tag3'])
				$selectedTags[] = $this->selectedTags['tag3'];
			if ($this->selectedTags['tag4'])
				$selectedTags[] = $this->selectedTags['tag4'];
			if ($this->selectedTags['tag5'])
				$selectedTags[] = $this->selectedTags['tag5'];

			foreach ($this->products as $product) {
				$foundCount = 0;
				foreach ($selectedTags as $k => $tag) {
					if (strtolower($tag) == strtolower($product->tags[$k])) {
						$foundCount++;
					}
				}
				if ($foundCount == count($selectedTags) && !in_array($product->number, $insertedItems)) {
					$product->slug = $this->getSlug($product->name);
					$product->url = $this->getURL($product);
					$product->permalink = $this->getURL($product, true);
					$temp_array[] = $product;
					$insertedItems[] = $product->number;
				}
			}
			$this->products = $temp_array;
			//added sorting for out-of-stock products to be dipped to bottom
			$this->products = $this->sortOutOfStock($this->products);
			$this->getAvailableTags();
			return true;
		}
		return false;
	}

	function getProductTagProducts()
	{
		if ($this->validCall) {
			$tag = strtolower($this->selectedProductTag);
			$temp_array = array();
			foreach ($this->products as $product) {
				if (in_array($tag, array_map("strtolower", $product->tags))) {
					$product->slug = $this->getSlug($product->name);
					$product->url = $this->getURL($product);
					$product->permalink = $this->getURL($product, true);
					$temp_array[] = $product;
				}
			}
			$this->products = $temp_array;

			//added sorting for out-of-stock products to be dipped to bottom
			$this->products = $this->sortOutOfStock($this->products);

			return true;
		}
		return false;
	}


	function getProduct()
	{
		if ($this->validCall) {
			$temp_product = null;
			foreach ($this->products as $product) {
				if ($product->number == $this->selectedProduct) {
					$product->slug = $this->getSlug($product->name);
					$product->url = $this->getURL($product);
					$product->permalink = $this->getURL($product, true);
					$temp_product = $product;
					break;
				}
			}

			if (!$temp_product) {
				$this->products = array();
				return false;
			}

			foreach ($this->products as $product) {
				if (sizeof($temp_product->tags)) {
					foreach ($temp_product->tags as $tag) {
						if (in_array($tag, $product->tags) && $product->number != $temp_product->number && !in_array($product, $this->relatedProducts)) {
							$product->slug = $this->getSlug($product->name);
							$product->url = $this->getURL($product);
							$product->permalink = $this->getURL($product, true);
							$this->relatedProducts[] = $product;
						}
					}
				}
			}

			if ($temp_product)
				$this->products = array($temp_product);
			else
				$this->products = array();

			#$this->getAvailableTags();
			return true;
		}
		return false;
	}


	function getProducts()
	{
		if ($this->validCall) {
			if ($this->pref) {
				if ($this->pref->hidden && count($this->pref->hidden) > 0) {
					$tmp_arr = array();
					foreach ($this->products as $product) {
						if (!in_array($product->number, $this->pref->hidden))
							$tmp_arr[] = $product;
					}
					$this->products = $tmp_arr;
				}
			}
			$this->getAvailableTags();
			$temp_array = array();
			$temp_products = $this->products;
			if ($this->pref) {
				if ($this->pref->pinned && count($this->pref->pinned) > 0) {
					$tmp_arr = array();
					foreach ($this->products as $product) {
						if (in_array($product->number, $this->pref->pinned))
							$tmp_arr[] = $product;
					}
					foreach ($this->products as $product) {
						if (!in_array($product, $tmp_arr))
							$tmp_arr[] = $product;
					}
					$this->products = $tmp_arr;
				}
				if ($this->pref->pinned_down && count($this->pref->pinned_down) > 0) {
					$tmp_arr = array();
					foreach ($this->products as $product) {
						if (!in_array($product->number, $this->pref->pinned_down))
							$tmp_arr[] = $product;
					}
					foreach ($this->products as $product) {
						if (!in_array($product, $tmp_arr))
							$tmp_arr[] = $product;
					}
					$this->products = $tmp_arr;
				}
			}

			//added sorting for out-of-stock products to be dipped to bottom
			$this->products = $this->sortOutOfStock($this->products);

			$this->totalCount = count($this->products);
			$this->totalPages = ceil($this->totalCount / $this->size);
			$this->products = array_slice($this->products, ($this->currentPage * $this->size), $this->size);

			foreach ($this->products as $product) {
				$product->slug = $this->getSlug($product->name);
				$product->url = $this->getURL($product);
				$product->permalink = $this->getURL($product, true);
				$temp_array[] = $product;
			}
			$this->products = $temp_array;
			return true;
		}
		return false;
	}

	function sortOutOfStock($products)
	{
		//added sorting for out-of-stock products to be dipped to bottom
		$tmpProducts = [];
		foreach ($products as $product) {
			if ($product->out_of_stock == true)
				;
			else
				$tmpProducts[] = $product;
		}
		foreach ($products as $product) {
			if ($product->out_of_stock == true)
				$tmpProducts[] = $product;
		}
		$products = $tmpProducts;
		return $products;
	}

	function debugger($obj)
	{
		echo "<pre>";
		print_r($obj);
		die();
	}

}
?>