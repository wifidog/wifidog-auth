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
 * Network map/status page.
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/MainUI.php');
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/User.php');
require_once('classes/Server.php');

// Get information about user
$currentUser = User::getCurrentUser();

// Check if Google maps support has been enabled
if (!defined("GMAPS_HOTSPOTS_MAP_ENABLED") || (defined("GMAPS_HOTSPOTS_MAP_ENABLED") && GMAPS_HOTSPOTS_MAP_ENABLED == false)) {
    header("Location: hotspot_status.php");
    exit();
}

// Check if user is at a Hotspot and if he is authenticated
/*This code is definitely not ready for prime time
 * if (!is_null(Node::getCurrentRealNode()) && !$currentUser) {
    header("Location: hotspot_status.php");
    exit();
}*/

// Init ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Init ALL smarty values
$smarty->assign('isSuperAdmin', false);
$smarty->assign('isOwner', false);
$smarty->assign('selectNetworkUI', null);

/**
 * Define user security levels for the template
 *
 * These values are used in the default template of WiFoDog but could be used
 * in a customized template to restrict certain links to specific user
 * access levels.
 */
$smarty->assign('isSuperAdmin', $currentUser && $currentUser->isSuperAdmin());
$smarty->assign('isOwner', $currentUser && $currentUser->isOwner());

/*
 * Header JavaScripts
 */

// Add Google Maps JavaScript (must set config values)
$html_headers  = "<script src='http://maps.google.com/maps?file=api&amp;v=2&amp;&key=" . Server::getCurrentServer()->getGoogleAPIKey() . "' type='text/javascript'></script>";
$html_headers .= "<script src='js/hotspots_status_map.js' type='text/javascript'></script>";

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);

// Compile HTML code
$html = $smarty->fetch("templates/sites/hotspots_map.tpl");

/*
 * Main content
 */

// Reset ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Set section of Smarty template
$smarty->assign('sectionMAINCONTENT', true);

// Set network selector
$smarty->assign('selectNetworkUI', Network::getSelectNetworkUI('network_map', (!empty($_REQUEST['network_map']) ? Network::getObject($_REQUEST['network_map']) : Network::getCurrentNetwork())) . (count(Network::getAllNetworks()) > 1 ? '<input class="submit" type="submit" name="submit" value="' . _("Change network") . '">' : ""));

// Compile HTML code
$html_body = $smarty->fetch("templates/sites/hotspots_map.tpl");

/*
 * Footer JavaScripts
 */

// Get GIS data to set
if (!empty($_REQUEST['network_map'])) {
    $network = Network::getObject($_REQUEST['network_map']);
} else {
    $network = Network::getCurrentNetwork();
}

$gis_data = $network->getGisLocation();

// The onLoad code should only be called once all DIV are created.
$script  = "<script type=\"text/javascript\"><!--\n";
$script .= "    function toggleOverlay(name)\n";
$script .= "    {\n";
$script .= "        o = document.getElementById('map_postalcode_overlay');\n";
$script .= "        if (o != undefined) {\n";
$script .= "            if (o.style.display == 'block') {\n";
$script .= "                o.style.display = 'none';\n";
$script .= "            } else {\n";
$script .= "                o.style.display = 'block';\n";
$script .= "            }\n";
$script .= "        }\n";
$script .= "    }\n";
$script .= "    translations = new HotspotsMapTranslations('" . addcslashes(_("Sorry, your browser does not support Google Maps."), "'") . "', '" . addcslashes(_("Homepage"), "'") . "', '" . addcslashes(_("Show me on the map"), "'") . "', '" . addcslashes(_("Loading, please wait..."), "'") . "');\n";
$script .= "    hotspots_map = new HotspotsMap('map_frame', 'hotspots_map', translations, '" . COMMON_IMAGES_URL . "');\n";
$script .= "    hotspots_map.setXmlSourceUrl('" . GMAPS_XML_SOURCE_URL . "');\n";
$script .= "    hotspots_map.setHotspotsInfoList('map_hotspots_list');\n";
$script .= "    hotspots_map.setInitialPosition(" . $gis_data->getLatitude() . ", " . $gis_data->getLongitude() . ", " . $gis_data->getAltitude() . ");\n";
$script .= "    hotspots_map.setMapType(" . $network->getGisMapType() . ");\n";
$script .= "    hotspots_map.redraw();\n";
$script .= "//-->\n";
$script .= "</script>\n";

/*
 * Render output
 */
$ui = new MainUI();
$ui->setTitle(_("Hotspots status map"));
$ui->setHtmlHeader($html_headers);
$ui->addContent('left_area_middle', $html);
$ui->addContent('main_area_middle', $html_body);
$ui->addFooterScript($script);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>