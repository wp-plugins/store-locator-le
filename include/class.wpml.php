<?php

/**
 * Store Locator Plus WPML interface.
 *
 * @package StoreLocatorPlus\Muitl-Language
 * @author Li xintao <isurgeli@gmail.com>
 * @copyright 2013-2014 Charleston Software Associates, LLC
 */
class SLPlus_WPML {
	
	/**
	 * Is WPML has been installed and activated?
	 *
	 * @var boolean is_active
	 */
	private $is_active = null;

	/**
	 * Name of this module.
	 *
	 * @var mixed name
	 */
	private $name;

	//----------------------------------
    // Methods
    //----------------------------------

	/**
	 * Instantiate the WPML Class.
	 *
	 * @param mixed[] $params
	 */
	public function __construct($params = null) {
        $this->name = 'WPML';

        // Do the setting override or initial settings.
        //
        if ($params != null) {
            foreach ($params as $name => $sl_value) {
                $this->$name = $sl_value;
            }
        }
    }

	/**
	 * Is WPML has been installed and activated?
	 *
	 * @return boolean true if WPML is active
	 */
	public function isActive() {
		if ( $this->is_active == null ) {
			$this->is_active = function_exists('icl_register_string');
		}

		return $this->is_active;
	}

	/**
	 * Return WPML translation string of $value if WPML is active.
	 * If WPML is not active, just return $value.
	 *
	 * @param string The name of the text need to translate.
	 * @param string The value of the text need to translate.
     * @param string The textdomain to be used for the translation.
	 * @return string WPML translated text
	 */
	public function getWPMLText($name, $value, $textdomain = 'csa-slplus') {
		if ( $this->isActive() ) {
			return icl_t($textdomain, $name, $value);
		} else {
			return $value;
		}
	}
}
