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
 * WiFiDog Authentication Server home page
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: change_password.php 914 2006-01-23 05:25:43Z max-horvath $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <div id="login_form">
        <ul>
			{if $isValidUser}
            <li><a href="{$base_ssl_path}change_password.php">{"Change password"|_}</a></li>
			{else}
            <li><a href="{$base_url_path}faq.php">{"I have trouble connecting and I would like some help"|_}</a></li>
			{/if}
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
	<p>
		{if $networkNumValidUsers == 1}
			{"The %s network currently has one valid user."|_|sprintf:$networkName}
		{else}
			{"The %s network currently has %d valid users."|_|sprintf:$networkName:$networkNumValidUsers}
		{/if}

		{if $networkNumOnlineUsers == 1}
			{"One user is currently online."|_|sprintf:$networkNumOnlineUsers}
		{else}
			{"%d users are currently online."|_|sprintf:$networkNumOnlineUsers}
		{/if}
		<br/>
		{if $networkNumDeployedNodes == 1}
        		{"This network currently has 1 deployed hotspot."|_}
        {else}
        		{"This network currently has %d deployed hotspots."|_|sprintf:$networkNumDeployedNodes}
        {/if}

        {if $networkNumOnlineNodes == 1}
            {"One hotspot is currently operationnal."|_}
        {else}
            {"%d hotspots are currently operationnal."|_|sprintf:$networkNumOnlineNodes}
        {/if}
    </p>

    <ul>
        {if $googleMapsEnabled} {* This needs to be imporved before being deployed  {if $googleMapsEnabled && !$userIsAtHotspot}*}
            <li><a href="{$base_non_ssl_path}hotspots_map.php">{"Deployed HotSpots map"|_}</a></li>
        {/if}
        <li><a href="{$base_url_path}hotspot_status.php">{"Deployed HotSpots status with coordinates"|_}</a></li>
        <li><a href="{$base_url_path}node_list.php">{"Full node technical status (includes non-deployed nodes)"|_}</a></li>
        <li><a href="{$base_url_path}admin/index.php">{"Administration"|_}</a></li>
    </ul>
{*
    END section MAINCONTENT
*}
{/if}
