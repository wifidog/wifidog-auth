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

isset($_REQUEST["username"]) && $smarty->assign("username", $_REQUEST["username"]);

if (isset($_REQUEST["submit"])) {
    try {
        if (!$_REQUEST["username"] || !$_REQUEST["oldpassword"] || !$_REQUEST["newpassword"] || !$_REQUEST["newpassword_again"])
            throw new Exception(_('You MUST fill in all the fields.'));
        $username = $db->EscapeString(trim($_REQUEST['username']));
	    $current_password = $db->EscapeString(trim($_REQUEST['oldpassword']));
    	$new_password = $db->EscapeString(trim($_REQUEST['newpassword']));

        if ($_REQUEST["newpassword"] != $_REQUEST["newpassword_again"])
            throw new Exception(_("Passwords do not match."));

        // Warning for now, password change only works for local users, registered through our signup process.
        $user = User::getUserByUsernameAndOrigin($username, LOCAL_USER_ACCOUNT_ORIGIN);
        if ($user->getPasswordHash() != User::passwordHash($current_password))
            throw new Exception(_("Wrong password."));

        $user->SetPassword($new_password);
        $smarty->assign("message", _("Your password has been changed succesfully."));
        $smarty->display("templates/validate.html");
        exit;
    } catch (Exception $e) {
        $smarty->assign("error", $e->getMessage());
    }
}
$smarty->display("templates/change_password.html");
?>
