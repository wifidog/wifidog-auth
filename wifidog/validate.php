<?php
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
   * @author Copyright (C) 2004 Benoit Grégoire, Philippe April.
   */
define('BASEPATH','./');
require_once (BASEPATH.'/include/common.php');
require_once (BASEPATH.'/include/common_interface.php');
require_once (BASEPATH.'/classes/User.php');

try {
    if (!isset($_REQUEST["token"]))
        throw new Exception(_('No token specified!'));
        
    if (!isset($_REQUEST["user_id"]))
        throw new Exception(_('No user ID specified!'));

    $user = User::getUserByID($_REQUEST['user_id']);

    if ($db->EscapeString($_REQUEST['token']) != $user->getValidationToken())
        throw new Exception(_('The validation token does not match the one in the database.'));

    if ($user->getAccountStatus() == ACCOUNT_STATUS_ALLOWED)
        throw new Exception(_('Your account has already been activated.'));

    $user->SetAccountStatus(ACCOUNT_STATUS_ALLOWED);
    $smarty->assign('message', _("Your account has been succesfully activated!\n\nYou may now browse to a remote Internet address and take advantage of the free Internet access!\n\nIf you get prompted for a login, enter the username and password you have just created."));
} catch (Exception $e) {
    $smarty->assign('message', $e->getMessage());
}

$smarty->display("templates/validate.html");
?>
