<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * Configuration file of WiFiDog Authentication Server
 *
 * The configure the WiFiDOG auth server you can either use this configuration
 * file or make a local copy of it named local.config.php.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2005 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2005 Max Horvath, maxspot GmbH
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

/**
 * In case this is the local.config.php you should remove the next lines.
 */

if (file_exists(dirname(__FILE__) . "/local.config.php")) {
    // Use a local copy of the configuration if found instead of the distro's.
    require dirname(__FILE__) . "/local.config.php";
} else {

/**
 * In case this is the local.config.php stop removing the lines.
 */

/********************************************************************\
 * DATABASE CONFIGURATION                                           *
\********************************************************************/

/**
 * Database abstraction classes
 * ============================
 *
 * The next two items are constants, do not edit!
 */

// Database abstraction class for MySQL access.
define('DBMS_MYSQL','AbstractDbMySql.php');

// Database abstraction class for PostgreSQL access.
define('DBMS_POSTGRES','AbstractDbPostgres.php');

/**
 * Which database management software do you want to use?
 * ======================================================
 *
 * Possible values:
 * - DBMS_POSTGRES (Use PostgreSQL server)
 * - DBMS_MYSQL (Use MySQL server)
 *
 * Please note that MySQL support is currently broken!
 */
define('CONF_DBMS', DBMS_POSTGRES);

/**
 * Configuration values needed to access the database.
 * ===================================================
 */

// Host of the database server.
define('CONF_DATABASE_HOST', 'localhost');

// Username for database access.
define('CONF_DATABASE_USER', 'wifidog');

// Password for database access.
define('CONF_DATABASE_PASSWORD', 'wifidogtest');

// Name of database used by WiFiDOG auth server.
define('CONF_DATABASE_NAME', 'wifidog');

/**
 * Database cleanup
 * ================
 *
 * Normally,  the database cleanup routines will be called everytime a portal
 * page is displayed. If you set this to true, you must set a cron job on the
 * server which will execute the script "cron/cleanup.php".
 */
define('CONF_USE_CRON_FOR_DB_CLEANUP', false);

/********************************************************************\
 * WEBSERVER CONFIGURATION                                          *
\********************************************************************/

/**
 * Path of WiFiDOG auth server installation
 * ========================================
 *
 * SYSTEM_PATH must be set to the url path needed to reach the wifidog
 * directory.
 *
 * Normally '/' or '/wifidog/', depending on where configure your
 * document root.
 *
 * Gateway configuration must match this as well.
 */
define('SYSTEM_PATH', '/');

/**
 * Use SSL
 * =======
 *
 * If your webserver has SSL available set this value to true, otherwise,
 * passwords will be transmitted in clear text over the air.
 */
define('SSL_AVAILABLE', false);

/**
 * Caching
 * =======
 *
 * If you installed PEAR::Cache_Lite and set this value to true, caching
 * will be enabled.
 *
 * If you haven't installed PEAR::Cache_Lite, caching won't be enabled at all.
 */
define('USE_CACHE_LITE', false);

/********************************************************************\
 * WIFIDOG BASIC CONFIGURATION                                      *
\********************************************************************/

/**
 * Custom signup system
 * ====================
 *
 * If you wanto to use a custom signup system instead of the built in signup
 * page uncomment the next line and enter the URL to the system.
 */
//define("CUSTOM_SIGNUP_URL","https://www.bcwireless.net/hotspot/signup.php");

/**
 * Available locales (languages)
 * =============================
 *
 * Define the list of locales you want to support.
 * English, French and German are supported.
 *
 * See below examples
 */
global $AVAIL_LOCALE_ARRAY;

$AVAIL_LOCALE_ARRAY = array('fr' => 'Français',
                            'en' => 'English',
                            'de' => 'Deutsch');

/**
 * A lot of linux distributions (Debian, BSD and Mac OS X) use locales like this:
 */
//$AVAIL_LOCALE_ARRAY = array('fr_CA' => 'Français',
//                            'en_US' => 'English',
//                            'de_DE' => 'Deutsch');

/**
 * Other linux distributions use locales like this:
 */
//$AVAIL_LOCALE_ARRAY = array('fr_CA.UTF8' => 'Français',
//                            'en_US.UTF8' => 'English',
//                            'de_DE.UTF8' => 'Deutsch');

/**
 * Default language
 * ================
 *
 * Define the default language of the WiFiDOG auth server.
 *
 * Remember to change this value to a valid locale, i.e.:
 * - fr
 * - fr_CA
 * - fr_CA.UTF8
 */
define('DEFAULT_LANG', 'fr');

/********************************************************************\
 * WIFIDOG FEATURES CONFIGURATION                                   *
\********************************************************************/

/**
 * RSS support
 * ===========
 *
 * If set to true, MAGPIERSS must be installed in MAGPIE_REL_PATH.
 *
 * Normally MAGPIE_REL_PATH is "lib/magpie/".
 */
define('RSS_SUPPORT', true);

/**
 * Flickr Photostream content support
 * ==================================
 *
 * If set to true, Phlickr must be installed in PHLICKR_REL_PATH.
 *
 * Normally PHLICKR_REL_PATH is "lib/", Phlickr being installed in directory
 * "Phlickr".
 */
define('PHLICKR_SUPPORT', false);

/**
 * Google Maps support
 * ===================
 *
 * Enable Google Maps mapping using "hotspots_map.php".
 */
define('GMAPS_HOTSPOTS_MAP_ENABLED', true);

/**
 * Google public API key
 * =====================
 *
 * In order to use the Google API you need to register your domain at Google and
 * enter the given API key.
 *
 * Sign up for an API key here
 * http://www.google.com/apis/maps/
 */
define('GMAPS_PUBLIC_API_KEY', 'ENTER_YOUR_KEY_HERE');

/**
 * Center Coordinates
 * ==================
 *
 * Enter the center coordinates for your the area of your wireless network.
 *
 * The default values are for Montréal, Canada.
 */

// Latitude.
define('GMAPS_INITIAL_LATITUDE', '45.494511');

// Longitude.
define('GMAPS_INITIAL_LONGITUDE', '-73.560285');

// Zoomlevel of the Google Map.
define('GMAPS_INITIAL_ZOOM_LEVEL', '5');

/**
 * XSLT support for Hotspot status page
 * ====================================
 *
 * If you want to enable XSLT support for the Hotspot status page enable this
 * value.
 *
 * Enabling it will let you you display hostpot status in any format.
 * http://server_ip/hotspot_status.php?format=XML&xslt=http://xslt_server/xslt/wifidog_status.xsl
 */
define('XSLT_SUPPORT', true);

/********************************************************************\
 * ADVANCED CONFIGURATION                                           *
 *                                                                  *
 * You should normally not have to edit anything below this!        *
\********************************************************************/

/**
 * WiFiDOG configuration
 * =====================
 *
 * Name and version of the WiFiDOG auth server.
 */
define('WIFIDOG_NAME', 'WiFiDog Authentication server');
define('WIFIDOG_VERSION', 'CVS');

/**
 * WiFiDOG features configuration
 * ==============================
 *
 * Paths to libraries used by the WiFiDOG auth server.
 */

// Path to Magpie RSS Parser.
define('MAGPIE_REL_PATH', 'lib/magpie/');

// Path to Smarty Template engine.
define('SMARTY_REL_PATH',  'lib/smarty/');

// Path to Phlickr API.
define('PHLICKR_REL_PATH',  'lib/');

/**
 * WiFiDOG internals configuration
 * ===============================
 *
 * Internal configuration values for WiFiDog - don't touch!
 */

// Path to the directory containing the different node specific directories.
// Relative to BASE_URL_PATH.
define('LOCAL_CONTENT_REL_PATH', 'local_content/');

// These are the file names of the different templates that can be put in
// the CONTENT_PATH/(node_id)/ folders.
define('STYLESHEET_NAME', 'stylesheet.css');
define('LOGIN_PAGE_NAME', 'login.html');
define('HOTSPOT_STATUS_PAGE', 'hotspot_status.php');

// Source URL for the hotspot status page
define('GMAPS_XML_SOURCE_URL', 'hotspot_status.php?format=XML');

// Path for files in LOCAL_CONTENT_REL_PATH/common/
define('NETWORK_LOGO_NAME', 'network_logo.png');
define('NETWORK_LOGO_BANNER_NAME', 'network_logo_banner.png');
define('WIFIDOG_LOGO_NAME', 'wifidog_logo_banner.png');
define('WIFIDOG_LOGO_BANNER_NAME', 'wifidog_logo_banner.png');

// Name of default node.
define('DEFAULT_NODE_ID', 'default');

/********************************************************************\
 * DEPRECATED VALUES                                                *
\********************************************************************/

/**
 * @deprecated since 2005-04-19
 */
define('PAGE_HEADER_NODE', 'header.html');

/**
 * @deprecated since 2005-04-19
 */
define('PAGE_HEADER_NAME', 'header.html');

/**
 * @deprecated since 2005-04-19
 */
define('PAGE_FOOTER_NODE', 'footer.html');

/**
 * @deprecated since 2005-04-19
 */
define('PAGE_FOOTER_NAME', 'footer.html');

/**
 * @deprecated since 2005-04-19
 */
define('PORTAL_PAGE_NAME', 'portal.html');

/**
 * In case this is the local.config.php you should remove the next lines.
 */

}

/**
 * In case this is the local.config.php stop removing the lines.
 */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>