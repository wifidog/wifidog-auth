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
 * Common objects which don't need a session for every call and ping from a
 * gateway
 *
 * The purpose of this file is to have common objects and things declared but
 * NOT to have them in common.php because common.php is also used for functions
 * called by the gateway and we do not (for example) want to create a session
 * for every call and pings from the gateways.
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @copyright  2005-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Disable APC cache (in case it has been installed)
 */
if (function_exists("apc_clear_cache")) {
    ini_set("apc.enabled", 0);
}

/**
 * Disable eAccelerator cache (in case it has been installed)
 */
if (function_exists("eaccelerator_rm")) {
    ini_set("eaccelerator.enable", 0);
}

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/Statistics.php');
require_once('classes/SmartyWifidog.php');
require_once('classes/User.php');

$session = new Session();
$stats = new Statistics();
$smarty = new SmartyWifidog;

require_once('include/language.php');

try
{
    $username = null;
    $current_user = User :: getCurrentUser();
    if ($current_user != null)
    {
        $username = $current_user->getUsername();
    }

    $smarty->assign("auth_user", $username);
}
catch (Exception $e)
{
    ;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>