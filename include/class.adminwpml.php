<?php

/**
 * Store Locator Plus interface WPML interface.
 *
 * @package StoreLocatorPlus\Muitl-Language
 * @author Li xintao <isurgeli@gmail.com>
 * @copyright 2013-2014 Charleston Software Associates, LLC
 */
class SLPlus_AdminWMPL {
	/**
	 * The SLPlus Plugin
	 *
	 * @var \SLPlus
	 */
	private $parent = null;

	/**
	 * The SLPlus Plugin
	 *
	 * @var \SLPlus
	 */
	private $plugin = null;

	/**
	 * Set the parent property to point to the primary plugin object.
	 *
	 * Returns false if we can't get to the main plugin object.
	 *
	 * @global wpCSL_plugin__slplus $slplus_plugin
	 * @return type boolean true if plugin property is valid
	 */
	public function setParent() {
        if (!isset($this->parent) || ($this->parent == null)) {
            global $slplus_plugin;
            $this->parent = $slplus_plugin;
            $this->plugin = $slplus_plugin;
        }

        return (isset($this->parent) && ($this->parent != null));
    }

	/**
	 * Register a text need to tanslate to WPML
	 *
	 * @param string text name, which used by WPML to tell user the meaning of the text.
	 * @param string text value, the text need to translate.
	 */
	public function regWPMLText($name, $value, $textdomain = 'csa-slplus') {
		if ( $this->plugin->WPML->isActive() ) {
			icl_register_string($textdomain , $name, $value);
		}
	}

	/**
	 * Register need save option value to WPML
	 *
	 * @param string[] option name array.
	 * @param string default value used if $_POST don't have the option.
	 */
	public function regPostOptions($optionname, $default=null) {
        if ($default != null) {
            if (!isset($_POST[$optionname])) {
                $_POST[$optionname] = $default;
            }
        }

        // Save the option
        //
        if (isset($_POST[$optionname])) {
            $optionValue = $_POST[$optionname];

            $optionValue = stripslashes_deep($optionValue);
            $this->regWPMLText($optionname,$optionValue);
        }
    }
}

