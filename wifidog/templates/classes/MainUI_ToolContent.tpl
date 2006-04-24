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
 * Content of tool pane
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: change_password.php 914 2006-01-23 05:25:43Z max-horvath $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionSTART}
{*
    BEGIN section START
*}
        <div class="tool_user_info">
            <span class="tool_user_info">
                {if $isValidUser}
                    <p>{"Logged in as"|_}: {$username}</p>
                    <a class="administration" href="{$base_ssl_path}user_profile.php"><img class="administration" src="{$base_url_path}images/profile.gif">{"My Profile"|_}</a>
                    <a class="administration" href="{$base_ssl_path}login/?logout=true{$logoutParameters}"><img class="administration" src="{$base_url_path}images/logout.gif">{"Logout"|_}</a>
                {else}
					{if !$shrinkLeftArea}
                    <p>
                        {"I am not logged in."|_}<br>
                        <a href="{$base_ssl_path}login/{$loginParameters}">{"Login"|_}</a>
                    </p>
					{/if}

                    <a class="administration" href="{$networkHomepageURL}"><img class="administration" src="{$base_url_path}images/lien_ext.gif">{$networkName}</a>
                    <a class="administration" href="{$base_url_path}faq.php"><img class="administration" src="{$base_url_path}images/where.gif">{"Where am I?"|_}</a>
                {/if}
            </span>
        </div>

        <div class="navigation">
            <span class="navigation">{$networkName} {"Building your wireless community"|_}</span>
        </div>

        <div class="language">
            <form class="language" name="lang_form" method="post" action="{$formAction}">
                {"Language"|_}:
                <select name="wifidog_language" onchange="javascript: document.lang_form.submit();">
                    {foreach from=$languageChooser item=currLanguage}
                        {$currLanguage}
                    {/foreach}
                </select>
            </form>
        </div>

        <div class="tool_content">
            {$toolContent}
        </div>

		{if !$shrinkLeftArea}
        <div class="avis">
            <span class="avis">
                {$accountInformation}
                {$techSupportInformation}
            </span>
        </div>
		{/if}
{*
    END section START
*}
{/if}
