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

    <div id="login_form">
        <h1>{"Login or Signup here"|_}:</h1>
            <form name="login_form" method="post" onsubmit="return false" action="{$base_ssl_path}login/index.php">
			<input type="hidden" name="form_request" value="login">
                {if $node != null}
                    <input type="hidden" name="gw_address" value="{$gw_address}">
                    <input type="hidden" name="gw_port" value="{$gw_port}">
                    <input type="hidden" name="gw_id" value="{$gw_id}">
                {/if}
                {if $origin != null}
                    <input type="hidden" name="origin" value="{$origin}" />
                {/if}

                {$selectNetworkUI}<br>

                {"Username (or email)"|_}:<br>
                <input type="text" name="username" value="{$username}" size="20" id="form_username" onkeypress="return focusNext(this.form, 'password', event)"><br>
                {"Password"|_}:<br>
                <input type="password" name="password" size="20" id="form_password" onkeypress="return focusNext(this.form, 'form_submit', event)"><br>

                <div id="form_errormsg" class="errormsg">
				{if $error == null}
				  &nbsp;
				{else}
				  {$error}
				{/if}
				</div>

                <input class="submit" type="button" name="form_submit" value="{"Login"|_}" onclick="if (validateForm(this.form)) this.form.submit()">
				&nbsp;
                <input class="submit" type="button" name="form_signup" value="{$create_a_free_account}" onclick="this.form.action='{$base_ssl_path}signup.php'; this.form.submit()">
            </form>
    </div>
    <div id="login_help">
        <h1>{"I'm having difficulties"|_}:</h1>

        <ul>
            <li><a href="{$base_url_path}lost_username.php">{"I Forgot my username"|_}</a></li>
            <li><a href="{$base_url_path}lost_password.php">{"I Forgot my password"|_}</a></li>
            <li><a href="{$base_url_path}resend_validation.php">{"Re-send the validation email"|_}</a></li>
            <li><a href="{$base_url_path}faq.php">{"Frequently asked questions"|_}</a></li>
        </ul>
    </div>

    <script type="text/javascript">
        <!--
		{literal}
		var messages = {
		{/literal}
		  empty_form: "{"You must specify your username and password"|_}",
		  username_required: "{'Username is required.'|_}",
		  username_invalid: "{'Username contains invalid characters.'|_}",
		  email_invalid: "{'A valid email address is required.'|_}",
		  password_empty: "{'A password is required.'|_}",
		  password_invalid: "{'Password contains invalid characters.'|_}",
		  password_short: "{'Password is too short, it must be 6 characters minimum'|_}"
		{literal}
		};

		document.getElementById("form_username").focus();

		function validateForm(form) {
		  if (!isValidUsername(form.username)) {
			if (isEmpty(form.username)) {
			  document.getElementById("form_errormsg").innerHTML = messages.username_required;
			  return false;
			}
			else {
			  if (!isValidEmail(form.username)) {
				document.getElementById("form_errormsg").innerHTML = messages.username_invalid;
				return false;
			  }
			}
		  }


			if (isEmpty(form.password)){
			  document.getElementById("form_errormsg").innerHTML = messages.password_empty;
			return false;
		  }

		  return true;
		}

		{/literal}
        //-->
    </script>
