<?php

/**
 * @copyright Acugis
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Mapfig
 */
class EscherPlugin extends Omeka_Plugin_AbstractPlugin
{

    protected $_hooks = array(
        'define_routes',
        'define_acl'
    );

    protected $_filters = array(
        'admin_navigation_main'
    );

    public function hookDefineRoutes($array)
    {}

    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $acl->addResource('Escher');
        $acl->allow(null, 'Escher');
    }

    public function filterAdminNavigationMain($navArray)
    {
        $navArray['Escher'] = array(
            'label' => __("Escher"),
            'uri' => url('escher')
        );
        return $navArray;
    }
}
