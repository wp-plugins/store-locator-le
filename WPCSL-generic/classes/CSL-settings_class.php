<?php

/**
 * The wpCSL Settings Class
 *
 * @package wpCSL\Settings
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 *
 */
class wpCSL_settings__slplus {

    //-----------------------------
    // Properties
    //-----------------------------

    /**
     * The form encryption type.
     * 
     * If set, the enctype attribute will be added to the form output.
     * 
     * Default: none.
     * 
     * Usually: multipart/form-data
     * 
     * @var string
     */
    protected $form_enctype = '';

    /**
     * The form name and ID.
     *
     * @var string
     */
    private $form_name = '';
    
    /**
     * Skip the save button.
     * 
     * @var boolean $no_save_button
     */
    public $no_save_button = false;

    /**
     * The main WPCSL object.
     * 
     * @var wpCSL_plugin__slplus
     */
    public $parent;

    /**
     * The settings page "containers" for settings.
     * 
     * @var \wpCSL_settings_section__slplus[] $sections
     */
    private $sections;

    /**
     * True if the CSS tweak was rendered for a slider already.
     *
     * @var boolean $slider_rendered
     */
    public $slider_rendered;

    /**
     * Instantiate a settings object.
     *
     * @param mixed[] $params
     */
    function __construct($params) {
        // Default Params
        //
        $this->render_csl_blocks = true;        // Display the CSL info blocks
        $this->form_action = 'options.php';     // The form action for this page
        $this->save_text =__('Save Changes','wpcsl');
        $this->css_prefix = '';
        
        // Passed Params
        //
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }

        // Set helper for help on settings panel items.
        //
        $this->helper =
                (isset($this->parent) && isset($this->parent->helper))  ?
                $this->parent->helper                                   :
                new wpCSL_helper__slplus(array('parent'=>$this)) ;

