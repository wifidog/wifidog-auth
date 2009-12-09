<?php

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
 * @author     Philippe April
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2009 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('classes/User.php');
require_once('classes/Security.php');
require_once('classes/MainUI.php');
require_once('classes/Mail.php');
$smarty = SmartyWifidog::getObject();
/**
 * Load custom signup URL if it has been defined in config.php
 */
if (defined("CUSTOM_SIGNUP_URL")) {
    header("Location: " . CUSTOM_SIGNUP_URL . "?gw=" . base64_encode($_SERVER['REQUEST_URI']));
    exit;
}

/**
 * Validates the format of an username
 *
 * @param string $username The username
 *
 * @return void
 *
 * @throws Exeption if no username was given or if the username contains
 *         invalid characters
 */
function validate_username($username)
{
    if (!isset ($username) || !$username) {
        throw new Exception(_('Username is required.'));
    }

    if (!ereg("^[0-9a-zA-Z_]*$", $username)) {
        throw new Exception(_('Username contains invalid characters.'));
    }
}

/**
 * Validates the format of an email address
 *
 * @param string $email The email address
 *
 * @return void
 *
 * @throws Exeption if no email address was given or if the format of the email
 *         address is invalid characters or if the domain of the email address
 *         is black-listed
 */
function validate_email($email)
{
    if (!isset ($email) || !$email) {
        throw new Exception(_("A valid email address is required."));
    }

   	if (Mail::validateEmailAddress($email) === false) {
        throw new Exception(_("The email address must be valid (i.e. user@domain.com). Please understand that we also black-listed various temporary-email-address providers."));
   	}
}

/**
 * Validates the format of a password
 *
 * @param string $password       The password
 * @param string $password_again Copy of password
 *
 * @return void
 *
 * @throws Exeption if no password was given or if the password contains
 *         invalid characters or if the two given passwords don't match or
 *         if the password is too short
 */
function validate_passwords($password, $password_again)
{
    if (!isset ($password) || !$password) {
        throw new Exception(_("A password of at least 6 characters is required."));
    }

    if (!ereg("^[0-9a-zA-Z]*$", $password)) {
        throw new Exception(_("Password contains invalid characters.  Allowed characters are 0-9, a-z and A-Z"));
    }

    if (!isset ($password_again)) {
        throw new Exception(_("You must type your password twice."));
    }

    if ($password != $password_again) {
        throw new Exception(_("Passwords do not match."));
    }

    if (strlen($password) < 6) {
        throw new Exception(_("Password is too short, it must be 6 characters minimum."));
    }
}

/**
 * Process signing up
 */

// Init ALL smarty values
$smarty->assign('username', "");
$smarty->assign('email', "");
$smarty->assign('error', "");
$smarty->assign('auth_sources', "");
$smarty->assign('selected_auth_source', "");
$smarty->assign('SelectNetworkUI', "");

