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
  /**@file Security.php
   * @author Copyright (C) 2004 Technologies Coeus inc.
   */
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Session.php';

/** 
 */
class Security {
  private $session;

  function Security() {
    $this->session = new Session();
  }

/**
 * @todo:  Move to User
*/
  function login($user_id, $hash) {
    global $db;
    $user_id = $db->EscapeString($user_id);
    $hash = $db->EscapeString($hash);
    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$user_id' AND pass='$hash'", $user_info, false);
    if (empty($user_info)) {
	echo '<p class=error>'._("Your user_id and password do not match")."</p>\n";
	exit;
    } else {
      /* Access granted */
      $this->session->set(SESS_USER_ID_VAR, $user_id);
      $this->session->set(SESS_PASSWORD_HASH_VAR, $hash);
    }
  }

  function requireAdmin() {
     if (!User::getCurrentUser()->isSuperAdmin())
      {
      echo '<p class=error>'._("You do not have administrator privileges")."</p>\n";
      exit;
    } else {
      /* Access granted */
      //echo '<p class=error>'._("Access granted")."</p>\n";
    }

  }

  function requireOwner($node_id) {
    global $db;
    //$this->session->dump();
    $user = $this->session->get(SESS_USER_ID_VAR);
    $password_hash = $this->session->get(SESS_PASSWORD_HASH_VAR);

    $db->ExecSqlUniqueRes("SELECT * FROM users NATURAL JOIN node_owners WHERE (users.user_id='$user') AND pass='$password_hash' AND node_owners.node_id='$node_id'", $user_info, false);
    if(empty($user_info)) {
        echo '<p class=error>'._("You do not have owner privileges")."</p>\n";
        exit;
    } else {
      /* Access granted */
	  //echo '<p class=error>'._("Access granted")."</p>\n";
    }
  }

} /* end class Security */
?>