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
 * @copyright  2004-2005 Benoit Gregoire <bock@step.polymtl.ca> - Technologies Coeus
 * inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

error_reporting(E_ALL);

require_once BASEPATH.'config.php';

function undo_magic_quotes()
{
    if (get_magic_quotes_gpc())
    {
        $_GET = array_map_recursive('stripslashes', $_GET);
        $_POST = array_map_recursive('stripslashes', $_POST);
        $_COOKIE = array_map_recursive('stripslashes', $_COOKIE);
        $_REQUEST = array_map_recursive('stripslashes', $_REQUEST);
    }
}

if (!function_exists('array_map_recursive'))
{
    function array_map_recursive($function, $data)
    {
        foreach ($data as $i => $item)
        {
            $data[$i] = is_array($item) ? array_map_recursive($function, $item) : $function ($item);
        }
        return $data;
    }
}
undo_magic_quotes();

require_once BASEPATH.'classes/AbstractDb.php';
require_once BASEPATH.'classes/Dependencies.php';
require_once BASEPATH.'classes/Session.php';

global $db;
$db = new AbstractDb();

/* NEVER edit these, as they mush match the C code of the gateway */
define('ACCOUNT_STATUS_ERROR', -1);
define('ACCOUNT_STATUS_DENIED', 0);
define('ACCOUNT_STATUS_ALLOWED', 1);
define('ACCOUNT_STATUS_VALIDATION', 5);
define('ACCOUNT_STATUS_VALIDATION_FAILED', 6);
define('ACCOUNT_STATUS_LOCKED', 254);

$account_status_to_text[ACCOUNT_STATUS_ERROR] = "Error";
$account_status_to_text[ACCOUNT_STATUS_DENIED] = "Denied";
$account_status_to_text[ACCOUNT_STATUS_ALLOWED] = "Allowed";
$account_status_to_text[ACCOUNT_STATUS_VALIDATION] = "Validation";
$account_status_to_text[ACCOUNT_STATUS_VALIDATION_FAILED] = "Validation Failed";
$account_status_to_text[ACCOUNT_STATUS_LOCKED] = "Locked";

define('TOKEN_UNUSED', 'UNUSED');
define('TOKEN_INUSE', 'INUSE');
define('TOKEN_USED', 'USED');

$token_to_text[TOKEN_UNUSED] = "Unused";
$token_to_text[TOKEN_INUSE] = "In use";
$token_to_text[TOKEN_USED] = "Used";

define('STAGE_LOGIN', "login");
define('STAGE_LOGOUT', "logout");
define('STAGE_COUNTERS', "counters");

define('ONLINE_STATUS_ONLINE', 1);
define('ONLINE_STATUS_OFFLINE', 2);

/* This section deals with sessions */

define('SESS_USERNAME_VAR', 'SESS_USERNAME');
define('SESS_USER_ID_VAR', 'SESS_USER_ID');
define('SESS_PASSWORD_HASH_VAR', 'SESS_PASSWORD_HASH');
define('SESS_ORIGINAL_URL_VAR', 'SESS_ORIGINAL_URL');
define('SESS_LANGUAGE_VAR', 'SESS_LANGUAGE');
define('SESS_GW_ADDRESS_VAR', 'SESS_GW_ADDRESS');
define('SESS_GW_PORT_VAR', 'SESS_GW_PORT');
define('SESS_GW_ID_VAR', 'SESS_GW_ID');

/* End */

/* This section deals with PATHs */
define('BASE_NON_SSL_PATH', 'http://'.$_SERVER['SERVER_NAME'].SYSTEM_PATH);

//echo "<pre>";print_r($_SERVER);echo "</pre>";

$curent_url = 'http';
if ($_SERVER['SERVER_PORT'] == '443')
{
    $curent_url .= 's';
}
$curent_url .= '://'.$_SERVER['HTTP_HOST'];
if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
    $curent_url .= ':'.$_SERVER['SERVER_PORT'];
$curent_url .= $_SERVER['REQUEST_URI'];
define('CURRENT_REQUEST_URL', $curent_url);

if (SSL_AVAILABLE)
{
    define('BASE_SSL_PATH', 'https://'.$_SERVER['SERVER_NAME'].SYSTEM_PATH);
}
else
{
    define('BASE_SSL_PATH', BASE_NON_SSL_PATH);
}

/* If we actually ARE in SSL mode, make all URLS http:// to avoid security warnings. */
if (isset ($_SERVER['HTTPS']))
{
    define('BASE_URL_PATH', BASE_SSL_PATH);
}
else
{
    define('BASE_URL_PATH', BASE_NON_SSL_PATH);
}

if (empty ($_REQUEST['gw_id']))
{
    define('CURRENT_NODE_ID', DEFAULT_NODE_ID);
}
else
{
    define('CURRENT_NODE_ID', trim($_REQUEST['gw_id']));
}

define('DEFAULT_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.DEFAULT_NODE_ID.'/');
define('DEFAULT_CONTENT_PHP_RELATIVE_PATH', BASEPATH.LOCAL_CONTENT_REL_PATH.DEFAULT_NODE_ID.'/');

define('NODE_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');
define('NODE_CONTENT_PHP_RELATIVE_PATH', BASEPATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');

define('COMMON_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.'common/');

define('GENERIC_OBJECT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/generic_object_admin.php');
define('CONTENT_ADMIN_ABS_HREF', BASE_URL_PATH.'admin/content_admin.php');

/** Convert a password hash form a NoCat passwd file into the same format as get_password_hash().
* @return The 32 character hash.
*/
function convert_nocat_password_hash($hash)
{
    return $hash.'==';
}

function iso8601_date($unix_timestamp)
{
    $tzd = date('O', $unix_timestamp);
    $tzd = substr(chunk_split($tzd, 3, ':'), 0, 6);
    $date = date('Y-m-d\TH:i:s', $unix_timestamp).$tzd;
    return $date;
}

/** Cleanup dangling tokens and connections from the database, left if a gateway crashed, etc. */
function garbage_collect()
{
    global $db;

    // 10 minutes
    $expiration = time() - 60 * 10;
    $expiration = iso8601_date($expiration);
    $db->ExecSqlUpdate("UPDATE connections SET token_status='".TOKEN_USED."' WHERE last_updated < '$expiration' AND token_status = '".TOKEN_INUSE."'", false);
}

/** Get the url from the local content_specific folder if the file exists, and from the default content folder otherwise */
function find_local_content_url($filename)
{
    //echo "find_local_content_url():  Looking for:                  ".NODE_CONTENT_PHP_RELATIVE_PATH.$filename."<br>\n";
    if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.$filename))
    {
        $retval = NODE_CONTENT_URL.$filename;
    }
    else
    {
        $retval = DEFAULT_CONTENT_URL.$filename;
    }
    //echo "find_local_content_url():  Returned:                  $retval<br>\n";
    return $retval;
}

/** Return a 32 byte guid valid for database use */
function get_guid()
{
    return md5(uniqid(rand(), true));
}

/** like the php function print_r(), but the way it was meant to be... */
function pretty_print_r($param)
{
    echo "\n<pre>\n";
    print_r($param);
    echo "\n</pre>\n";
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
