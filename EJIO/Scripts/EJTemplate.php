<?php
require_once 'Parsedown.php';
require_once 'Spyc.php';

class EJTemplate
{
	var $page = null;
	var $EJ = null;
	var $API = null;
	var $ChildPages = null;

	function __construct($page, $api, $EJ = null)
	{
		$this->page = $page;
		$this->API = $api;
		if ($EJ) {
			$this->EJ = $EJ;
		}
	}

	function getTemplate($templateName)
	{
		return file_get_contents($this->page->location->templates . "/" . $templateName) . "\n";
	}

	function getContents()
	{
		return file_get_contents($this->page->location->pages . "/" . $this->page->name . ".md");
	}

	function importIncludes($template)
	{
		do {
			$pos = strpos($template, "@include");
			if ($pos === FALSE)
				return $template;
			$str = substr($template, $pos, (strpos($template, "\n", $pos) - $pos));
			$contents = file_get_contents($this->page->location->templates . "/" . str_replace("@include ", "", $str) . ".ej");
			$template = str_replace($str, $contents, $template);
		} while (true);
	}

	function cleanTemplate($template, $key)
	{
		while (strpos($template, "{" . $key . "}") !== FALSE) {
			$template = str_replace("{" . $key . "}" . $this->getTemplateString($template, $key) . "{/$key}", "", $template);
		}
		return $template;
	}

	function insertScripts($content, $yaml)
	{
		$tmp_str = "";
		if (count($yaml->Scripts) > 0) {
			foreach ($yaml->Scripts as $script) {
				$tmp_str .= "<script src='$script' type='text/javascript'></script>";
			}
			$tmp_str .= "\n";
			$content = $tmp_str . $content;
		}
		if ($yaml->Javascript) {
			$content = $content . "\n<script type='text/javascript'>\n";
			$content = $content . $yaml->Javascript;
			$content = $content . "\n</script>";
		}
		return $content;
	}

