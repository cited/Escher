<?php

class Escher_IndexController extends Omeka_Controller_AbstractActionController
{

    public function indexAction()
    {
        $csrf = new Omeka_Form_SessionCsrf;

        $this->view->csrf = $csrf;
        $this->view->status = '';
        $this->view->message = '';

        $plugins = $this->_listAddons();
        $this->view->plugins = $plugins;

        if (!is_writeable(PLUGIN_DIR)) {
            $this->_helper->_flashMessenger(__('The plugin directory is not writeable by the server.'), 'error');
            return;
        }

        // Add a message for security hole.
        $this->_helper->_flashMessenger(__('The plugin directory is writeable by the server: don’t forget to protect it after installation.'), 'info');

        if (empty($plugins)) {
            $this->_helper->_flashMessenger(__('Unable to fetch addons from Omeka.org.'), 'error');
            return;
        }

        // Handle a submitted edit form.
        if (!$this->getRequest()->isPost()) {
            return;
        }

        if (!$csrf->isValid($_POST)) {
            $this->_helper->_flashMessenger(__('There was an error on the form. Please try again.'), 'error');
            return;
        }

        $url = $this->getParam('addon');
        if (!empty($url)) {
            $this->_installAddon($url, PLUGIN_DIR);
        }
    }

    protected function _listAddons()
    {
        $addons = array();

        $source = 'https://omeka.org/add-ons/plugins/';

        $html = file_get_contents($source);
        if (empty($html)) {
            return array();
        }

        libxml_use_internal_errors(true);
        $pokemon_doc = new DOMDocument();
        $pokemon_doc->loadHTML($html);
        $pokemon_xpath = new DOMXPath($pokemon_doc);
        $pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');
        if ($pokemon_row->length > 0) {
            foreach ($pokemon_row as $row) {
                $url = $row->nodeValue;
                $filename = basename(parse_url($url, PHP_URL_PATH));
                $result = preg_match("/(.*)-([0-9\.]*)\.zip/", $filename, $matches);
                $name = str_replace('-', ' ', $matches[1]);
                $version = $matches[2];
                $addons[$url] = $name . ' [v' . $version . ']';
            }
        }

        return $addons;
    }

    /**
     * Helper to install an addon.
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    protected function _installAddon($source, $destination)
    {
        // Get the local zip file path.
        $filename = basename(parse_url($source, PHP_URL_PATH));
        $zipFile = $destination . DIRECTORY_SEPARATOR . $filename;

        // Local zip file path.
        if (file_exists($zipFile)) {
            $result = @unlink($zipFile);
            if (!$result) {
                $this->view->status = 'error';
                $this->view->message = __('A zipfile exists with the same name in the addon directory and cannot be removed.');
                return false;
            }
        }

        // Get plugin directory name without version number.
        $result = preg_match("/(.*)-([0-9\.]*)\.zip/", $filename, $matches);
        $directory = Inflector::camelize($matches[1]);
        if (file_exists($destination . DIRECTORY_SEPARATOR . $directory)) {
            $this->view->status = 'error';
            $this->view->message = __('The addon directory already exists.');
            return false;
        }

        // Get the zip file from server.
        $result = $this->_downloadFile($source, $zipFile);
        if (!$result) {
            $this->view->status = 'error';
            $this->view->message = __('Unable to fetch the addon.');
            return false;
        }

        // Unzip downloaded file.
        $result = $this->_unzipFile($zipFile, $destination);

        unlink($zipFile);

        if ($result) {
            $this->view->status = 'success';
            $this->view->message = __('Plugin uploaded successfully');
            $this->_helper->_flashMessenger(__('If "%s" doesn’t appear in the list of addons, its directory may need to be renamed.', $directory), 'info');
        }
        else {
            $this->view->status = 'error';
            $this->view->message = __('An error occurred during the process.');
        }

        return $result;
    }

    /**
     * Helper to download a file.
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    protected function _downloadFile($source, $destination)
    {
        $handle = fopen($source, 'rb');
        $result = (boolean) file_put_contents($destination, $handle);
        @fclose($handle);
        return $result;
    }

    /**
     * Helper to unzip a file.
     *
     * @param string $source A local file.
     * @param string $destination A writeable dir.
     * @return boolean
     */
    protected function _unzipFile($source, $destination)
    {
        // Unzip via php-zip.
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            $result = $zip->open($source);
            if ($result === true) {
                $result = $zip->extractTo($destination);
                $zip->close();
            }
        }

        // Unzip via command line
        else {
            // Check if the zip command exists.
            Omeka_File_Derivative_Strategy_ExternalImageMagick::executeCommand('unzip', $status, $output, $errors);
            // A return value of 0 indicates the convert binary is working correctly.
            $result = $status != 0;
            if ($result) {
                $command = 'unzip ' . escapeshellarg($source) . ' -d ' . escapeshellarg($destination);
                Omeka_File_Derivative_Strategy_ExternalImageMagick::executeCommand($command, $status, $output, $errors);
                $result = $status == 0;
            }
        }

        return $result;
    }
}
