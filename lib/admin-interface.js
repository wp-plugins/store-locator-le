/**
 * wpCSL admin UI JS
 */
(function($) {

    AdminUI = {
        /**
         * Confirm a message then redirect the user.
         */
        confirmClick: function(message, href) {
            if (confirm(message)) {
                location.href = href;
            }
            else {
                return false;
            }
        },
        /**
         * Perform an action on the specified form.
         */
        doAction: function(theAction, thePrompt, formID, fieldID) {
            formID = typeof formID !== 'undefined' ? formID : 'locationForm';

            if (jQuery('#' + formID).length && jQuery('#' + formID).is('form')) {
                targetForm = '#' + formID;
            } else {
                targetForm = '#' + formID + ' form';
            }

            fieldID = typeof fieldID !== 'undefined' ? fieldID : 'act';
            if ((thePrompt === '') || confirm(thePrompt)) {
                jQuery(targetForm + ' [name="' + fieldID + '"]').attr('value', theAction);
                jQuery(targetForm).submit();
            } else {
                return false;
            }
        },

        /**
         * toggle_nav_tabs()
         *
         */
        toggle_nav_tabs: function() {
            var flip = 0;

            $('#expand_options').click(function() {
                if (flip == 0) {
                    flip = 1;
                    $('#wpcsl_container #wpcsl-nav').hide();
                    $('#wpcsl_container #content').width(785);
                    $('#wpcsl_container .group').add('#wpcsl_container .group h1').show();

                    $(this).text('[-]');

                } else {
                    flip = 0;
                    $('#wpcsl_container #wpcsl-nav').show();
                    $('#wpcsl_container #content').width(595);
                    $('#wpcsl_container .group').add('#wpcsl_container .group h1').hide();
                    $('#wpcsl_container .group:first').show();
                    $('#wpcsl_container #wpcsl-nav li').removeClass('current');
                    $('#wpcsl_container #wpcsl-nav li:first').addClass('current');

                    $(this).text('[+]');

                }

            });
        }, // End toggle_nav_tabs()

        /**
         * load_first_tab()
         */
        load_first_tab: function() {
            $('.group').hide();
            var selectedNav = $('#selected_nav_element').val();
            if ((typeof selectedNav === 'undefined') || (selectedNav == '')) {
                $('.group:has(".section"):first').fadeIn(); // Fade in the first tab containing options (not just the first tab).
            } else {
                $(selectedNav).fadeIn();
            }
        }, // End load_first_tab()

        /**
         * open_first_menu()
         */
        open_first_menu: function() {
            $('#wpcsl-nav li.current.has-children:first ul.sub-menu').slideDown().addClass('open').children('li:first').addClass('active').parents('li.has-children').addClass('open');
        }, // End open_first_menu()

        /**
         * Set the theme options.
         */
        set_ThemeOptions: function(message) {
            jQuery('.theme_details.active > .theme_option > .theme_option_value').each(
                    function(index) {
                        jQuery('[name="' + $(this).attr('settings_field') + '"]').val($(this).text());
                    }
            );
            alert(message);
            return false;
        },
        /**
         * Show the theme details panel and hide the prior active selection.
         * 
         * @returns {undefined}
         */
        show_ThemeDetails: function(current_dropdown) {
            jQuery('.theme_details').hide();
            selected_theme_details = '#' + jQuery('option:selected', current_dropdown).val() + '_details';
            jQuery('.theme_details.active').removeClass('active');
            jQuery(selected_theme_details).show();
            jQuery(selected_theme_details).addClass('active');
        },
        /**
         * toggle_nav_menus()
         */
        toggle_nav_menus: function() {
            $('#wpcsl-nav li.has-children > a').click(function(e) {
                if ($(this).parent().hasClass('open')) {
                    return false;
                }

                $('#wpcsl-nav li.top-level').removeClass('open').removeClass('current');
                $('#wpcsl-nav li.active').removeClass('active');
                if ($(this).parents('.top-level').hasClass('open')) {
                } else {
                    $('#wpcsl-nav .sub-menu.open').removeClass('open').slideUp().parent().removeClass('current');
                    $(this).parent().addClass('open').addClass('current').find('.sub-menu').slideDown().addClass('open').children('li:first').addClass('active');
                }

                // Find the first child with sections and display it.
                var clickedGroup = $(this).parent().find('.sub-menu li a:first').attr('href');

                if (clickedGroup != '') {
                    $('.group').hide();
                    $(clickedGroup).fadeIn();
                }
                return false;
            });
        }, // End toggle_nav_menus()

        /**
         * toggle_collapsed_fields()
         */
        toggle_collapsed_fields: function() {
            $('.group .collapsed').each(function() {
                $(this).find('input:checked').parent().parent().parent().nextAll().each(function() {
                    if ($(this).hasClass('last')) {
                        $(this).removeClass('hidden');
                        return false;
                    }
                    $(this).filter('.hidden').removeClass('hidden');

                    $('.group .collapsed input:checkbox').click(function(e) {
                        AdminUI.unhide_hidden($(this).attr('id'));
                    });

                });
            });
        }, // End toggle_collapsed_fields()

        /**
         * setup_nav_highlights()
         */
        setup_nav_highlights: function() {
            // Highlight the first item by default.
            var selectedNav = $('#selected_nav_element').val();
            if (selectedNav == '') {
                $('#wpcsl-nav li.top-level:first').addClass('current').addClass('open');
            } else {
                $('#wpcsl-nav li.top-level:has(a[href="' + selectedNav + '"])').addClass('current').addClass('open');
            }

            // Default single-level logic.
            $('#wpcsl-nav li.top-level').not('.has-children').find('a').click(function(e) {
                var thisObj = $(this);
                var clickedGroup = thisObj.attr('href');

                if (clickedGroup != '') {
                    $('#selected_nav_element').val(clickedGroup);
                    $('#wpcsl-nav .open').removeClass('open');
                    $('.sub-menu').slideUp();
                    $('#wpcsl-nav .active').removeClass('active');
                    $('#wpcsl-nav li.current').removeClass('current');
                    thisObj.parent().addClass('current');

                    $('.group').hide();
                    $(clickedGroup).fadeIn();

                    return false;
                }
            });

            $('#wpcsl-nav li:not(".has-children") > a:first').click(function(evt) {
                var thisObj = $(this);

                var clickedGroup = thisObj.attr('href');

                if ($(this).parents('.top-level').hasClass('open')) {
                } else {
                    $('#wpcsl-nav li.top-level').removeClass('current').removeClass('open');
                    $('#wpcsl-nav .sub-menu').removeClass('open').slideUp();
                    $(this).parents('li.top-level').addClass('current');
                }

                $('.group').hide();
                $(clickedGroup).fadeIn();

                evt.preventDefault();
                return false;
            });

            // Sub-menu link click logic.
            $('.sub-menu a').click(function(e) {
                var thisObj = $(this);
                var parentMenu = $(this).parents('li.top-level');
                var clickedGroup = thisObj.attr('href');

                if ($('.sub-menu li a[href="' + clickedGroup + '"]').hasClass('active')) {
                    return false;
                }

                if (clickedGroup != '') {
                    parentMenu.addClass('open');
                    $('.sub-menu li, .flyout-menu li').removeClass('active');
                    $(this).parent().addClass('active');
                    $('.group').hide();
                    $(clickedGroup).fadeIn();
                }

                return false;
            });
        }, // End setup_nav_highlights()

        /**
         * init_flyout_menus()
         */
        init_flyout_menus: function() {
            // Only trigger flyouts on menus with closed sub-menus.
            $('#wpcsl-nav li.has-children').each(function(i) {
                $(this).hover(
                        function() {
                            if ($(this).find('.flyout-menu').length == 0) {
                                var flyoutContents = $(this).find('.sub-menu').html();
                                var flyoutMenu = $('<div />').addClass('flyout-menu').html('<ul>' + flyoutContents + '</ul>');
                                $(this).append(flyoutMenu);
                            }
                        },
                        function() {
                        }
                );
            });

            // Add custom link click logic to the flyout menus, due to custom logic being required.
            $('.flyout-menu a').on('click', function(e) {
                var thisObj = $(this);
                var parentObj = $(this).parent();
                var parentMenu = $(this).parents('.top-level');
                var clickedGroup = $(this).attr('href');

                if (clickedGroup != '') {
                    $('.group').hide();
                    $(clickedGroup).fadeIn();

                    // Adjust the main navigation menu.
                    $('#wpcsl-nav li').removeClass('open').removeClass('current').find('.sub-menu').slideUp().removeClass('open');
                    parentMenu.addClass('open').addClass('current').find('.sub-menu').slideDown().addClass('open');
                    $('#wpcsl-nav li.active').removeClass('active');
                    $('#wpcsl-nav a[href="' + clickedGroup + '"]').parent().addClass('active');
                }

                return false;
            });
        }, // End init_flyout_menus()

        /**
         * unhide_hidden()
         */
        unhide_hidden: function(obj) {
            obj = $('#' + obj); // Get the jQuery object.

            if (obj.attr('checked')) {
                obj.parent().parent().parent().nextAll().slideDown().removeClass('hidden').addClass('visible');
            } else {
                obj.parent().parent().parent().nextAll().each(function() {
                    if ($(this).filter('.last').length) {
                        $(this).slideUp().addClass('hidden');
                        return false;
                    }
                    $(this).slideUp().addClass('hidden');
                });
            }
        } // End unhide_hidden()

    }; // End AdminUI Object 

    /**
     * Document Loaded, Run This Stuff...
     */
    $(document).ready(function() {

        // Setup Panel Navigation Elements
        //
        AdminUI.toggle_nav_tabs();
        AdminUI.load_first_tab();
        AdminUI.toggle_collapsed_fields();
        AdminUI.setup_nav_highlights();
        AdminUI.toggle_nav_menus();
        AdminUI.init_flyout_menus();
        AdminUI.open_first_menu();

        // postbox class handlediv turn insdie siblings on/off
        // Defunct(?)
        $('.postbox').children('h3, .handlediv').click(function() {
            $(this).siblings('.inside').toggle();
        });

        // UX View Page Only
        // Expand Theme Divs
        //
        AdminUI.show_ThemeDetails(jQuery('select[name=csl-slplus-theme]'));
        
        // Info Page , Highlight Plugin News
        //
        $('#wpcsl-option-plugin_news_sidemenu').click();

        // Manage Locations, Expand Details
        //
        $('#manage_locations_table tbody tr').click( function(e) {
            if (e.target.type == 'checkbox' ) {
                e.stopPropagation();
            } else {
                var location_id = $(this).attr('data-id');
                var details_div = '#location-details-' + location_id;
                var base_div = '#location-' + location_id;

                jQuery(details_div).toggle();

                var cursor_type = 's-resize';
                if (jQuery(details_div).is(':visible')) {
                    cursor_type = 'n-resize';
                }
                jQuery(base_div).css('cursor', cursor_type);
                jQuery(details_div).css('cursor', cursor_type);
            }
        });
    });

})(jQuery);
