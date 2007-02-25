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
* @author     Benoit Grégoire <bock@step.polymtl.ca>
* @author     Max Horváth <max.horvath@freenet.de>
* @copyright  2004-2006 Philippe April
* @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
* @copyright  2006 Max Horváth, Horvath Web Consulting
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
		<li><a href="{$base_url_path}lost_username.php">{"I Forgot my username"|_}</a></li>
		<li><a href="{$base_url_path}lost_password.php">{"I Forgot my password"|_}</a></li>
		<li><a href="{$base_url_path}resend_validation.php">{"Re-send the validation email"|_}</a></li>
		<li><a href="{$base_url_path}faq.php">{"Frequently asked questions"|_}</a></li>
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
	<legend>{"Register a free account with"|_} {$networkName}</legend>

	<form name="signup_form" method="post" onsubmit="return false" action="{$base_ssl_path}signup.php">
	<input type="hidden" name="form_request" value="signup">
		{if $SelectNetworkUI}
			{$SelectNetworkUI}
		{/if}

		<table>
			<tr>
				<th>{"Username desired"|_}:</th>
				<td><input type="text" name="username" value="{$username}" size="30" id="form_username" onkeypress="return focusNext(this.form, 'email', event)"></td>
			</tr>
			<tr>
				<th>{"Your email address"|_}:</th>
				<td><input type="text" name="email" value="{$email}" size="30" onkeypress="return focusNext(this.form, 'password', event)"></td>
			</tr>
			<tr>
				<th>{"Password"|_}:</th>
				<td><input type="password" name="password" size="30" onkeypress="return focusNext(this.form, 'password_again', event)"></td>
			</tr>
            <tr>
                <th>{"Password (again)"|_}:</th>
                <td><input type="password" name="password_again" size="30" onkeypress="return focusNext(this.form, 'form_submit', event)"></td>
            </tr>
            <tr>
                <th></th>
                <td><input class="submit" type="submit" name="form_submit" value="{"Sign-up"|_}" onclick="if (validateForm(this.form)) this.form.submit()"></td>
            </tr>
        </table>
    </form>

    <hr>

    <p>
        <b>{"Please note"|_}</b>:
        {"While accounts are free, we <em>strongly</em> suggest that you use your previously created account if you have one."|_}
    </p>

	<p>
        {"<b>Your email address must be valid</b> in order for your account to be activated."|_}
		{"A validation email will be sent to that email address."|_}
		{"To fully activate your account you must respond to that email."|_}
	</p>

    <p>
        <b>{"Note to free web-based email users"|_}</b>:
        {"Sometimes our validation email ends up in the 'spam' folder of some providers. If you have not received any email with the validation URL 5 minutes after submitting this form, please take a look in the spam folder."|_}
    </p>

    <p>
        <b>{"You can also use the following links if you need help"|_}:</b>
        <ul>
            <li><a href="{$base_url_path}lost_username.php">{"I Forgot my username"|_}</a></li>
            <li><a href="{$base_url_path}lost_password.php">{"I Forgot my password"|_}</a></li>
        </ul>
    </p>
</fieldset>

    <div id="form_errormsg" class="errormsg">
    	{if $error != null}
		  {$error}
        {/if}
	</div>

    <script type="text/javascript">
        <!--
        {literal}
		var messages = {
        {/literal}
		  username_required: "{'Username is required.'|_}",
		  username_invalid: "{'Username contains invalid characters.'|_}",
		  email_invalid: "{'The email address must be valid (i.e. user@domain.com). Please understand that we also black-listed various temporary-email-address providers.'|_}",
		  password_empty: "{'A password of at least 6 characters is required.'|_}",
		  password_invalid: "{'Password contains invalid characters.'|_}",
		  password_twice: "{'You must type your password twice.'|_}",
		  password_match: "{'Passwords do not match.'|_}",
		  password_short: "{'Password is too short, it must be 6 characters minimum'|_}"
        {literal}
		};

        document.getElementById("form_username").focus();

		function validateForm(form) {
		  if (!isValidUsername(form.username)) {
			if (isEmpty(form.username))
			  document.getElementById("form_errormsg").innerHTML = messages.username_required;
			else
			  document.getElementById("form_errormsg").innerHTML = messages.username_invalid;

			return false;
		  }

		  if (!isValidEmail(form.email)) {
			document.getElementById("form_errormsg").innerHTML = messages.email_invalid;
			return false;
		  }

		  if (!isValidPassword(form.password)) {
			if (isEmpty(form.password))
			  document.getElementById("form_errormsg").innerHTML = messages.password_empty;
			else if (form.password.value.length<6)
			  document.getElementById("form_errormsg").innerHTML = messages.password_short;
			else
			  document.getElementById("form_errormsg").innerHTML = messages.password_invalid;

			return false;
		  }

		  if (isEmpty(form.password_again)) {
			document.getElementById("form_errormsg").innerHTML = messages.password_twice;
			focusElement(form.name, 'password_again');
			return false;
		  }

		  if (form.password.value != form.password_again.value) {
			document.getElementById("form_errormsg").innerHTML = messages.password_match;
			focusElement(form.name, 'password_again');
			return false;
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
