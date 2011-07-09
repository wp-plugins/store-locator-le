=== Store Locator LE ===
Plugin Name: Store Locator LE
Contributors: cybersprocket
Donate link: http://www.cybersprocket.com/products/store-locator-le/
Tags: store locator, store locater, google, google maps, dealer locator, dealer locater, zip code search, shop locator, shop finder, zipcode, location finder, places, stores, maps, mapping, mapper, plugin, posts, post, page, coordinates, latitude, longitude, geo, geocoding, shops, ecommerce, e-commerce, business locations, store locator le, store locater le
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.9.52

This plugin puts a search form and an interactive Google map on your site so you can show visitors your store locations.    

== Description ==

This plugin puts a search form and an interactive Google map on your site so you 
can show visitors your store locactions.  Users search for stores within a 
specified radius.  Full admin panel data entry and management of stores from a few to
a few thousand.

= Features =

* You can use it for a variety of countries, as supported by Google Maps.
* Supports international languages and character sets.
* Allows you to use unique map icons or your own custom map icons.
* Change default map settings via the admin panel including:
* Map type (terrain, satellite, street, etc.)
* Inset map show/hide
* Starting zoom level
* You can use miles or kilometers

= Want More? Try Store Locator Plus =

Version 2.0 of [Store Locator Plus](http://www.cyberpsrocket.com/products/store-locator-plus/) has just been released with 78 new features and/or bug fixes. 
The latest 2.0 release includes a new reporting system for tracking what your users are looking for in the map search forms and what results they retrieve.
You also have control over almost every display element of the map including turning on/off the scroll wheel zoom, scale overlay, compass overlay, and more.

Other features that are part of the Plus release that will not be part of the LE version include:

* [Advanced Tag Support](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/Tag_Search) : enter multiple tags on each location, options to search by tags, option to display tag pull down, option to show only results that match a specific tag allowing pages to be keyed to stores only tagged with a specific value.
* [Extended Map
* Settings](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/Map_Settings): Control details about how your map looks, remove the scale, zoom controls, and disable scroll wheel zoom
* [Bulk Upload](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/CSV_Bulk_Uploads) : Upload thousands of locations at one time using the CSV bulk importer.
* [Reporting](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/Reporting) : Find out what people are searching for and what results they are getting back.
* [Country Management](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/)  : records country data, allows for search by country, option to display a country pull down on the search form.
* [WordPress Roles](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/) : A custom role labelled "manage_slp" exists to make it easy to restrict access to your location management when using third party plugins to extend custom roles and capabilities for your users.
* [Starting Image](http://redmine.cybersprocket.com/projects/mc-closeststore/wiki/) : set a default starting image to show in place of the map before a search is performed.
* Bug Fixes: Latest bug fixes come out on the plus edition first, LE is 2-4 weeks behind.

= Want Some Special Features? =

Cyber Sprocket can provide modifications to the plugin to make it the perfect solution for your site.  
We charge $60/hour to create custom additions that we roll into the next product release. 
You get exactly the plugin you want and will have the benefit of having a mainstream product release.
You get the benefit of getting our future upgrades without having to re-apply your patches.

Learn more at: http://www.cybersprocket.com/services/wordpress-developers/

= Related Links =

* [Store Locator Plus](http://www.cyberpsrocket.com/products/store-locator-plus/) 
* [Other Cyber Sprocket Plugins](http://wordpress.org/extend/plugins/profile/cybersprocket/) 
* [Our Facebook Page](http://www.facebook.com/cyber.sprocket.labs)

== Installation ==

= Requirements =

* PHP 5.1+
* SimpleXML enabled (must be enabled manually during install for PHP versions before 5.1.2)

= Main Plugin =

1. Upload the `store-locator-le` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Sign up for a Google Maps API Key for your domain at http://code.google.com/apis/maps/signup.html
4. Add your locations through the 'Add Locations' page in the Store Locator admin panel
5. Place the code '[STORE-LOCATOR]' (case-sensitive) in the body of a page or a post to display your store locator

= Custom CSS (Stylesheet) =

You can modify the default style sheet included with the plugin at 
./css/csl-slplus.css' and place it under `/wp-content/uploads/sl-uploads/custom-css/`. 
The store locator will give priority to the 'csl-slplus.css' in the 'custom-css/' 
folder over the default 'csl-slplus.css' file that is included.  This allows you 
to upgrade the main store locator plugin without worrying about losing your 
custom styling. 

== Frequently Asked Questions ==

= How can i translate the plugin into my language? =

* Find on internet the free program POEDIT, and learn how it works.
* Use the .pot file located in the languages directory of this plugin to create or update the .po and .mo files.
* Place these file in the languages subdirectory.
* If everything is ok, email the files to lobbyjones@cybersprocket.com and we will add them to the next release.
* For more information on POT files, domains, gettext and i18n have a look at the I18n for WordPress developers Codex page and more specifically at the section about themes and plugins.

== Screenshots ==

1. Location Details
2. Basic Address Search
3. All Options Search
6. Admin Map Settings
7. Admin Add Locations
8. Admin Manage Locations
9. UI Map Result With Bubble

== Changelog ==

= 1.9.52 (July 9th 2011) =

* No license error patch.

= 1.9.5 (July 2011) =

* Fixed array index warning.
* Fixed DB Collate error on reporting tables.
* Remove various defunct functions to "lighten" JavaScript load.

= 1.9.4 (June 25th 2011) =

* Some files went missing in WordPress svn kit

= 1.9.2 (June 24th 2011) =

* Feature: Multiple retries available for better geocoding() on bulk or single-item uploads.
* Feature: Improved failed goecode reporting.
* Update: Icon paths have changed - make sure you reset your icons via the map designer.
* Update: Added Republic of Ireland to the countries list.
* Update: Revised map settings interface.
* Fix: conflict with copyr() with other plugins.
* Fix: language file loading.
* Fix: Custom icons are back for Internet Explorer.

= 1.9.1 (June 2011) =

* Added missing data-xml.php, went missing after splitting SL Plus into LE version

= 1.9 (May 2011) =

* Better reporting of failed PHP connector loading.
* More checking & user reporting on failed map interface loading.
* Fix problem with multisite installs where plugin was only installed in parent.
* Updated language file.

= 1.X (prior to public release) =

* Fix broken paths in the config loader.
* Short open tag fix.
* Look for wp-config in secure location (one level up) for secured installs
* Set search form input font to black, the background is currently forced white in the CSS.
* Fix errors on javascript processing on some systems with no subdomain support.
* Better path processing in javascript files to find wp-config.php (fixes missing maps on some installs)
* Rename base php file to prevent "not a valid header" messages.
* Update various links to prevent double-slash and possible URL issues on WAMP systems.
* Debugging mode turns on debugging in store-locator-js.php for JavaScript issues.
* Extended debugging output.
* Fix problem with Map API key not saving.
* Fix problem with subdomain installs not finding store locations.
* Various performance tweaks for page loads:
* ... built-in shortcode processor v. custom regex processor
* ... removed customization backups on each page load
* ... admin panel helper info setup only on settings page call

