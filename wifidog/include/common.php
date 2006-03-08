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
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Include PHP initialization file file
 */
require_once('init_php.php');

/**
 * Include configuration file
 */
cmnRequireConfig();

/**
 * Filter super globals
 */
undo_magic_quotes();

/**
 * Set default timezone
 */
dateFix();

/**
 * Include path detection code
 */
require_once('path_defines_base.php');

/**
 * Load required classes
 */
require_once('classes/EventLogging.php');
require_once('classes/AbstractDb.php');
require_once('classes/Locale.php');
require_once('classes/Dependencies.php');
require_once('classes/Server.php');

global $db;

$db = new AbstractDb();

/**
 * Check for SSL support
 */

if (Server::getCurrentServer(true) != null) {
    if (Server::getCurrentServer(true)->isSSLAvailable()) {
        /**
         * @ignore
         */
        define("SSL_AVAILABLE", true);
    } else {
        /**
         * @ignore
         */
        define("SSL_AVAILABLE", false);
    }
} else {
    define("SSL_AVAILABLE", false);
}

/**
 * Set paths
 */
require_once('path_defines_url_content.php');

/* Constant shared with the gateway
 * NEVER edit these, as they mush match the C code of the gateway */
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

$token_to_text[TOKEN_UNUSED] = _("Unused");
$token_to_text[TOKEN_INUSE] = _("In use");
$token_to_text[TOKEN_USED] = _("Used");

define('STAGE_LOGIN', "login");
define('STAGE_LOGOUT', "logout");
define('STAGE_COUNTERS', "counters");

define('ONLINE_STATUS_ONLINE', 1);
define('ONLINE_STATUS_OFFLINE', 2);
/* End Constant shared with the gateway*/

/* session constants, perhaps this coulb be moved to Session.php?  benoitg, 2005-08-01 */
define('SESS_USERNAME_VAR', 'SESS_USERNAME');
define('SESS_USER_ID_VAR', 'SESS_USER_ID');
define('SESS_PASSWORD_HASH_VAR', 'SESS_PASSWORD_HASH');
define('SESS_ORIGINAL_URL_VAR', 'SESS_ORIGINAL_URL');
define('SESS_LANGUAGE_VAR', 'SESS_LANGUAGE');
define('SESS_GW_ADDRESS_VAR', 'SESS_GW_ADDRESS');
define('SESS_GW_PORT_VAR', 'SESS_GW_PORT');
define('SESS_GW_ID_VAR', 'SESS_GW_ID');
/* End session constants */

/** Convert a password hash form a NoCat passwd file into the same format as get_password_hash().
* @return The 32 character hash.
*/
function convert_nocat_password_hash($hash) {
    return $hash.'==';
}

function iso8601_date($unix_timestamp) {
    $tzd = date('O', $unix_timestamp);
    $tzd = substr(chunk_split($tzd, 3, ':'), 0, 6);
    $date = date('Y-m-d\TH:i:s', $unix_timestamp).$tzd;

    return $date;
}

/** Cleanup dangling tokens and connections from the database, left if a gateway crashed, etc. */
function garbage_collect() {
    global $db;

    // 10 minutes
    $expiration = time() - 60 * 10;
    $expiration = iso8601_date($expiration);
    $db->execSqlUpdate("UPDATE connections SET token_status='".TOKEN_USED."' WHERE last_updated < '$expiration' AND token_status = '".TOKEN_INUSE."'", false);
}

/** Get the url from the local content_specific folder if the file exists, and from the default content folder otherwise */
function find_local_content_url($filename) {
    //echo "find_local_content_url():  Looking for:                  ".NODE_CONTENT_PHP_RELATIVE_PATH.$filename."<br>\n";
    if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.$filename)) {
        $retval = NODE_CONTENT_URL.$filename;
    }
    else {
        $retval = DEFAULT_CONTENT_URL.$filename;
    }
    //echo "find_local_content_url():  Returned:                  $retval<br>\n";
    return $retval;
}

/** Return a 32 byte guid valid for database use */
function get_guid() {
    return md5(uniqid(rand(), true));
}

/** like the php function print_r(), but the way it was meant to be... */
function pretty_print_r($param) {
    echo "\n<pre>\n";
    print_r($param);
    echo "\n</pre>\n";
}

