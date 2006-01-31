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

/* This file deals with PATHs.
 *
 * It adds the content of WIFIDOG_ABS_FILE_PATH o PHPs path, so you can
 * reference classes uniformly once this file is included.
 *
 *  You should NEVER go to any $_SERVER[] variables for path related stuff
 * All  you need is already available here and in @see http_and_file_path.php .
 * The following constants are defined here:
 *
 * DOCUMENT_ROOT: The absolute filesystem path of the webserver document root
 *
 * SYSTEM_PATH: The path of the base /wifidog directory relative to the document
 * root,  Also the absolute URI (path after the domain name), but you should use
 * the constants in @see http_and_file_path.php or you will have problems with
 * SSL
 *
 * WIFIDOG_ABS_FILE_PATH: The absolute filesystem path to the /wifidog directory
 *
 * Examples:  If you have wifidog installed in /var/www/wifidog-auth
 and your document root is /var/www/wifidog-auth/wifidog, the constants will have the following values:
 * DOCUMENT_ROOT: /var/www/wifidog-auth/wifidog
 *
 * SYSTEM_PATH: /
 *
 * WIFIDOG_ABS_FILE_PATH: /var/www/wifidog-auth/wifidog/
 *
  *
  *  */
/**
 * Path of WiFiDOG auth server installation
 * ========================================
 *
 * SYSTEM_PATH must be set to the url path needed to reach the wifidog
 * directory.
 *
 * Normally '/' or '/wifidog/', depending on where configure your
 * document root.
 *
 * Gateway configuration must match this as well.
 */
/*
echo '$_SERVER[\'DOCUMENT_ROOT\']: '.$_SERVER['DOCUMENT_ROOT'].'<br/>'; //Not   always available on windows
echo '$_SERVER[\'PHP_SELF\']: '.$_SERVER['PHP_SELF'].'<br/>';
echo '$_SERVER[\'SCRIPT_NAME\']: '.$_SERVER['SCRIPT_NAME'].'<br/>'; //Not always available on windows
echo '$_SERVER[\'SCRIPT_FILENAME\']: '.$_SERVER['SCRIPT_FILENAME'].'<br/>';
echo '$_SERVER[\'REQUEST_URI\']: '.$_SERVER['REQUEST_URI'].'<br/>'; //Not useable because of index.php...
echo '__FILE__: '.__FILE__.'<br/>'; //Problem if document root is a symlink
echo '$_SERVER[\'PATH_TRANSLATED\']: '.$_SERVER['PATH_TRANSLATED'].'<br/>'; //Not always available with apache2...
echo "<br/>";*/

/* This will never work for subdirectories.
    $path_tmp = strstr ( $_SERVER['SCRIPT_FILENAME'], $_SERVER['PHP_SELF']);
    $pos = strrpos($path_tmp, '/');
$path_tmp = substr ( $path_tmp, 0, $pos+1);
 define('SYSTEM_PATH', $path_tmp);
*/
if (!defined('DOCUMENT_ROOT')) {
    define('DOCUMENT_ROOT', substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF'])));
}
$count = 0;
if (!defined('SYSTEM_PATH')) {
    $path_tmp = str_replace(DOCUMENT_ROOT, '', __FILE__, $count);
    if ($count === 0) { // note: three equal signs
        throw new exception(sprintf('Path detection failed (DOCUMENT_ROOT was: %s, __FILE__ was: %s).  You may have to define SYSTEM_PATH manually in your config.php'), DOCUMENT_ROOT, __FILE__);
    }
    $path_tmp = str_replace('include/path_defines_base.php', '', $path_tmp, $count);
    if ($count === 0) { // note: three equal signs
        throw new exception(sprintf('Path detection failed ($path_tmp was: %s).  You may have to define SYSTEM_PATH manually in your config.php'), $path_tmp);
    }
    define('SYSTEM_PATH',     $path_tmp    );
}

define('WIFIDOG_ABS_FILE_PATH', DOCUMENT_ROOT.SYSTEM_PATH);
/*
echo "SYSTEM_PATH:".SYSTEM_PATH."<br/>";
echo "DOCUMENT_ROOT:".DOCUMENT_ROOT."<br/>";
echo "WIFIDOG_ABS_FILE_PATH:".WIFIDOG_ABS_FILE_PATH."<br/>";
exit;*/
/**
 * Add system path of WiFiDog installation to PHPs include path
 */

set_include_path(DOCUMENT_ROOT.SYSTEM_PATH.PATH_SEPARATOR.get_include_path());

?>