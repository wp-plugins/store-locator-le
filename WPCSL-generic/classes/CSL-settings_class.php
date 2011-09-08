<?php

/****************************************************************************
 **
 ** class: wpCSL_settings__slplus
 **
 ** The main settings class.
 ** 
 **/
class wpCSL_settings__slplus {

    /**------------------------------------
     ** method: __construct
     **
     ** Overload of the default class instantiation.
     **
     **/
    function __construct($params) {
        // Default Params
        //
        $this->render_csl_blocks = true;        // Display the CSL info blocks
        $this->form_action = 'options.php';     // The form action for this page
        $this->save_text =__('Save Changes');
        $this->css_prefix = '';
        
        // Passed Params
        //        
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }

        // Only show the license section if the plugin settings
        // wants a license module
        if (!$this->no_license) {
            $this->add_section(array(
                    'name' => 'Plugin License',
                    'description' => "<p>To obtain a key, please purchase this plugin " .
                        "from <a href=\"{$this->url}\" target=\"_new\">{$this->url}</a></p>",
                    'auto' => false
                )
            );
        }

        // Render CSL Blocks - if set false we don't need this overhead
        //
        if ($this->render_csl_blocks) {        
            $this->csl_php_modules = get_loaded_extensions();
            natcasesort($this->csl_php_modules);
            global $wpdb;
            $this->add_section(
                array(
                    'name' => 'Plugin Environment',
                    'description' =>
                        '<p>Here are the technical details about your plugin:<br />
                           <div style="border: solid 1px #E0E0E0; padding: 6px; margin: 6px;
                               background-color: #F4F4F4;">
                               
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">Active WPCSL:</div>
                               <div style="float: left;">' . plugin_dir_path(__FILE__) . '</div>
                             </div>                                
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">Site URL:</div>
                               <div style="float: left;">' . get_option('siteurl') . '</div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">Encryption Key:</div>
                               <div style="float: left;">' . md5(get_option($this->prefix.'-license_key')) . '</div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">License Key:</div>
                               <div style="float: left;">' . (get_option($this->prefix.'-purchased')?'licensed':'unlicensed') . '</div>
                             </div>
                             
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">WPCSL Version:</div>
                               <div style="float: left;">' . WPCSL__slplus__VERSION . '
                               </div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">WordPress Version:</div>
                               <div style="float: left;">' . $GLOBALS['wp_version'] . '
                               </div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">MySQL Version:</div>
                               <div style="float: left;">' . $wpdb->db_version() . '
                               </div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">PHP Version:</div>
                               <div style="float: left;">' . phpversion() .'</div>
                             </div>
                             <div style="clear:left;">
                               <div style="width:150px; float:left; text-align: right;
                                   padding-right: 6px;">PHP Modules:</div>
                               <div style="float: left;">' .
                                 implode('<br/>',$this->csl_php_modules) . '
                               </div>
                             </div>
                             <div style="clear:left;">&nbsp;</div>
                           </div>
                         </p>',
                    'auto' => false
                )
            );
    
            $this->add_item(
                'Plugin Environment', 
                'Enable Debugging Output: ',   
                'debugging',    
                'checkbox'
            );
    
            $this->add_section(array(
                    'name' => 'Plugin Info',
                    'description' =>
                        '<div class="cybersprocket-cslbox">
                        <div class="cybersprocket-csllogo">
                        <a href="http://www.cybersprocket.com/" target="cslinfo"><img src="'. $this->plugin_url .'/images/CSL_banner_logo.png"/></a>
                         </div>
                         <div class="cybersprocket-cslinfo">
                         <h4>This plugin has been brought to you by <a href="http://www.cybersprocket.com"
                                target="_new">Cyber Sprocket Labs</a></h4>
                         <p>Cyber Sprocket Labs is a custom software development company.  
                            We develop desktop, mobile, and web applications for clients large and small  
                            from all around the world. We hope our plugin brings you closer to the perfect site.
                            If there is anything we can do to improve our work or if you wish to hire us to customize
                            this plugin please call our Charleston South Carolina headquarters or 
                            <a href="http://www.cybersprocket.com/contact-us/" target="cyber-sprocket-labs">email us</a>
                            and let us know.<br/>
                            <br>
                            <strong>Cyber Sprocket Is...</strong><br/>
                            Lance Cleveland, Paul Grimes, Chris Rasys, Lobby Jones, Seth Hayward<br/>
                            <br/>
                            <strong>For more information:</strong><br/>
                            <a href="http://www.cybersprocket.com" target="cyber-sprocket-labs">Please visit our website at www.cybersprocket.com</a>.<br/>
                         </p>
                         </div>
                         </div>
                         ',
                    'auto' => false
                )
            );
        }       
    }

    /**------------------------------------
     ** method: add_section
     **
     **/
    function add_section($params) {
        if (!isset($this->sections[$params['name']])) {
            $this->sections[$params['name']] = new wpCSL_settings_section__slplus(
                array_merge(
                    $params,
                    array('plugin_url' => $this->plugin_url,
                          'css_prefix' => $this->css_prefix,                       
                            )
                )
            );
        }            
    }

    /**------------------------------------
     ** Class: WPCSL_Settings
     **------------------------------------
     ** Method: add_item 
     ** 
     ** Parameters:
     **    section name
     **    display name, the label that shows before the input field
     **    name, the database key for the setting
     **    type (default: text, list, checkbox, textarea)
     **    required setting? (default: false, true)
     **    description (default: null) - this is what shows via the expand/collapse setting
     **    custom (default: null, name/value pair if list
     **    value (default: null), the value to use if not using get_option
     **
     **/
    function add_item($section, $display_name, $name, $type = 'text',
            $required = false, $description = null, $custom = null,
            $value = null
            ) {

        $name = $this->prefix .'-'.$name;
    
        $this->sections[$section]->add_item(
            array(
                'prefix' => $this->prefix,
                'css_prefix' => $this->css_prefix,                
                'display_name' => $display_name,
                'name' => $name,
                'type' => $type,
                'required' => $required,
                'description' => $description,
                'custom' => $custom,
                'value' => $value
            )
        );

        if ($required) {
            if (get_option($name) == '') {
                if (isset($this->notifications)) {
                    $this->notifications->add_notice(
                        1,
                        "Please provide a value for <em>$display_name</em>",
                        "options-general.php?page={$this->prefix}-options#".
                            strtolower(strtr($display_name,' ', '_'))
                    );
                }
            }
        }
    }

    /**------------------------------------
     ** Method: register
     ** 
     ** This function should be used via an admin_init action 
     **
     **/
    function register() {
        if (isset($this->license)) {
            $this->license->initialize_options();
        }
        if (isset($this->cache)) {
            $this->cache->initialize_options();
        }

        foreach ($this->sections as $section) {
            $section->register($this->prefix);
        }
    }

    /**------------------------------------
     ** method: render_settings_page
     **
     ** Create the HTML for the plugin settings page on the admin panel
     **/
    function render_settings_page() {
        $this->header();

        // Only render license section if plugin settings
        // asks for it
        if (!$this->no_license) {
            $this->sections['Plugin License']->header();
            $this->show_plugin_settings();
            $this->sections['Plugin License']->footer();
        }

        // Draw each settings section as defined in the plugin config file
        //
        foreach ($this->sections as $section) {
            if ($section->auto) {
                $section->display();
            }
        }

        // Show the plugin environment and info section on every plugin
        //
        if ($this->render_csl_blocks) {
            $this->sections['Plugin Environment']->display();
            $this->sections['Plugin Info']->display();
        }
        $this->render_javascript();
        $this->footer();
    }

    /**------------------------------------
     ** method: show_plugin_settings
     **
     ** This is a function specifically for showing the licensing stuff,
     ** should probably be moved over to the licensing submodule
     **/
    function show_plugin_settings() {
       $license_ok =(  (get_option($this->prefix.'-purchased') == '1')   &&
            	      (get_option($this->prefix.'-license_key') != '')            	    	    
            	      );     
    	    

        $content = "<tr valign=\"top\">\n";
        $content .= "  <th scope=\"row\">License Key *</th>";
        $content .= "    <td>";
        $content .= "<input type=\"text\"".
            ((!$license_ok) ?
                "name=\"{$this->prefix}-license_key\"" :
                '') .
            " value=\"". get_option($this->prefix.'-license_key') .
            "\"". ($license_ok?'disabled' :'') .
            " />";

        if ($license_ok) {
            $content .= "<input type=\"hidden\" name=\"{$this->prefix}-license_key\" value=\"".
                get_option($this->prefix.'-license_key')."\"/>";
            $content .= '<span><img src="'. $this->plugin_url .
                '/images/check_green.png" border="0" style="padding-left: 5px;" ' .
                'alt="License validated!" title="License validated!"></span>';
        }
        
        $content .= (!$license_ok) ?
            ('<span><font color="red"><br/>Without a license key, this plugin will ' .
                'only function for Admins</font></span>') :
            '';
        $content .= (!(get_option($this->prefix.'-license_key') == '') &&
                    !get_option($this->prefix.'-purchased')) ?
            ('<span><font color="red">Your license key could not be verified</font></span>') :
            '';

        if (!$license_ok) {
            $content .= "
                <div>
                  <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick" .
                      "&hosted_button_id={$this->paypal_button_id}\"
                      target=\"_new\">
                    <img alt=\"PayPal - The safer, easier way to pay online!\"
                        src=\"https://www.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif\" />
                  </a>
                </div>
                <div>
                  <p>Your license key is emailed to you within 15 minutes of your
                     purchase. If you did not receive your license check your spam
                     folder.  <a href='http://www.cybersprocket.com/contact-us/'
                     target='cyber sprocket'>Contact us</a> if you need your license
                     sent again.
                  </p>
                </div>";
        }

        $content .= ' </td></tr>';

        echo $content;
    }

    /**------------------------------------
     ** method: header
     **
     **/
    function header() {
        echo "<div class=\"wrap\">\n";
        screen_icon(preg_replace('/\s/','_',$this->name));
        echo "<h2>{$this->name}</h2>\n";
        echo "<form method='post' action='".$this->form_action."'>\n";
        echo settings_fields($this->prefix.'-settings');

        echo "\n<div id=\"poststuff\" class=\"metabox-holder\">
     <div class=\"meta-box-sortables\">
       <script type=\"text/javascript\">
         jQuery(document).ready(function($) {
             $('.postbox').children('h3, .handlediv').click(function(){
                 $(this).siblings('.inside').toggle();
             });
         });         
         jQuery(document).ready(function($) {
             $('.".$this->css_prefix."-moreicon').click(function(){
                 $(this).siblings('.".$this->css_prefix."-moretext').toggle();
             });
         });         
       </script>\n";
    }

    /**------------------------------------
     ** method: footer
     **
     **/
    function footer() {
        printf('</div></div><p class="submit">
            <input type="submit" class="button-primary" value="%s" />
          </p></form></div>',
         $this->save_text
         );
    }

    /**------------------------------------
     ** method: render_javascript
     **
     **/
    function render_javascript() {
        echo "<script type=\"text/javascript\">
            function swapVisibility(id) {
              var item = document.getElementById(id);
              item.style.display = (item.style.display == 'block') ? 'none' : 'block';
            }
          </script>";
    }

    /**------------------------------------
     ** method: check_required
     **
     **/
    function check_required($section = null) {
        if ($section == null) {
            foreach ($this->sections as $section) {
                foreach ($section->items as $item) {
                    if ($item->required && get_option($item->name) == '') return false;
                }
            }
        } else {
            
            // The requested section does not exist yet.
            if (!isset($this->sections[$section])) { return false; }
            
            // Check the required items
            //
            foreach ($this->sections[$section]->items as $item) {
                if ($item->required && get_option($item->name) == '') return false;
            }
        }

        return true;
    }

}

