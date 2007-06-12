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
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/* This section deals with PATHs used in URLs and local content
 * BASE_SSL_PATH should be used to enter SSL mode (if available)
 * BASE_NON_SSL_PATH should be used to break out of SSL mode of when we
 * explicitely do not want someting to be referenced over http
 * BASE_URL_PATH should be used in all other cases to avoid needless SSL warning
 * 
 *   */

/**
 * Define base web address without SLL
 */
if ($_SERVER['SERVER_PORT'] != 80) {
    define('BASE_NON_SSL_PATH', 'http://'.$_SERVER['SERVER_NAME'] . ':'.$_SERVER['SERVER_PORT']. SYSTEM_PATH);
} else {
    define('BASE_NON_SSL_PATH', 'http://'.$_SERVER['SERVER_NAME'] . SYSTEM_PATH);
}


//echo "<pre>";print_r($_SERVER);echo "</pre>";

$current_url = 'http';
if ($_SERVER['SERVER_PORT'] == '443') {
    $current_url .= 's';
}
$current_url .= '://'.$_SERVER['HTTP_HOST'];
if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
    $current_url .= ':'.$_SERVER['SERVER_PORT'];
$current_url .= $_SERVER['REQUEST_URI'];

/**
 * Define current request URL
 */
define('CURRENT_REQUEST_URL', $current_url);

if (SSL_AVAILABLE) {
    /**
     * Define base web address to use (this time using SLL)
     */
    define('BASE_SSL_PATH', 'https://'.$_SERVER['SERVER_NAME'].SYSTEM_PATH);
}
else {
    /**
     * Define base web address to use
     *
     * @ignore
     */
    define('BASE_SSL_PATH', BASE_NON_SSL_PATH);
}

/* If we actually ARE in SSL mode, make all URLS https:// to avoid security warnings. */
 if (isset ($_SERVER['HTTPS'])) {
    /**
     * Define base web address to use (this time using SLL)
     */
    define('BASE_URL_PATH', BASE_SSL_PATH);
 }
 else {
    /**
     * Define base web address to use
     *
     * @ignore
     */
    define('BASE_URL_PATH', BASE_NON_SSL_PATH);
 }


/***************************************************************************************************
 *  NOTE:  This stuff has to go elsewhere or be removed as part of the layout system refactoring.   
 * benoitg, 24/04/2006 
 * ***************************************************************************************/

/**
 * Define URLs
 */
define('COMMON_IMAGES_URL', BASE_URL_PATH.'media/common_images/');
define('BASE_THEME_URL', BASE_URL_PATH.'media/base_theme/');

// define('GENERIC_OBJECT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/generic_object_admin.php');
define('GENERIC_OBJECT_ADMIN_ABS_HREF', SYSTEM_PATH.'admin/generic_object_admin.php');
// define('CONTENT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/content_admin.php');
define('CONTENT_ADMIN_ABS_HREF', SYSTEM_PATH.'admin/content_admin.php');

?>
