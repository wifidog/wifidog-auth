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
require_once BASEPATH.'classes/SmartyWifidog.php';
require_once BASEPATH.'classes/Security.php';

$smarty = new SmartyWifidog;
$session = new Session;

include BASEPATH.'include/language.php';

function send_validation_email($email) {
    global $db;
    global $smarty;

    $user_info = null;
    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
    if ($user_info == null) {
        $smarty->assign("error", _("Unable to locate ") . $_REQUEST["email"] . _(" in the database."));
    } else {
        if ($user_info['account_status'] != ACCOUNT_STATUS_VALIDATION) {
            /* Note:  Do not display the username here, for privacy reasons */
            $smarty->assign("error", _("The user is not in validation period."));
	    } else {
            if (empty($user_info['validation_token'])) {
                $smarty->assign("error", _("The validation token is empty."));
            } else {
                $subject = VALIDATION_EMAIL_SUBJECT;
                $url = "http://" . $_SERVER["HTTP_HOST"] . "/validate.php?username=" . $_REQUEST["username"] . "&token=" . $user_info["validation_token"];
                $body = "Hello!

Please follow the link below to validate your account.

$url

Thank you,

The Team";
                $from = "From: " . VALIDATION_EMAIL_FROM_ADDRESS;

                mail($email, $subject, $body, $from);
                $smarty->append("message", _("An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retrieve your email and validate your account.  You may now open a browser window and go to any remote Internet address to obtain the login page."));
                $smarty->display("templates/validate.html");
                exit;
            }
        }
    }
}

if (isset($_REQUEST["submit"])) {

    isset($_REQUEST["username"]) && $smarty->assign("username", $_REQUEST["username"]);
    isset($_REQUEST["email"]) && $smarty->assign("email", $_REQUEST["email"]);

    if (!isset($_REQUEST["username"]) || !$_REQUEST["username"]) {
        $smarty->assign("error", _("Username is required."));
    } else if (!ereg("^[0-9a-zA-Z]*$", $_REQUEST["username"])) {
        $smarty->assign("error", _("Username contains invalid characters."));
    } else if (!isset($_REQUEST["email"]) || !$_REQUEST["email"]) {
        $smarty->assign("error", _("A valid email address is required."));
    } else if (!ereg("^.*@.*\..*$", $_REQUEST["email"])) {
        $smarty->assign("error", _("The email address must be of the form user@domain.com."));
    } else if (!isset($_REQUEST["password"]) || !$_REQUEST["password"]) {
        $smarty->assign("error", _("A password of at least 6 characters is required."));
    } else if (!ereg("^[0-9a-zA-Z]*$", $_REQUEST["password"])) {
        $smarty->assign("error", _("Password contains invalid characters."));
    } else if (!isset($_REQUEST["password_again"])) {
        $smarty->assign("error", _("You must type your password twice."));
    } else if ($_REQUEST["password"] != $_REQUEST["password_again"]) {
        $smarty->assign("error", _("Passwords do not match."));
    } else if (strlen($_REQUEST["password"]) < 6) {
        $smarty->assign("error", _("Password is too short, it must be 6 characters minimum."));
    } else {
        /* Everything is ok */
        $_REQUEST["username"] = trim($_REQUEST["username"]);
        $_REQUEST["email"] = trim($_REQUEST["email"]);
        $password = $db->EscapeString($_REQUEST['password']);
        $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='{$_REQUEST["username"]}'", $user_info_username, false);
        $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='{$_REQUEST["email"]}'", $user_info_email, false);
        if ($user_info_username != null) {
            $smarty->assign("error", _("Sorry, a user account is already associated to this username. Pick another one."));
        } else if ($user_info_email) {
            $smarty->assign("error", _("Sorry, this email address is already registered."));
            $smarty->append("choice", array(
                        "description"   => _("Email me my username"),
                        "link"          => "mail_username.php",
                    )
                );
        } else {
            $status = ACCOUNT_STATUS_VALIDATION;
            $token = gentoken();
            $password_hash = get_password_hash($_REQUEST["password"]);
            $update_successful = $db->ExecSqlUpdate("INSERT INTO users (user_id,email,pass,account_status,validation_token,reg_date) VALUES ('{$_REQUEST["username"]}','{$_REQUEST["email"]}','$password_hash','{$status}','{$token}',NOW())");
            if ($update_successful) {
                send_validation_email($_REQUEST["email"], $smarty);
            } else {
                $smarty->assign("error", _("An internal error occured, please contact us."));
            }
        }
    }
}

$smarty->display("templates/signup.html");
?>
