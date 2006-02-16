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

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <div id="login_form">
        <h1><a href="{$base_ssl_path}signup.php">{"Create a free account"|_}</a></h1>

        <h1>{"I already have an account"|_}:</h1>

        <p class="indent">
            <form name="login_form" method="post">
                {if $node != null}
                    <input type="hidden" name="gw_address" value="{$gw_address}">
                    <input type="hidden" name="gw_port" value="{$gw_port}">
                    <input type="hidden" name="gw_id" value="{$gw_id}">
                {/if}

                {$selectNetworkUI}<br>

                {"Username (or email)"|_}:<br>
                <input type="text" name="username" value="{$username}" size="20" id="form_username"><br>
                {"Password"|_}:<br>
                <input type="password" name="password" size="20"><br>

                {if $error != null}
                    <div class="errormsg">{$error}</div>
                {/if}

                <input class="submit" type="submit" name="submit" value="{"Login"|_}">
            </form>
        </p>

        <h1>{"I'm having difficulties"|_}:</h1>

        <ul>
            <li><a href="{$base_ssl_path}lost_username.php">{"I Forgot my username"|_}</a></li>
            <li><a href="{$base_ssl_path}lost_password.php">{"I Forgot my password"|_}</a></li>
            <li><a href="{$base_ssl_path}resend_validation.php">{"Re-send the validation email"|_}</a></li>
            <li><a href="{$base_ssl_path}faq.php">{"Frequently asked questions"|_}</a></li>
        </ul>
    </div>

    <script type="text/javascript" language="JavaScript">
    <!--
        document.getElementById("form_username").focus();
    //-->
    </script>
{*
    END section TOOLCONTENT
*}
{/if}

{if $sectionMAINCONTENT}
{*
    BEGIN section MAINCONTENT
*}
    <div class="login_body">
        <center>
            {if $node != null}
                {if $hotspot_homepage_url != null}
                    <a href="{$hotspot_homepage_url}"><h1>{$hotspot_name}</h1></a>
                {else}
                    <h1>{$hotspot_name}</h1>
                {/if}

                {if $contents}
                    <table>
                        <tr>
                            <td>
                                <div class="portal_node_section">
                                    {section name=content loop=$contentArray}
                                        {if $contentArray[content].isDisplayableAt}
                                            <div class="portal_content">{$contentArray[content].userUI}</div>
                                        {/if}
                                    {/section}
                                </div>
                            </td>
                        </tr>
                    </table>
                {/if}
            {/if}
        </center>
    </div>
{*
    END section MAINCONTENT
*}
{/if}