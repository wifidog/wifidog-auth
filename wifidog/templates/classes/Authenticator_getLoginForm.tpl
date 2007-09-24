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
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}
                <h1>{"Login or Signup here"|_}:</h1>
			<p>
            {$selectNetworkUI}
            </p>
			{if $user_id}
			    <input type="hidden" name="user_id" id="form_user_id" value="{$user_id}"/>
			{else}
            	{"Username (or email)"|_}:<br/>
            	<input type="text" name="username" id="form_username" tabindex="1" value="{$username}" size="20" /><br/>
            {/if}
            {"Password"|_}:<br/>
            <input type="password" name="password" id="form_password" tabindex="2" size="20" /><br/>

            <div id="form_errormsg" class="errormsg">
			{if $error == null}
			  &nbsp;
			{else}
			  {$error}
			{/if}
			</div>

            <input class="submit" type="submit" tabindex="3" name="login_form_submit" value="{"Login"|_}" onclick="return validateForm(this.form);"/>&nbsp;
            <input class="submit" type="submit" tabindex="4" name="form_signup" value="{"Create a free account"|_}" onclick="location.href='{$base_ssl_path}signup.php';" />
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
