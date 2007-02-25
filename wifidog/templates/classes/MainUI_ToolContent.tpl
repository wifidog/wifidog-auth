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
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionSTART}
{*
    BEGIN section START
*}
        <div class="tool_user_info">
                {if $userIsValid}
                    <p>{"Logged in as"|_}: {$userName}</p>
                    <a class="administration" href="{$base_ssl_path}admin/generic_object_admin.php?object_id={$userId}&object_class=User&action=edit"><img class="administration" src="{$common_images_url}profile.gif">&nbsp;{"My profile"|_}</a>
                    <a class="administration" href="{$base_ssl_path}login/?logout=true{$logoutParameters}"><img class="administration" src="{$common_images_url}logout.gif">&nbsp;{"Logout"|_}</a>
                {else}
					{if !$shrinkLeftArea}
                    <p>
                        {"I am not logged in."|_}<br>
                        <a href="{$base_ssl_path}login/{$loginParameters}">{"Login"|_}</a>
                    </p>
					{/if}

                    <a class="administration" href="{$networkHomepageURL}"><img class="administration" src="{$common_images_url}lien_ext.gif">&nbsp;{$networkName}</a>
                    <a class="administration" href="{$base_url_path}faq.php"><img class="administration" src="{$common_images_url}where.gif">&nbsp;{"Where am I?"|_}</a>
                {/if}
        </div>

        {if count($languageChooser) > 1}
        <div class="language">
            <form class="language" name="lang_form" method="post" action="{$formAction}">
                <div>{"Language"|_}:
                <select name="wifidog_language" onchange="javascript: document.lang_form.submit();">
                    {foreach from=$languageChooser item=currLanguage}
                        {$currLanguage}
                    {/foreach}
                </select>
                </div>
            </form>
        </div>
        {/if}

        <div class="tool_content">
            {$toolContent}
        </div>

{*
    END section START
*}
{/if}
