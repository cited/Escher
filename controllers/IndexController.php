<?php

class Escher_IndexController extends Omeka_Controller_AbstractActionController
{
 	
    public function init(){
	}
	
	public function indexAction(){ 
		//phpinfo();
		
		$html = file_get_contents("http://omeka.org/add-ons/plugins/");
		//echo $html;
		$pokemon_doc = new DOMDocument();
	$pokemon_doc->loadHTML($html);
	
$pokemon_xpath = new DOMXPath($pokemon_doc);

$pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');

$pluginName = array();

if($pokemon_row->length > 0){
  foreach($pokemon_row as $row){
      $n = basename($row->nodeValue);
	  $url = $row->nodeValue;
	  $n = explode('.zip', $n);
	  
	  $n = preg_match("/(.*)-[0-9\.]*/", $n[0], $m);
	  
	  $pluginName[$m[1]] = $url;
  }
}

    	$this->view->plugins = $pluginName;			
    }
	
	public function uploadAction(){ 
		
		$pluginName = $_POST['plugin-name'];
		$pluginDir = __DIR__ . '/../';
		
		
		$url = $pluginName;
$zipFile = $pluginDir . "e.zip"; // Local Zip File Path
$zipResource = fopen($zipFile, "w");
// Get The Zip File From Server
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_FILE, $zipResource);
$page = curl_exec($ch);
if(!$page) {
 echo "Error :- ".curl_error($ch);
}
curl_close($ch);

$zip = new ZipArchive;
$res = $zip->open($pluginDir . 'e.zip');
if ($res === TRUE) {
	
    $zip->extractTo($pluginDir . '../');
    $zip->close();
    	
	header("location:" . url("escher?s=1"));

} else {    	
	echo "something is wrong.";
}
	
	    exit;
    }
}