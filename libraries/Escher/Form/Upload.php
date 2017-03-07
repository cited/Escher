<?php

class Escher_Form_Upload extends Omeka_Form
{

    /**
     * List of addons.
     *
     * @var array
     */
    protected $addons = array();

    public function init()
    {
        parent::init();

        $this->setAttrib('id', 'easyinstall');
        $this->setMethod('post');

        $addons = new Escher_Controller_Action_Helper_Addons();
        $this->setAddons($addons);

        $addonLabels = array(
            'omekaplugin' => __('Plugins'),
            'omekatheme' => __('Themes'),
            'plugin' => __('Plugins'),
            'theme' => __('Themes'),
        );

        $list = $addons->list();
        foreach($list as $addonType => $addonsForType) {
            if (empty($addonsForType)) {
                continue;
            }
            $valueOptions = array();
            $valueOptions[''] = 'Select Below'; // @translate
            foreach ($addonsForType as $url => $addon) {
                $label = $addon['name'] . ' [v' . $addon['version'] . ']';
                $label .= $addons->dirExists($addon) ? ' *' : '';
                $valueOptions[$url] = $label;
            }

            $this->addElement(
                'select',
                $addonType,
                array(
                    'label' => $addonLabels[$addonType],
                    'description' => '',
                    'multiOptions' => $valueOptions,
                )
            );
        }

        if (!empty($list['omekaplugin']) || !empty($list['omekatheme'])) {
            $this->addDisplayGroup(
                array(
                    'omekaplugin',
                    'omekatheme',
                ),
                'omekaorg',
                array(
                    'legend' => __('From Omeka.org'),
            ));
        }

        if (!empty($list['plugin']) || !empty($list['theme'])) {
            $this->addDisplayGroup(
                array(
                    'plugin',
                    'theme',
                ),
                'web',
                array(
                    'legend' => __('From the web'),
            ));
        }

        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);

        $this->addElement('sessionCsrfToken', 'csrf_token');

        $this->addElement('submit', 'submit', array(
            'label' => __('Upload'),
            'class' => 'submit submit-medium',
            'decorators' => (array(
                'ViewHelper',
                array('HtmlTag', array('tag' => 'div', 'class' => 'field')))),
        ));
    }

    /**
     * @param Escher_Controller_Action_Helper_Addons $addons
     * @return void
     */
    public function setAddons(Escher_Controller_Action_Helper_Addons $addons)
    {
        $this->addons = $addons;
    }

    /**
     * @return array
     */
    public function getAddons()
    {
        return $this->addons;
    }
}
