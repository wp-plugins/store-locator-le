<?php
/**
 * Store Locator Plus manage locations admin / info tab.
 *
 * This actually only manages the how to text for now.
 *
 * @package StoreLocatorPlus\AdminUI\Info
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 - 2015 Charleston Software Associates, LLC
 *
 */
class SLPlus_AdminUI_Info {

    //------------------------------------------------------
    // METHODS
    //------------------------------------------------------
    function createstring_HowToUse() {
        return
        '<h4>'.__('Add A Location','csa-slplus').'</h4>
        <p style="padding-left: 30px;">
        Add a location or two via the <a href="'.admin_url().'admin.php?page=slp_manage_locations">Add Location form</a>.
        You will find this link, and other Store Locator Plus links, in the left sidebar under the "Store Locator Plus" entry.
        If you have many locations to add, check out the <a href="http://www.storelocatorplus.com//product/slp4-pro/" target="csa">Pro Pack</a> and the bulk import options.
        </p>

        <h4>'.__('Create A Page','csa-slplus').'</h4>
        <p style="padding-left: 30px;">
        Go to the sidebar and select "Add New" under the pages section.  You will be creating a standard WordPress page.
        On that page add the [SLPLUS] <a href="http://www.storelocatorplus.com/support/documentation/store-locator-plus/getting-started/shortcodes/" target="csa">shortcode</a>.  When a visitor goes to that page it will show a default search form and a Google Map.
        When someone searches for a zip code that is close enough to a location you entered it will show those locations on the map.
        </p>

        <h4>'.__('Tweak The Settings','csa-slplus').'</h4>
        <p style="padding-left: 30px;">
        You can modify basic settings such as the options shown on the radius pull down list on the <a href="'.admin_url().'admin.php?page=slp_map_settings">User Experience</a> page.
        Even more settings are available via <a href="http://www.storelocatorplus.com/product-category/slp4-products/" target="csa">the premium add-on packs</a>.
        </p>
        <p style="padding-left: 30px;"><strong>'.

        sprintf(        
            __('It is recommended that you start by going to <a href="%s">General Settings</a> and turning OFF "Force Load JavaScript".','csa-slplus'),
            admin_url() . 'admin.php?page=slp_general_settings'
        ) .
                
        '</strong>
        It is on by default because 20% of the WordPress themes on the market do not properly support WordPress 3.3 standard page footer processing.
        This break Store Locator Plus functionality.   
        Since many users will not read this help text, Force Load JavaScript is ON be default.   
        It makes ALL of your pages load a little slower.  
        Some features like the extended shortcode attributes in the base plugin and premium add-on packs will not function.
        If your WordPress theme breaks the Store Locator Plus plugin when you turn off Force Load JavaScript, 
        write to the theme author and ask them to read the <a href="http://codex.wordpress.org/Function_Reference/wp_enqueue_script" target="csa">WordPress Codex on how to support wp_footer() in relation to wp_enqueue_script()</a>.
        </p>

        <h4>'.__('More Help?','csa-slplus').'</h4>
        <p style="padding-left: 30px;">
        Check out the <a href="http://www.storelocatorplus.com/support/" target="csa">online documentation and support forums</a>.
        </p>
        ';
    }

}

// Dad. Explorer. Rum Lover. Code Geek. Not necessarily in that order.
