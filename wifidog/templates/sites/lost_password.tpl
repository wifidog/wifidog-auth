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
 * Lost password page
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
            <li><a href="{$system_path}lost_username.php">{"I Forgot my username"|_}</a></li>
            <li><a href="{$system_path}lost_password.php">{"I Forgot my password"|_}</a></li>
            <li><a href="{$system_path}resend_validation.php">{"Re-send the validation email"|_}</a></li>
            <li><a href="{$system_path}faq.php">{"Frequently asked questions"|_}</a></li>
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
    <fieldset class="pretty_fieldset">
       <legend>{"Lost password"|_}</legend>

        <form name="form" method="post" onsubmit="return false" action="{$base_ssl_path}lost_password.php">
		<input type="hidden" name="form_request" value="lost_password">
            {if $SelectNetworkUI}
                {$SelectNetworkUI}
            {/if}

            <table>
                <tr>
                    <th>{"Your username"|_}:</th>
                    <td><input type="text" name="username" value="{$username}" size="20" id="form_username" onkeypress="return focusNext(this.form, 'email', event)"></td>
                </tr>
                <tr>
                    <th>{"Your email address"|_}:</th>
                    <td><input type="text" name="email" value="{$email}" size="20" onkeypress="return focusNext(this.form, 'form_submit', event)"></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input class="submit" type="submit" name="form_submit" value="{"Reset my password"|_}" onclick="if (validateForm(this.form)) this.form.submit()"></td>
                </tr>
            </table>
        </form>
    </fieldset>

    <div id="help">
    {"Please enter your username or email address to reset your password"|_}.
    </div>

    <div id="form_errormsg" class="errormsg">
    {if $error}
		{$error}
	{/if}
	</div>

    <script type="text/javascript">
    <!--
        {literal}
		var messages = {
        {/literal}
		  username_required: "{'Please specify a username or email address'|_}",
		  username_invalid: "{'Username contains invalid characters.'|_}",
		  email_invalid: "{'The email address must be valid (i.e. user@domain.com). Please understand that we also black-listed various temporary-email-address providers.'|_}"
        {literal}
		};

        document.getElementById("form_username").focus();

		function validateForm(form) {
		  if (!isValidUsername(form.username)) {
			if (isEmpty(form.username)) {
			  if (isEmpty(form.email)) {
				// username and email cannot both be empty
				document.getElementById("form_errormsg").innerHTML = messages.username_required;
				return false;
			  }
			}
			else {
			  document.getElementById("form_errormsg").innerHTML = messages.username_invalid;
			  return false;
			}
		  }

		  if (!isValidEmail(form.email)) {
			if (isNotEmpty(form.email)) {
			  document.getElementById("form_errormsg").innerHTML = messages.email_invalid;
			  return false;
			}
			else if (isEmpty(form.username)) {
			  // username and email cannot both be empty
			  document.getElementById("form_errormsg").innerHTML = messages.username_required;
			  return false;
			}
		  }

		  return true;
		}

		{/literal}
    //-->
    </script>
{*
    END section MAINCONTENT
*}
{/if}
