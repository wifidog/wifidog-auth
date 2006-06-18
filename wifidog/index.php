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
 * WiFiDog Authentication Server home page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Don't change the first require_once() as we didn't add WiFiDogs installation
 * path to the global include_path variable of PHP, yet!
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/MainUI.php');
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/User.php');

// Init ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Init ALL smarty values
$smarty->assign('googleMapsEnabled', false);

// Get information about network
$network = Network::getCurrentNetwork();
Network::assignSmartyValues($smarty, $network);

// Get information about user
User::assignSmartyValues($smarty);

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);

// Compile HTML code
$html = $smarty->fetch("templates/sites/index.tpl");

/*
 * Main content
 */

// Reset ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Set section of Smarty template
$smarty->assign('sectionMAINCONTENT', true);

// Set Google maps information
$smarty->assign('googleMapsEnabled', defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED);

// Compile HTML code
$html_body = $smarty->fetch("templates/sites/index.tpl");

/*
 * Render output
 */
$ui = new MainUI();
$ui->addContent('left_area_middle', $html);
$ui->addContent('main_area_top', $html_body);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>