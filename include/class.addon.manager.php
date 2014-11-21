<?php

/**
 * The Store Locator Plus Add On Manager
 *
 * @package StoreLocatorPlus\AddOn_Manager
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2014 Charleston Software Associates, LLC
 *
 */
class SLPlus_AddOn_Manager {
    //-------------------------------------------------
    // Properties
    //-------------------------------------------------
    
    /**
     * An array of all the available add-on packs we know about.
     * 
     * Example: print $this->slplus->add_ons->available['slp-pro']['link']
     * 
     * Slugs
     * o slp-contact-extender
     * o slp-enhanced-map
     * o slp-enhanced-results
     * o slp-enhanced-search
     * o slp-janitor
     * o slp-pages
     * o slp-pro
     * o slp-tagalong
     * o slp-user-managed-locations
     * o slp-widget
     *
     * Properties
     * o name = translated text name of add on
     * o link = full HTML anchor link to product
     * 
     * @var mixed[]
     */
    public $available = array();
    
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
        
        $this->prepare();
    }

    /**
     * Add a sanctioned add on pack to the available add ons array.
     * 
     * @param string $slug
     * @param string $name
     * @param string $leaf_url
     */
    private function add_AddOn( $slug , $name , $leaf_url ) {
        $this->available[$slug] = array(
            'name' => $name,
            'link' => $this->createstring_ProductLink( $name , $leaf_url ),
        );        
    }
            
    /**
     * Set add on active boolean flag, if active set addon property to point to active addon object.
     */
    private function connect_ActiveAddons() {
        foreach ($this->available as $slug => $properties) {
            $this->available[$slug]['active'] = $this->is_active( $slug );
            $this->available[$slug]['addon'] = $this->available[$slug]['active'] ? $this->slplus->addons[$slug] : null;
        }        
    }

    /**
     * Given the text to display and the leaf (end) portion of the product URL, return a full HTML link to the product page.
     * 
     * @param string $text
     * @param string $leaf_url
     * @return string
     */
    private function createstring_ProductLink( $text , $leaf_url ) {
        return 
            sprintf(
                '<a href="%s%s/" target="csa" name="%s" title="%s">%s</a>',
                'http://www.storelocatorplus.com/product/' ,
                $leaf_url,
                $text,
                $text,
                $text
            );
    }

    /**
     * Returns true if an add on, specified by its slug, is active.
     * 
     * @param string $slug
     * @return boolean
     */
    public function is_active ( $slug ) {
        return (
                array_key_exists( $slug, $this->slplus->addons ) &&
                is_object($this->slplus->addons[$slug]) &&
                !empty($this->slplus->addons[$slug]->options['installed_version'])
                );
    }
    
    /**
     * Prepare the add ons interface for use, setting up the available array.
     * 
     */
    private function prepare() {
        if (count($this->available) > 0) { return; }

        // plugin dir path (slug) , plain text name , web page purchase URL (after /product/)
        //
        // TODO : make this an autoregister via reflection or other code trickery.
        // Need ot learn when this is queued and then where the object/property array is referenced.
        //
        $this->add_AddOn( 'slp-contact-extender'       , __( 'Contact Extender'       , 'csa-slplus' ) , 'slp4-contact-extender'        );
        $this->add_AddOn( 'slp-directory-builder'      , __( 'Directory Builder'      , 'csa-slplus' ) , 'directory-builder'            );
        $this->add_AddOn( 'slp-enhanced-map'           , __( 'Enhanced Map'           , 'csa-slplus' ) , 'slp4-enhanced-map'            );
        $this->add_AddOn( 'slp-enhanced-results'       , __( 'Enhanced Results'       , 'csa-slplus' ) , 'slp4-enhanced-results'        );
        $this->add_AddOn( 'slp-enhanced-search'        , __( 'Enhanced Search'        , 'csa-slplus' ) , 'slp4-enhanced-search'         );
        $this->add_AddOn( 'slp-janitor'                , __( 'Janitor'                , 'csa-slplus' ) , 'store-locator-plus-janitor'   );
        $this->add_AddOn( 'slp-location-extender'      , __( 'Location Extender'      , 'csa-slplus' ) , 'location-extender'            );
        $this->add_AddOn( 'slp-pages'                  , __( 'Store Pages'            , 'csa-slplus' ) , 'slp4-store-pages'             );
        $this->add_AddOn( 'slp-pro'                    , __( 'Pro Pack'               , 'csa-slplus' ) , 'slp4-pro'                     );
        $this->add_AddOn( 'slp-tagalong'               , __( 'Tagalong'               , 'csa-slplus' ) , 'slp4-tagalong'                );
        $this->add_AddOn( 'slp-social-media-extender'  , __( 'Social Media Extender'  , 'csa-slplus' ) , 'slp4-social-media-extender'   );
        $this->add_AddOn( 'slp-user-managed-locations' , __( 'User Managed Locations' , 'csa-slplus' ) , 'slp4-user-managed-locations'  );
        $this->add_AddOn( 'slp-widget'                 , __( 'Widget'                 , 'csa-slplus' ) , 'slp4-Widgets'                 );

        $this->connect_ActiveAddons();
    }    

}
