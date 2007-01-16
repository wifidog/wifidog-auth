{*

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
 * Hotspot map page.
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <div id="login_form">
        <ul>
            <li><a href="{$base_url_path}hotspot_status.php">{"Deployed HotSpots status with coordinates"|_}</a></li>
            <li><a href="{$base_url_path}node_list.php">{"Full node technical status (includes non-deployed nodes)"|_}</a></li>
        </ul>
    </div>
{*
    END section TOOLCONTENT
*}
{/if}

{if $sectionMAINCONTENT}
{*
    BEGIN section MAINCONTENT
*}
    <form name="hotspots_form" method="post">
        {$selectNetworkUI}
    </form>

    <div id="map_title">
        <div id="map_toolbox">
            <input type="button" value="{"Show me the closest hotspot"|_}" onclick="toggleOverlay('map_postalcode_overlay');">

            <div id="map_postalcode_overlay">
                {"Enter your postal code"|_}:<br/>
                <input type="text" id="postal_code" size="10"><p/>
                <input type="button" value="{"Show"|_}" onclick="toggleOverlay('map_postalcode_overlay'); p = document.getElementById('postal_code'); hotspots_map.findClosestHotspotByPostalCode(p.value);">
            </div>

            <input type="button" value="{"Refresh map"|_}" onclick="hotspots_map.redraw();">
        </div>

        {"Deployed HotSpots map"|_}
    </div>

    <div id="map_outer_hotspots_list">
        <div id="map_hotspots_list"></div>
    </div>

    <div id="map_frame">
        <br /><br />
        <center><h2>{"Loading, please wait..."|_}</h2></center>
    </div>

    <div id="map_legend">
        <b>{"Legend"|_}:</b>
        <img src="{$common_images_url}HotspotStatusMap/up.gif"><i>{"the hotspot is operational"|_}</i>
        <img src="{$common_images_url}HotspotStatusMap/down.gif"><i>{"the hotspot is down"|_}</i>
        <img src="{$common_images_url}HotspotStatusMap/unknown.gif"><i>{"not monitored"|_}</i>
    </div>
{*
    END section MAINCONTENT
*}
{/if}