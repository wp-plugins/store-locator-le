<?php

/**
 * A Store Locator Plus Add On.
 *
 * @package StoreLocatorPlus\AddOn
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2014 Charleston Software Associates, LLC
 *
 */
class SLPlus_AddOn {
    //-------------------------------------------------
    // Properties
    //-------------------------------------------------

    /**
     * The SLP plugin.
     *
     * @var \SLPlus
     */
    private $slplus;

    //-------------------------------------------------
    // Methods
    //-------------------------------------------------

    /**
     * Invoke a new object.
     */
    function __construct($params) {

        // Set properties based on constructor params,
        // if the property named in the params array is well defined.
        //
        if ($params !== null) {
            foreach ($params as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }
        }
    }

}
