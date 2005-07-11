<?php

if(file_exists(dirname(__FILE__)."/local.config.php")) {
	// use a local copy of the configuration if found instead of the distro's.
	require dirname(__FILE__)."/local.config.php";
} else {

/* Used by AbstractDb */
define('CONF_DATABASE_HOST',   'localhost');
define('CONF_DATABASE_NAME',   'wifidog');
define('CONF_DATABASE_USER',   'wifidog');
define('CONF_DATABASE_PASSWORD',   'wifidogtest');

/*************************** Common setup option.  Adjust to suit your environment *******************************/

/* The SYSTEM_PATH, must be set to the url path needed to reach the wifidog directory.  Normally '/' or '/wifidog/', depending on where configure your document root.  Gateway configuration must match this as well */
define('SYSTEM_PATH', '/');
/**< Set this to true if your server has SSL available, otherwise, passwords will be transmitted in clear text over the air */
define('SSL_AVAILABLE', true);
/** @deprecated version - 2005-04-19 */
define('HOTSPOT_NETWORK_NAME', 'Île sans fil');
define('HOTSPOT_NETWORK_URL', 'http://www.ilesansfil.org/');
define('TECH_SUPPORT_EMAIL', 'tech@ilesansfil.org');
define('UNKNOWN_HOSTPOT_NAME', 'Unknown HotSpot');

define('VALIDATION_GRACE_TIME', 20); /**< Number of minutes after new account creation during which internet access is available to validate your account.  Once elapsed, you have to validate from home... */
define('VALIDATION_EMAIL_FROM_ADDRESS', 'validation@ilesansfil.org');
/* RSS support.  If set to true, MAGPIERSS must be installed in MAGPIE_REL_PATH */
define('RSS_SUPPORT', true);
/* Flickr Photostream content support. If set to true, Phlickr must be installed in PHLICKR_REL_PATH */
define('PHLICKR_SUPPORT', false);
/* Normally, the database cleanup routines will be called everytime a portal page is displayed.  If you set this to true, you must set a cron job on the server which will execute the script cron/cleanup.php. */
define('CONF_USE_CRON_FOR_DB_CLEANUP', false);

/* XSLT for Hotspot status page */
define('XSLT_SUPPORT', false);
/* Google Maps mapping hotspots_map.php */
define('GMAPS_HOTSPOTS_MAP_ENABLED', false);
define('GMAPS_XML_SOURCE_URL', 'hotspot_status.php?format=XML');
define('GMAPS_API_KEY', 'ENTER_YOUR_KEY_HERE');
// Enter center coords ( ie. Montréal )
define('GMAPS_INITIAL_LATITUDE', '45.494511');
define('GMAPS_INITIAL_LONGITUDE', '-73.560285');
define('GMAPS_INITIAL_ZOOM_LEVEL', '5');

/* Use a custom signup system instead of the built in signup page. */
//define("CUSTOM_SIGNUP_URL","https://www.bcwireless.net/hotspot/signup.php");

/** The next two items are constants, do not edit */
define('DBMS_MYSQL','AbstractDbMySql.php');
define('DBMS_POSTGRES','AbstractDbPostgres.php');

/** Defines which Database management software you want to use */
define('CONF_DBMS',DBMS_POSTGRES);

/* Available Locales (Languages) */
$AVAIL_LOCALE_ARRAY=array('fr'=>'Français',
            			'en'=>'English');
            					
/***** You should normally not have to edit anything below this ******/
define('WIFIDOG_NAME', 'WiFiDog Authentication server');
define('WIFIDOG_VERSION', 'CVS');

define('MAGPIE_REL_PATH',  'lib/magpie/');
define('SMARTY_REL_PATH',  'lib/smarty/');
define('PHLICKR_REL_PATH',  'lib/');
//define('NETWORK_RSS_URL', 'http://wifinetnews.com/index.rdf');
define('NETWORK_RSS_URL', 'http://www.ilesansfil.org/tiki-articles_rss.php?ver=2, http://auth.ilesansfil.org/hotspot_status.php?format=RSS');
define('UNKNOWN_HOTSPOT_RSS_URL', '');

define('LOCAL_CONTENT_REL_PATH', 'local_content/');//Path to the directory containing the different node specific directories.  Relative to BASE_URL_PATH

// Authentication sources section
/* The array index for the source must match the account_origin in the user table */

// Local User authenticators
require_once BASEPATH.'classes/AuthenticatorLocalUser.php';

/**********************************************
 * BIG FAT WARNING
 * DO NOT remove this authenticator under any circumstance
 * you SHOULD NOT change its name either
 * The system relies heavily on its main authenticator to do
 * multiple tasks with users...
 * ********************************************
 */
define('LOCAL_USER_ACCOUNT_ORIGIN', 'LOCAL_USER');
$AUTH_SOURCE_ARRAY[LOCAL_USER_ACCOUNT_ORIGIN]=array(
			     'name'=>HOTSPOT_NETWORK_NAME,
			     'authenticator'=>new AuthenticatorLocalUser(LOCAL_USER_ACCOUNT_ORIGIN));
			     
// RADIUS authenticators ( see AuthenticatorRadius constuctor doc for details )
/*
require_once BASEPATH.'classes/AuthenticatorRadius.php';

define('IDRC_ACCOUNT_ORIGIN', 'IDRC_RADIUS_USER');
$AUTH_SOURCE_ARRAY[IDRC_ACCOUNT_ORIGIN]=array(
			     'name'=>"IDRC RADIUS Server",
			     'authenticator'=>new AuthenticatorRadius(IDRC_ACCOUNT_ORIGIN, "192.168.0.11", 1812, 1813, "secret_key", "CHAP_MD5"));
*/

/*These are the file names of the different templates that can be put in the CONTENT_PATH/(node_id)/ folders */
define('STYLESHEET_NAME', 'stylesheet.css');
define('LOGIN_PAGE_NAME', 'login.html');
define('PORTAL_PAGE_NAME', 'portal.html');/**< @deprecated version - 19-Apr-2005*/
define('PAGE_HEADER_NAME', 'header.html');/**< @deprecated version - 19-Apr-2005*/
define('PAGE_FOOTER_NAME', 'footer.html');/**< @deprecated version - 19-Apr-2005*/
define('HOTSPOT_STATUS_PAGE', 'hotspot_status.php');
define('HOTSPOT_LOGO_NAME', 'hotspot_logo.jpg');
define('HOTSPOT_LOGO_BANNER_NAME', 'hotspot_logo_banner.jpg');

/* Path for files in LOCAL_CONTENT_REL_PATH/common/ */
define('NETWORK_LOGO_NAME', 'network_logo.png');
define('NETWORK_LOGO_BANNER_NAME', 'network_logo_banner.png');
define('WIFIDOG_LOGO_NAME', 'wifidog_logo_banner.png');
define('WIFIDOG_LOGO_BANNER_NAME', 'wifidog_logo_banner.png');

define('DEFAULT_NODE_ID', 'default');
define('DEFAULT_LANG', 'fr');


}
?>