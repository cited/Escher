<?php

class Escher_IndexController extends Omeka_Controller_AbstractActionController {

    public function init() {
        
    }

    public function indexAction() {
        //phpinfo();

        $html = file_get_contents("http://omeka.org/add-ons/plugins/");
        //echo $html;
        $pokemon_doc = new DOMDocument();
        $pokemon_doc->loadHTML($html);

        $pokemon_xpath = new DOMXPath($pokemon_doc);

        $pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');

        $pluginName = array();

        if ($pokemon_row->length > 0) {
            foreach ($pokemon_row as $row) {
                $n = basename($row->nodeValue);
                $url = $row->nodeValue;
                $n = explode('.zip', $n);

                $n = preg_match("/(.*)-[0-9\.]*/", $n[0], $m);

                $pluginName[$m[1]] = $url;
            }
        }

        $this->view->plugins = $pluginName;
    }

    public function uploadAction() {

        $pluginName = $_POST['plugin-name'];
        $pluginDir = __DIR__ . '/../';

        $url = $pluginName;
        $filename = basename($pluginName);
        $zipFile = $pluginDir . $filename; // Local Zip File Path
        //get plugin directory name without version number
        $pName = explode('.zip', $filename);
        preg_match("/(.*)-[0-9\.]*/", $pName[0], $m);
        
        $pName = str_replace('-', '', $m[1]);
        if (file_exists($pluginDir . '../' . $pName)){
            header("location:" . url("escher?error=1&msg=Plugin Directrory already exists.")); exit;
        }

        // create empty zip file.
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE);
        $zip->close();


        $zipResource = fopen($zipFile, "w");

        // Get The Zip File From Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, $zipResource);
        $page = curl_exec($ch);

        if (!$page) {
            echo "Error :- " . curl_error($ch);
        }

        curl_close($ch);

        $zip = new ZipArchive;
        $res = $zip->open($zipFile);

        if ($res === TRUE) {

            $zip->extractTo($pluginDir . '../');
            $zip->close();

            unlink($zipFile);
            
            
            header("location:" . url("escher?success=1&msg=Plugin uploaded successfully."));
            
        } else {

            unlink($zipFile);
            echo "something is wrong.";
        }

        exit;
    }

}
