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
 * This file contains all code that must be run to set up PHP behaviours
 * before any code gets executed by the WiFiDog Authentication Server.
 *
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: common.php 927 2006-01-29 22:24:06Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Security function - filters super globals
 *
 * @return void
 */
function undo_magic_quotes()
{
    /**
     * Helper function used by undo_magic_quotes() only
     *
     * @param array  $item Item be processed
     * @param string $key  Key of array being worked on
     *
     * @return array Array with un-quoted strings
     */
    function stripslashes_cb(&$item, $key)
    {
        if (is_array($item)) {
            $item = stripslashes($item);
        }
    }

    /**
     * Helper function used by undo_magic_quotes() only
     *
     * @param mixed  $item Item be processed
     *
     * @return void
     */
    function do_stripslashes(&$item)
    {
        if (is_array($item)) {
            array_walk_recursive($item, 'stripslashes_cb');
        }
    }

    if (get_magic_quotes_gpc()) {
        do_stripslashes($_GET);
        do_stripslashes($_POST);
        do_stripslashes($_COOKIE);
        do_stripslashes($_REQUEST);
        do_stripslashes($_GLOBALS);
        do_stripslashes($_SERVER);
    }
}

/**
 * Disables APC cache (in case it has been installed) and fixes an APC bug
 *
 * @return void
 */
function disableAPC()
{
    if (function_exists("apc_clear_cache")) {
        ini_set("apc.enabled", 0);
        ini_set("apc.optimization", 0);

        /**
         * Disable Just-In-Time creating of super globals when APC is enabled
         *
         * @see http://pecl.php.net/bugs/bug.php?id=4772
         */
        ini_set("auto_globals_jit", 0);
    }
}

/**
 * Disables eAccelerator cache (in case it has been installed)
 *
 * @return void
 */
function disableEA()
{
    if (function_exists("eaccelerator_rm")) {
        ini_set("eaccelerator.enable", 0);
        ini_set("eaccelerator.optimizer", 0);
    }
}

/**
 * Set timezone if PHP version >= 5.1.0
 *
 * @return void
 */
function dateFix()
{
    // Set timezone if PHP version >= 5.1.0
    if (str_replace(".", "", phpversion()) >= 510) {
        date_default_timezone_set(defined(DATE_TIMEZONE) ? DATE_TIMEZONE : "Canada/Eastern");
    }
}

/*
 * Do the magic ...
 */

// First we need to disable PHP caches
disableAPC();
disableEA();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>