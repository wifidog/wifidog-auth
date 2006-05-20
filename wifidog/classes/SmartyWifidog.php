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
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once("classes/Locale.php");

// Check if Smarty installed, if not redirect user to web-base installation
if (Dependencies::check("Smarty", $errmsg)) {
    // Load Smarty library
    require_once('lib/smarty/Smarty.class.php');
} else {
    // Build the system_path for the auth-server
    print "Redirecting to Wifidog web-based install script since Smarty is missing (Error was: $errmsg)<META HTTP-EQUIV=Refresh CONTENT=\"5; URL=".BASE_URL_PATH."/install.php\">";
    exit();
}

// The setup.php file is a good place to load
// required application library files, and you
// can do that right here. An example:
// require('guestbook/guestbook.lib.php');

/**
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class SmartyWifidog extends Smarty {

   function __construct()
   {

        // Class Constructor. These automatically get set with each new instance.

        $this->Smarty();

        $this->template_dir = WIFIDOG_ABS_FILE_PATH;
        $this->compile_dir = $this->template_dir . 'tmp/smarty/templates_c/';
        $this->config_dir = $this->template_dir . 'tmp/smarty/configs/';
        $this->cache_dir = $this->template_dir . 'tmp/smarty/cache/';

        /* Register the _ smarty modifier to call the _()
         * PHP function which is the gettext() function
         */
        $this->register_modifier("_","_");

        $this->caching = true;
        $this->compile_check = true;
        $this->assign('app_name','Wifidog auth server');

    /* We need this for various forms to redirect properly (language form) */
    $this->assign('request_uri', $_SERVER["REQUEST_URI"]);

/* Common content */
    $network = Network::GetCurrentNetwork();

/* Useful stuff from config.php */

	$this->assign('base_url_path', BASE_URL_PATH);
	$this->assign('base_ssl_path', BASE_SSL_PATH);
    $this->assign('base_non_ssl_path', BASE_NON_SSL_PATH);
    $this->assign('common_images_url', COMMON_IMAGES_URL);
    $this->assign('hotspot_network_name',$network->getName());
    $this->assign('hotspot_network_url',$network->getHomepageURL());

     $this->assign('hotspot_id', CURRENT_NODE_ID);

     /* Other useful stuff */
     $this->assign('userIsAtHotspot', Node::getCurrentRealNode() != null ? true : false);

     $this->assign('currentLocale', Locale::getCurrentLocale());
   }

   function SetTemplateDir( $template_dir)
   {
     $this->template_dir= $template_dir;
   }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


