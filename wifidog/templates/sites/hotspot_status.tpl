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
 * Hotspot status page.
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <div id="login_form">
        <ul>
            {if $GMapsEnabled && !$realNodeId}
                <li><a href="{$base_non_ssl_path}hotspots_map.php">{"Deployed HotSpots map"|_}</a></li>
            {/if}
            {if $PdfSupported}
                <li><a href="?format=PDF">{"Get this list as a PDF file"|_}</a></li>
            {/if}
            <li><a href="?format=RSS">{"Get this list as a RSS feed"|_}</a></li>
            <li><a href="?format=KML">{"Get this list for Google Earth"|_}</a></li>
            <li><a href="{$base_non_ssl_path}node_list.php">{"Full node technical status (includes non-deployed nodes)"|_}</a></li>
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
    <div id="hotspot_status">
        <table>
            <thead>
                <tr>
                    <th colspan="6">{"Status of the %d open %s Hotspots"|_|sprintf:$num_deployed_nodes:$networkName}</th>
                </tr>
                <tr>
                    <th>{"Hotspot / Status"|_}</th>
                    <th>{"Description"|_}</th>
                    <th>{"Location"|_}</th>
                </tr>
            </thead>

            {section name=node loop=$nodes}
                <tr class="{cycle values="odd,even"}">
                    <td>
                        {if $nodes[node].node_deployment_status == 'NON_WIFIDOG_NODE'}
                            ?
                        {else}
                            {if $nodes[node].is_up == 't'}
                                <img src='{$common_images_url}HotspotStatus/up.gif'>
                            {else}
                                <img src='{$common_images_url}HotspotStatus/down.gif'>
                            {/if}
                        {/if}

                        {if !$nodes[node].home_page_url}
                            {$nodes[node].name}
                        {else}
                            <a href='{$nodes[node].home_page_url}' target='_new'>{$nodes[node].name}</a>
                        {/if}

                        {if $nodes[node].node_deployment_status == 'IN_TESTING'}
                            <br />
                            {"Hotspot in testing phase"|_}
                        {/if}

                        {if $nodes[node].node_deployment_status == 'NON_WIFIDOG_NODE' && $nodes[node].is_up != 't'}
                            <br />
                            {"Hotspot not monitored"|_}
                        {/if}
                    </td>

                    <td>
                        {if $nodes[node].description}
                            {$nodes[node].description}
                            <br />
                        {/if}

                        {"Opened on %s"|_|sprintf:$nodes[node].creation_date}
                    </td>

                    <td>
                        {if $nodes[node].civic_number}
                            {$nodes[node].civic_number},
                        {/if}
                        {if $nodes[node].street_name}
                            {$nodes[node].street_name}
                        {/if}
                        <br/>
                        {if $nodes[node].city}
                            {$nodes[node].city},
                        {/if}
                        {if $nodes[node].province}
                            {$nodes[node].province},
                        {/if}
                        {if $nodes[node].postal_code}
                            {$nodes[node].postal_code},
                        {/if}
                        {if $nodes[node].country}
                            {$nodes[node].country}
                        {/if}
                        {if $nodes[node].map_url}
                            - <a href='{$nodes[node].map_url}' target='_new'>{"Map"|_}</a>
                        {/if}
                        {if $nodes[node].mass_transit_info}
                            <br />
                            {$nodes[node].mass_transit_info}
                        {/if}
                        {if $nodes[node].public_phone_number}
                            <br />
                            {$nodes[node].public_phone_number}
                        {/if}
                        {if $nodes[node].public_email}
                            <br />
                            {$nodes[node].public_email}
                        {/if}
                    </td>
                </tr>
            {/section}
        </table>
    </div>
{*
    END section MAINCONTENT
*}
{/if}