/* Signup was requested */
if (isset ($_REQUEST["form_request"]) && $_REQUEST["form_request"] == "signup") {
    // Sanitize user-entered values
    $username = trim($_REQUEST['username']);
    $email = trim($_REQUEST['email']);
    $password = trim($_REQUEST['password']);
    $password_again = trim($_REQUEST['password_again']);

    $smarty->assign('username', $username);
    $smarty->assign('email', $email);

    $selectedNetwork = Network::getObject($_REQUEST['auth_source']);

    try {
        /*
         * Tool content
         */

        // Retrieve the TOOLCONTENT section of the Smarty template
        $smarty->assign('sectionTOOLCONTENT', true);
        $smarty->assign('sectionMAINCONTENT', false);
        $htmlToolContent = $smarty->fetch("templates/sites/signup.tpl");

        /*
         * Main content
         */

        if (!isset($selectedNetwork)) {
            throw new Exception(_("Sorry, this network does not exist !"));
        }

        if (!$selectedNetwork->getAuthenticator()->isRegistrationPermitted()) {
            throw new Exception(_("Sorry, this network does not accept new user registration !"));
        }

        // Validate entered values
        validate_username($username);
        validate_email($email);
        validate_passwords($password, $password_again);

        // Check if user exists
        if (User::getUserByUsernameAndOrigin($username, $selectedNetwork)) {
            throw new Exception(_("Sorry, a user account is already associated to this username. Pick another one."));
        }

        if (User::getUserByEmailAndOrigin($email, $selectedNetwork)) {
            throw new Exception(_("Sorry, a user account is already associated to this email address."));
        }

        // Create user and send him the validation email
        $created_user = User::createUser(get_guid(), $username, $selectedNetwork, $email, $password);
        $created_user->sendValidationEmail();

        // Authenticate this new user automatically
        $errmsg = "";
        $authenticated_user = $selectedNetwork->getAuthenticator()->login($username, $password, $errmsg);
        if (empty($authenticated_user)) {
            throw new Exception(_("Unable to authenticate newly created user.  Please report this bug.  Error was: $errmsg"));
        }

        // While in validation period, alert user that he should validate his account ASAP
        $validationMsgHtml = "<div id='warning_message_area'>\n";
        $validationMsgHtml .= _("An email with confirmation instructions was sent to your email address.");
        $validationMsgHtml .= sprintf(_("Your account has been granted %s minutes of access to retrieve your email and validate your account."), ($selectedNetwork->getValidationGraceTime() / 60));
        $validationMsgHtml .= _('You may now open a browser window or start your email client and go to any remote Internet address to obtain the validation email.');
        $validationMsgHtml .= "</div>\n";

        // If the user is at a REAL hotspot, login the user and give him his sign-up minutes right away
        $session = Session::getObject();
        $gw_id = $session->get(SESS_GW_ID_VAR);
        $gw_address = $session->get(SESS_GW_ADDRESS_VAR);
        $gw_port = $session->get(SESS_GW_PORT_VAR);
        $mac = $session->get(SESS_USER_MAC_VAR);

        if ($gw_id && $gw_address && $gw_port) {
            // Make sure the user IDs match
            if(($created_user->getId() == $authenticated_user->getId())) {
                $token = $created_user->generateConnectionToken($mac);

                $redirURL = "http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token;
            } else {
                $redirURL = BASE_NON_SSL_PATH;
            }

            MainUI::redirect($redirURL, 0);
        }
        // Set section of Smarty template
        $smarty->assign('sectionTOOLCONTENT', false);
        $smarty->assign('sectionMAINCONTENT', true);
        // Compile HTML code
        $htmlMainContent = $smarty->fetch("templates/sites/signup.tpl");

        /*
         * Render output (siggess message)
         */
        $ui = MainUI::getObject();

        $ui->addContent('left_area_middle', $htmlToolContent);
        $ui->addContent('main_area_middle', $htmlMainContent);

        // $ui->addContent('page_header', $validationMsgHtml);
        $ui->addContent('main_area_top', $validationMsgHtml);

        $ui->display();

        // We're done ...
        exit;
    }
    catch (Exception $e) {
        $smarty->assign('error', $e->getMessage());

        // Reset HTML output
        $htmlToolContent = "";
        $htmlMainContent = "";
    }
}

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);
$smarty->assign('sectionMAINCONTENT', false);

// Compile HTML code
$htmlToolContent = $smarty->fetch("templates/sites/signup.tpl");

/*
 * Main content
 */

// Use the account_origin along, if it was set (it may be set in case there was an error processing the form).
if (isset($_REQUEST["auth_source"])) {
    $selectedNetwork = Network::getObject($_REQUEST['auth_source']);
}
else {
    $selectedNetwork = Network::getDefaultNetwork();
}

if (Server::getServer()->getUseGlobalUserAccounts()){
    $smarty->assign('SelectNetworkUI', "<input type=\"hidden\" name=\"auth_source\" value='".$selectedNetwork->getId()."' />");
}
else {
    //Make sure to only list networks whose authenticator allows user self-signup
    $smarty->assign('SelectNetworkUI', Network::getSelectUI('auth_source', array('preSelectedObject' => $selectedNetwork, 'onlyNetwoksAllowingSignup' => true)) );
}

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', true);
// Compile HTML code
$htmlMainContent = $smarty->fetch("templates/sites/signup.tpl");

/*
 * Render final output
 */
$ui = MainUI::getObject();
$ui->addContent('left_area_middle', $htmlToolContent);
$ui->addContent('main_area_middle', $htmlMainContent);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
