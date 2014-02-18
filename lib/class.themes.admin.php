<?php

/**
 * The wpCSL Themes Admin Class
 *
 * @package wpCSL\Themes\Admin
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2014 Charleston Software Associates, LLC
 *
 */
class PluginThemeAdmin {

    //-------------------------------------
    // Properties
    //-------------------------------------

    /**
     * The theme CSS directory, absolute.
     *
     * @var string $css_dir
     */
    public $css_dir;

    /**
     * The theme CSS URL, absolute.
     *
     * @var string $css_url
     */
    public $css_url;

    /**
     * The current theme slug.
     * 
     * @var string $current_slug
     */
    private $current_slug;

    /**
     * Plugin notifications system.
     *
     * @var \wpCSL_notifications__slplus $notifications
     */
    public $notifications;

    /**
     * The plugin base object.
     *
     * @var \wpCSL_plugin__slplus $parent
     */
    private $parent;

    /**
     *
     * @var string $plugin_path
     */
    private $plugin_path;

    /**
     * Full web address to this plugin directory.
     *
     * @var string $plugin_url
     */
    private $plugin_url;

    /**
     * The CSS and name space prefix for the plugin.
     *
     * @var string $prefix
     */
    public $prefix;

    /**
     * Full web address to the support web pages.
     *
     * @var string $support_url
     */
    private $support_url;

    /**
     * A named array containing meta data about the CSS theme.
     * 
     * @var mixed[] $themeDetails
     */
    private $themeDetails;

    /**
     * The array of theme meta data option fields in slug => full_text format.
     * 
     * @var mixed[] $theme_options_fields
     */
    private $theme_option_fields;

    //-------------------------------------
    // Methods
    //-------------------------------------

    /**
     * Theme constructor.
     * 
     * @param mixed[] $params named array of properties, only set if property exists.
     */
    function __construct($params) {
        foreach ($params as $property => $value) {
            if ( property_exists($this,$property) ) { $this->$property = $value; }
        }
    }

    /**
     * Build an HTML string to show under the theme selection box.
     * 
     * @return string
     */
    private function createstring_ThemeDetails() {
        $HTML = "<div id='{$this->current_slug}_details' class='theme_details'>";

        // Description
        //
        $HTML .= $this->parent->helper->create_SubheadingLabel(__('About This Theme','wpcsl'));
        if ( empty ( $this->themeDetails[$this->current_slug]['description'] ) ) {
            $HTML .= __('No description has been set for this theme.','wpcsl');
        } else {
            $HTML .= $this->themeDetails[$this->current_slug]['description'];
        }

        // Add On Packs
        //
        if ( ! empty( $this->themeDetails[$this->current_slug]['add-ons'] ) ) {

            // List The Add On Packs Wanted
            //
            $HTML.=
                $this->parent->helper->create_SubheadingLabel(__('Add On Packs','wpcsl')) .
                __('Works best with the following add-on packs enabled: ','wpcsl') .
                '<ul>'
                ;
            $this->parent->set_AvailableAddons();
            $addon_list = explode(',',$this->themeDetails[$this->current_slug]['add-ons']);
            foreach ($addon_list as $slug) {
                $slug = trim(strtolower($slug));
                if ( isset( $this->parent->available_addons[$slug] ) ) {

                    // Active - show name
                    //
                    if ( $this->parent->available_addons[$slug]['active'] ) {
                        $HTML.= '<li>' . $this->parent->available_addons[$slug]['name'].'</li>';

                    // Not Active - show link
                    //
                    } else {
                        $HTML.= '<li><strong>' .
                                $this->parent->available_addons[$slug]['link'].
                                '</strong></li>';
                    }
                }
            }
            $HTML .= '</ul>';

            // Add the options section
            //
            $HTML .= $this->createstring_ThemeOptions();
        }

        $HTML .= '</div>';

        return $HTML;
    }

