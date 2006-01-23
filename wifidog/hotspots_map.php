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
 * Network status page.
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/MainUI.php');

$ui = new MainUI();
$ui->setTitle(_("Hotspots status map"));

// Add Google Maps JavaScript ( must set config values )
$html_headers = "<script src=\"http://maps.google.com/maps?file=api&v=1&key=".GMAPS_PUBLIC_API_KEY."\" type=\"text/javascript\"></script>\n";
$html_headers .= "<script src=\"js/hotspots_status_map.js\" type=\"text/javascript\"></script>";
$ui->setHtmlHeader($html_headers);

// Create HTML body
$html = "<div id=\"map_title\">\n";
$html .= "<div id=\"map_toolbox\">\n";
$html .= "<input type=\"button\" value=\""._("Show me the closest hotspot")."\" onclick=\"toggleOverlay('map_postalcode_overlay');\">\n";
$html .= "<div id=\"map_postalcode_overlay\">\n";
$html .= _("Enter your postal code")." :<br/>\n";
$html .= "<input type=\"text\" id=\"postal_code\" size=\"10\"><br/>\n";
$html .= "<input type=\"button\" value=\""._("Show")."\" onclick=\"toggleOverlay('map_postalcode_overlay'); p = document.getElementById('postal_code'); hotspots_map.findClosestHotspotByPostalCode(p.value);\">\n";
$html .= "</div>\n";
$html .= "<input type=\"button\" value=\""._("Refresh map")."\" onclick=\"hotspots_map.redraw();\">\n";
$html .= "</div>\n";
$html .= _("Deployed HotSpots map")."\n";
$html .= "</div>\n";
$html .= "<div id=\"map_outer_hotspots_list\"><div id=\"map_hotspots_list\"></div></div>\n";
$html .= "<div id=\"map_frame\"><p/><center><h2>"._("Loading, please wait...")."</h2></center></div>\n";
$html .= "<div id=\"map_legend\">\n";
$html .= "<b>"._("Legend")." :</b>\n";
$html .= "<img src='images/hotspots_status_map_up.png'> <i>"._("the hotspot is operational")."</i>\n";
$html .= "<img src='images/hotspots_status_map_down.png'> <i>"._("the hotspot is down")."</i>\n";
$html .= "<img src='images/hotspots_status_map_unknown.png'> <i>"._("not monitored")."</i>\n";
$html .= "</div>\n";
$ui->setMainContent($html);

// The onLoad code should only be called once all DIV are created.
$script = "<script type=\"text/javascript\">\n";
$script .= "function toggleOverlay(name) { o = document.getElementById('map_postalcode_overlay');";
$script .= "	if(o != undefined) { if(o.style.display == 'block') o.style.display='none'; else o.style.display='block'; }}\n";
$script .= "hotspots_map = new HotspotsMap(\"map_frame\", \"hotspots_map\");\n";
$script .= "hotspots_map.setXmlSourceUrl(\"".GMAPS_XML_SOURCE_URL."\");\n";
$script .= "hotspots_map.setHotspotsInfoList(\"map_hotspots_list\");\n";
$script .= "hotspots_map.setInitialPosition(".GMAPS_INITIAL_LATITUDE.", ".GMAPS_INITIAL_LONGITUDE.", ".GMAPS_INITIAL_ZOOM_LEVEL.");\n";
$script .= "hotspots_map.redraw();\n";
$script .= "</script>\n";
$ui->addFooterScript($script);

$tool_html = '<p class="indent">'."\n";
$tool_html .= "<ul class='users_list'>\n";
$tool_html .= "<li><a href='hotspot_status.php'>"._('Deployed HotSpots status with coordinates')."</a></li>";
$tool_html .= "</ul>\n";
$tool_html .= '</p>'."\n";

$ui->setToolContent($tool_html);

$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>