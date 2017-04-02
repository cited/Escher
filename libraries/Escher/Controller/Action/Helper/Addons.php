<?php
/**
 * @see Zend_Session
 */
require_once 'Zend/Session.php';

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * List addons for Omeka.
 *
 * @uses Zend_Controller_Action_Helper_Abstract
 */
class Escher_Controller_Action_Helper_Addons extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * Source of data and destination of addons.
     *
     * @var array
     */
    protected $data = array(
        'omekaplugin' => array(
            'source' => 'https://omeka.org/add-ons/plugins/',
            'destination' => PLUGIN_DIR,
        ),
        'omekatheme' => array(
            'source' => 'https://omeka.org/add-ons/themes/',
            'destination' => PUBLIC_THEME_DIR,
        ),
        'plugin' => array(
            'source' => 'https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/master/docs/_data/omeka_plugins.csv',
            'destination' => PLUGIN_DIR,
        ),
        'theme' => array(
            'source' => 'https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/master/docs/_data/omeka_themes.csv',
            'destination' => PUBLIC_THEME_DIR,
        ),
    );

    /**
     * Expiration seconds.
     *
     * @var integer
     */
    protected $expirationSeconds = 86400;

    /**
     * Expiration hops.
     *
     * @var integer
     */
    protected $expirationHops = 50;

    /**
     * Cache for the list of addons.
     *
     * @var string
     */
    protected $_addons;

    /**
     * Return this.
     *
     * @return string
     */
    public function addons()
    {
        return $this;
    }

    /**
     * Return the addon list.
     *
     * @return string
     */
    public function addonList()
    {
        // Build the list of addons only once.
        if (!$this->isEmpty()) {
            return $this->_addons;
        }

        // Check the cache.
        $session = new Zend_Session_Namespace('Escher');
        if (isset($session->addons)) {
            $this->_addons = json_decode(gzuncompress($session->addons), true);
            if (!$this->isEmpty()) {
                return $this->_addons;
            }
        }

        $addons = array();
        foreach ($this->types() as $addonType) {
            $addons[$addonType] = $this->listAddonsForType($addonType);
        }

        $this->_addons = $addons;
        $this->cacheAddons();
        return $this->_addons;
    }

    /**
     * Helper to save addons in the cache.
     *
     * @return void
     */
    protected function cacheAddons()
    {
        $session = new Zend_Session_Namespace('Escher');
        $session->setExpirationSeconds($this->expirationSeconds);
        $session->setExpirationHops($this->expirationHops);
        // The max size of a data is 64 KB, so data are json encoded and zipped.
        $session->addons = gzcompress(json_encode($this->_addons), 1);
    }

    /**
     * Check if the lists of addons are empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if (empty($this->_addons)) {
            return true;
        }
        foreach ($this->_addons as $addons) {
            if (!empty($addons)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the list of default types.
     *
     * @return array
     */
    public function types()
    {
        return array_keys($this->data);
    }

    /**
     * Get addon data.
     *
     * @param string $url
     * @param string $type
     * @return array
     */
    public function dataForUrl($url, $type)
    {
        return $this->_addons && isset($this->_addons[$type][$url])
            ? $this->_addons[$type][$url]
            : array();
    }

    /**
     * Check if an addon is installed.
     *
     * @param array $addon
     * @return boolean
     */
    public function dirExists($addon)
    {
        $destination = $this->data[$addon['type']]['destination'];
        $existings = $this->listDirsInDir($destination);
        $existings = array_map('strtolower', $existings);
        return in_array(strtolower($addon['dir']), $existings)
            || in_array(strtolower($addon['basename']), $existings);
    }

    /**
     * Helper to list the addons from a web page.
     *
     * @param string $type
     * @return array
     */
    protected function listAddonsForType($type)
    {
        if (!isset($this->data[$type]['source'])) {
            return array();
        }
        $source = $this->data[$type]['source'];

        $content = @file_get_contents($source);
        if (empty($content)) {
            return array();
        }

        switch ($type) {
            case 'plugin':
            case 'theme':
                return $this->extractAddonList($content, $type);
            case 'omekaplugin':
            case 'omekatheme':
                return $this->extractAddonListFromOmeka($content, $type);
        }
    }

    /**
     * Helper to parse a csv file to get urls and names of addons.
     *
     * @param string $csv
     * @param string $type
     * @return array
     */
    protected function extractAddonList($csv, $type)
    {
        $list = array();

        $addons = array_map('str_getcsv', explode(PHP_EOL, $csv));
        $headers = array_flip($addons[0]);

        foreach ($addons as $key => $row) {
            if ($key == 0 || empty($row) || !isset($row[$headers['Url']])) {
                continue;
            }

            $url = $row[$headers['Url']];
            $name = $row[$headers['Name']];
            $version = $row[$headers['Last Version']];
            $addonName = preg_replace('~[^A-Za-z0-9]~', '', $name);
            $server = strtolower(parse_url($url, PHP_URL_HOST));
            switch ($server) {
                case 'github.com':
                    $zip = $url . '/archive/master.zip';
                    break;
                case 'gitlab.com':
                    $zip = $url .'/repository/archive.zip';
                    break;
                default:
                    $zip = $url .'/master.zip';
                    break;
            }

            $addon = array();
            $addon['type'] = $type;
            $addon['name'] = $name;
            $addon['basename'] = basename($url);
            $addon['dir'] = $addonName;
            $addon['version'] = $version;
            $addon['zip'] = $zip;
            $addon['server'] = $server;

            $list[$url] = $addon;
        }

        return $list;
    }

    /**
     * Helper to parse html to get urls and names of addons.
     *
     * @param string $html
     * @param string $type
     * @return array
     */
    protected function extractAddonListFromOmeka($html, $type)
    {
        $list = array();

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

                $addonName = preg_replace('~[^A-Za-z0-9]~', '', $name);
                $server = strtolower(parse_url($url, PHP_URL_HOST));
                $zip = $url;

                $addon = array();
                $addon['type'] = $type;
                $addon['name'] = str_replace(array('-', '_'), ' ', $name);
                $addon['basename'] = $addonName;
                $addon['dir'] = $addonName;
                $addon['version'] = $version;
                $addon['zip'] = $zip;
                $addon['server'] = $server;

                $list[$url] = $addon;
            }
        }

        return $list;
    }

    /**
     * Helper to extract the name and the version from the name of a zip file.
     *
     * @param string $filename
     * @return array
     */
    protected function _extractNameAndVersion($filename)
    {
        // Some addons have "-" in name; some have letters in version.
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
     * List directories in a directory, not recursively.
     *
     * @param string $dir
     * @return array
     */
    protected function listDirsInDir($dir)
    {
        static $dirs;

        if (isset($dirs[$dir])) {
            return $dirs[$dir];
        }

        if (empty($dir) || !file_exists($dir) || !is_dir($dir) || !is_readable($dir)) {
            return array();
        }

        $list = array_filter(array_diff(scandir($dir), array('.', '..')), function($file) use ($dir) {
            return is_dir($dir . DIRECTORY_SEPARATOR . $file);
        });

        $dirs[$dir] = $list;
        return $dirs[$dir];
    }
}