    /**
     * Create the HTML string that shows the preferred settings section on the Style interface.
     * 
     * @return string
     */
    private function createstring_ThemeOptions() {
        $HTML =
            $this->parent->helper->create_SubheadingLabel(__('Preferred Settings','wpcsl')) .
            __('The following settings will make the most of this theme: ','wpcsl') .
            '<br/>'
            ;

        $this->setup_ThemeOptionFields();
        foreach ( $this->theme_option_fields as $option_slug => $option_settings ) {
            if ( ! empty ( $this->themeDetails[$this->current_slug][$option_slug] ) ) {
                $HTML .=
                    '<div class="theme_option"> ' .
                    "<span class='theme_option_label'>{$option_settings['name']}</span>" .
                    "<pre class='theme_option_value' settings_field='{$option_settings['field']}'>" .
                    esc_textarea($this->themeDetails[$this->current_slug][$option_slug]) .
                    '</pre>' .
                    '</div>'
                    ;
            }
        }
        $HTML .= '</ul>';

        // Add An Apply Settings Button
        //
        $save_message = __('Settings have been made. Click Save Settings to activate or the User Exprience tab to cancel.','wpcsl');
        $HTML .=
            '<a href="#" '.
                'class="like-a-button" ' .
                "onClick='AdminUI.set_ThemeOptions(\"$save_message\"); return false;' ".
                '>'.
                __('Set Options','wpcsl').
            '</a>'
            ;

        return $HTML;
    }

    /**
     * Extract the label & key from a CSS file header.
     *
     * @param string $filename - a fully qualified path to a CSS file
     * @return mixed - a named array of the data.
     */
    private function get_ThemeInfo ($filename) {
        $dataBack = array();
        if ($filename != '') {
           $default_headers =
               array(
                'add-ons'       => 'add-ons',
                'description'   => 'description',
                'file'          => 'file',
                'label'         => 'label',
               );
           $all_headers = $this->setup_PluginThemeHeaders($default_headers);
           $dataBack = get_file_data($filename,$all_headers,'plugin_theme');
           $dataBack['file'] = preg_replace('/.css$/','',$dataBack['file']);
        }

        return $dataBack;
     }

    /**
     * Add the theme settings to the admin panel.
     *
     * @param mixed[] $settingsObj
     * @return type
     */
    public function add_settings($settingsObj = null,$section=null,$group=null) {
        if ($settingsObj == null) {
            $settingsObj = $this->settings;
        }

        // Exit is directory does not exist
        //
        if (!is_dir($this->css_dir)) {
            if (isset($this->notifications)) {
                $this->notifications->add_notice(
                    2,
                    sprintf( __('The theme directory:<br/>%s<br/>is missing. ', 'wpcsl'), $this->css_dir ) .
                    __( 'Create it to enable themes and get rid of this message.', 'wpcsl' )
                );
            }
            return;
        }

        // The Themes
        // No themes? Force the default at least
        //
        $themeArray = get_option($this->prefix.'-theme_array');
        if (count($themeArray, COUNT_RECURSIVE) < 2) {
            $themeArray = array('Default' => 'default');
        }

        // Check for theme files
        //
        $lastNewThemeDate = get_option($this->prefix.'-theme_lastupdated');
        $newEntry = array();
        if ($dh = opendir($this->css_dir)) {
            while (($file = readdir($dh)) !== false) {

                // If not a hidden file
                //
                if (!preg_match('/^\./',$file)) {
                    $thisFileModTime = filemtime($this->css_dir.$file);

                    // We have a new theme file possibly...
                    //
                    if ($thisFileModTime > $lastNewThemeDate) {
                        $newEntry = $this->get_ThemeInfo($this->css_dir.$file);
                        $themeArray = array_merge($themeArray, array($newEntry['label'] => $newEntry['file']));
                        update_option($this->prefix.'-theme_lastupdated', $thisFileModTime);
                    }
                }
            }
            closedir($dh);
        }


        // Remove empties and sort
        $themeArray = array_filter($themeArray);
        ksort($themeArray);

        // Delete the default theme if we have specific ones
        //
        $resetDefault = false;

        if ((count($themeArray, COUNT_RECURSIVE) > 1) && isset($themeArray['Default'])){
            unset($themeArray['Default']);
            $resetDefault = true;
        }

        // We added at least one new theme
        //
        if ((count($newEntry, COUNT_RECURSIVE) > 1) || $resetDefault) {
            update_option($this->prefix.'-theme_array',$themeArray);
        }


        if ($section==null) { $section = 'Display Settings'; }
        $settingsObj->add_itemToGroup(
                array(
                    'section'       => $section                                     ,
                    'group'         => $group                                       ,
                    'label'         => __('Select A Theme','wpcsl')                 ,
                    'setting'       => 'theme'                                      ,
                    'type'          => 'list'                                       ,
                    'custom'        => $themeArray                                  ,
                    'value'         => 'default'                                    ,
                    'description'   =>
                        __('How should the plugin UI elements look?  ','wpcsl') .
                        sprintf(
                            __('Learn more in the <a href="%s" target="csa">online documentation</a>.','wpcsl'),
                            $this->support_url . 'user-experience/view/themes-custom-css/'
                            ),
                    'onChange'      => "AdminUI.show_ThemeDetails(this);"
                )
            );

        // Add Theme Details Divs
        //
        $settingsObj->add_ItemToGroup(
                array(
                    'section'       => $section     ,
                    'group'         => $group       ,
                    'setting'       => 'themedesc'  ,
                    'type'          => 'subheader'  ,
                    'label'         => '',
                    'description'   => $this->setup_ThemeDetails($themeArray),
                    'show_label'    => false
                ));
    }

