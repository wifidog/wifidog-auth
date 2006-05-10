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
 * This file deals with PATHs
 *
 * It adds the content of WIFIDOG_ABS_FILE_PATH to PHPs path, so you can
 * reference classes uniformly once this file is included.
 *
 * You should NEVER go to any $_SERVER[] variables for path related stuff.
 *
 * All  you need is already available here.
 *
 * The following constants are defined here:
 *   + DOCUMENT_ROOT:         The absolute filesystem path of the webserver
 *                            document root.  Doesn't really matter much
 *                            what this is anymore.
 *   + SYSTEM_PATH:           The url path to the base /wifidog directory.
 *                            Use "/" if the apache DocumentRoot (DOCUMENT_ROOT)
 *                            is the wifidog directory.  Due to apache
 *                            aliases the SYSTEM_PATH may not correspond
 *                            with any real directory path.
 *   + WIFIDOG_ABS_FILE_PATH: The absolute filesystem path to the /wifidog
 *                            directory.
 *
 * Examples:
 * If you have wifidog installed in <code>/var/www/wifidog-auth</code> and your
 * document root is <code>/var/www/wifidog-auth/wifidog</code>, the constants
 * will have the following values:
 *
 *   + DOCUMENT_ROOT:         /var/www/wifidog-auth/wifidog
 *   + SYSTEM_PATH:           /
 *   + WIFIDOG_ABS_FILE_PATH: /var/www/wifidog-auth/wifidog/
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/*
 * Tests ...
 *

echo '$_SERVER[\'DOCUMENT_ROOT\']: ' . $_SERVER['DOCUMENT_ROOT'] . '<br/>';
// Not always available on Windows

echo '$_SERVER[\'PHP_SELF\']: ' . $_SERVER['PHP_SELF'] . '<br/>';

echo '$_SERVER[\'SCRIPT_NAME\']: ' . $_SERVER['SCRIPT_NAME'] . '<br/>';
// Not always available on Windows

echo '$_SERVER[\'SCRIPT_FILENAME\']: ' . $_SERVER['SCRIPT_FILENAME'] . '<br/>';

echo '$_SERVER[\'REQUEST_URI\']: ' . $_SERVER['REQUEST_URI'] . '<br/>';
// Not useable because of index.php ...

echo '__FILE__: ' . __FILE__ . '<br/>';
// Problem if document root is a symlink

echo '$_SERVER[\'PATH_TRANSLATED\']: ' . $_SERVER['PATH_TRANSLATED'] . '<br/>';
// Not always available with Apache 2 ...

echo "<br/>";

 *
 *
 *

// This will never work for subdirectories
$path_tmp = strstr($_SERVER['SCRIPT_FILENAME'], $_SERVER['PHP_SELF']);
$pos = strrpos($path_tmp, '/');
$path_tmp = substr($path_tmp, 0, $pos + 1);
define('SYSTEM_PATH', $path_tmp);

 *
 * End of tests ...
 */

if (!defined('DOCUMENT_ROOT') || !defined('SYSTEM_PATH') || !defined('WIFIDOG_ABS_FILE_PATH')) {
  /**
   * Detect wifidog-auth directory base
   */

  // the name of this file's parent directory is the wifidog root.
  // that's a constant, ok, unless this file moves up or down in the hierarchy.
  $wifidog_base = dirname(dirname(__FILE__));

  $browser_url = $_SERVER['SCRIPT_NAME']; // browser url to the script
  $apache_path = $_SERVER['SCRIPT_FILENAME']; // system path to the very same script
  while ($browser_url != "" && $browser_url != "/" && $apache_path != $wifidog_base) {
    // find the URI that maps to the wifidog base.
    // figure out the difference between the browser's url, the file system path, and $wifidog_base,
    // piece by piece, starting on the right.
    // The point at which they diverge defines our DOCUMENT_ROOT and SYSTEM_PATH
    // note: forget about apache's "DOCUMENT_ROOT".  there may be apache ALIASES!!!
    $url_piece = basename($browser_url);
    $path_piece = basename($apache_path);
    if ($url_piece != $path_piece) break;

    $browser_url = dirname($browser_url);
    $apache_path = dirname($apache_path);
  }

  // assert: we have found the point at which the two paths diverge:
  // original $browser_url is SYSTEM_PATH + common path to the current script
  // original $apache_path is DOCUMENT_ROOT + common path to the current script
  // and, SYSTEM_PATH is not equal to DOCUMENT_ROOT
  if (substr($apache_path,-1,1) == '/' && $apache_path != '/') $apache_path = substr($apache_path,0,-1);
  if (!defined('DOCUMENT_ROOT')) define('DOCUMENT_ROOT', $apache_path);
  if ($browser_url == "" || substr($browser_url,-1,1) != '/') $browser_url .= '/';
  if (!defined('SYSTEM_PATH')) define('SYSTEM_PATH', $browser_url);

  //if (!defined('WIFIDOG_ABS_FILE_PATH')) define('WIFIDOG_ABS_FILE_PATH', $apache_path . "/");
  if (!defined('WIFIDOG_ABS_FILE_PATH')) define('WIFIDOG_ABS_FILE_PATH', $wifidog_base . "/");
}

/*
 * Debug output
 *

echo "SYSTEM_PATH: " . SYSTEM_PATH . "<br/>";
echo "DOCUMENT_ROOT: " . DOCUMENT_ROOT . "<br/>";
echo "WIFIDOG_ABS_FILE_PATH: " . WIFIDOG_ABS_FILE_PATH . "<br/>";
exit;

 *
 * End of debug output
 */

/*
 * Add system path of WiFiDog installation to PHPs include path
 */

//set_include_path(DOCUMENT_ROOT.SYSTEM_PATH.PATH_SEPARATOR.get_include_path());
set_include_path(substr(WIFIDOG_ABS_FILE_PATH,0,-1).PATH_SEPARATOR.get_include_path());

?>
