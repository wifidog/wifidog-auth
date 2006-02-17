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
 * Sign up page
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Philippe April
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

*}

{if $sectionTOOLCONTENT}
{*
    BEGIN section TOOLCONTENT
*}
    <div id="login_form">
        <h1>{"I'm having difficulties"|_}:</h1>

        <ul>
            <li><a href="{$base_ssl_path}lost_username.php">{"I Forgot my username"|_}</a></li>
            <li><a href="{$base_ssl_path}lost_password.php">{"I Forgot my password"|_}</a></li>
            <li><a href="{$base_ssl_path}resend_validation.php">{"Re-send the validation email"|_}</a></li>
            <li><a href="{$base_ssl_path}faq.php">{"Frequently asked questions"|_}</a></li>
        </ul>
    </div>

    <script type="text/javascript">
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
    <fieldset class="pretty_fieldset">
        <legend>{"Register a free account with"|_} {$hotspot_network_name}</legend>

        <form name="signup_form" method="post">
            {if $SelectNetworkUI}
                {$SelectNetworkUI}
            {/if}

            <table>
                <tr>
                    <th>{"Username desired"|_}:</th>
                    <td><input type="text" name="username" value="{$username}" size="30" id="form_username"></td>
                </tr>
                <tr>
                    <th>{"Your email address"|_}:</th>
                    <td><input type="text" name="email" value="{$email}" size="30"></td>
                </tr>
                <tr>
                    <th>{"Password"|_}:</th>
                    <td><input type="password" name="password" size="30"></td>
                </tr>
                <tr>
                    <th>{"Password (again)"|_}:</th>
                    <td><input type="password" name="password_again" size="30"></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input class="submit" type="submit" name="submit" value="{"Sign-up"|_}"></td>
                </tr>
            </table>
        </form>

        <hr>

        <p>
            <b>{"Please note"|_}</b>:
            {"While accounts are free, we <em>strongly</em> suggest that you use your previously created account if you have one."|_}
        </p>

        <p>
            <b>{"Note to free web-based email users"|_}</b>:
            {"Sometimes our validation email ends up in the 'spam' folder of some providers. If you have not received any email with the validation URL 5 minutes after submitting this form, please take a look in the spam folder."|_}
        </p>

        <p>
            <b>{"You can also use the following links if you need help"|_}:</b>
            <ul>
                <li><a href="{$smarty.const.BASE_SSL_PATH}lost_username.php">{"I Forgot my username"|_}</a></li>
                <li><a href="{$smarty.const.BASE_SSL_PATH}lost_password.php">{"I Forgot my password"|_}</a></li>
            </ul>
        </p>
    </fieldset>

    <div id="help">
        {if $error}
            <div class="errormsg">{$error}</div>
        {else}
            {"Your email address must be valid in order for your account to be activated"|_}.
        {/if}
    </div>

    <script type="text/javascript">
        <!--
            document.getElementById("form_username").focus();
        //-->
    </script>
{*
    END section MAINCONTENT
*}
{/if}