    /**
     * Add the theme-specific headers to the get_file_data header processor.
     * 
     * @param string[] $headers
     */
    private function setup_PluginThemeHeaders($headers) {
        $this->setup_ThemeOptionFields();
        $option_headers = array();
        foreach ( $this->theme_option_fields as $option_slug => $option_settings ) {
            $option_headers[$option_slug] = $option_settings['name'];
        }
        return array_merge($headers,$option_headers);
    }

    /**
     * Setup the array of theme meta data options fields.
     */
    private function setup_ThemeOptionFields() {
        if ( count($this->theme_option_fields) > 0 ) { return; }

        $this->theme_option_fields =
            array(
                'PRO.layout'    => array(
                    'name'  => 'Pro Pack Locator Layout',
                    'field' => 'csl-slplus-layout'
                ),
                'EM.layout'    => array(
                    'name'  => 'Enhanced Map Bubble Layout',
                    'field' => 'bubblelayout'
                ),
                'ER.layout'    => array(
                    'name'  => 'Enhanced Results Results Layout',
                    'field' => 'csl-slplus-ER-options[resultslayout]'
                ),
                'ES.layout'    => array(
                    'name'  => 'Enhanced Search Search Layout',
                    'field' => 'csl-slplus-ES-options[searchlayout]'
                ),
            );
    }

    /**
     * Create the details divs for the SLP themes.
     *
     * @param mixed[] $themeArray
     * @return string the div HTML
     */
    private function setup_ThemeDetails($themeArray) {
        $this->parent->debugMP('wpcsl.main','msg','PluginTheme::'.__FUNCTION__);
        $HTML = '';
        $newDetails = false;

        // Get an array of metadata for each theme present.
        //
        $this->themeDetails = get_option($this->prefix.'-theme_details');

        // Check all our themes for details
        //
        foreach ($themeArray as $label=>$theme_slug) {

            // No details? Read from the CSS File.
            //
            if (
                !isset($this->themeDetails[$theme_slug]) || empty($this->themeDetails[$theme_slug]) ||
                !isset($this->themeDetails[$theme_slug]['label']) || empty($this->themeDetails[$theme_slug]['label'])
                ) {

                $themeData = $this->get_ThemeInfo($this->css_dir.$theme_slug.'.css');
                $themeData['fqfname'] = $this->css_dir.$theme_slug.'.css';

                $this->themeDetails[$theme_slug] = $themeData;
                $newDetails = true;
            }

            $this->current_slug = $theme_slug;
            $HTML .= $this->createstring_ThemeDetails();
        }

        // If we read new details, go save to disk.
        //
        if ($newDetails) {
            update_option($this->prefix.'-theme_details',$this->themeDetails);
        }

        return $HTML;
    }
}
