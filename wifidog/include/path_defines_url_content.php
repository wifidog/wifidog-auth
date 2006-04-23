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
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/* This section deals with PATHs used in URLs and local content */

/**
 * Define base web address without SLL
 */
define('BASE_NON_SSL_PATH', 'http://'.$_SERVER['SERVER_NAME'].SYSTEM_PATH);

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

/* If we actually ARE in SSL mode, make all URLS http:// to avoid security warnings. */
// no no no just use the SYSTEM_PATH ... use /login/index.php rather than http://auth.wirelesstoronto.ca/login/index.php
// if (isset ($_SERVER['HTTPS'])) {
//    /**
//     * Define base web address to use (this time using SLL)
//     */
//    define('BASE_URL_PATH', BASE_SSL_PATH);
// }
// else {
    /**
     * Define base web address to use
     *
     * @ignore
     */
    define('BASE_URL_PATH', BASE_NON_SSL_PATH);
// }

/**
 * Define URLs
 */

if (empty ($_REQUEST['gw_id'])) {
    /**
     * Define id of current node
     */
    define('CURRENT_NODE_ID', DEFAULT_NODE_ID);
} else {
    /**
     * Define id of current node
     *
     * @ignore
     */
    define('CURRENT_NODE_ID', trim($_REQUEST['gw_id']));
}

/**
 * Define URLs
 */
// define('NODE_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');
define('NODE_CONTENT_URL', SYSTEM_PATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');
define('NODE_CONTENT_PHP_RELATIVE_PATH', LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');

// define('COMMON_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.'common/');
define('COMMON_CONTENT_URL', SYSTEM_PATH.LOCAL_CONTENT_REL_PATH.'common/');

// define('GENERIC_OBJECT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/generic_object_admin.php');
define('GENERIC_OBJECT_ADMIN_ABS_HREF', SYSTEM_PATH.'admin/generic_object_admin.php');
// define('CONTENT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/content_admin.php');
define('CONTENT_ADMIN_ABS_HREF', SYSTEM_PATH.'admin/content_admin.php');

?>
