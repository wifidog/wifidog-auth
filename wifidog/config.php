<?php
/* Used by AbstractDb */
define('CONF_DATABASE_HOST',   'localhost');
define('CONF_DATABASE_NAME',   'wifidog');
define('CONF_DATABASE_USER',   'root');
define('CONF_DATABASE_PASSWORD',   '');

/* Normally, the database cleanup routines will be called everytime a portal page is displayed.  If you set this to true, you must set a cron job on the server which will execute the script cron/cleanup.php. */
define('CONF_USE_CRON_FOR_DB_CLEANUP', false);

define("SYSTEM_PATH", '/wifidog/');
define("HOTSPOT_NETWORK_NAME", 'a WifiDog community network');
define("HOTSPOT_NETWORK_URL", 'http://www.ilesansfil.org/wiki/WiFiDog');
define('UNKNOWN_HOSTPOT_NAME', 'Unknown HotSpot');

define("VALIDATION_EMAIL_FROM_ADDRESS", 'validation@yourdomain.org');
define("VALIDATION_EMAIL_SUBJECT", HOTSPOT_NETWORK_NAME.' new user validation');
define("LOST_PASSWORD_EMAIL_SUBJECT", HOTSPOT_NETWORK_NAME.' new password request');
define("LOST_USERNAME_EMAIL_SUBJECT", HOTSPOT_NETWORK_NAME.' lost username request');

if($_SERVER['SERVER_PORT']==80)
  {
    define("BASE_URL_PATH",  'http://' . $_SERVER['SERVER_NAME'] . SYSTEM_PATH);
  } 
else
  {
   define("BASE_URL_PATH",  'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . SYSTEM_PATH);
  }

define('RSS_SUPPORT', true); //If true, MAGPIERSS must be installed in MAGPIE_REL_PATH

/***** You should normally not have to edit anything below this ******/
define('MAGPIE_REL_PATH',  'lib/magpie/');
define("SMARTY_REL_PATH",  'lib/smarty/');
define('NETWORK_RSS_URL', 'http://wifinetnews.com/index.rdf');
define('UNKNOWN_HOTSPOT_RSS_URL', 'http://slashdot.org/index.rss');

define('LOCAL_CONTENT_REL_PATH', 'local_content/');//Path to the directory containing the different node specific directories.  Relative to BASE_URL_PATH

/*These are the file names of the different templates that can be put in the CONTENT_PATH/(node_id)/ folders */
define('STYLESHEET_NAME', 'stylesheet.css');
define('LOGIN_PAGE_NAME', 'login.html');
define('PORTAL_PAGE_NAME', 'portal.html');
define('PAGE_HEADER_NAME', 'header.html');
define('PAGE_FOOTER_NAME', 'footer.html');
define('HOTSPOT_LOGO_NAME', 'hotspot_logo.jpg');
define('HOTSPOT_LOGO_BANNER_NAME', 'hotspot_logo_banner.jpg');

/* Path for files in LOCAL_CONTENT_REL_PATH/common/ */
define('NETWORK_LOGO_NAME', 'network_logo.png');
define('NETWORK_LOGO_BANNER_NAME', 'network_logo_banner.png');
define('WIFIDOG_LOGO_NAME', 'wifidog_logo_banner.png');
define('WIFIDOG_LOGO_BANNER_NAME', 'wifidog_logo_banner.png');

define("DEFAULT_NODE_ID", 'default');
?>
