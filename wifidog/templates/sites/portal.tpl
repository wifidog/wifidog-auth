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
 * @version    Subversion $Id: change_password.php 914 2006-01-23 05:25:43Z max-horvath $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <h1>{"Online users"|_}</h1>

    <p class="indent">
        {if $currentNode != null}
            {if ($numOnlineUsers > 0)}
                {if ($numOnlineUsers == 1)}
                    1 {"user is online at this hotspot"|_}
                {else}
                    {$numOnlineUsers} {"users are online at this hotspot"|_}
                {/if}

                <ul class="users_list">
                    {section name=onlineUser loop=$onlineUsers}
                        <li>{$onlineUsers[onlineUser].Username}{if $onlineUsers[onlineUser].showRoles} <span class="roles">{$onlineUsers[onlineUser].roles}</span>{/if}</li>
                    {/section}
                </ul>
            {else}
                 {"Nobody is online at this hotspot"|_} ...
             {/if}
         {/if}
    </p>

    {if $userIsAtHotspot}
        {literal}
            <script type="text/javascript">
                <!--
                    function getElementById(id) {
                        if (document.all) {
                            return document.getElementById(id);
                        }

                        for (i = 0; i < document.forms.length; i++) {
                            if (document.forms[i].elements[id]) {
                                return document.forms[i].elements[id];
                            }
                        }
                    }

                    function getWindowSize(window) {
                        var size_array = new Array(2);
                        var myWidth = 0, myHeight = 0;

                        if (typeof(window.innerWidth) == "number") {
                            // Non-IE
                            myWidth = window.innerWidth;
                            myHeight = window.innerHeight;
                        } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
                            // IE 6+ in "standards compliant mode"
                            myWidth = document.documentElement.clientWidth;
                            myHeight = document.documentElement.clientHeight;
                        } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
                            // IE 4 compatible
                            myWidth = document.body.clientWidth;
                            myHeight = document.body.clientHeight;
                        }

                        size_array[0] = myWidth;
                        size_array[1] = myHeight;

                        return size_array;
                    }
                //-->
            </script>
        {/literal}

        <p class="indent">
            <a id="wifidog_portal_expand" href="javascript:
                var wifidog_portal_expand = getElementById('wifidog_portal_expand');
                var wifidog_portal_collapse = getElementById('wifidog_portal_collapse');

                wifidog_portal_expand.style.display = 'none';
                wifidog_portal_collapse.style.display = 'inline';
                var size_array = getWindowSize(window.opener);
                window.resizeTo(size_array[0], size_array[1]);
            ">{"Expand portal"|_}</a>

            <a id="wifidog_portal_collapse" href="javascript:
                var wifidog_portal_expand = getElementById('wifidog_portal_expand');
                var wifidog_portal_collapse = getElementById('wifidog_portal_collapse');

                wifidog_portal_expand.style.display = 'inline';
                wifidog_portal_collapse.style.display = 'none';
                var size_array = getWindowSize(window.opener);
                window.resizeTo(250, size_array[1]);
            ">{"Collapse portal"|_}</a>
        </p>

        <p class="indent">
            <a id="wifidog_use_internet" href="{$url}" onclick="
                var size_array = getWindowSize(window);
                var original_location=window.location.href;
                var old_window = window;
                var new_window = window.open('{$url}','wifidog_portal');

                new_window.blur();
                old_window.focus();
                new_window.resizeTo(250, size_array[1]);
            "><img src="{$base_ssl_path}images/start.gif"></a>
        </p>

        {literal}
            <script type="text/javascript">
                <!--
                    //Set up if expand/collapse functionnality is to be enabled by checking if we were called from another portal window.

                    window.is_wifidog_portal=true; //This assignement may be read by another window

                    var wifidog_portal_expand = document.getElementById("wifidog_portal_expand");
                    var wifidog_portal_collapse = document.getElementById("wifidog_portal_collapse");
                    var wifidog_use_internet = document.getElementById("wifidog_use_internet");

                    if (window.opener && window.opener.is_wifidog_portal == true) {
                        wifidog_portal_expand.style.display = "inline";
                        wifidog_portal_collapse.style.display = "none";
                        wifidog_use_internet.style.display = "none";
                    } else {
                        wifidog_portal_expand.style.display = "none";
                        wifidog_portal_collapse.style.display = "none";
                    }
                //-->
            </script>
        {/literal}
    {/if}
{*
    END section TOOLCONTENT
*}
{/if}

{if $sectionMAINCONTENT}
{*
    BEGIN section MAINCONTENT
*}
    {if $accountValidation}
        <div id="warning_message_area">
            {"An email with confirmation instructions was sent to your email address."|_}
            {"Your account has been granted"|_} {$validationTime} {"minutes of access to retrieve your email and validate your account."|_}
        </div>
    {/if}

    <div id="portal_container">
        <div class="portal_network_section">
            <a href="{$hotspotNetworkUrl}"><img class="portal_section_logo" alt="{$hotspotNetworkName} logo" src="{$networkLogoBannerUrl}" border="0"></a>

            {if $networkContents}
                {section name=networkContent loop=$networkContentArray}
                    {if $networkContentArray[networkContent].isDisplayableAt}
                        <div class="portal_content">{$networkContentArray[networkContent].userUI}</div>
                    {/if}
                {/section}
            {/if}
        </div>

        {if $nodeContents}
            <div class="portal_node_section">
                <span class="portal_section_title">
                    {"Content from"|_}:
                    {if $nodeHomepage}
                        <a href="{$nodeURL}">{$nodeName}</a>
                    {else}
                        {$nodeName}
                    {/if}
                </span>
                {section name=nodeContent loop=$nodeContentArray}
                    {if $nodeContentArray[nodeContent].isDisplayableAt}
                        <div class="portal_content">{$nodeContentArray[nodeContent].userUI}</div>
                    {/if}
                {/section}
            </div>
        {/if}

        {if $userContents}
            <div class="portal_user_section">
                <span class="portal_section_title">{"My content"|_}:</span>
                {section name=userContent loop=$userContentArray}
                    <div class="portal_content">{$userContentArray[userContent].userUI}</div>
                {/section}
            </div>
        {/if}

        {if $showMoreLink}
            <a href="{$base_ssl_path}content/?gw_id={$currentNodeId}">{"Show all available contents for this hotspot"|_}</a>
        {/if}

        <div style="clear: both;"></div>
    </div>
{*
    END section MAINCONTENT
*}
{/if}