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
 * @copyright  2004-2005 Benoit Gregoire, Technologies Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

error_reporting(E_ALL);

/**
 * Include configuration file
 */
cmnRequireConfig();

/**
 * Add system path of WiFiDog installation to PHPs include path
 */
 
set_include_path(cmnHomeDir() . PATH_SEPARATOR . get_include_path());

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

require_once('classes/EventLogging.php');
require_once('classes/AbstractDb.php');
require_once('classes/Dependencies.php');
// require_once('classes/Session.php');

global $db;

// $db = AbstractDb::Connect('DEFAULT');
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
define('DEFAULT_CONTENT_PHP_RELATIVE_PATH', LOCAL_CONTENT_REL_PATH.DEFAULT_NODE_ID.'/');

define('NODE_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');
define('NODE_CONTENT_PHP_RELATIVE_PATH', LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');

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
    $db->execSqlUpdate("UPDATE connections SET token_status='".TOKEN_USED."' WHERE last_updated < '$expiration' AND token_status = '".TOKEN_INUSE."'", false);
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

/** pop directory path */
function cmnPopDir($dirname=null, $popcount=1) {
  if (empty($dirname)) $dirname = dirname($_SERVER['PHP_SELF']);
  if ($dirname === DIRECTORY_SEPARATOR) return DIRECTORY_SEPARATOR;
  if (substr($dirname,-1,1) === DIRECTORY_SEPARATOR) $popcount++;

  $popped = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $dirname), 0, -$popcount));

  return empty($popped) ? DIRECTORY_SEPARATOR : substr($popped,-1,1) === DIRECTORY_SEPARATOR ? $popped :
    $popped . DIRECTORY_SEPARATOR;
}

function cmnDirectorySlash($dirname) {
  return empty($dirname) ? DIRECTORY_SEPARATOR : substr($dirname,-1,1) === DIRECTORY_SEPARATOR ? $dirname :
    $dirname . DIRECTORY_SEPARATOR;
}

/** search parent directory hierarchy for a file */
function cmnSearchParentDirectories($dirname, $searchfor) {
  $pieces = explode(DIRECTORY_SEPARATOR, $dirname);
  $is_absolute = substr($dirname,0,1) === DIRECTORY_SEPARATOR ? 1 : 0;

  for ($i=count($pieces); $i > $is_absolute; $i--) {
    $filename = implode(DIRECTORY_SEPARATOR, array_merge(array_slice($pieces,0,$i), array($searchfor)));
    if (file_exists($filename)) return $filename;
  }

  return false;
}

/** get the execution home directory */
function cmnHomeDir() {
  if (defined('BASEPATH')) {
    // the old way of setting the home directory
    $basedir = constant('BASEPATH');
  }
  elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
    // the new way of setting the home directory
    $basedir = $_SERVER["DOCUMENT_ROOT"] . ( defined('SYSTEM_PATH') ? constant('SYSTEM_PATH') : DIRECTORY_SEPARATOR );
  }
  elseif (!empty($_SERVER['PHP_SELF'])) {
    // look to the name of the executing file
    $basedir = dirname($_SERVER['PHP_SELF']);
  }
  else {
    // look to the path name of this file (common.php)
    $basedir = cmnPopDir( dirname(__FILE__) );
  }

  $path_to_config = cmnSearchParentDirectories($basedir, 'config.php');
  if (empty($path_to_config)) $path_to_config = cmnSearchParentDirectories($basedir, 'local.config.php');

  return empty($path_to_config) ? false : cmnDirectorySlash(dirname($path_to_config));
}

/** join file path pieces together */
function cmnJoinPath() {
  $fullpath = '';

  //$arguments = func_get_args();

  for ($i=0; $i < func_num_args(); $i++) {
    $pathelement = func_get_arg($i);
    if ($pathelement=='') continue;

    if ($fullpath=='') $fullpath = $pathelement;
    elseif (substr($fullpath,-1,1)==DIRECTORY_SEPARATOR) {
      if (substr($pathelement,0,1)==DIRECTORY_SEPARATOR)
	$fullpath .= substr($pathelement,1);
      else
	$fullpath .= $pathelement;
    }
    else {
      if (substr($pathelement,0,1)==DIRECTORY_SEPARATOR)
	$fullpath .= $pathelement;
      else
	$fullpath .= DIRECTORY_SEPARATOR . $pathelement;
    }
  }

  return $fullpath;
}

/** find a named file in the include path */
function cmnFindPackage($rel_path, $private=false) {
  $basepath = cmnHomeDir();

  $paths = isset($private) && ($private===true || $private==='PRIVATE') ? array($basepath) :
    explode(PATH_SEPARATOR, get_include_path());

  foreach ($paths as $topdir) {
    $package = cmnJoinPath($topdir, $rel_path);
    if (file_exists($package)) {
      if ($private)
	return $package;
      else
	return $rel_path;
    }
  }

  return false;			// package was not found
}

/** require_once a named file */
function cmnRequirePackage($rel_path, $private=false) {
  $basepath = cmnHomeDir();

  $paths = isset($private) && ($private===true || $private==='PRIVATE') ? array($basepath) :
    explode(PATH_SEPARATOR, get_include_path());

  foreach ($paths as $topdir) {
    $package = cmnJoinPath($topdir, $rel_path);
    if (file_exists($package)) {
      if ($private)
	@require_once $package;
      else
	@require_once $rel_path;

      return true;		// package was found
    }
  }

  return false;			// package was not found
}

/** include_once a named file */
function cmnIncludePackage($rel_path, $private=false) {
  $basepath = cmnHomeDir();

  $paths = isset($private) && ($private===true || $private==='PRIVATE') ? array($basepath) :
    explode(PATH_SEPARATOR, get_include_path());

  foreach ($paths as $topdir) {
    $package = cmnJoinPath($topdir, $rel_path);
    if (file_exists($package)) {
      if ($private)
	@include_once $package;
      else
	@include_once $rel_path;

      return true;		// package was found
    }
  }

  return false;			// package was not found
}

function cmnRequireConfig($config_file='config.php') {
  global $AVAIL_LOCALE_ARRAY;	// so that nobody has to change their custom config.php
  $config_path = cmnSearchParentDirectories(dirname(__FILE__), $config_file);
  if (!empty($config_path)) require_once($config_path);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
