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
 * Configuration file to list the available locales in the WiFiDog Authentication Server
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/** Returns an array of available languages for the user.  Each entry must have:
 * -The language code (the part before the _) be present in wifidog/locales
 * -Have the entire locale available in your system locale
 * OR
 * -Have a system locale available with only the language (ex: an en locale).
 * Note that if you specify en_UK and en_US, and have only en available the
 * system will NOT warn you that both will have identical results.
 * Note that even if your system uses locales like fr_CA.UTF8, you do not need
 * to change this, wifidog will translate for you.
 */
function getAvailableLanguageArray() {
    $retval = array(
    'fr_CA' => array('Français', _("French")),
    'en_US' => array('English',_("English")),
    'de_DE' => array('Deutsch',_("German")),
    'es_ES' => array('Español',_("Spanish")),
    'pt_BR' => array('Português (Brasil)',_("Brazilian Portuguese")),
    'pt_PT' => array('Português (Portugal)',_("Portuguese")),
    'ja_JP' => array('日本語',_("Japanese")),
    'el_GR' => array('Greek',_("Greek")),
    'bg_BG' => array('Български език',_("Bulgarian")),
    'sv_SE' => array('Svenska',_("Swedish")),
    'it_IT' => array('Italiano', _("Italian")) 
    );
    return $retval;
}
?>
