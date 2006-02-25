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
 * Section of tool pane for the administration interface
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: change_password.php 914 2006-01-23 05:25:43Z max-horvath $
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionADMIN}
{*
    BEGIN section ADMIN
*}
    {if $isSuperAdmin}
            <div class='admin_section_container'>
                <div class="admin_section_title">{"User administration"|_}:</div>

                <div class="admin_section_data">
                    <ul>
                        <li><a href="{$base_ssl_path}admin/user_log.php">{"User logs"|_}</a></li>
                        <li><a href="{$base_ssl_path}admin/online_users.php">{"Online Users"|_}</a></li>
                        <li><a href="{$base_ssl_path}admin/stats.php">{"Statistics"|_}</a></li>
                        <li><a href="{$base_ssl_path}admin/import_user_database.php">{"Import NoCat user database"|_}</a></li>
                    </ul>
                </div>
            </div>
    {/if}

    {if $isSuperAdmin || $isOwner}
        <div class="admin_section_container">
            {if !$isSuperAdmin}
                <form action="{$formAction}" method="post">
                    <div class="admin_section_title">{"Node administration"|_}:</div>

                    <div class="admin_section_data">
                        <div id="NodeSelector">{$nodeUI}</div>
                    </div>

                    <div class="admin_section_tools">
                        <input type="hidden" name="object_class" value="Node">
                        <input type="hidden" name="action" value="edit">
                        <input type="submit" name="edit_submit" value="{"Edit"|_}">
                    </div>
                </form>
            {else}
                <div class="admin_section_title">{"Node administration"|_}:</div>

                <div class="admin_section_data">
                    <ul>
                        <li><a href="{$base_ssl_path}admin/generic_object_admin.php?object_class=Node&action=list">{"Nodes"|_}</a></li>
                        <li><a href="{$base_ssl_path}admin/generic_object_admin.php?object_class=Node&action=new_ui">{"Add new Node"|_}</a></li>
                    </ul>
                </div>
            {/if}
        </div>

        {if $isSuperAdmin}
            <div class='admin_section_container'>
                <div class="admin_section_title">{"Network administration"|_}:</div>

                <div class="admin_section_data">
                    <ul>
                        <li><a href="{$base_ssl_path}admin/generic_object_admin.php?object_class=Network&action=list">{"Networks"|_}</a></li>
                    </ul>
                </div>
            </div>

            <div class='admin_section_container'>
                <div class="admin_section_title">{"Server administration"|_}:</div>

                <div class="admin_section_data">
                    <ul>
                        <li><a href="{$base_ssl_path}admin/generic_object_admin.php?object_class=Server&action=list">{"Servers"|_}</a></li>
                        <li><a href="{$base_ssl_path}admin/generic_object_admin.php?object_class=Content&action=list">{"Content"|_}</a></li>
                    </ul>
                </div>
            </div>
        {/if}
    {/if}
{*
    END section ADMIN
*}
{/if}