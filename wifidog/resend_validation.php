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

if (isset($_REQUEST["submit"])) {
    if (!$_REQUEST["username"]) {
        $smarty->assign("error", _("Please specify a username"));
    } else {
        try {
        	// Get a local user
            $user = User::getUserByUsernameAndOrigin($_REQUEST['username'], LOCAL_USER_ACCOUNT_ORIGIN);
            $user->sendValidationEmail();
            $smarty->assign('message', _("An email with confirmation instructions was sent to your email address."));
            $smarty->display("templates/validate.html");
            exit;
        } catch (Exception $e) {
            $smarty->assign('error', $e->getMessage());
        }
    }
}

$smarty->display("templates/resend_validation.html");
?>