        if (isset($this->parent) && $this->parent->check_isOurAdminPage()){
            add_action('admin_init',array($this,'create_InfoSection'));
        }
    }

    /**
     * Create the plugin news section.
     *
     */
    function create_InfoSection() {
        $this->add_section(array(
                'name'          => 'Plugin News',
                'div_id'        => 'plugin_news',
                'description'   => $this->get_broadcast(),
                'auto'          => true,
                'innerdiv'      => true,
            )
        );
    }

     /**
      * Create a settings group box.
      *
      * @param string $slug - a unique div ID (slug) for this group box, required.  alpha_numeric _ and - only please.
      * @param string $header - the text to put in the header
      * @param string $intro - the text to put directly under the header
      * @param string $content - the settings HTML
      * @return string HTML
      */
     function create_SettingsGroup($slug=null, $header='Settings',$intro='',$content='') {
         if ($slug === null) { return ''; }

         $content =
            "<div class='section_column wpcsl-group' id='wpcsl_settings_group-$slug'>" .
                "<h2>$header</h2>" .
                (($intro != '')     ?
                    "<div class='section_column_intro' id='wpcsl_settings_group_intro-$slug'>$intro</div>" :
                    ''
                ).
                (($content != '')   ?
                    "<div class='section_column_content' id='wpcsl_settings_group_content-$slug'>$content</div>" :
                    ''
                ).
            '</div>'
            ;
         return apply_filters('wpcsl_settings_group-'.$slug,$content);
     }

    /**
     * Create the Environment Panel
     *
     * @global type $wpdb
     */
    function create_EnvironmentPanel() {
        if (!isset($this->parent))          { return; }
        if (!$this->parent->isOurAdminPage) { return; }
        if (!$this->render_csl_blocks)      { return; }
        if (!is_admin())                    { return; }

        global $wpdb;
        $this->csl_php_modules = get_loaded_extensions();
        natcasesort($this->csl_php_modules);
        $this->parent->metadata = get_plugin_data($this->parent->fqfile, false, false);

        // Add ON Packs
        //
        $addonStr = '';
        if (isset($this->parent->addons)) {
            foreach ($this->parent->addons as $addon => $object) {
                $version  = ($object!=null)?$object->metadata['Version']:'active';
                $addonStr .= $this->create_EnvDiv($addon,$version);
            }
        }

        $this->add_section(
            array(
                'name' => 'Plugin Environment',
                'description' =>
                    $this->create_EnvDiv($this->parent->metadata['Name'] . ' Version' ,$this->parent->metadata['Version'] ).
                    $addonStr . 
                    '<br/><br/>' .
                    $this->create_EnvDiv('WPCSL Version'                            ,WPCSL__slplus__VERSION             ).
                    $this->create_EnvDiv('Active WPCSL'                             ,plugin_dir_path(__FILE__)          ).
                    '<br/><br/>' .
                    $this->create_EnvDiv('WordPress Version'                        ,$GLOBALS['wp_version']             ).
                    $this->create_EnvDiv('Site URL'                                 ,get_option('siteurl')              ).
                    '<br/><br/>' .
                    $this->create_EnvDiv('MySQL Version'                            ,$wpdb->db_version()                ).
                    '<br/><br/>' .
                    $this->create_EnvDiv('PHP Version'                              ,phpversion()                       ).
                    $this->create_EnvDiv('PHP Peak RAM'                             ,
                            sprintf('%0.2d MB',memory_get_peak_usage(true)/1024/1024)                                   ).
                    $this->create_EnvDiv('PHP Modules'                              ,
                            '<pre>'.print_r($this->csl_php_modules,true).'</pre>'                                       )
                    ,
                'auto'              => true,
                'innerdiv'          => true,
                'start_collapsed'   => false
            )
        );
    }

    /**
     * Create a plugin environment div.
     *
     * @param string $label
     * @param string $content
     * @return string
     */
    function create_EnvDiv($label,$content) {
        return "<p class='envinfo'><span class='label'>{$label}:</span>{$content}</p>";
    }

    /**
     * Get the news broadcast from the remote server.
     *
     * @return string the HTML for the news panel
     */
     function get_broadcast() {
         $content = '';
        if (isset($this->http_handler)) { 
            if ($this->broadcast_url != '') {
                $result = $this->http_handler->request( 
                                $this->broadcast_url, 
                                array('timeout' => 3) 
                                ); 
                if ($this->parent->http_result_is_ok($result) ) {
                    return $result['body'];
                }
            }                
        }         
        
        // Return default content
        //
        if ($content == '') {
            return $this->default_broadcast();
        }
     }
     
   /**
    * Call parent DebugMP only if parent has been set.
    * 
    *
    * @param string $panel - panel name
    * @param string $type - what type of debugging (msg = simple string, pr = print_r of variable)
    * @param string $header - the header
    * @param string $message - what you want to say
    * @param string $file - file of the call (__FILE__)
    * @param int $line - line number of the call (__LINE__)
    * @param boolean $notime - show time? default true = yes.
    * @return null
    */
    function debugMP($panel='main', $type='msg', $header='wpCSL DMP',$message='',$file=null,$line=null,$notime=false) {
         if (is_object($this->parent)) {
             $this->parent->debugMP($panel,$type,$header,$message,$file,$line,$notime);
         }
     }

     /**
      * Set the default HTML string if the server if offline.
      *
      * @return string
      */
     function default_broadcast() {
         return
                        '
                        <div class="csa-infobox">
                         <h4>This plugin has been brought to you by <a href="http://www.charlestonsw.com"
                                target="_new">Charleston Software Associates</a></h4>
                         <p>If there is anything I can do to improve my work or if you wish to hire me to customize
                            this plugin please
                            <a href="http://www.charlestonsw.com/mindset/contact-us/" target="csa">email me</a>
                            and let me know.
                         </p>
                         </div>
                         ' ;
     }

     /**
      * Create a settings page panel.
      *
      * Does not render the panel, it simply creates the container to add stuff to for later rendering.
      *
      * @param array $params named array of the section properties, name is required.
      */
    function add_section($params) {
        if (!isset($this->sections[$params['name']])) {
            $this->sections[$params['name']] = new wpCSL_settings_section__slplus(
                array_merge(
                    $params,
                    array('plugin_url'  => $this->plugin_url,
                          'css_prefix'  => $this->css_prefix,
                          'parent'      => $this            ,
                            )
                )
            );
        }            
    }


    /**
     * Add a simple on/off slider to the settings array.
     *
     * @param string $section - slug for the parent section
     * @param string $label - text to appear before the setting
     * @param string $fieldID - the option value field
     * @param string $description - the help text under the more icon expansion
     * @param string $value - the default value to use, overrides get-option(name)
     * @param boolean $disabled - true if the field is disabled
     */
    function add_slider($section,$label,$fieldID,$description=null,$value=null,$disabled=false) {
        $this->add_item(
                $section,
                $label,
                $fieldID,
                'slider',
                false,
                $description,
                null,
                $value,
                $disabled
                );
    }

    /**------------------------------------
     ** method: get_item
     **
     ** Return the value of a WordPress option that was saved via the settings interface.
     **/
    function get_item($name, $default = null, $separator='-', $forceReload = false) {
        $option_name = $this->prefix . $separator . $name;
        if (!isset($this->$option_name) || $forceReload) {
            $this->$option_name =
                ($default == null) ?
                    get_option($option_name) :
                    get_option($option_name,$default)
                    ;
        }
        return $this->$option_name;
    }
    
    /**
     * Add a setting to a panel.
     *
     * @param string $section section slug
     * @param string $display_name the label that shows before the input field
     * @param string $name the database key for the setting
     * @param string $type input style (default: text, list, checkbox, textarea)
     * @param boolean $required required setting? (default: false, true)
     * @param string $description this is what shows via the expand/collapse setting
     * @param mixed[] $custom  (default: null, name/value pair if list)
     * @param mixed $value (default: null), the value to use if not using get_option
     * @param boolean $disabled (default: false), show the input but keep it disabled
     * @param string $onChange onChange JavaScript trigger
     * @param string $group group heading
     * @param string $separator separator for prefix
     * @param boolean $show_label if true prepend output with label
     * @param boolean $use_prefix if true prepend name with prefix and separator
     * @param string $selectedVal the drop down value to be marked as selected
     * @return null
     */
    function add_item($section, $display_name, $name, $type = 'text',
            $required = false, $description = null, $custom = null,
            $value = null, $disabled = false, $onChange = '', $group = null,
            $separator = '-',$show_label=true,$use_prefix = true,$selectedVal=''
            ) {

        // Prefix not provided, prepend name with this->prefix and separator
        if ($use_prefix) {
            $name = $this->prefix .$separator.$name;
        }

        //** Need to check the section exists first. **/
        if (!isset($this->sections[$section])) {
            if (isset($this->notifications)) {
                $this->notifications->add_notice(
                    3,
                    sprintf(
                       __('Program Error: section <em>%s</em> not defined.','wpcsl'),
                       $section
                       )
                );
            }
            return;
        }

        $this->sections[$section]->add_item(
            array(
                'parent'        => $this,
                'prefix'        => $this->prefix,
                'css_prefix'    => $this->css_prefix,
                'display_name'  => $display_name,
                'name'          => $name,
                'type'          => $type,
                'required'      => $required,
                'description'   => $description,
                'custom'        => $custom,
                'value'         => $value,
                'disabled'      => $disabled,
                'onChange'      => $onChange,
                'group'         => $group,
                'show_label'    => $show_label,
                'selectedVal'   => $selectedVal
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

    /**
     * Same as add_item but uses named params.
     *
     * 'type' => textarea, text, checkbox, dropdown, slider, list, submit_button, ..custom..
     * 
     * @param mixed $params
     */
    function add_ItemToGroup($params) {
        $defaultSettingName = wp_generate_password(8,false);
        $this->add_item(
                isset($params['section']    )?$params['section']            :'Settings',
                isset($params['label']      )?$params['label']              :'Setting '.$defaultSettingName,
                isset($params['setting']    )?$params['setting']            : $defaultSettingName,              // item->name
                isset($params['type']       )?$params['type']               : 'text',
                isset($params['required']   )?$params['required']           : false,
                isset($params['description'])?$params['description']        : null,
                isset($params['custom']     )?$params['custom']             : null,                             // item->custom
                isset($params['value']      )?$params['value']              : null,
                isset($params['disabled']   )?$params['disabled']           : false,
                isset($params['onChange']   )?$params['onChange']           : '',                               // item->onChange
                isset($params['group']      )?$params['group']              : null,
                isset($params['separator']  )?$params['separator']          : '-',
                isset($params['show_label'] )?$params['show_label']         : true,
                isset($params['use_prefix'] )?$params['use_prefix']         : true,
                isset($params['selectedVal'])?$params['selectedVal']        : ''
                );
    }

    /**
     * Add a simple checkbox to the settings array.
     *
     * @param string $section - slug for the parent section
     * @param string $label - text to appear before the setting
     * @param string $fieldID - the option value field
     * @param string $description - the help text under the more icon expansion
     * @param string $value - the default value to use, overrides get-option(name)
     * @param boolean $disabled - true if the field is disabled
     */
    function add_checkbox($section,$label,$fieldID,$description=null,$value=null,$disabled=false) {
        $this->add_item(
                $section,
                $label,
                $fieldID,
                'checkbox',
                false,
                $description,
                null,
                $value,
                $disabled
                );
    }

    /**
     * Add a simple text input to the settings array.
     *
     * @param string $section - slug for the parent section
     * @param string $label - text to appear before the setting
     * @param string $fieldID - the option value field
     * @param string $description - the help text under the more icon expansion
     * @param string $value - the default value to use, overrides get-option(name)
     * @param boolean $disabled - true if the field is disabled
     */
    function add_input($section,$label,$fieldID,$description=null,$value=null,$disabled=false) {
        $this->add_item(
                $section,
                $label,
                $fieldID,
                'text',
                false,
                $description,
                null,
                $value,
                $disabled
                );
    }

    /**
     * Add a simple text input to the settings array.
     *
     * @param string $section - slug for the parent section
     * @param string $label - text to appear before the setting
     * @param string $fieldID - the option value field
     * @param string $description - the help text under the more icon expansion
     * @param string $value - the default value to use, overrides get-option(name)
     * @param boolean $disabled - true if the field is disabled
     */
    function add_textbox($section,$label,$fieldID,$description=null,$value=null,$disabled=false) {
        $this->add_item(
                $section,
                $label,
                $fieldID,
                'textarea',
                false,
                $description,
                null,
                $value,
                $disabled
                );
    }

    /**------------------------------------
     ** Method: register
     ** 
     ** This function should be used via an admin_init action 
     **
     **/
    function register() {
        if (isset($this->cache)) {
            $this->cache->initialize_options();
        }

        if (isset($this->sections)) {
            foreach ($this->sections as $section) {
                $section->register($this->prefix);
            }
        }            
    }

    /**
     * Create the HTML for the plugin settings page on the admin panel.
     *
     * @var $section \wpCSL_settings_section__slplus
     */
    function render_settings_page() {
        
        // Will add debug environment panel at end of general settings panel only.
        //
        $this->create_EnvironmentPanel();

        $this->header();

        // Render all top menus first.
        //
        foreach ($this->sections as $section) {
            $this->debugMP('wpcsl.settings','msg',
                    "{$section->name} first:{$section->first} is_topmenu:{$section->is_topmenu}",
                    '',
                    NULL,NULL,true);
            if (isset($section->is_topmenu) && ($section->is_topmenu)) {
                $section->display();
            }
        }

        // Main area with left sidebar
        //
        print '<div id="main">';

        // Menu Area
        //
        $selectedNav = isset($_REQUEST['selected_nav_element'])?
                $_REQUEST['selected_nav_element']:
                ''
                ;
        $firstOne = true;
        print '<div id="wpcsl-nav" style="display: block;">';
        print '<ul>';
        foreach ($this->sections as $section) {
            if ($section->auto) {
                $friendlyName = strtolower(strtr($section->name, ' ', '_'));
                $friendlyDiv  = (isset($section->div_id) ?  $section->div_id : $friendlyName);
                $firstClass   = (
                                 ("#wpcsl-option-{$friendlyDiv}" == $selectedNav) ||
                                 ($firstOne && ($selectedNav == ''))
                                )?
                                ' first current open' :
                                '';
                $firstOne = false;

                print "<li class='top-level general {$firstClass}'>"       .
                      '<div class="arrow"><div></div></div>'            .
                      '<span class="icon"></span>'                      .
                      "<a href='#wpcsl-option-{$friendlyDiv}' "     .
                            "title='{$section->name}'>"                 .
                      $section->name                                    .
                      '</a>'                                            .
                      '</li>'
                    ;
            }
        }
        print '</ul>';
        if (!$this->no_save_button) { print '<div class="navsave">'.$this->generate_save_button_string().'</div>'; }
        print '</div>';


        // Content Area
        //
        print '<div id="content">';

        // Show the plugin environment and info section on every plugin
        //
        if ($this->render_csl_blocks && isset($this->sections['Plugin News'])) {
            $this->sections['Plugin News']->display();
        }

        // Draw each settings section as defined in the plugin config file
        //
        $firstClass = true;
        foreach ($this->sections as $section) {
            if ($section->auto) {
                if ($firstClass) {
                    $section->first = true;
                    $firstClass = false;
                }
                $section->display();
            }
        }

        // Show the plugin environment and info section on every plugin
        //
        if ($this->render_csl_blocks && isset($this->sections['Plugin Environment'])) {
            $this->sections['Plugin Environment']->display();
        }

        // Close Content
        //
        print '</div>';

        // Close Main
        //
        print '</div>';

        $this->footer();
    }

    /**
     * Output the settings page header HTML
     */
    function header() {
        $selectedNav = isset($_REQUEST['selected_nav_element'])?$_REQUEST['selected_nav_element']:'';
        print '<div id="wpcsl_container" class="wrap">';
        screen_icon(preg_replace('/\W/','_',$this->name));
        print
            "<h2>{$this->name}</h2>"                                                            .
            "<form method='post' "                                                              .
                "action='{$this->form_action}' "                                                .
                ( ( $this->form_name    !== '' ) ? "id='{$this->form_name}' "           : '' )  .
                ( ( $this->form_name    !== '' ) ? "name='{$this->form_name}' "         : '' )  .
                ( ( $this->form_enctype !== '' ) ? "enctype='{$this->form_enctype}' "   : '' )  .
                ">"                                                                             .
            "<input type='hidden' "                                                             .
                "id='selected_nav_element' "                                                    .
                "name='selected_nav_element' "                                                  .
                "value='{$selectedNav}' "                                                       .
                "/>"                                                                            ;
        print settings_fields($this->prefix.'-settings');
    }

    /**------------------------------------
     ** method: footer
     **
     **/
    function footer() {
        print 
              $this->generate_save_button_string() .
             '</form></div>';
    }
        
    /**------------------------------------
     ** method: generate_save_button_string
     **
     **/
    function generate_save_button_string() {
        if ($this->no_save_button) { return ''; }
        return sprintf('<input type="submit" class="button-primary" value="%s" />',
         $this->save_text
         );                    
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

if (class_exists('wpCSL_settings_group') == false) {

    /**
     * Manage sections of admin settings pages.
     *
     * @package wpCSL\Settings\Group
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 Charleston Software Associates, LLC
     *
     * @property string $header the header
     * @property string $intro the starting text
     * @property \wpCSL_plugin__slplus parent wpCSL object
     * @property string $slug the slug
     */
    class wpCSL_settings_group {
        public $css_prefix;
        public $intro;
        public $items;
        public $header;
        public $parent;
        public $plugin_url;
        public $slug;

        /**
         * Instantiate a group.
         *
         * @param mixed[] $params
         */
        function __construct($params) {
            foreach ($params as $name => $value) {
                $this->$name = $value;
            }
            $this->parent->debugMP('wpcsl.settings','msg',
                    "Created group {$params['slug']} in section {$this->parent->name}.",
                    '',NULL,NULL,true);
        }

        /**
         * Add an item to the group.
         * 
         * @param mixed[] $params
         */
        function add_item($params) {
            $this->parent->debugMP('wpcsl.settings','msg','',"settings group added {$params['name']} item",NULL,NULL,true);
            $this->items[] = new wpCSL_settings_item__slplus(
                array_merge(
                    $params,
                    array(
                          'parent'     => $this->parent,
                          'plugin_url' => $this->plugin_url,
                          'css_prefix' => $this->css_prefix,
                          )
                )
            );
        }

        /**
         * Render a group.
         */
        function render_Group() {
            $this->parent->debugMP('wpcsl.settings','msg','',
                    "render_Group {$this->slug}",
                    NULL,NULL,true);
            $this->render_Header();
            if (isset($this->items)) {
                foreach ($this->items as $item) {
                    $item->display();
                }
            }
            $this->render_Footer();
        }

        /**
         * Output the group footer.
         */
        function render_Footer() {
            print '</div>';
        }

        /**
         * Output the group header.
         */
        function render_Header() {
            print
                "<div class='section_column wpcsl-group' id='wpcsl_settings_group-{$this->slug}'>" .
                "<h2>{$this->header}</h2>" .
                (
                    ($this->intro != '')                                                                                   ?
                    "<div class='section_column_intro' id='wpcsl_settings_group_intro-{$this->slug}'>{$this->intro}</div>" :
                    ''
                )
                ;
        }
    }

}

/**
 * Manage sections of admin settings pages.
 *
 * @package wpCSL\Settings\Section
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 Charleston Software Associates, LLC
 */
class wpCSL_settings_section__slplus {

    //-----------------------------
    // Properties
    //-----------------------------

    /**
     *
     * @var boolean $auto
     */
    public $auto = true;

    /**
     * The ID to go in the div.
     * 
     * @var string $div_id
     */
    public $div_id;

    /**
     * True if the first rendered section on the panel.
     * 
     * @var boolean $first
     */
    public $first = false;

    /**
     *
     * @var boolean $headerbar
     */
    private $headerbar = true;

    /**
     *
     * @var boolean $innerdiv
     */
    private $innerdiv = true;

    /**
     * True if this is a top-of-page menu.
     * 
     * @var boolean $is_topmenu
     */
    public $is_topmenu = false;

    /**
     * The collection of section items that are in this section.
     * 
     * @var \wpCSL_settings_group $groups
     */
    private $groups;

    /**
     * The title of the section.
     *
     * @var string $name
     */
    public $name;

    /**
     * The main settings parent.
     *
     * @var \wpCSL_settings__slplus $parent
     */
    public $parent;

    /**
     * Start "open" or collapsed.
     *
     * @var boolean $start_collapsed
     */
    private $start_collapsed = false;

    //-----------------------------
    // Methods
    //-----------------------------

    /**
     * Instantiate a section panel.
     * 
     * @param mixed[] $params
     */
    function __construct($params) {
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }
        $this->parent->debugMP('wpcsl.settings','msg',"Created section {$params['name']}",'',NULL,NULL,true);
    }

    /**
     * Add an item to a section.
     * 
     * @param string $params
     */
    function add_item($params) {        
        
        // Manage Groups
        //
        if (empty($params['group'])) { $params['group'] = 'Settings'; }
        $groupSlug = strtolower(str_replace(' ','_',$params['group']));
        if (!isset($this->groups[$groupSlug])) {
            $this->groups[$groupSlug] =
                    new wpCSL_settings_group(
                                array(
                                    'parent'    => $this->parent,
                                    'slug'      => $groupSlug,
                                    'header'    => $params['group'],
                                    'intro'     => isset($this->description)?$this->description:''
                                )
                            );
            $this->description = '';
        }

        $this->groups[$groupSlug]->add_item($params);
    }

    /**
     * Register the setting.
     *
     * @param mixed[] $prefix
     * @return mixed false if not items
     */
    function register($prefix) {
        if (!isset($this->items)) return false;
        foreach ($this->items as $item) {
            $item->register($prefix);
        }
    }

    /**
     * Render a section panel.
     *
     * Panels are rendered in the order they are put in the stack, FIFO.
     */
    function display() {
        $this->header();
        if (isset($this->groups)) {
            foreach ($this->groups as $group) {
                $group->render_Group();
            }
        }
        $this->footer();
    }

    /**
     * Render a section header.
     */
    function header() {
        $friendlyName = strtolower(strtr($this->name, ' ', '_'));
        $friendlyDiv  = (isset($this->div_id) ?  $this->div_id : $friendlyName);
        $groupClass   = $this->is_topmenu?'':'group';

        echo '<div '                                        .
            "id='wpcsl-option-{$friendlyDiv}' "                          .
            "class='{$groupClass}' "  .
            "style='display: block;' "             .
            ">";
        
        if ($this->headerbar) {
            echo "<h1 class='subtitle'>{$this->name}</h1>";
        }             

        if ($this->innerdiv) {
            echo "<div class='inside section' " .
                    (isset($this->start_collapsed) && $this->start_collapsed ? 'style="display:none;"' : '') .
                    ">";
            if (!empty($this->description)) { print "<div class='section_description'>"; }
         }         
         if (!empty($this->description)) { echo $this->description; }
         if ($this->innerdiv) {         
            if (!empty($this->description)) { echo '</div>'; }
         }
    }

    /**
     * Should the section be show (display:block) now?
     * 
     * @return boolean
     */
    function show_now() {
        return ($this->first || $this->is_topmenu);
    }

    /**
     * Render a section footer.
     */
    function footer() {
        if ($this->innerdiv) {
            echo '</table></div>';
        }
        echo '</div>';
    }

}

/**
 * This class manages individual settings on the admin panel settings page.
 *
 * Items go inside sections.
 *
 * @package wpCSL\Settings\Item
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 Charleston Software Associates, LLC
 */
class wpCSL_settings_item__slplus {


    //-------------------------
    // Properties
    //-------------------------

    /**
     * The name attribute for an input item.
     *
     * @var string $name
     */
    private $name;

    /**
     *
     * @var \wpCSL_settings_section_
     */
    private $parent;

    /**
     * The onChange JavaScript for an input item.
     * 
     * @var string $onChange
     */
    private $onChange;

    /**
     * What comes after the label during rendering.
     *
     * @var string $post_label
     */
    private $post_label = ':';

    /**
     * Show a label for this entry when rendered?
     *
     * @var boolean $show_label
     */
    private $show_label = true;

    /**
     * Value of item to be selected for a drop down object.
     *
     * @var string $selectedVal
     */
    private $selectedVal = '';

    /**
     * What type of item is it?
     *
     * Values: checkbox, custom (default), dropdown/list, slider, subheader, submitbutton, text, textarea
     *
     * @var string $type
     */
    private $type = 'custom';

    //-------------------------
    // Methods
    //-------------------------

    /**
     * Constructor.
     *
     * @param mixed[] $params
     */
    function __construct($params) {
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     *
     * @param string $prefix
     */
    function register($prefix) {
        register_setting( $prefix.'-settings', $this->name );
    }

    /**
     * Render the item to the page.
     *
     */
    function display() {
        $optVal = get_option($this->name);
        $optVal = is_array($optVal)?print_r($optVal,true):$optVal;
        $showThis = htmlspecialchars((isset($this->value)?$this->value:$optVal));

        echo '<div class="wpcsl-setting">';

        // Show label wrapper.
        //
        $disabledClass = ($this->disabled?'disabled':'');
        echo "<div class='wpcsl-input wpcsl-{$this->type} {$disabledClass}'>";
        if ($this->show_label) {
            $requiredMark  = ($this->required?' * ':'');
            echo "<label for='{$this->name}'>{$this->display_name}{$requiredMark}{$this->post_label}</label>";
        }

        // Type Processing
        //
        switch ($this->type) {
            case 'textarea':
                echo '<textarea name="'.$this->name.'" '.
                    'cols="50" '.
                    'rows="5" '.
                    ($this->disabled?'disabled="disabled" ':'').
                    '>'.$showThis .'</textarea>';
                break;

            case 'text':
                echo '<input type="text" name="'.$this->name.'" '.
                    ($this->disabled?'disabled="disabled" ':'').                
                    'value="'. $showThis .'" />';
                break;

            case 'checkbox':
                echo '<input type="checkbox" name="'.$this->name.'" '.
                    ($this->disabled?'disabled="disabled" ':'').                
                    ($showThis?' checked' : '').'>';
                break;

            case 'slider':
                $setting = $this->name;
                $label   = '';
                $checked = ($showThis ? 'checked' : '');
                $onClick = 'onClick="'.
                    "jQuery('input[id={$setting}]').prop('checked',".
                        "!jQuery('input[id={$setting}]').prop('checked')" .
                        ");".
                    '" ';

                echo
                    "<input type='checkbox' id='$setting' name='$setting' style='display:none;' $checked>" .
                    "<div id='{$setting}_div' class='onoffswitch-block'>" .
                    "<span class='onoffswitch-pretext'>$label</span>" .
                    "<div class='onoffswitch'>" .
                    "<input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' id='{$setting}-checkbox' $checked>" .
                    "<label class='onoffswitch-label' for='{$setting}-checkbox'  $onClick>" .
                    '<div class="onoffswitch-inner"></div>'.
                    "<div class='onoffswitch-switch'></div>".
                    '</label>'.
                    '</div>' .
                    '</div>'
                    ;

                    if (!$this->parent->slider_rendered) {
                        $this->parent->slider_rendered=true;
                        echo
                            "<style type='text/css'>" .
                                "    .onoffswitch-inner:before { content: '".__('ON','wpcsl') ."'; } " .
                                "    .onoffswitch-inner:after  { content: '".__('OFF','wpcsl')."'; } " .
                            "</style>"
                            ;
                    }

                break;

            // TYPE: subheader
            // Displays  the label (display_name) in a H3 tag with the description in a paragraph below.
            //
            case 'subheader':
                if (!empty($this->display_name)) { echo "<h3>{$this->display_name}</h3>"; }
                echo "<p class='wpcsl_subheader_description' id='{$this->name}_p'>{$this->description}</p>";
                $this->description = null;
                break;

            // TYPE: dropdown
            //
            case 'dropdown':
                echo
                    $this->parent->helper->createstring_DropDownMenu(
                        array(
                            'id'            => $this->name,
                            'name'          => $this->name,
                            'items'         => $this->custom,
                            'onchange'      => $this->onChange,
                            'selectedVal'   => $this->selectedVal,
                        )
                     );
                break;

            // TYPE: list
            //
            case 'list':
                echo $this->createstring_List();
                break;
                
            // TYPE: submit_button
            //
            case 'submit_button':
                echo '<input class="button-primary" type="submit" value="'.$showThis.'">';
                break;                

            // TYPE: custom
            //
            default:
                echo $this->custom;
                break;

        }

        // Close show label wrapper.
        //
        echo '</div>';

        // Required Icon
        //
        if ($this->required) {
            echo ((get_option($this->name) == '') ?
                '<div class="'.$this->css_prefix.'-reqbox">'.
                    '<div class="'.$this->css_prefix.'-reqicon"></div>'.
                    '<div class="'.$this->css_prefix.'-reqtext">This field is required.</div>'.
                '</div>'
                : ''
                );
        }

        // Help text via description.
        //
        if ($this->description != null) {
            print $this->parent->helper->CreateHelpDiv($this->name,$this->description);
        }

        // Close the div.
        //
        echo '</div>';
    }

    /**
     * Create the drop down for the 'list' input types.
     *
     * If $type is 'list' then $custom is a hash used to make a <select>
     * drop-down representing the setting.  This function returns a
     * string with the markup for that list.
     *
     * The selected value will use the get_option() on the name of the drop down,
     * with a default being allowed in the $value parameter.
     *
     * @return string
     */
    function createstring_List() {
        $content =
            "<select class='csl_select' ".
                "name='".$this->name."' ".
                'onChange="'.$this->onChange.'" '.
                "/>"
                ;
        $selectMatch = get_option($this->name, $this->value);

        foreach ($this->custom as $key => $value) {
            if ($selectMatch === $value) {
                $content .= "<option class='csl_option' value=\"$value\" " .
                    "selected=\"selected\">$key</option>\n";
            } else {
                $content .= "<option class='csl_option'  value=\"$value\">$key</option>\n";
            }
        }

        $content .= "</select>\n";

        return $content;
    }
}
