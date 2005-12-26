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
 * This page displays location of a hotspot on a map, it can be used to set the
 * precise location
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx <francois.proulx@gmail.com> - Technologies
 * Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

/**
 * @ignore
 */
define('BASEPATH', '../');

require_once BASEPATH.'admin/admin_common.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/AbstractGeocoder.php';
require_once BASEPATH.'classes/MainUI.php';

$ui = new MainUI();
$ui->setTitle(_("Hotspot location map"));

if(!empty($_REQUEST['node_id']))
{
    $node = Node::getObject($_REQUEST['node_id']);

    // Add Google Maps JavaScript ( must set config values )
    $html_headers = "<script src=\"http://maps.google.com/maps?file=api&v=1&key=".GMAPS_PUBLIC_API_KEY."\" type=\"text/javascript\"></script>";
    $ui->setHtmlHeader($html_headers);

    // Create HTML body
    $html = _("Click anywhere on the map to extract the GIS location, then click on the button to save the data.")."<br>";
    $html .= "<div id=\"map_frame\"></div>\n";
    $html .= "<input type='button' value='"._("Use these coordinates")."' onClick='setLocationInOriginalWindow();'>\n";
    $ui->setMainContent($html);

    if($node->getGisLocation() !== null)
    {
        $lat = $node->getGisLocation()->getLatitude();
        $long = $node->getGisLocation()->getLongitude();
    }
    else
    {
        $lat = "null";
        $long = "null";
    }

    // The onLoad code should only be called once all DIV are created.
    $script = "<script type=\"text/javascript\">\n";
    $script .= "var map = new GMap(document.getElementById(\"map_frame\"));\n";
    $script .= "map.addControl(new GLargeMapControl());\n";
    $script .= "map.addControl(new GMapTypeControl());\n";
    $script .= "map.centerAndZoom(new GPoint($long, $lat), 1);\n";
    $script .= "var current_marker_point = new GPoint($long, $lat);\n";
    $script .= "var current_marker = new GMarker(current_marker_point);\n";
    $script .= "map.addOverlay(current_marker);\n";
    $gis_lat_name = "node_".$node->getId()."_gis_latitude";
    $gis_long_name = "node_".$node->getId()."_gis_longitude";
    $script .= "function setLocationInOriginalWindow() {\n";
    $script .= "  window.opener.document.getElementById(\"$gis_lat_name\").value = current_marker_point.y;\n";
    $script .= "  window.opener.document.getElementById(\"$gis_long_name\").value = current_marker_point.x;";
    $script .= "}\n";
    $script .= "GEvent.addListener(this.map, 'click', function(overlay, point) {
                if (point)
                {
                    if(current_marker != null)
                        map.removeOverlay(current_marker);
                    current_marker_point = point;
                    current_marker = new GMarker(point);
                    map.addOverlay(current_marker);
                }
                });\n";
    $script .= "</script>\n";
    $ui->addFooterScript($script);
}
else
    echo _("You need to set a node ID.");

$ui->setToolSectionEnabled(false);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