/** pop directory path */
function cmnPopDir($dirname = null, $popcount = 1) {
    if (empty ($dirname))
        $dirname = dirname($_SERVER['PHP_SELF']);
    if ($dirname === DIRECTORY_SEPARATOR)
        return DIRECTORY_SEPARATOR;
    if (substr($dirname, -1, 1) === DIRECTORY_SEPARATOR)
        $popcount ++;

    $popped = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $dirname), 0, - $popcount));

    return empty ($popped) ? DIRECTORY_SEPARATOR : substr($popped, -1, 1) === DIRECTORY_SEPARATOR ? $popped : $popped.DIRECTORY_SEPARATOR;
}

function cmnDirectorySlash($dirname) {
    return empty ($dirname) ? DIRECTORY_SEPARATOR : substr($dirname, -1, 1) === DIRECTORY_SEPARATOR ? $dirname : $dirname.DIRECTORY_SEPARATOR;
}

/** search parent directory hierarchy for a file */
function cmnSearchParentDirectories($dirname, $searchfor) {
    $pieces = explode(DIRECTORY_SEPARATOR, $dirname);
    $is_absolute = substr($dirname, 0, 1) === DIRECTORY_SEPARATOR ? 1 : 0;

    for ($i = count($pieces); $i > $is_absolute; $i --) {
        $filename = implode(DIRECTORY_SEPARATOR, array_merge(array_slice($pieces, 0, $i), array ($searchfor)));
        if (file_exists($filename))
            return $filename;
    }

    return false;
}

/** join file path pieces together */
function cmnJoinPath() {
    $fullpath = '';

    //$arguments = func_get_args();

    for ($i = 0; $i < func_num_args(); $i ++) {
        $pathelement = func_get_arg($i);
        if ($pathelement == '')
            continue;

        if ($fullpath == '')
            $fullpath = $pathelement;
        elseif (substr($fullpath, -1, 1) == DIRECTORY_SEPARATOR) {
            if (substr($pathelement, 0, 1) == DIRECTORY_SEPARATOR)
                $fullpath .= substr($pathelement, 1);
            else
                $fullpath .= $pathelement;
        }
        else {
            if (substr($pathelement, 0, 1) == DIRECTORY_SEPARATOR)
                $fullpath .= $pathelement;
            else
                $fullpath .= DIRECTORY_SEPARATOR.$pathelement;
        }
    }

    return $fullpath;
}

/** find a named file in the include path */
function cmnFindPackage($rel_path, $private = false) {

    $paths = isset ($private) && ($private === true || $private === 'PRIVATE') ? array (WIFIDOG_ABS_FILE_PATH) : explode(PATH_SEPARATOR, get_include_path());

    foreach ($paths as $topdir) {
        $package = cmnJoinPath($topdir, $rel_path);
        if (file_exists($package)) {
            if ($private)
                return $package;
            else
                return $rel_path;
        }
    }

    return false; // package was not found
}

/** require_once a named file */
function cmnRequirePackage($rel_path, $private = false) {

    $paths = isset ($private) && ($private === true || $private === 'PRIVATE') ? array (WIFIDOG_ABS_FILE_PATH) : explode(PATH_SEPARATOR, get_include_path());

    foreach ($paths as $topdir) {
        $package = cmnJoinPath($topdir, $rel_path);
        if (file_exists($package)) {
            if ($private)
                @ require_once $package;
            else
                @ require_once $rel_path;

            return true; // package was found
        }
    }

    return false; // package was not found
}

/** include_once a named file */
function cmnIncludePackage($rel_path, $private = false) {

    $paths = isset ($private) && ($private === true || $private === 'PRIVATE') ? array (WIFIDOG_ABS_FILE_PATH) : explode(PATH_SEPARATOR, get_include_path());

    foreach ($paths as $topdir) {
        $package = cmnJoinPath($topdir, $rel_path);
        if (file_exists($package)) {
            if ($private)
                @ include_once $package;
            else
                @ include_once $rel_path;

            return true; // package was found
        }
    }

    return false; // package was not found
}

function cmnRequireConfig($config_file = 'config.php') {
    global $AVAIL_LOCALE_ARRAY; // so that nobody has to change their custom config.php
    $config_path = cmnSearchParentDirectories(dirname(__FILE__), $config_file);
    if (!empty ($config_path))
        require_once ($config_path);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>