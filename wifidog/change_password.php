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
include BASEPATH.'include/mgmt_helpers.php';

isset($_REQUEST["username"]) && $smarty->assign("username", $_REQUEST["username"]);

if (isset($_REQUEST["submit"])) {
    $user_info = null;
    if ($_REQUEST["username"] && $_REQUEST["oldpassword"] && $_REQUEST["newpassword"] && $_REQUEST["newpassword_again"]) {
	    $current_password = $db->EscapeString(trim($_REQUEST['oldpassword']));
    	$new_password = $db->EscapeString(trim($_REQUEST['newpassword']));

	    $user_info = null;
	    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='{$_REQUEST["username"]}'", $user_info, false);
	    if ($user_info == null) {
            $smarty->assign("error", _("Unable to find ") . $_REQUEST["username"] . _(" in the database."));
	    } else {
	        $user_info = null;
	        $current_password_hash = get_password_hash($current_password);
	        $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='{$_REQUEST["username"]}' AND pass='$current_password_hash'", $user_info, false);
	        if ($user_info == null) {
                $smarty->assign("error", _("Wrong password."));
	        } else {
                if ($_REQUEST["newpassword"] != $_REQUEST["newpassword_again"]) {
                    $smarty->assign("error", _("Passwords do not match."));
                } else {
                    $new_password_hash = get_password_hash($new_password);
	                $update_successful = $db->ExecSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$user_info["user_id"]}'");
	                if ($update_successful) {
                        $smarty->append("message", _("Your password has been changed succesfully."));
                        $smarty->display("templates/validate.html");
                        exit;
	                } else {
                        $smarty->assign("error", _("Could not change your password"));
	                }
                }
            }
        }
    } else {
        $smarty->assign("error", _("Your MUST fill in all the fields"));
    }
}

$smarty->display("templates/change_password.html");
?>
