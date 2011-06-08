=== Store Locator LE ===
Plugin Name: Store Locator LE
Contributors: cybersprocket
Donate link: http://www.cybersprocket.com/products/store-locator-le/
Tags: store locator, store locater, google, google maps, dealer locator, dealer locater, zip code search, shop locator, shop finder, zipcode, location finder, places, stores, maps, mapping, mapper, plugin, posts, post, page, coordinates, latitude, longitude, geo, geocoding, shops, ecommerce, e-commerce, business locations, store locator le, store locater le
Requires at least: 3.0
Tested up to: 3.1.1
Stable tag: 1.9.1

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

= Looking For Customized WordPress Plugins? =

If you are looking for custom WordPress development for your own plugins, give 
us a call.   Not only can we offer competitive rates but we can also leverage 
our existing framework for WordPress applications which reduces development time 
and costs.

Learn more at: http://www.cybersprocket.com/services/wordpress-developers/

= Related Links =

* [Other Cyber Sprocket Plugins](http://wordpress.org/extend/plugins/profile/cybersprocket/) 
* [Custom WordPress Development](http://www.cybersprocket.com/services/wordpress-developers/)
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

== Changelog ==

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

