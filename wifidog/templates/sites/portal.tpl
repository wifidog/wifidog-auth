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
 * Login page
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}
    <h1>{"Online users"|_}</h1>

    <p class="indent">
        {if $nodeId != null}
            {if ($nodeNumOnlineUsers > 0)}
                {if ($nodeNumOnlineUsers == 1)}
                    1 {"user is online at this hotspot"|_}
                {else}
                    {$nodeNumOnlineUsers} {"users are online at this hotspot"|_}
                {/if}

                <ul class="users_list">
                    {section name=onlineUser loop=$onlineUsers}
                        <li>{$onlineUsers[onlineUser]}</li>
                    {/section}
                </ul>
            {else}
                 {"Nobody is online at this hotspot"|_} ...
             {/if}
         {/if}
    </p>

    {if $realNodeId}
        <p class="indent">
            <a id="wifidog_use_internet" href="{$url}">{"Use the Internet"|_}</a>
        </p>
    {/if}