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
require_once("classes/Utils.php");
require_once("include/smarty.resource.string.php");

// Check if Smarty installed, if not redirect user to web-base installation
if (Dependencies::check("Smarty", $errmsg)) {
    // Load Smarty library
    require_once('lib/smarty/Smarty.class.php');
} else {
    // Build the system_path for the auth-server
    print "Redirecting to Wifidog web-based install script since Smarty is missing (Error was: $errmsg)<META HTTP-EQUIV=Refresh CONTENT=\"5; URL=".BASE_URL_PATH."/install.php\">";
    exit();
}

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:    modifier
 * Name:    fsize_format
 * Version:    0.2
 * Date:    2003-05-15
 * Author:    Joscha Feth, joscha@feth.com
 * Purpose: formats a filesize (in bytes) to human-readable format
 * Usage:    In the template, use
 {$filesize|fsize_format}    =>    123.45 B|KB|MB|GB|TB
 or
 {$filesize|fsize_format:"MB"}    =>    123.45 MB
 or
 {$filesize|fsize_format:"TB":4}    =>    0.0012 TB
 * Params:
 int        size            the filesize in bytes
 string    format            the format, the output shall be: B, KB, MB, GB or TB
 int        precision        the rounding precision
 string    dec_point        the decimal separator
 string    thousands_sep    the thousands separator
 * Install: Drop into the plugin directory
 * Version:
 *            2003-05-15    Version 0.2    - added dec_point and thousands_sep thanks to Thomas Brandl, tbrandl@barff.de
 *                                    - made format always uppercase
 *                                    - count sizes "on-the-fly"
 *            2003-02-21    Version 0.1    - initial release
 * -------------------------------------------------------------
 */
function smarty_modifier_fsize_format($size,$format = '',$precision = 2, $dec_point = ".", $thousands_sep = ",")
{
    $format = strtoupper($format);

    static $sizes = array();

    if(!count($sizes)) {
        $b = 1024;
        $sizes["B"]        =    1;
        $sizes["KB"]    =    $sizes["B"]  * $b;
        $sizes["MB"]    =    $sizes["KB"] * $b;
        $sizes["GB"]    =    $sizes["MB"] * $b;
        $sizes["TB"]    =    $sizes["GB"] * $b;

        $sizes = array_reverse($sizes,true);
    }

    //~ get "human" filesize
    foreach($sizes    AS    $unit => $bytes) {
        if($size > $bytes || $unit == $format) {
            //~ return formatted size
            return    number_format($size / $bytes,$precision,$dec_point,$thousands_sep)." ".$unit;
        } //~ end if
    } //~ end foreach
} //~ end function

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
    public static function getObject() {
        return new self();
    }
    private function __construct()
    {

        // Class Constructor. These automatically get set with each new instance.

        $this->Smarty();
        //Now that we have user-definable templates, we must turn on security
        $this->security = true;
        //pretty_print_r($this->security_settings);
        $this->security_settings['MODIFIER_FUNCS'][] = 'sprintf';
        $this->template_dir = WIFIDOG_ABS_FILE_PATH;
        $this->compile_dir = $this->template_dir . 'tmp/smarty/templates_c/';
        $this->config_dir = $this->template_dir . 'tmp/smarty/configs/';
        $this->cache_dir = $this->template_dir . 'tmp/smarty/cache/';

        /* Register the _ smarty modifier to call the _()
         * PHP function which is the gettext() function
         */
        $this->register_modifier("_","_");
        $this->register_modifier("urlencode","urlencode");
        $this->register_modifier("remove_accents",array('Utils', "remove_accents"));
        $this->register_modifier("fsize_format", "smarty_modifier_fsize_format");

		// register the resource name "string"
		$this->register_resource("string", array("smarty_resource_string_source",
                                       "smarty_resource_string_timestamp",
                                       "smarty_resource_string_secure",
                                       "smarty_resource_string_trusted"));
        $this->caching = false;
        //$this->compile_check = true;

	/* Common content */
    $network = Network::GetCurrentNetwork();

	/* Useful stuff from config.php */

	$this->assign('base_url_path', BASE_URL_PATH);
	$this->assign('base_ssl_path', BASE_SSL_PATH);
    $this->assign('base_non_ssl_path', BASE_NON_SSL_PATH);
    $this->assign('common_images_url', COMMON_IMAGES_URL);

     /* Other useful stuff */
     Network::assignSmartyValues($this);
     Node::assignSmartyValues($this);
     User::assignSmartyValues($this);
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


