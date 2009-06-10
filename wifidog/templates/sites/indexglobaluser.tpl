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
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionMAINCONTENT}
{*
    BEGIN section MAINCONTENT
*}
	<p>
		{if $serverNumValidUsers == 1}
			{"The server currently has one valid user."|_|sprintf}
		{else}
			{"The server currently has %d valid users."|_|sprintf:$serverNumValidUsers}
		{/if}

		{if $serverNumOnlineUsers == 1}
			{"One user is currently online."|_|sprintf:$serverNumOnlineUsers}
		{else}
			{"%d users are currently online."|_|sprintf:$serverNumOnlineUsers}
		{/if}
		<br/>
		{if $serverNumDeployedNodes == 1}
        		{"This network currently has 1 deployed hotspot."|_}
        {else}
        		{"This network currently has %d deployed hotspots."|_|sprintf:$serverNumDeployedNodes}
        {/if}

        {if $serverNumOnlineNodes == 1}
            {"One hotspot is currently operational."|_}
        {else}
            {"%d hotspots are currently operational."|_|sprintf:$serverNumOnlineNodes}
        {/if}

        {if $serverNumNonMonitoredNodes > 0}
            {if $serverNumNonMonitoredNodes == 1}
                {"One hotspot isn't monitored so we don't know if it's currently operational."|_}
            {else}
                {"%d hotspots aren't monitored so we don't know if they are currently operational."|_|sprintf:$serverNumNonMonitoredNodes}
            {/if}
        {/if}
    </p>
{*
    END section MAINCONTENT
*}
{/if}
