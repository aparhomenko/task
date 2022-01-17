<?
	if(!\Bitrix\Main\Loader::includeModule("iblock"))
	{
		echo "failure";
		return;
	}
	class RiaRSS extends CAllIBlockRSS
	{
		private $iblock_id;
		public function __construct($a)
		{
			$this->iblock_id = $a;	
		}
		/* загрузка массива из xml в инфоблок*/
		public function LoadNews()
		{			
			$res = self::GetNewsEx("https://ria.ru", "/export/rss2/archive/index.xml", "");
			$news = self::FormatArray($res);
			if ($news)
			{
				self::ClearNews();
				foreach($news["item"] as $newsItem)
				{					
					$pubDate = new DateTime($newsItem["pubDate"]);
					$curDate = new DateTime();
					$curDate->setTimezone($pubDate->getTimezone());
					$diff = date_diff($pubDate, $curDate);
					
					if ($diff->h<=4)
					{
						$el = new CIBlockElement;
						$PROP = array();
						$PROP[9] = $newsItem["link"];  
						$PROP[10] = $newsItem["category"];       
						$date = ConvertTimeStamp($pubDate->getTimestamp() , "FULL");
						$arLoadProductArray = Array(
						"IBLOCK_ID"      =>$this->iblock_id,
						"PROPERTY_VALUES"=> $PROP,
						"NAME"           => $newsItem["title"],
						"ACTIVE"         => "Y",
						"PREVIEW_TEXT"   => $newsItem["description"],
						"DATE_ACTIVE_FROM"   => $date,					
						"DATE_CREATE"   => $date,
						"PREVIEW_PICTURE" => CFile::MakeFileArray($newsItem["enclosure"]["url"])
						
						);
						if(!$PRODUCT_ID = $el->Add($arLoadProductArray))
						echo "Error: ".$el->LAST_ERROR."<br>";
					}
				}
			}
			echo("Загрузка выполнена");
		}
		/* удаление старых новостей из инфоблока*/
		private function ClearNews()
		{
			$result = CIBlockElement::GetList(array("ID"=>"ASC"),array('IBLOCK_ID'=>$this->iblock_id));
			while($element = $result->Fetch())
			CIBlockElement::Delete($element['ID']);			
		}
		/* переопределение метода родителя (удалены параметры порт и канал) */
		public static function GetNewsEx($SITE, $PATH, $QUERY_STR)
		{
			global $APPLICATION;
			$text = "";
			
			$http = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => 120,
			));
			$http->setHeader("User-Agent", "BitrixSMRSS");
			$text = $http->get($SITE.":".$PATH.($QUERY_STR <> ''? "?".$QUERY_STR: ""));
			if ($text)
			{
				$rss_charset = "windows-1251";
				if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $text, $matches))
				{
					$rss_charset = Trim($matches[1]);
				}
				else
				{
					$headers = $http->getHeaders();
					$ct = $headers->get("Content-Type");
					if (preg_match("#charset=([a-zA-Z0-9-]+)#m", $ct, $match))
					$rss_charset = $match[1];
				}
				$text = preg_replace("/<!DOCTYPE.*?>/i", "", $text);
				$text = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $text);
				$text = $APPLICATION->ConvertCharset($text, $rss_charset, SITE_CHARSET);
			}
			
			if ($text != "")
			{
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");
				$objXML = new CDataXML();
				$res = $objXML->LoadString($text);
				if($res !== false)
				{
					$ar = $objXML->GetArray();
					if (
					is_array($ar) && isset($ar["rss"])
					&& is_array($ar["rss"]) && isset($ar["rss"]["#"])
					&& is_array($ar["rss"]["#"]) && isset($ar["rss"]["#"]["channel"])
					&& is_array($ar["rss"]["#"]["channel"]) && isset($ar["rss"]["#"]["channel"][0])
					&& is_array($ar["rss"]["#"]["channel"][0]) && isset($ar["rss"]["#"]["channel"][0]["#"])
					)
					$arRes = $ar["rss"]["#"]["channel"][0]["#"];
					else
					$arRes = array();
					$arRes["rss_charset"] = mb_strtolower(SITE_CHARSET);
					
				}
				return $arRes;
			}
			else
			{
				return array();
			}
		}
	}	
									