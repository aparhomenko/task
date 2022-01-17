<?
	
	require($_SERVER["DOCUMENT_ROOT"]."/local/scripts/rss.php");
	function importNews()
	{
		$ob = new RiaRSS(1);
		$res = $ob->LoadNews();
	}		
