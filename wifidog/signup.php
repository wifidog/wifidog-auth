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
define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/User.php';

if(defined("CUSTOM_SIGNUP_URL")){
	header("Location: ".CUSTOM_SIGNUP_URL."?gw=".base64_encode($_SERVER['REQUEST_URI']));
	exit;	
}

function validate_username($username) {
    if (!isset($username) || !$username)
        throw new Exception(_('Username is required.'));

    if (!ereg("^[0-9a-zA-Z_]*$", $username))
        throw new Exception(_('Username contains invalid characters.'));
}

function validate_email($email) {
    if (!isset($email) || !$email)
        throw new Exception(_("A valid email address is required."));

    if (!ereg("^.*@.*\..*$", $email))
        throw new Exception(_("The email address must be of the form user@domain.com."));
}

function validate_passwords($password, $password_again) {
    if (!isset($password) || !$password)
        throw new Exception(_("A password of at least 6 characters is required."));

    if (!ereg("^[0-9a-zA-Z]*$", $password))
        throw new Exception(_("Password contains invalid characters."));

    if (!isset($password_again))
        throw new Exception(_("You must type your password twice."));

    if ($password != $password_again)
        throw new Exception(_("Passwords do not match."));

    if (strlen($password) < 6)
        throw new Exception(_("Password is too short, it must be 6 characters minimum."));
}

if (isset($_REQUEST["submit"])) {
    $username       = trim($_REQUEST['username']);
    $email          = trim($_REQUEST['email']);
    $password       = trim($_REQUEST['password']);
    $password_again = trim($_REQUEST['password_again']);
    $smarty->assign('username', $username);
    $smarty->assign('email',    $email);

    try {
        validate_username($username);
        validate_email($email);
        validate_passwords($password, $password_again);

        if (User::UserExists($username))
            throw new Exception(_("Sorry, a user account is already associated to this username. Pick another one."));

        if (User::EmailExists($email))
            throw new Exception(_("Sorry, a user account is already associated to this email address."));

        $user = User::CreateUser($username, $email, $password);
        $user->sendValidationEmail();
        $smarty->assign('message', _('An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retrieve your email and validate your account.  You may now open a browser window and go to any remote Internet address to obtain the login page.'));
        $smarty->display("templates/validate.html");
        exit;
    } catch (Exception $e) {
        $smarty->assign('error', $e->getMessage());
    }
}

$smarty->display("templates/signup.html");
?>
