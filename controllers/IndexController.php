<?php

class Escher_IndexController extends Omeka_Controller_AbstractActionController
{

    protected $_autoCsrfProtection = true;

    public function indexAction()
    {
        $form = new Escher_Form_Upload();
        $this->view->form = $form;

        $addons = $form->getAddons();
        if ($addons->isEmpty()) {
            $this->_helper->_flashMessenger(__(
                'No addon to list: check your connection.'), 'error');
            return;
        }

        $request = $this->getRequest();

        if (!$request->isPost()) {
            return;
        }

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $this->_helper->_flashMessenger(__(
                'There was an error on the form. Please try again.'), 'error');
            return;
        }

        foreach ($addons->types() as $type) {
            $url = $this->getParam($type);
            if ($url) {
                $addon = $addons->dataForUrl($url, $type);
                if ($addons->dirExists($addon)) {
                    // Hack to get a clean message.
                    $type = str_replace('omeka', '', $type);
                    $this->_helper->_flashMessenger(__(
                        'The %s "%s" is already downloaded.', $type, $addon['name']));
                    return $this->redirect('escher');
                }
                $this->installAddon($addon);
                return $this->redirect('escher');
            }
        }

        $this->_helper->_flashMessenger(__(
            'Nothing processed. Please try again.'));
    }

    /**
     * Helper to install an addon.
     *
     * @param array $addon
     * @return void
     */
    protected function installAddon($addon)
    {
        switch ($addon['type']) {
            case 'plugin':
            case 'omekaplugin':
                $destination = PLUGIN_DIR;
                $type = 'plugin';
                break;
            case 'theme':
            case 'omekatheme':
                $destination = PUBLIC_THEME_DIR;
                $type = 'theme';
                break;
            default:
                return false;
        }

        $isWriteableDestination = is_writeable($destination);
        if (!$isWriteableDestination) {
            $this->_helper->_flashMessenger(__(
                'The %s directory is not writeable by the server.', $type), 'error');
            return;
        }
        // Add a message for security hole.
        $this->_helper->_flashMessenger(__(
            'Don’t forget to protect the %s directory from writing after installation.', $type), 'info');

        // Local zip file path.
        $zipFile = $destination . DIRECTORY_SEPARATOR . basename($addon['zip']);;
        if (file_exists($zipFile)) {
            $result = @unlink($zipFile);
            if (!$result) {
                $this->_helper->_flashMessenger(__(
                    'A zipfile exists with the same name in the %s directory and cannot be removed.', $type),
                    'error');
                return;
            }
        }

        if (file_exists($destination . DIRECTORY_SEPARATOR . $addon['dir'])) {
            $this->_helper->_flashMessenger(__(
                'The %s directory "%s" already exists.', $type, $addon['dir']),
                'error');
            return;
        }

        // Get the zip file from server.
        $result = $this->downloadFile($addon['zip'], $zipFile);
        if (!$result) {
            $this->_helper->_flashMessenger(__(
                'Unable to fetch the %s "%s".', $type, $addon['name']),
                'error');
            return;
        }

        // Unzip downloaded file.
        $result = $this->unzipFile($zipFile, $destination);

        unlink($zipFile);

        if ($result) {
            $msg = __('If "%s" doesn’t appear in the list of %s, its directory may need to be renamed.',
                $addon['name'], Inflector::pluralize($type));
            $result = $this->moveAddon($addon);
            if ($result) {
                $this->_helper->_flashMessenger($msg, 'info');
            } else {
                $this->_helper->_flashMessenger($msg, 'error');
            }

            $this->_helper->_flashMessenger(__(
                '%s uploaded successfully', ucfirst($type)),
                'success');
        }
        else {
            $this->_helper->_flashMessenger(__(
                'An error occurred during the process.'),
                'error');
        }
    }

    /**
     * Helper to download a file.
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    protected function downloadFile($source, $destination)
    {
        $handle = fopen($source, 'rb');
        if (empty($handle)) {
            return false;
        }
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
    protected function unzipFile($source, $destination)
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
            try {
                Omeka_File_Derivative_Strategy_ExternalImageMagick::executeCommand('unzip', $status, $output, $errors);
            } catch (Exception $e) {
                $status = 1;
            }
            // A return value of 0 indicates the convert binary is working correctly.
            $result = $status == 0;
            if ($result) {
                $command = 'unzip ' . escapeshellarg($source) . ' -d ' . escapeshellarg($destination);
                try {
                    Omeka_File_Derivative_Strategy_ExternalImageMagick::executeCommand($command, $status, $output, $errors);
                } catch (Exception $e) {
                    $status = 1;
                }
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
     * @todo Get the directory name from the zip.
     *
     * @param string $addon
     * @return boolean
     */
    protected function moveAddon($addon)
    {
        switch ($addon['type']) {
            case 'plugin':
            case 'omekaplugin':
                $destination = PLUGIN_DIR;
                break;
            case 'theme':
            case 'omekatheme':
                $destination = PUBLIC_THEME_DIR;
                break;
            default:
                return false;
        }

        // Allows to manage case like AddItemLink, where the project name on
        // github is only "AddItem".
        $loop = array($addon['dir']);
        if ($addon['basename'] != $addon['dir']) {
            $loop[] = $addon['basename'];
        }

        // Manage only the most common cases.
        // @todo Use a scan dir + a regex.
        $checks = array(
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
            array('plugin_', '-master'),
            array('theme_', '-master'),
            array('omeka_plugin_', '-master'),
            array('omeka_theme_', '-master'),
            array('omeka_Plugin_', '-master'),
            array('omeka_Theme_', '-master'),
        );

        $name = '';
        foreach ($loop as $addonName) {
            foreach ($checks as $check) {
                $checkName = $destination . DIRECTORY_SEPARATOR
                    . $check[0] . $addonName . $check[1];
                if (file_exists($checkName)) {
                    $name = $checkName;
                    break 2;
                }
                // Allows to manage case like name is "Ead", not "EAD".
                $checkName = $destination . DIRECTORY_SEPARATOR
                    . $check[0] . ucfirst(strtolower($addonName)) . $check[1];
                if (file_exists($checkName)) {
                    $name = $checkName;
                    $addonName = ucfirst(strtolower($addonName));
                    break 2;
                }
                if ($check[0]) {
                    $checkName = $destination . DIRECTORY_SEPARATOR
                        . ucfirst($check[0]) . $addonName . $check[1];
                    if (file_exists($checkName)) {
                        $name = $checkName;
                        break 2;
                    }
                    $checkName = $destination . DIRECTORY_SEPARATOR
                        . ucfirst($check[0]) . ucfirst(strtolower($addonName)) . $check[1];
                    if (file_exists($checkName)) {
                        $name = $checkName;
                        $addonName = ucfirst(strtolower($addonName));
                        break 2;
                    }
                }
            }
        }

        if (empty($name)) {
            return false;
        }

        $path = $destination . DIRECTORY_SEPARATOR . $addon['dir'];
        return rename($name, $path);
    }

    /**
     * Execute a shell command without exec().
     *
     * @see Omeka_File_Derivative_Strategy_ExternalImageMagick::executeCommand()
     *
     * @param string $command
     * @param integer $status
     * @param string $output
     * @param array $errors
     * @throws Exception
     */
    protected function executeCommand($command, &$status, &$output, &$errors)
    {
        // Using proc_open() instead of exec() solves a problem where exec('convert')
        // fails with a "Permission Denied" error because the current working
        // directory cannot be set properly via exec().  Note that exec() works
        // fine when executing in the web environment but fails in CLI.
        $descriptorSpec = [
            0 => array('pipe', 'r'), //STDIN
            1 => array('pipe', 'w'), //STDOUT
            2 => array('pipe', 'w'), //STDERR
        ];
        if ($proc = proc_open($command, $descriptorSpec, $pipes, getcwd())) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            $status = proc_close($proc);
        } else {
            throw new Exception(__(
                'Failed to execute command: %s', $command));
        }
    }
}
