<?php
/**
 * Plugin Name: Quick Assign SKU Images for WooCommerce
 * Plugin URI: http://www.extreme-idea.com/
 * Description: Plugin helps you mass assign images for products in simple way.
 * Version: 1.1.6
 * Author: EXTREME IDEA LLC
 * Author URI: http://www.extreme-idea.com/
 */

/**
 * Copyright (c) 2018 EXTREME IDEA LLC https://www.extreme-idea.com
 * This software is the proprietary information of EXTREME IDEA LLC.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without modification
 * are not permitted without prior written approval by the copyright holder.
 *
 */

namespace Com\ExtremeIdea\Woocommerce\QuickAssignSkuImages;

use Com\ExtremeIdea\Ecommerce\Software\License\Manager\Verifier\Service\Rest\Impl\SoftwareLicenseVerifier;
use Com\ExtremeIdea\Ecommerce\Update\Plugin\Verifier\License\Fields\Service\Wordpress\Impl\WordpressUpdatePluginVerifierLicenseFields;

use Com\ExtremeIdea\Ecommerce\Update\Plugin\Verifier\Service\Wordpress\Impl\WordpressUpdatePluginVerifier;
use Com\ExtremeIdea\Ecommerce\Wordpress\License\Manager\Verifier\Impl\WordpressLicenseVerifier;
use Com\ExtremeIdea\Woocommerce\QuickAssignSkuImages\View\AdminPage;

// phpcs:disable PSR1.Files.SideEffects
require_once __DIR__ . "/vendor/autoload.php";

add_action('plugins_loaded', array('Com\ExtremeIdea\Woocommerce\QuickAssignSkuImages\AssignSkuImages', 'init'));
register_activation_hook(
    __FILE__,
    array('Com\ExtremeIdea\Woocommerce\QuickAssignSkuImages\AssignSkuImages', 'activation')
);

/**
 * Class AssignSkuImages
 *
 * @package Com\ExtremeIdea\Woocommerce\Quick\Assign\Sku\Images.
 */
class AssignSkuImages
{
    const PLUGIN_VERSION = '1.1.6';
    const SOFTWARE_INTERNAL_ID = 'woocommerce_quick_assign_sku_images';


    protected static $instance = null;

    protected $adminPage;

    /**
     * AssignSkuImages constructor.
     *
     * @return AssignSkuImages
     */
    protected function __construct()
    {
        $this->adminPage = new AdminPage();
        /* Add admin settings*/
        add_action('admin_menu', array($this, 'adminSettings'));
    }

    /**
     * Singleton function.
     * Init Plugin.
     *
     * @return AssignSkuImages
     */
    public static function init()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Activation wordpress hook.
     *
     * @return void
     */
    public static function activation()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            wp_die(
                'Plugin requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> '
                . 'to be activated. Please install and activate <a href="'
                . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce')
                . '" target="_blank">WooCommerce</a> first.'
            );
        }
    }

    /**
     * Admin settings menu.
     *
     * @return void
     */
    public function adminSettings()
    {
        add_submenu_page(
            'woocommerce',
            'Quick Assign Sku Images for WooCommerce',
            'Quick Assign Sku Images for WooCommerce',
            'manage_woocommerce',
            'woo-quick-assign-sku-images',
            array($this, 'assignPage')
        );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function assignPage()
    {
        $this->adminPage->render();
    }
}
