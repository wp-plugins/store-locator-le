<?php

// Make sure the class is only defined once.
//
if (!class_exists('SLP_Extension'   )) {

    require_once(WP_PLUGIN_DIR . '/store-locator-le/include/base_class.addon.php');

    /**
     * Base Store Locator Plus extensions using the 4.2 add-on framework.
     *
     * This is an attempt to "eat our own cooking", extending the basic Store Locator Plus algorithms using
     * the built-in hooks and filters whenever possible.
     *
     * @package StoreLocatorPlus\Extension
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 Charleston Software Associates, LLC
     */
    class SLP_Extension extends SLP_BaseClass_Addon {

    }
}