/****************************************************************************
 **
 ** class: wpCSL_settings_section__slplus
 **
 **/
class wpCSL_settings_section__slplus {

    /**------------------------------------
     **/
    function __construct($params) {
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }

        if (!isset($this->auto)) $this->auto = true;
    }

    /**------------------------------------
     ** Class: wpCSL_settings_section
     ** Method: add_item
     **
     **/
    function add_item($params) {
        $this->items[] = new wpCSL_settings_item__slplus(
            array_merge(
                $params,
                array('plugin_url' => $this->plugin_url,
                      'css_prefix' => $this->css_prefix,
                      )
            )
        );
    }

    /**------------------------------------
     **/
    function register($prefix) {
        if (!isset($this->items)) return false;
        foreach ($this->items as $item) {
            $item->register($prefix);
        }
    }

    /**------------------------------------
     **/
    function display() {
        $this->header();

        if (isset($this->items)) {
            foreach ($this->items as $item) {
                $item->display();
            }
        }

        $this->footer();
    }

    /**------------------------------------
     **/
    function header() {
        echo "<div class=\"postbox\">
         <div class=\"handlediv\" title=\"Click to toggle\"><br/></div>
         <h3 class=\"hndle\">
           <span>{$this->name}</span>
           <a name=\"".strtolower(strtr($this->name, ' ', '_'))."\"></a>
         </h3>
         <div class=\"inside\">
            <div class='section_description'>{$this->description}</div>
    <table class=\"form-table\" style=\"margin-top: 0pt;\">\n";

    }

    /**------------------------------------
     **/
    function footer() {
        echo "</table>
         </div>
       </div>\n";
    }

}

