<?php

/**
 * The Store Locator Plus Add On Manager
 *
 * @package StoreLocatorPlus\AddOn_Manager
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2014-2015 Charleston Software Associates, LLC
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
     * Properties
     * o name = translated text name of add on
     * o link = full HTML anchor link to product
     * 
     * @var mixed[]
     */
    public $available = array();

	/**
	 * The add on objects in a named array.  The slug is the key.
	 *
	 * $instances['slp-pro'] => \SLPPro instance.
	 *
	 * @var SLP_BaseClass_Addon[]
	 */
	public $instances = array();
    
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
        
        $this->make_always_available();
    }

    /**
     * Given the text to display and the leaf (end) portion of the product URL, return a full HTML link to the product page.
     * 
     * @param string $text
     * @param string $url
     *
     * @return string
     */
    private function create_string_product_link( $text , $url ) {

	    $product_site_url = 'http://www.storelocatorplus.com/product/';
	    if ( strpos( $url , $product_site_url ) === false ) {
		    $url = sprintf('%s%s' , $product_site_url , $url .'/' );
	    }

        return 
            sprintf(
                '<a href="%s" target="store_locator_plus" name="%s" title="%s">%s</a>',
                $url,
                $text,
                $text,
                $text
            );
    }

	/**
	 * Fetched installed and active version info.
	 *
	 * @return array
	 */
	public function get_versions() {
		$version_info = array();

		foreach ( $this->instances as $slug => $instance ) {
			$version_info[$slug] = $this->get_version( $slug );
		}

		return $version_info;
	}

	/**
	 * Return the version of the specified registered/active add-on pack.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_version( $slug ) {
		if ( isset( $this->instances[$slug] ) && is_object( $this->instances[$slug] ) ) {
			return $this->instances[ $slug ]->options['installed_version'];
		}
		return '';
	}

	/**
	 * Return the product URL of the specified registered/active add-on pack.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_product_url( $slug ) {

		// Active object, get from meta
		//
		if ( isset( $this->instances[$slug] ) && is_object( $this->instances[$slug] ) ) {

			// Newer meta interface
			//
			if ( method_exists( $this->instances[$slug] , 'get_meta' ) ) {
				return $this->create_string_product_link($this->instances[$slug]->name , $this->instances[$slug]->get_meta('PluginURI') );
			}

			// Older meta interface
			// Remove after all plugins are updated to have get_meta()
			//
			return $this->create_string_product_link($this->instances[$slug]->name , $this->instances[$slug]->metadata['PluginURI'] );
		}

		// Manually registered in available array, link exists.
		//
		if ( isset( $this->available[$slug]['link'] ) ) { return $this->available[$slug]['link']; }
		if ( isset( $this->available[$slug] ) ) {
			switch ( $slug ) {

				case 'slp-enhanced-map':
					return $this->create_string_product_link( $this->available[$slug]['name'] , 'slp4-enhanced-map'            );

				case 'slp-enhanced-results':
					return $this->create_string_product_link( $this->available[$slug]['name'] , 'slp4-enhanced-results'        );

				case 'slp-enhanced-search':
					return $this->create_string_product_link( $this->available[$slug]['name'] , 'slp4-enhanced-search'         );

				case 'slp-pro' :
					return $this->create_string_product_link( $this->available[$slug]['name'] , 'slp4-pro'                     );

				case 'slp-tagalong' :
					return $this->create_string_product_link( $this->available[$slug]['name'] , 'slp4-tagalong'                );
			}


		}

		// Unknown
		//
		return '';
	}

    /**
     * Returns true if an add on, specified by its slug, is active.
     * 
     * @param string $slug
     * @return boolean
     */
    public function is_active ( $slug ) {
        return (
                array_key_exists( $slug, $this->instances ) &&
                is_object($this->instances[$slug]) &&
                !empty($this->instances[$slug]->options['installed_version'])
                );
    }

	/**
	 * Add a sanctioned add on pack to the available add ons array.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param boolean $active
	 *
	 * @param SLP_BaseClass_Addon $instance
	 */
	private function make_available( $slug , $name , $active = false, $instance = null ) {
		if (
			! isset( $this->available[$slug] ) ||
		    is_null( $this->available[$slug]['addon'] ) && ! is_null( $instance )
		   ) {

			$this->available[ $slug ] = array(
				'name'   => $name,
				'active' => $active,
				'addon'  => $instance
			);

			// Pro Pack is the only direct-referenced available link in other plugins.
			//
			// TODO: Remove this when other plugins use the get_product_url() method. (ELM,TAG)
			//
			if ( $slug === 'slp-pro' ) {
				$this->available[ $slug ]['link'] =
					is_null( $instance ) ?
						$this->create_string_product_link( $name , 'slp4-pro' ) :
						$this->get_product_url( $slug )                         ;
			}
		}
	}

	/**
     * Make these add ons always available, mostly to be able to reference product links.
     */
    private function make_always_available() {
        $this->make_available( 'slp-enhanced-map'           , __( 'Enhanced Map'           , 'csa-slplus' ) );
        $this->make_available( 'slp-enhanced-results'       , __( 'Enhanced Results'       , 'csa-slplus' ) );
        $this->make_available( 'slp-enhanced-search'        , __( 'Enhanced Search'        , 'csa-slplus' ) );
        $this->make_available( 'slp-pro'                    , __( 'Pro Pack'               , 'csa-slplus' ) );
        $this->make_available( 'slp-tagalong'               , __( 'Tagalong'               , 'csa-slplus' ) );
    }

	/**
	 * Register an add on object to the manager class.
	 *
	 * @param string $slug
	 * @param SLP_BaseClass_Addon $object
	 */
	public function register( $slug , $object ) {
		if ( ! is_object( $object ) ) { return; }

		$slug_parts = explode('/', $slug);
		$clean_slug = str_replace('.php', '', $slug_parts[count($slug_parts) - 1]);

		if ( ! isset( $this->instances[$clean_slug] ) ||  is_null( $this->instances[$clean_slug] )  ) {
			$this->instances[$clean_slug] = $object;
			$this->make_available( $clean_slug, $object->name, true , $object );
		}
	}

}
