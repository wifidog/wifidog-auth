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

/** A Smarty resource plugin to process a template from a php string
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/* This function must be called to set the resource before calling it from smarty */
function smarty_resource_string_add_string($string_name, $string) {
	global $smarty_resource_string_strings;
	$smarty_resource_string_strings[$string_name]=$string;
    return true;
}

function smarty_resource_string_source($tpl_name, & $tpl_source, & $smarty) {
	global $smarty_resource_string_strings;
	$retval = false;
	if(isset($smarty_resource_string_strings[$tpl_name])){
    $tpl_source = $smarty_resource_string_strings[$tpl_name];
    $retval = true;
	}
    return $retval;
}

function smarty_resource_string_timestamp($tpl_name, & $tpl_timestamp, & $smarty) {
    $tpl_timestamp = time();
    return true;
}

function smarty_resource_string_secure($tpl_name, & $smarty) {
    //No choice but to set it true
    return true;
}

function smarty_resource_string_trusted($tpl_name, & $smarty) {
    // not used for templates
}