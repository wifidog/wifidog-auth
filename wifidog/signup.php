<?php
// $Id$
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file
 * Login page
 * @author Copyright (C) 2004 Benoit Grégoire et Philippe April
 */
define('BASEPATH', './');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/MainUI.php';

if (defined("CUSTOM_SIGNUP_URL"))
{
	header("Location: ".CUSTOM_SIGNUP_URL."?gw=".base64_encode($_SERVER['REQUEST_URI']));
	exit;
}

function validate_username($username)
{
	if (!isset ($username) || !$username)
		throw new Exception(_('Username is required.'));

	if (!ereg("^[0-9a-zA-Z_]*$", $username))
		throw new Exception(_('Username contains invalid characters.'));
}

function validate_email($email)
{
	if (!isset ($email) || !$email)
		throw new Exception(_("A valid email address is required."));

	if (!ereg("^.*@.*\..*$", $email))
		throw new Exception(_("The email address must be of the form user@domain.com."));
}

function validate_passwords($password, $password_again)
{
	if (!isset ($password) || !$password)
		throw new Exception(_("A password of at least 6 characters is required."));

	if (!ereg("^[0-9a-zA-Z]*$", $password))
		throw new Exception(_("Password contains invalid characters."));

	if (!isset ($password_again))
		throw new Exception(_("You must type your password twice."));

	if ($password != $password_again)
		throw new Exception(_("Passwords do not match."));

	if (strlen($password) < 6)
		throw new Exception(_("Password is too short, it must be 6 characters minimum."));
}

if (isset ($_REQUEST["submit"]))
{
	$username = trim($_REQUEST['username']);
	$email = trim($_REQUEST['email']);
	$password = trim($_REQUEST['password']);
	$password_again = trim($_REQUEST['password_again']);
	$smarty->assign('username', $username);
	$smarty->assign('email', $email);
    $network = Network::getObject($_REQUEST['auth_source']);
	try
	{
		if (!isset($network))
			throw new Exception(_("Sorry, this network does not exist !"));
		if (!$network->getAuthenticator()->isRegistrationPermitted())
			throw new Exception(_("Sorry, this network does not accept new user registration !"));
		validate_username($username);
		validate_email($email);
		validate_passwords($password, $password_again);

		if (User :: getUserByUsernameAndOrigin($username, $network))
			throw new Exception(_("Sorry, a user account is already associated to this username. Pick another one."));

		if (User :: getUserByEmailAndOrigin($email, $network))
			throw new Exception(_("Sorry, a user account is already associated to this email address."));

		$created_user = User :: createUser(get_guid(), $username, $network, $email, $password);
		$created_user->sendValidationEmail();
		
		// If the user is at a REAL hotspot, give him his 15 minutes right away
		$gw_id = $session->get(SESS_GW_ID_VAR);
        $gw_address = $session->get(SESS_GW_ADDRESS_VAR);
        $gw_port = $session->get(SESS_GW_PORT_VAR);
        
		if($gw_id && $gw_address && $gw_port)
		{
			// Authenticate this new user automatically
	        $authenticated_user = $network->getAuthenticator()->login($username, $password, $errmsg);
	        
	        // Make sure the user IDs match
			if(($created_user->getId() == $authenticated_user->getId()))
			{
				$token = $created_user->generateConnectionToken();
				header("Location: http://{$gw_address}:{$gw_port}/wifidog/auth?token={$token}");
			}
			else
				header("Location: ".BASE_NON_SSL_PATH);
		}
		else
			$smarty->assign('message', _('An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retrieve your email and validate your account.  You may now open a browser window and go to any remote Internet address to obtain the login page.'));
			
		//$smarty->display("templates/validate.html");
        
        $ui = new MainUI();
        $ui->setMainContent($smarty->fetch("templates/validate.html"));
        $ui->display();
		exit;
	}
	catch (Exception $e)
	{
		$smarty->assign('error', $e->getMessage());
	}
}
// Add the auth servers list to smarty variables
$sources = array ();
// Preserve keys
$network_array=Network::getAllNetworks();
foreach ($network_array as $network)
	if ($network->getAuthenticator()->isRegistrationPermitted())
		$sources[$network->getId()] = $network->getName();
		
isset ($sources) && $smarty->assign('auth_sources', $sources);
// Pass the account_origin along, if it's set
isset ($_REQUEST["auth_source"]) && $smarty->assign('selected_auth_source', $_REQUEST["auth_source"]);

$ui = new MainUI();
$smarty->assign('SelectNetworkUI', Network::getSelectNetworkUI('auth_source'));
$ui->setMainContent($smarty->fetch("templates/signup.html"));
$ui->display();
?>