/****************************************************************************
 **
 ** class: wpCSL_settings_item__slplus
 **
 ** Settings Page : Items Class
 ** This class manages individual settings on the admin panel settings page.
 **
 **/
class wpCSL_settings_item__slplus {

    /**------------------------------------
     **/
    function __construct($params) {
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }
    }

    /**------------------------------------
     **/
    function register($prefix) {
        register_setting( $prefix.'-settings', $this->name );
    }

    /**------------------------------------
     **/
    function display() {
        $this->header();
        if (isset($this->value)) {
            $showThis = $this->value;
        } else {
            $showThis = get_option($this->name);
        }
        $showThis = htmlspecialchars($showThis);
        
        echo '<div class="'.$this->css_prefix.'-input">';
        
        switch ($this->type) {
            case 'textarea':
                echo "<textarea name=\"{$this->name}\" cols=\"50\" rows=\"5\">".
                    $showThis ."</textarea>";
                break;

            case 'text':
                echo "<input type=\"text\" name=\"{$this->name}\" value=\"". $showThis ."\" />";
                break;

            case "checkbox":
                echo "<input type=\"checkbox\" name=\"{$this->name}\"".
                    (($showThis) ? ' checked' : '').">";
                break;

            case "list":
                echo $this->create_option_list();
                break;
                
            case "submit_button":
                echo '<input class="button-primary" type="submit" value="'.$showThis.'">';
                break;                

            default:
                echo $this->custom;
                break;

        }
        echo '</div>';

        if ($this->description != null) {
            $this->display_description_icon();
        }

        if ($this->required) {
            echo ((get_option($this->name) == '') ?
                '<div class="'.$this->css_prefix.'-reqbox">'.
                    '<div class="'.$this->css_prefix.'-reqicon"></div>'.
                    '<div class="'.$this->css_prefix.'-reqtext">This field is required.</div>'.
                '</div>'
                : ''
                );
        }
        
        if ($this->description != null) {
            $this->display_description_text($this->description);
        }

        
        
        $this->footer();
    }

    /**------------------------------------
     * If $type is 'list' then $custom is a hash used to make a <select>
     * drop-down representing the setting.  This function returns a
     * string with the markup for that list.
     */
    function create_option_list() {
        $output_list = array("<select class='csl_select' name=\"{$this->name}\">\n");

        foreach ($this->custom as $key => $value) {
            if (get_option($this->name) === $value) {
                $output_list[] = "<option class='csl_option' value=\"$value\" " .
                    "selected=\"selected\">$key</option>\n";
            }
            else {
                $output_list[] = "<option class='csl_option'  value=\"$value\">$key</option>\n";
            }
        }

        $output_list[] = "</select>\n";
        
        return implode('', $output_list);
    }

    /**------------------------------------
     **/
    function header() {
        echo "<tr><th class='input_label' scope='row'>" .
        "<a name='" .
        strtolower(strtr($this->display_name, ' ', '_')).
            "'></a>{$this->display_name}".
            (($this->required) ? ' *' : '').
            '</th><td>';

    }

    /**------------------------------------
     **/
    function footer() {
        echo '</td></tr>';
    }

    /**------------------------------------
     **/
    function display_description_icon() {
        echo '<div class="'.$this->css_prefix.'-moreicon" title="click for more info"><br/></div>';        
    }    
    
    /**------------------------------------
     **/
    function display_description_text($content) {
        echo '<div class="'.$this->css_prefix.'-moretext">';
        echo $content;
        echo '</div>';
    }
}