	function generateJSONLD($header, $pageType = "")
	{
		$json_ld = array();
		$json_ld["@context"] = "http://schema.org";
		$json_ld["@graph"] = array();
		$json_ld["@graph"][] = array(
			"@type" => "Organization",
			"url" => $this->page->site->url,
			"name" => $header->author,
			"logo" => $header->logo,
		);

		if ($pageType == "shop") {
			$x = array();
			foreach ($this->EJ->availableTags as $po => $t) {
				if ($t != "") {
					$x[] = array(
						"@type" => "ListItem",
						"position" => $po + 1,
						"item" => array(
							"@id" => "http" . ($_SERVER['HTTPS'] == "on" ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $this->page->url . "/tags/" . str_replace("%2F", "/", urlencode($t)),
							"name" => $t,
						)
					);
				}
			}
			/*$json_ld["@graph"][] = array (
										   "@type" => "BreadcrumbList",
										   "itemListElement" => $x
									   );*/

			if (
				$this->EJ->selectedTags['tag1'] ||
				$this->EJ->selectedTags['tag2'] ||
				$this->EJ->selectedTags['tag3'] ||
				$this->EJ->selectedTags['tag4'] ||
				$this->EJ->selectedTags['tag5']
			) {
				$xy = array();
				for ($x = 1; $x <= 5; $x++) {
					if ($this->EJ->selectedTags['tag' . $x]) {
						$url = ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat";
						$tag = $this->EJ->selectedTags['tag' . $x];
						for ($y = 1; $y <= $x; $y++) {
							$url .= "/" . urlencode($this->EJ->selectedTags['tag' . $y]);
						}
						$xy[] = array(
							"@type" => "ListItem",
							"position" => $x,
							"item" => array(
								"@id" => "http" . ($_SERVER['HTTPS'] == "on" ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $url,
								"name" => $tag,
							)
						);
					}
				}
				$json_ld["@graph"][] = array(
					"@type" => "BreadcrumbList",
					"itemListElement" => $xy
				);
			}

			$y = array();
			foreach ($this->EJ->products as $m => $p) {
				$y[] = array(
					"@type" => "ListItem",
					"position" => $m + 1,
					"url" => "http" . ($_SERVER['HTTPS'] == "on" ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $p->url
				);
			}
			$json_ld["@graph"][] = array(
				"@type" => "ItemList",
				"itemListElement" => $y
			);
		} else if ($pageType == "product") {
			foreach ($this->EJ->products as $p) {
				$product_price = explode(' ', $p->price);
				if ($p->needs_advance_options == "true") {
					$offers = array(
						"@type" => "AggregateOffer",
						"url" => $this->page->site->url . "/" . $header->url,
						"priceCurrency" => $p->currency,
						"lowPrice" => $product_price[0],
						"highPrice" => $product_price[2],
						"priceValidUntil" => date('Y-m-d', strtotime('+1 years')),
						"availability" => "https://schema.org/InStock",
						"seller" => array(
							"@type" => "Organization",
							"name" => $this->EJ->client->shop_name,
							"logo" => $this->EJ->client->logo != "" && $this->EJ->client->logo != "https://s3.amazonaws.com/logos.e-junkie.com/" ? $this->EJ->client->logo : ""
						)
					);
				} else {
					$offers = array(
						"@type" => "Offer",
						"url" => $this->page->site->url . "/" . $header->url,
						"priceCurrency" => $p->currency,
						"availability" => "https://schema.org/InStock",
						"priceValidUntil" => date('Y-m-d', strtotime('+1 years')),
						"price" => $product_price[0],
						"seller" => array(
							"@type" => "Organization",
							"name" => $this->EJ->client->shop_name,
							"logo" => $this->EJ->client->logo != "" && $this->EJ->client->logo != "https://s3.amazonaws.com/logos.e-junkie.com/" ? $this->EJ->client->logo : ""
						)
					);
				}

				$xy = array();
				foreach ($p->tags as $k => $tag) {
					$url = ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat";
					for ($y = 0; $y <= $k; $y++) {
						$url .= "/" . urlencode($this->EJ->products[0]->tags[$y]);
					}
					$xy[] = array(
						"@type" => "ListItem",
						"position" => ($k + 1),
						"item" => array(
							"@id" => "http" . ($_SERVER['HTTPS'] == "on" ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $url,
							"name" => $tag,
						)
					);
				}
				$json_ld["@graph"][] = array(
					"@type" => "BreadcrumbList",
					"itemListElement" => $xy
				);

				$json_ld["@graph"][] = array(
					"@type" => "Product",
					"name" => $p->name,
					"sku" => $p->number,
					"brand" => array("@type" => "Thing", "name" => $this->page->site->title),
					"image" => array($p->thumbnail),
					"description" => $p->description,
					"offers" => $offers
				);
			}
		} else {
			$json_ld["@graph"][] = array(
				"@type" => $this->page->site->type,
				"name" => $header->name,
				"url" => $header->url,
				"address" => $this->page->site->address,
				"geo" => $this->page->site->geo,
				"telephone" => $this->page->site->telephone,
				"logo" => $header->logo,
				"sameAs" => $this->page->site->social
			);
		}
		return '<script type="application/ld+json">' . json_encode($json_ld) . '</script>' . "\n";
	}

	function getTemplateString($str, $needle)
	{
		$starting_needle = "{" . $needle . "}";
		$ending_needle = "{/" . $needle . "}";
		$s_n = strpos($str, $starting_needle);
		$e_n = strpos($str, $ending_needle, $s_n);
		$str = substr($str, $s_n, ($e_n - $s_n));
		$str = str_replace($starting_needle, "", $str);
		$str = str_replace($ending_needle, "", $str);
		if ($str == "")
			return false;
		return $str;
	}

	function generateChildPages($str, $parent = null)
	{
		while (strpos($str, "{ChildPages}") !== FALSE) {
			$child_template = $this->getTemplateString($str, 'ChildPages');
			$childOptions = explode("}", $child_template)[0];
			$child_final_template = str_replace($childOptions . "}", "", $child_template);
			$childOptions = str_replace("{Options:", "", $childOptions);
			$childOptions = (object) explode(";", $childOptions);
			$tempOptions = new stdClass();
			foreach ($childOptions as $ch) {
				$ch = trim($ch);
				$ch = explode('=', $ch);
				if ($ch[0] != "")
					$tempOptions->{$ch[0]} = $ch[1];
			}
			$childOptions = $tempOptions;
			if ($parent != "")
				$childOptions->Parent = $parent;
			$childOptions->Size = ($childOptions->Size == null || intval($childOptions->Size) <= 0) ? 10 : $childOptions->Size;
			if ($childOptions->Pagination == "true")
				$childOptions->Pagination = true;
			else
				$childOptions->Pagination = false;
			$childPages = $this->API->getChildPages($childOptions->Parent, $childOptions->Size, $childOptions->Pagination, $childOptions->Order, $this->page->pageNo);
			$final_str = "";
			$this->ChildPages = new stdClass();
			$this->ChildPages->parent = $childOptions->Parent;
			$this->ChildPages->size = $childOptions->Size;
			$this->ChildPages->totalPages = ceil(count($this->API->UserJSON->folders->{$childOptions->Parent}) / $childOptions->Size);
			$this->ChildPages->currentPage = $this->page->pageNo;
			foreach ($childPages as $page) {
				$tstr = str_replace("{ChildPage.Url}", $this->page->url . "/" . str_replace(".md", "", $page->key), $child_final_template);
				$tstr = str_replace("{ChildPage.Title}", $page->yaml->title, $tstr);
				$tstr = str_replace("{ChildPage.Author}", $page->yaml->author, $tstr);
				$tstr = str_replace("{ChildPage.Keywords}", $page->yaml->keywords, $tstr);
				$tstr = str_replace("{ChildPage.Description}", $page->yaml->description, $tstr);
				$tstr = str_replace("{ChildPage.Folder}", $page->yaml->folder, $tstr);
				$final_str = $final_str . $tstr;
			}
			$str = substr_replace($str, $final_str, strpos($str, "{ChildPages}"), strlen("{ChildPages}$child_template{/ChildPages}"));
		}
		return $str;
	}

	function generateChildPagesPagination($str)
	{
		while (strpos($str, "{Pagination}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'Pagination');
			$final_str = "";
			if ($this->ChildPages->totalPages == 1) {
			} else {
				for ($tmpPage = 1; $tmpPage <= $this->ChildPages->totalPages; $tmpPage++) {
					$tstr = str_replace("{Pagination.Url}", $this->page->url . "/" . $this->ChildPages->parent . "/" . $tmpPage, $tag_template);
					$tstr = str_replace("{Pagination.Number}", $tmpPage, $tstr);
					if ($tmpPage == $this->EJ->currentPage + 1)
						$tstr = str_replace("{Pagination.Active}", "active", $tstr);
					else
						$tstr = str_replace("{Pagination.Active}", "", $tstr);
					$final_str = $final_str . $tstr;
				}
			}
			$str = substr_replace($str, $final_str, strpos($str, "{Pagination}"), strlen("{Pagination}$tag_template{/Pagination}"));
		}
		return $str;
	}

	function generateProductsLite($str, $limit = null)
	{
		while (strpos($str, "{EJProducts}") !== FALSE) {
			$product_template = $this->getTemplateString($str, 'EJProducts');
			$EJ = new EJParser($this->page->EJ->clientId, null, null, $this->page, $this->page->EJ->apiKey);
			$EJ->getProducts();
			$x = 0;
			foreach ($EJ->products as $product) {
				$x++;
				if ($limit != null && $x > $limit)
					break;
				$tstr = "";
				$tstr = str_replace("{Product.Url}", $product->url, $product_template);
				$tstr = str_replace('{Product.Number}', $product->number, $tstr);
				$tstr = str_replace('{Product.Name}', $product->name, $tstr);
				$tstr = str_replace('{Product.Id}', $product->id, $tstr);
				$tstr = str_replace('{Product.Number}', $product->number, $tstr);
				$tstr = str_replace('{Product.Tagline}', $product->tagline, $tstr);
				if ($product->thumbnail)
					$tstr = str_replace('{Product.Thumbnail}', $product->thumbnail, $tstr);
				else
					$tstr = str_replace('{Product.Thumbnail}', "https://www.e-junkie.com/ecom/spacer.gif", $tstr);
				$tstr = str_replace('{Product.Price}', $product->price, $tstr);
				$tstr = str_replace('{Product.Currency}', $product->currency, $tstr);

				$final_str = $final_str . $tstr;
			}
			$str = substr_replace($str, $final_str, strpos($str, "{EJProducts}"), strlen("{EJProducts}$product_template{/EJProducts}"));
		}
		return $str;
	}


	function stripLanguage($content)
	{

		$languages = ["en", "hi"];
		$languageSelected = "en";
		if ($_GET['ln'] == "hi") {
			$languageSelected = "hi";
		}

		if (strpos($content, "{Page.Lang {$languageSelected}}") === FALSE) {
			$languageSelected = "en";
		}
		$languageSelected = "en";

		foreach ($languages as $language) {
			if ($languageSelected == $language) {
				continue;
			} else {
				$content = str_replace("{Page.Lang {$language}}", "{Page.Lang}", $content);
				while (strpos($content, "{Page.Lang}") !== FALSE) {
					$tContent = $this->getTemplateString($content, 'Page.Lang');
					$content = str_replace("{Page.Lang}{$tContent}{/Page.Lang}", "", $content);
				}
			}
		}

		$content = str_replace("{Page.Lang {$languageSelected}}", "", $content);
		$content = str_replace("{/Page.Lang}", "", $content);
		return $content;
	}

	function generateStatic()
	{
		$isParentFolder = null;
		$content = $this->getContents($this->page->name);
		//check if the requested page is visible to public or not
		if ($this->API->UserJSON->pages->{$this->page->name . ".md"}->visible === false && $this->page->editing == false) {
			show404();
		}
		if ($content == "") {
			$tempBlogName = str_replace("/", "", $this->page->name);
			if ($this->API->UserJSON->folders->{$tempBlogName})
				$isParentFolder = $tempBlogName;
			else
				show404();
		}
		if ($isParentFolder == null) {
			if (substr($content, 0, 3) == "---") {
				$content = explode('---', $content, 3);
				$yaml = $content[1];
				$content = $content[2];
				$yaml = (object) Spyc::YAMLLoad($yaml);
			}
			$Parsedown = new Parsedown();
			$content = $Parsedown->text($content);
			$content = $this->insertScripts($content, $yaml);
			if ($yaml->template)
				$templateName = $yaml->template . ".ej";
			else
				$templateName = "static.ej";
		} else {
			$templateName = "listing.ej";
			$yaml = new stdClass();
		}
		$template = $this->getTemplate($templateName);
		//Set Page Details and Properties
		$Page = new StdClass();
		$Page->author = ($yaml->author ? $yaml->author : $this->page->site->author);
		$Page->description = ($yaml->description ? $yaml->description : $this->page->site->description);
		$Page->title = ($yaml->title ? $yaml->title : $this->page->site->title);
		$Page->keywords = ($yaml->keywords ? $yaml->keywords : $this->page->site->keywords);
		$Page->logo = ($yaml->image ? $yaml->image : $this->page->site->logo);
		$Page->url = ($yaml->url ? $yaml->url : $this->page->site->url . $_SERVER['REQUEST_URI']);
		$template = $this->importIncludes($template);
		$template = $this->cleanTemplate($template, 'IfProduct');
		$template = $this->cleanTemplate($template, 'IfShop');
		if ($this->page->editing == true) {
			$content = "<div id='editor_live_mark'>" . $content . "</div>";
		}
		if (strpos($template, "{ChildPages}") !== FALSE) {
			$template = $this->generateChildPages($template, $isParentFolder);
			$template = $this->generateChildPagesPagination($template);
		}

		$template = str_replace('{Page.Content}', $content, $template);
		$template = $this->stripLanguage($template);
		$template = $this->replaceLiterals($template, $Page);
		if (strpos($template, "{JSONLD}") !== FALSE)
			$template = str_replace("{JSONLD}", $this->generateJSONLD($Page), $template);
		if (strpos($template, "{EJProducts limit=") !== FALSE) {
			$limit = explode("{EJProducts limit=", $template)[1];
			$limit = intval(explode("}", $limit)[0]);
			$template = str_replace("{EJProducts limit=$limit}", "{EJProducts}", $template);
			$template = $this->generateProductsLite($template, $limit);
		}
		if (strpos($template, "{Social-Feeds}") !== FALSE) {
			$FBTemplate = '<li><div class="fb-post" data-href="{ID}"></div></li>';
			$InstaTemplate = '<li><blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="https://www.instagram.com/p/{ID}/?utm_source=ig_embed" data-instgrm-version="9" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"></blockquote></li>';
			$ffFeeds = getSocialFeeds();
			$fbFeeds = $ffFeeds->fb;
			$instaFeeds = $ffFeeds->insta;

			$ffstr = "";
			for ($xx = 0; $xx < 3; $xx++) {
				$ffstr .= str_replace("{ID}", $fbFeeds[$xx], $FBTemplate);
				$ffstr .= str_replace("{ID}", $instaFeeds[$xx], $InstaTemplate);
			}
			$template = str_replace("{Social-Feeds}", $ffstr, $template);
		}

		while (strpos($template, "{EJAllProducts}") !== FALSE) {
			$EJ = new EJParser($this->page->EJ->clientId, null, null, $this->page, $this->page->EJ->apiKey);
			$EJ->currentPage = 0;
			$EJ->size = 1000;
			$EJ->getProducts();
			$template = str_replace("{EJAllProducts}", json_encode($EJ->products), $template);
		}

		$template = $this->executePHPCodes($template);
		echo $template;
		return true;
	}

	function executePHPCodes($template)
	{
		while (strpos($template, "<?") !== FALSE) {
			$orgCode = substr($template, strpos($template, "<?") + 2, (strpos($template, "?>") - strpos($template, "<?") - 2));
			$code = "\$EJTEMPVARPHP = " . $orgCode;
			$code = str_replace("\$EJ", "\$this->EJ", $code);
			eval($code);
			$template = str_replace("<?$orgCode?>", $this->EJTEMPVARPHP, $template);
		}
		return $template;
	}

	function replaceLiterals($str, $page)
	{
		//page level 
		$str = str_replace("{Page.Title}", $page->title, $str);
		$str = str_replace("{Page.Description}", $page->description, $str);
		$str = str_replace("{Page.Author}", $page->author, $str);
		$str = str_replace("{Page.Logo}", $page->logo, $str);
		$str = str_replace("{Page.Keywords}", $page->keywords, $str);
		$str = str_replace("{Page.Url}", $page->url, $str);
		$str = str_replace("{Page.ogURL}", ($page->ogURL != "" ? $page->ogURL : $page->url), $str);

		//site level
		$str = str_replace("{Site.Title}", $this->page->site->title, $str);
		$str = str_replace("{Site.Description}", $this->page->site->description, $str);
		$str = str_replace("{Site.Author}", $this->page->site->author, $str);
		$str = str_replace("{Site.Logo}", $this->page->site->logo, $str);
		$str = str_replace("{Site.Keywords}", $this->page->site->keywords, $str);
		$str = str_replace("{Site.Url}", $this->page->site->url, $str);

		$str = str_replace("{ROOT}", $this->page->url, $str);
		$str = str_replace("{Static}", "/static/" . $this->page->user, $str);
		/*Date - Time Replacements as described in http://php.net/manual/en/function.date.php */
		$str = str_replace("{time}", time(), $str);
		$str = str_replace("{d}", date('d'), $str);
		$str = str_replace("{D}", date('D'), $str);
		$str = str_replace("{j}", date('j'), $str);
		$str = str_replace("{l}", date('l'), $str);
		$str = str_replace("{l}", date('l'), $str);
		$str = str_replace("{N}", date('N'), $str);
		$str = str_replace("{S}", date('S'), $str);
		$str = str_replace("{w}", date('w'), $str);
		$str = str_replace("{z}", date('z'), $str);
		$str = str_replace("{w}", date('w'), $str);
		$str = str_replace("{z}", date('z'), $str);
		$str = str_replace("{W}", date('W'), $str);
		$str = str_replace("{F}", date('F'), $str);
		$str = str_replace("{m}", date('m'), $str);
		$str = str_replace("{n}", date('n'), $str);
		$str = str_replace("{t}", date('t'), $str);
		$str = str_replace("{L}", date('L'), $str);
		$str = str_replace("{o}", date('o'), $str);
		$str = str_replace("{y}", date('y'), $str);
		$str = str_replace("{Y}", date('Y'), $str);
		$str = str_replace("{a}", date('a'), $str);
		$str = str_replace("{A}", date('A'), $str);
		$str = str_replace("{B}", date('B'), $str);
		$str = str_replace("{g}", date('g'), $str);
		$str = str_replace("{G}", date('G'), $str);
		$str = str_replace("{h}", date('h'), $str);
		$str = str_replace("{H}", date('H'), $str);
		$str = str_replace("{i}", date('i'), $str);
		$str = str_replace("{s}", date('s'), $str);
		$str = str_replace("{u}", date('u'), $str);
		$str = str_replace("{v}", date('v'), $str);
		$str = str_replace("{e}", date('e'), $str);
		$str = str_replace("{I}", date('I'), $str);
		$str = str_replace("{O}", date('O'), $str);
		$str = str_replace("{P}", date('P'), $str);
		$str = str_replace("{T}", date('T'), $str);
		$str = str_replace("{Z}", date('Z'), $str);
		$str = str_replace("{c}", date('c'), $str);
		$str = str_replace("{r}", date('r'), $str);

		if ($this->EJ) {
			$str = str_replace("{EJ.ViewCartUrl}", "https://www.fatfreecartpro.com/ecom/gb.php?c=cart&ejc=2&cl=" . $this->EJ->client->client_id, $str);
		}
		return $str;
	}

	//EJ-Shop Related Code excluded FROM CMS
	function generateTags2($str)
	{
		while (strpos($str, "{Tags}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'Tags');
			$final_str = "";
			if ($this->page->EJ->selectedCategory || count($this->EJ->products) == 1) {
				$tstr = str_replace("{Tag.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop, $tag_template);
				$tstr = str_replace("{Tag.Name}", "All Products", $tstr);
				$tstr = str_replace("{Tag.Active}", "", $tstr);
				$final_str = $final_str . $tstr;
			}
			foreach ($this->EJ->availableTags as $tags) {
				if ($this->page->EJ->selectedCategory) {
					if (strtolower(urldecode($this->page->EJ->selectedCategory)) == strtolower($tags)) {
						$tstr = str_replace("{Tag.Url}", '#!" class="selected', $tag_template);
						$tstr = str_replace("{Tag.Name}", $tags, $tstr);
						$tstr = str_replace("{Tag.Active}", "active", $tstr);
						$final_str = $final_str . $tstr;
						break;
					}
				}
			}
			foreach ($this->EJ->availableTags as $tags) {
				if ($this->page->EJ->selectedCategory)
					if (strtolower(urldecode($this->page->EJ->selectedCategory)) == strtolower($tags))
						continue;
				if ($tags == "")
					continue;
				$tstr = str_replace("{Tag.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat/" . str_replace("%2F", "/", urlencode($tags)), $tag_template);
				$tstr = str_replace("{Tag.Name}", $tags, $tstr);
				$tstr = str_replace("{Tag.Active}", "", $tstr);
				$final_str = $final_str . $tstr;
			}
			$str = substr_replace($str, $final_str, strpos($str, "{Tags}"), strlen("{Tags}$tag_template{/Tags}"));
		}
		return $str;
	}

	//EJ-Shop Related Code excluded FROM CMS
	function generateProductTags($str)
	{
		while (strpos($str, "{Product.Tags}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'Product.Tags');
			$final_str = "";
			foreach ($this->EJ->products[0]->tags as $tag) {
				$tstr = str_replace("{Product.Tags.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/tags/" . urlencode($tag), $tag_template);
				$tstr = str_replace("{Product.Tags.Name}", $tag, $tstr);
				$final_str = $final_str . $tstr;
			}
			$str = substr_replace($str, $final_str, strpos($str, "{Product.Tags}"), strlen("{Product.Tags}$tag_template{/Product.Tags}"));
		}
		return $str;
	}

	function generateTags($str)
	{
		while (strpos($str, "{Tags}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'Tags');
			$final_str = "";

			if ($this->EJ->selectedProductTag) {
				$tstr = str_replace("{Tag.Url}", "#", $tag_template);
				$tstr = str_replace("{Tag.Name}", "#" . $this->EJ->selectedProductTag, $tstr);
				$final_str = $final_str . $tstr;
			} else {
				foreach ($this->EJ->availableTags as $tags) {
					$tstr = str_replace("{Tag.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat" . $tags['url'], $tag_template);
					$tstr = str_replace("{Tag.Name}", $tags['title'], $tstr);
					$final_str = $final_str . $tstr;
				}
			}
			$str = substr_replace($str, $final_str, strpos($str, "{Tags}"), strlen("{Tags}$tag_template{/Tags}"));
		}
		return $str;
	}

	function generateBreadCrumb($str)
	{
		while (strpos($str, "{BreadCrumb}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'BreadCrumb');
			$final_str = "";
			if ($this->EJ->selectedProductTag && false) {
				$tstr = str_replace("{BreadCrumb.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop, $tag_template);
				$tstr = str_replace("{BreadCrumb.Name}", "All Products", $tstr);
				$final_str = $final_str . $tstr;
			} else if ($this->EJ->selectedProduct) {
				$tstr = str_replace("{BreadCrumb.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop, $tag_template);
				$tstr = str_replace("{BreadCrumb.Name}", "All Products", $tstr);
				$final_str = $final_str . $tstr;
				foreach ($this->EJ->products[0]->tags as $k => $tag) {
					$url = ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat";
					for ($y = 0; $y <= $k; $y++) {
						$url .= "/" . urlencode($this->EJ->products[0]->tags[$y]);
					}
					$tstr = str_replace("{BreadCrumb.Url}", $url, $tag_template);
					$tstr = str_replace("{BreadCrumb.Name}", "> " . $tag, $tstr);
					$final_str = $final_str . $tstr;
				}
			} else if (
				$this->EJ->selectedTags['tag1'] ||
				$this->EJ->selectedTags['tag2'] ||
				$this->EJ->selectedTags['tag3'] ||
				$this->EJ->selectedTags['tag4'] ||
				$this->EJ->selectedTags['tag5']
			) {
				$tstr = str_replace("{BreadCrumb.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop, $tag_template);
				$tstr = str_replace("{BreadCrumb.Name}", "All Products", $tstr);
				$final_str = $final_str . $tstr;

				for ($x = 1; $x <= 5; $x++) {
					if ($this->EJ->selectedTags['tag' . $x]) {
						$url = ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/cat";
						$tag = $this->EJ->selectedTags['tag' . $x];
						for ($y = 1; $y <= $x; $y++) {
							$url .= "/" . urlencode($this->EJ->selectedTags['tag' . $y]);
						}
						$tstr = str_replace("{BreadCrumb.Url}", $url, $tag_template);
						$tstr = str_replace("{BreadCrumb.Name}", "> " . $tag, $tstr);
						$final_str = $final_str . $tstr;
					}
				}
			}
			$str = substr_replace($str, $final_str, strpos($str, "{BreadCrumb}"), strlen("{BreadCrumb}$tag_template{/BreadCrumb}"));
		}
		return $str;
	}

	function generatePagination($str)
	{
		while (strpos($str, "{Pagination}") !== FALSE) {
			$tag_template = $this->getTemplateString($str, 'Pagination');
			$final_str = "";
			if ($this->EJ->totalPages == 1) {
			} else {
				for ($tmpPage = 1; $tmpPage <= $this->EJ->totalPages; $tmpPage++) {
					$tstr = str_replace("{Pagination.Url}", ($this->page->url == "/" ? "" : $this->page->url) . "/" . $this->page->EJ->shop . "/" . $tmpPage, $tag_template);
					$tstr = str_replace("{Pagination.Number}", $tmpPage, $tstr);
					if ($tmpPage == $this->EJ->currentPage + 1)
						$tstr = str_replace("{Pagination.Active}", "active", $tstr);
					else
						$tstr = str_replace("{Pagination.Active}", "", $tstr);
					$final_str = $final_str . $tstr;
				}
			}
			$str = substr_replace($str, $final_str, strpos($str, "{Pagination}"), strlen("{Pagination}$tag_template{/Pagination}"));
		}
		return $str;
	}

	function generateRelatedProducts($str, $related)
	{
		$related_template = $related;
		if (count($this->EJ->relatedProducts) == 0 || $this->EJ->maxRelated == 0)
			return str_replace("{RelatedProducts}$related{/RelatedProducts}", "", $str);

		$related_product = $this->getTemplateString($related_template, 'RelatedProduct');
		$final_str = "";
		if (count($this->EJ->relatedProducts) < $this->EJ->maxRelated)
			$this->EJ->maxRelated = count($this->EJ->relatedProducts);
		for ($x = 0; $x < $this->EJ->maxRelated; $x++) {
			$tstr = str_replace('{RelatedProduct.Url}', $this->EJ->relatedProducts[$x]->url, $related_product);
			$tstr = str_replace('{RelatedProduct.Number}', $this->EJ->relatedProducts[$x]->number, $tstr);
			$tstr = str_replace('{RelatedProduct.Name}', $this->EJ->relatedProducts[$x]->name, $tstr);
			$tstr = str_replace('{RelatedProduct.Id}', $this->EJ->relatedProducts[$x]->id, $tstr);
			$tstr = str_replace('{RelatedProduct.Number}', $this->EJ->relatedProducts[$x]->number, $tstr);
			$tstr = str_replace('{RelatedProduct.Tagline}', $this->EJ->relatedProducts[$x]->tagline, $tstr);
			if ($this->EJ->relatedProducts[$x]->thumbnail)
				$tstr = str_replace('{RelatedProduct.Thumbnail}', $this->EJ->relatedProducts[$x]->thumbnail, $tstr);
			else
				$tstr = str_replace('{RelatedProduct.Thumbnail}', "https://www.e-junkie.com/ecom/spacer.gif", $tstr);
			$tstr = str_replace('{RelatedProduct.Price}', $this->EJ->relatedProducts[$x]->price, $tstr);
			$tstr = str_replace('{RelatedProduct.Currency}', $this->EJ->relatedProducts[$x]->currency, $tstr);
			$tstr = str_replace('{RelatedProduct.Description}', $this->EJ->relatedProducts[$x]->description, $tstr);
			$tstr = str_replace('{RelatedProduct.Details}', $this->EJ->relatedProducts[$x]->details, $tstr);
			$tstr = str_replace('{RelatedProduct.DownloadLink}', $this->EJ->relatedProducts[$x]->download_link, $tstr);
			$tstr = str_replace('{RelatedProduct.HomepageLink}', $this->EJ->relatedProducts[$x]->homepage_link, $tstr);
			$tstr = str_replace('{RelatedProduct.Purchased}', $this->EJ->relatedProducts[$x]->purchased, $tstr);

			$final_str = $final_str . $tstr;
		}
		$related_template = str_replace("{RelatedProduct}$related_product{/RelatedProduct}", $final_str, $related_template);

		return str_replace("{RelatedProducts}$related{/RelatedProducts}", $related_template, $str);
	}

	function generateAffiliate($str, $affiliate)
	{
		if ($this->EJ->client->affiliate == false || $this->EJ->products[0]->aff_perc == "0.00")
			return str_replace("{IfAffiliate}$affiliate{/IfAffiliate}", "", $str);
		$product = $this->EJ->products[0];
		$tstr = str_replace('{Affiliate.Percentage}', $product->aff_perc, $affiliate);
		$tstr = str_replace('{Affiliate.Url}', $this->EJ->client->affiliate_url, $tstr);
		$tstr = str_replace('{Site.Author}', $this->page->site->author, $tstr);
		return str_replace("{IfAffiliate}$affiliate{/IfAffiliate}", $tstr, $str);
	}

	function generateForm($str, $form, $product)
	{
		$form_template = $form;
		$dropdown_template = $this->getTemplateString($form_template, 'DropDown');
		$textfield_template = $this->getTemplateString($form_template, 'TextField');

		$form_str = "<form action='https://www.fatfreecartpro.com/ecom/gb.php?' method='GET' target='ej_ejc' accept-charset='UTF-8'>";
		$form_str .= "<input type='hidden' name='c' value='cart'><input type='hidden' name='ejc' value='2'>";
		$form_str .= "<input type='hidden' name='cl' value='" . $this->EJ->client->client_id . "'>
					  <input type='hidden' name='i' value='" . $product->number . "'>";

		if ($product->needs_advance_options == "true") {
			if ($product->option1 && count($product->option1_options)) {
				$form_str .= str_replace('{DropDown.Label}', $product->option1, $dropdown_template);
				$form_str = str_replace('{DropDown.Name}', "o1", $form_str);
				$product->option1_options = array_unique($product->option1_options);
				$ostr = "";
				foreach ($product->option1_options as $op)
					$ostr .= "<option value='$op'>$op</option>";
				$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
			}

			if ($product->option2 && count($product->option2_options)) {
				$form_str .= str_replace('{DropDown.Label}', $product->option2, $dropdown_template);
				$form_str = str_replace('{DropDown.Name}', "o2", $form_str);
				$product->option2_options = array_unique($product->option2_options);
				$ostr = "";
				foreach ($product->option2_options as $op)
					$ostr .= "<option value='$op'>$op</option>";
				$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
			}

			if ($product->option3 && count($product->option3_options)) {
				$form_str .= str_replace('{DropDown.Label}', $product->option3, $dropdown_template);
				$form_str .= str_replace('{DropDown.Name}', "o3", $form_str);
				$product->option3_options = array_unique($product->option3_options);
				$ostr = "";
				foreach ($product->option3_options as $op)
					$ostr .= "<option value='$op'>$op</option>";
				$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
			}
		}

		if ($product->needs_options == "true") {
			if ($product->on0) {
				$product->on0 = htmlspecialchars($product->on0, ENT_COMPAT, 'UTF-8');
				$product->on0_options = explode("\n", $product->on0_options);
				$form_str .= '<input type="hidden" name="on0" value="' . $product->on0 . '">';
				if (count($product->on0_options) >= 1 && $product->on0_options[0] != "") {
					$form_str .= str_replace('{DropDown.Label}', $product->on0, $dropdown_template);
					$form_str = str_replace('{DropDown.Name}', "os0", $form_str);
					$ostr = "";
					foreach ($product->on0_options as $on)
						$ostr .= "<option value='$on'>$on</option>";
					$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
				} else {
					$form_str .= str_replace('{TextField.Name}', "os0", $textfield_template);
					$form_str = str_replace('{TextField.PlaceHolder}', $product->on0, $form_str);
				}
			}
			if ($product->on1) {
				$product->on1 = htmlspecialchars($product->on1, ENT_COMPAT, 'UTF-8');
				$product->on1_options = explode("\n", $product->on1_options);
				$form_str .= '<input type="hidden" name="on1" value="' . $product->on1 . '">';
				if (count($product->on1_options) >= 1 && $product->on1_options[0] != "") {
					$form_str .= str_replace('{DropDown.Label}', $product->on1, $dropdown_template);
					$form_str = str_replace('{DropDown.Name}', "os1", $form_str);
					$ostr = "";
					foreach ($product->on1_options as $on)
						$ostr .= "<option value='$on'>$on</option>";
					$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
				} else {
					$form_str .= str_replace('{TextField.Name}', "os1", $textfield_template);
					$form_str = str_replace('{TextField.PlaceHolder}', $product->on1, $form_str);
				}
			}
			if ($product->on2) {
				$product->on2 = htmlspecialchars($product->on2, ENT_COMPAT, 'UTF-8');
				$product->on2_options = explode("\n", $product->on2_options);
				$form_str .= '<input type="hidden" name="on2" value="' . $product->on2 . '">';
				if (count($product->on2_options) >= 1 && $product->on2_options[0] != "") {
					$form_str .= str_replace('{DropDown.Label}', $product->on2, $dropdown_template);
					$form_str = str_replace('{DropDown.Name}', "os2", $form_str);
					$ostr = "";
					foreach ($product->on2_options as $on)
						$ostr .= "<option value='$on'>$on</option>";
					$form_str = str_replace('{DropDown.Options}', $ostr, $form_str);
				} else {
					$form_str .= str_replace('{TextField.Name}', "os2", $textfield_template);
					$form_str = str_replace('{TextField.PlaceHolder}', $product->on2, $form_str);
				}
			}
		}

		if ($product->out_of_stock == true)
			$form_str .= $this->getTemplateString($form_template, 'OutOfStockButton');
		else
			$form_str .= $this->getTemplateString($form_template, 'BuyNowButton');
		$form_str = $form_str . "</form>";
		return str_replace("{Product.Form}$form_template{/Product.Form}", $form_str, $str);
	}

	function generateEJProductImages($str, $product)
	{
		for ($x = 0; $x < 5; $x++) {
			while (strpos($str, "{Product.Image" . ($x + 1) . "}") !== FALSE) {
				$tstr = $this->getTemplateString($str, "Product.Image" . ($x + 1));
				if ($product->images[$x])
					$fstr = str_replace("{Image}", str_replace("images", "mid-images", $product->images[$x]), $tstr);
				else
					$fstr = "";
				$str = str_replace("{Product.Image" . ($x + 1) . "}" . $tstr . "{/Product.Image" . ($x + 1) . "}", $fstr, $str);
			}
			while (strpos($str, "{Product.TinyImage" . ($x + 1) . "}") !== FALSE) {
				$tstr = $this->getTemplateString($str, "Product.TinyImage" . ($x + 1));
				if ($product->images[$x])
					$fstr = str_replace("{Image}", str_replace("images", "tiny-images", $product->images[$x]), $tstr);
				else
					$fstr = "";
				$str = str_replace("{Product.TinyImage" . ($x + 1) . "}" . $tstr . "{/Product.TinyImage" . ($x + 1) . "}", $fstr, $str);
			}
		}
		return $str;
	}

	function generateShop()
	{

		if ($this->EJ->selectedProduct == null) {
			$shop = true; //shop/tags page
		} else {
			$shop = false; //product page
		}

		if ($shop) {
			$template = $this->getTemplate('shop.ej');
		} else {
			$template = $this->getTemplate('product.ej');
		}

		//Set Page Details and Properties
		$Page = new StdClass();
		$Page->author = $this->page->site->author;
		$Page->description = $this->page->site->description;
		$Page->title = $this->page->site->title;
		$Page->keywords = $this->page->site->keywords;
		$Page->logo = $this->page->site->logo;
		$Page->url = $this->page->site->url . $_SERVER['REQUEST_URI'];
		$Page->ogURL = $this->page->site->url . $_SERVER['REQUEST_URI'];

		if ($shop) { //shop or tags
			#$Page->title = ($this->page->site->title == "" ? $this->EJ->client->shop_name : $this->page->site->title)."'s Shop";
			$Page->title = "Shop";
			if ($this->EJ->selectedProductTag)
				$Page->title = $this->EJ->selectedProductTag;
			if ($this->EJ->selectedTags['tag1'])
				$Page->title = $this->EJ->selectedTags['tag1'];
			$str = "Buy ";
			for ($x = 0; $x < 5; $x++) {
				if (!$this->EJ->products[$x])
					break;
				$str = $str . htmlspecialchars($this->EJ->products[$x]->name, ENT_QUOTES, "UTF-8") . ", ";
			}
			$str = substr($str, 0, -2);
			$str = $str . " and more";
			$Page->description = $str;
		} else {
			$Page->title = $this->EJ->products[0]->name;
			$Page->description = $this->EJ->products[0]->description;
			$Page->logo = $this->EJ->products[0]->thumbnail;
			$Page->url = substr($this->EJ->products[0]->url, 1);
			$Page->ogURL = $this->page->site->url . $this->EJ->products[0]->permalink;
		}

		$template = $this->importIncludes($template);

		if ($shop) {
			$template = $this->cleanTemplate($template, 'IfProduct');
			$template = str_replace('{IfShop}', "", $template);
			$template = str_replace('{/IfShop}', "", $template);
			$products = $this->getTemplateString($template, 'Products');
			$has_form = $this->getTemplateString($products, 'Product.Form');
			$final_str = "";
			foreach ($this->EJ->products as $product) {
				$tstr = str_replace('{Product.Url}', $product->url, $products);
				$tstr = str_replace("{Product.OutOfStock_Bool}", ($product->out_of_stock ? "true" : "false"), $tstr);
				$tstr = str_replace('{Product.Number}', $product->number, $tstr);
				$tstr = str_replace('{Product.Name}', $product->name, $tstr);
				$tstr = str_replace('{Product.Id}', $product->id, $tstr);
				$tstr = str_replace('{Product.Number}', $product->number, $tstr);
				$tstr = str_replace('{Product.Tagline}', $product->tagline, $tstr);
				if ($product->thumbnail)
					$tstr = str_replace('{Product.Thumbnail}', $product->thumbnail, $tstr);
				else
					$tstr = str_replace('{Product.Thumbnail}', "https://www.e-junkie.com/ecom/spacer.gif", $tstr);
				$tstr = $this->generateEJProductImages($tstr, $product);
				$tstr = str_replace('{Product.Price}', $product->price, $tstr);
				$tstr = str_replace('{Product.Currency}', $product->currency, $tstr);
				$tstr = str_replace('{Product.Description}', $product->description, $tstr);
				if (strpos($tstr, '{Product.DescriptionShort/') !== FALSE) {
					$pos = intval(strpos($tstr, '{Product.DescriptionShort/')) + strlen('{Product.DescriptionShort/');
					$length = substr($tstr, strpos($tstr, '{Product.DescriptionShort/'), $pos - strpos($tstr, '{Product.DescriptionShort/') + 4);
					$length = intval(str_replace("{Product.DescriptionShort/", "", $length));
					$shortDesc = (strlen($product->description) <= $length ? $product->description : substr($product->description, 0, $length) . "...");
					$tstr = str_replace("{Product.DescriptionShort/$length}", $shortDesc, $tstr);
				}
				$tstr = str_replace('{Product.Details}', $product->details, $tstr);
				$tstr = str_replace('{Product.DownloadLink}', $product->download_link, $tstr);
				$tstr = str_replace('{Product.HomepageLink}', $product->homepage_link, $tstr);
				$tstr = str_replace('{Product.Purchased}', $product->purchased, $tstr);
				if ($has_form)
					$tstr = $this->generateForm($tstr, $has_form, $product);
				$final_str = $final_str . $tstr;
			}
			$template = str_replace("{Products}$products{/Products}", $final_str, $template);

			if (strpos($template, "{Pagination}") !== FALSE) {
				$template = $this->generatePagination($template);
			}

		} else {
			$template = $this->cleanTemplate($template, 'IfShop');
			$template = str_replace('{IfProduct}', "", $template);
			$template = str_replace('{/IfProduct}', "", $template);
			$has_form = $this->getTemplateString($template, 'Product.Form');
			$has_related = $this->getTemplateString($template, 'RelatedProducts');
			$has_affiliate = $this->getTemplateString($template, 'IfAffiliate');
			if ($has_related)
				$template = $this->generateRelatedProducts($template, $has_related);

			if ($has_affiliate)
				$template = $this->generateAffiliate($template, $has_affiliate);

			$product = $this->EJ->products[0]; //as there is only one item when product page is called

			$template = str_replace('{Product.Url}', $this->EJ->client->shop_url . substr($product->url, 1), $template);
			$encodeURL = explode('/', substr($product->url, 1));
			$encodeURL = $encodeURL[0] . "/" . $encodeURL[1];
			$encodeURL = urlencode($this->EJ->client->shop_url . $encodeURL);
			$template = str_replace('{Product.Url.Encoded}', $encodeURL, $template);
			$template = str_replace('{Product.Number}', $product->number, $template);
			$template = str_replace('{Product.Name}', $product->name, $template);
			$template = str_replace('{Product.Name.Encoded}', urlencode($product->name), $template);
			$template = str_replace('{Product.Id}', $product->id, $template);
			$template = str_replace('{Product.Number}', $product->number, $template);
			$template = str_replace('{Product.Tagline}', $product->tagline, $template);
			if ($product->thumbnail)
				$template = str_replace('{Product.Thumbnail}', $product->thumbnail, $template);
			else
				$template = str_replace('{Product.Thumbnail}', "https://www.e-junkie.com/ecom/spacer.gif", $template);
			$template = $this->generateEJProductImages($template, $product);
			$template = str_replace('{Product.Price}', $product->price, $template);
			$template = str_replace('{Product.Currency}', $product->currency, $template);
			$template = str_replace('{Product.Description}', $product->description, $template);
			$template = str_replace('{Product.Details}', $product->details, $template);
			$template = str_replace('{Product.DownloadLink}', $product->download_link, $template);
			$template = str_replace('{Product.HomepageLink}', $product->homepage_link, $template);
			$template = str_replace('{Product.Purchased}', $product->purchased, $template);

			if ($has_form)
				$template = $this->generateForm($template, $has_form, $product);

			if (strpos($template, "{Product.Tags}") !== FALSE)
				$template = $this->generateProductTags($template);
		}

		if (strpos($template, "{Tags}") !== FALSE)
			$template = $this->generateTags($template);

		if (strpos($template, "{BreadCrumb}") !== FALSE)
			$template = $this->generateBreadCrumb($template);

		while (strpos($template, "{EJAllProducts}") !== FALSE) {
			$EJ = new EJParser($this->page->EJ->clientId, null, null, $this->page, $this->page->EJ->apiKey);
			$EJ->currentPage = 0;
			$EJ->size = 1000;
			$EJ->getProducts();
			$template = str_replace("{EJAllProducts}", json_encode($EJ->products), $template);
			unset($EJ);
		}

		$template = $this->replaceLiterals($template, $Page);
		if (strpos($template, "{JSONLD}") !== FALSE)
			$template = str_replace("{JSONLD}", $this->generateJSONLD($Page, ($shop ? "shop" : "product")), $template);
		$template = $this->executePHPCodes($template);
		echo $template;
		return true;
	}
}
?>