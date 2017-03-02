<?php

class Escher_IndexController extends Omeka_Controller_AbstractActionController
{

    protected $_webplugins = array();

    public function indexAction()
    {
        $csrf = new Omeka_Form_SessionCsrf;

        $this->view->csrf = $csrf;
        $this->view->status = '';
        $this->view->message = '';

        $types = array(
            'plugin',
            'theme',
            'webplugin',
        );

        $result = false;
        foreach ($types as $type) {
            $addons = $this->_listAddons($type);
            $addonsName = Inflector::pluralize($type);
            // TODO Check if installed.
            $this->view->$addonsName = $addons;
            $result = $result || !empty($addons);
        }

        if (empty($result)) {
            $this->_helper->_flashMessenger(__('Unable to fetch addons.'), 'error');
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

        foreach ($types as $type) {
            $url = $this->getParam($type);
            if ($url) {
                $result = $this->_installAddon($url, $type);
                break;
            }
        }
    }

    /**
     * Helper to list the addons from a web page.
     *
     * @param string $type
     * @return array
     */
    protected function _listAddons($type)
    {
        switch ($type) {
            case 'plugin':
                $source = 'https://omeka.org/add-ons/plugins/';
                break;
            case 'theme':
                $source = 'https://omeka.org/add-ons/themes/';
                break;
            case 'webplugin':
                $source = 'https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/master/docs/_data/omeka_plugins.csv';
                break;
            default:
                return array();
        }

        $content = file_get_contents($source);
        if (empty($content)) {
            return array();
        }

        switch ($type) {
            case 'plugin':
            case 'theme':
                return $this->_listFromOmeka($content);
            case 'webplugin':
                return $this->_listFromWeb($content);
        }
    }

    /**
     * Helper to parse a page from omeka.org to get urls and names of addons.
     *
     * @param string $html
     * @return array
     */
    protected function _listFromOmeka($html)
    {
        $addons = array();

        libxml_use_internal_errors(true);
        $pokemon_doc = new DOMDocument();
        $pokemon_doc->loadHTML($html);
        $pokemon_xpath = new DOMXPath($pokemon_doc);
        $pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');
        if ($pokemon_row->length > 0) {
            foreach ($pokemon_row as $row) {
                $url = $row->nodeValue;
                $filename = basename(parse_url($url, PHP_URL_PATH));
                list($name, $version) = $this->_extractNameAndVersion($filename);
                if (empty($name)) {
                    continue;
                }
                $name = str_replace('-', ' ', $name);
                $addons[$url] = $name . ' [v' . $version . ']';
            }
        }

        return $addons;
    }

    /**
     * Helper to parse a csv file to get urls and names of addons.
     *
     * @param string $csv
     * @return array
     */
    protected function _listFromWeb($csv)
    {
        $addons = array();

        $this->_webplugins = array_map('str_getcsv', explode("\n", $csv));
        $list = &$this->_webplugins;
        $headers = array_flip($list[0]);

        foreach ($list as $key => $row) {
            if ($key == 0 || empty($row) || !isset($row[$headers['Url']])) {
                continue;
            }
            $url = $row[$headers['Url']];
            $name = $row[$headers['Name']];
            $version = $row[$headers['Last']];

            $server = strtolower(parse_url($url, PHP_URL_HOST));
            switch ($server) {
                case 'github.com':
                    $url .= '/archive/master.zip';
                    break;
                case 'gitlab.com':
                    $url .= '/repository/archive.zip';
                    break;
                default:
                    $url .= '/master.zip';
                    break;
            }

            $addons[$url] = $name . ' [v' . $version . ']';
        }

        return $addons;
    }

    /**
     * Helper to extract the name and the version from the name of a zip file.
     *
     * @param string $filename
     * @return array
     */
    protected function _extractNameAndVersion($filename)
    {
        // Some plugins have "-" in name; some have letters in version.
        $result = preg_match('~([^\d]+)\-(\d.*)\.zip~', $filename, $matches);
        // Manage for example "Select2".
        if (empty($matches)) {
            $result = preg_match('~(.*?)\-(\d.*)\.zip~', $filename, $matches);
        }
        if (empty($matches)) {
            return array(null, null);
        }
        $name = $matches[1];
        $version = $matches[2];
        return array($name, $version);
    }

    /**
     * Helper to extract the true addon name from the list.
     *
     * @param string $url
     * @return array
     */
    protected function _extractAddonName($url)
    {
        $addonName = '';

        $filepath = parse_url($url, PHP_URL_PATH);
        $server = strtolower(parse_url($url, PHP_URL_HOST));
        switch ($server) {
            case 'github.com':
            case 'gitlab.com':
                $pluginUrl = dirname(dirname($url));
                break;
            default:
                $pluginUrl = dirname($url);
                break;
        }

        $list = &$this->_webplugins;
        $headers = array_flip($list[0]);

        $name = '';
        foreach ($list as $key => $row) {
            if ($key == 0 || empty($row) || !isset($row[$headers['Url']])) {
                continue;
            }
            if ($pluginUrl == $row[$headers['Url']]) {
                $name = $row[$headers['Name']];
                break;
            }
        }

        if ($name) {
            $addonName = Inflector::camelize($name);
        }
        return $addonName;
    }

    /**
     * Helper to install an addon.
     *
     * @param string $source
     * @param string $type
     * @return boolean
     */
    protected function _installAddon($source, $type)
    {
        $from = $type;

        switch ($type) {
            case 'webplugin':
                $type = 'plugin';
                // No break.
            case 'plugin':
                $destination = PLUGIN_DIR;
                break;
            case 'theme':
                $destination = PUBLIC_THEME_DIR;
                break;
            default:
                return false;
        }

        $isWriteableDestination = is_writeable($destination);
        if (!$isWriteableDestination) {
            $this->_helper->_flashMessenger(__('The %s directory is not writeable by the server.', $type), 'error');
            return;
        }
        // Add a message for security hole.
        $this->_helper->_flashMessenger(__('Don’t forget to protect the %s directory from writing after installation.', $type), 'info');

            // Get the local zip file path.
        $filename = basename(parse_url($source, PHP_URL_PATH));
        $zipFile = $destination . DIRECTORY_SEPARATOR . $filename;

        // Local zip file path.
        if (file_exists($zipFile)) {
            $result = @unlink($zipFile);
            if (!$result) {
                $this->view->status = 'error';
                $this->view->message = __('A zipfile exists with the same name in the %s directory and cannot be removed.', $type);
                return false;
            }
        }

        // Check if the name of the plugins exists in the list.
        if ($from == 'webplugin') {
            $addonName = $this->_extractAddonName($source);
        }
        // Else, get plugin directory name without version number.
        else {
            list($name, $version) = $this->_extractNameAndVersion($filename);
            $addonName = Inflector::camelize($name);
        }

        if (empty($addonName)) {
            $this->view->status = 'error';
            $this->view->message = __('The name of the %s cannot be determined.', $type);
            return false;
        }

        if (file_exists($destination . DIRECTORY_SEPARATOR . $addonName)) {
            $this->view->status = 'error';
            $this->view->message = __('The addon directory already exists.');
            return false;
        }

        // Get the zip file from server.
        $result = $this->_downloadFile($source, $zipFile);
        if (!$result) {
            $this->view->status = 'error';
            $this->view->message = __('Unable to fetch the %s "%s".', $type, $addonName);
            return false;
        }

        // Unzip downloaded file.
        $result = $this->_unzipFile($zipFile, $destination);

        unlink($zipFile);

        if ($result) {
            $msg = __('If "%s" doesn’t appear in the list of %s, its directory may need to be renamed.',
                Inflector::humanize(Inflector::underscore($addonName), 'all'), Inflector::pluralize($type));
            if ($from == 'webplugin') {
                $result = $this->_moveAddon($addonName, $type);
                if ($result) {
                    $this->_helper->_flashMessenger($msg, 'info');
                } else {
                    $this->_helper->_flashMessenger($msg, 'error');
                }
            }
            // Warn in all other cases.
            else {
                $this->_helper->_flashMessenger($msg, 'info');
            }

            $this->view->status = 'success';
            $this->view->message = __('%s uploaded successfully', ucfirst($type));
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

    /**
     * Helper to rename the directory of an addon.
     *
     * @internal The name of the directory is unknown, because it is a subfolder
     * inside the zip file.
     *
     * @param string $addonName
     * @return boolean
     */
    protected function _moveAddon($addonName, $type)
    {
        switch ($type) {
            case 'webplugin':
            case 'plugin':
                $destination = PLUGIN_DIR;
                break;
            case 'theme':
                $destination = PUBLIC_THEME_DIR;
                break;
            default:
                return false;
        }

        $name = '';
        foreach (array(
                // Manage only the most common cases.
                array('', ''),
                array('', '-master'),
                array('', '-plugin-master'),
                array('', '-theme-master'),
                array('', '4Omeka-master'),
                array('omeka-', '-master'),
                array('plugin-', '-master'),
                array('omeka-plugin-', '-master'),
                array('theme-', '-master'),
                array('omeka-theme-', '-master'),
                array('omeka_', '-master'),
                array('omeka_plugin_', '-master'),
                array('omeka_theme_', '-master'),
                array('omeka_Plugin_', '-master'),
                array('omeka_Theme_', '-master'),
            ) as $array) {
            $checkName = $destination . DIRECTORY_SEPARATOR
                . $array[0] . $addonName . $array[1];
            if (file_exists($checkName)) {
                $name = $checkName;
                break;
            }
            $checkName = $destination . DIRECTORY_SEPARATOR
                . $array[0] . ucfirst(strtolower($addonName)) . $array[1];
            if (file_exists($checkName)) {
                $name = $checkName;
                $addonName = ucfirst(strtolower($addonName));
                break;
            }
            if ($array[0]) {
                $checkName = $destination . DIRECTORY_SEPARATOR
                    . ucfirst($array[0]) . $addonName . $array[1];
                if (file_exists($checkName)) {
                    $name = $checkName;
                    break;
                }
                $checkName = $destination . DIRECTORY_SEPARATOR
                    . ucfirst($array[0]) . ucfirst(strtolower($addonName)) . $array[1];
                if (file_exists($checkName)) {
                    $name = $checkName;
                    $addonName = ucfirst(strtolower($addonName));
                    break;
                }
            }
        }

        if (empty($name)) {
            return false;
        }

        $path = $destination . DIRECTORY_SEPARATOR . $addonName;
        return rename($name, $path);
    }
}
