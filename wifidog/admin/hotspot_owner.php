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
  /**@file hotspot.php
   * Node configuration page
   * @author Copyright (C) 2005 Pascal Leclerc
   */

// TODO : Permettre la recherche de users directement dans l'interface

require_once 'admin_common.php';

$user_id = $session->get(SESS_USERNAME_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

empty($_REQUEST['action'])        ? $action        = '' : $action        = $_REQUEST['action'];
empty($_REQUEST['node_id'])       ? $node_id       = '' : $node_id       = $_REQUEST['node_id'];
empty($_REQUEST['owner_user_id']) ? $owner_user_id = '' : $owner_user_id = $_REQUEST['owner_user_id'];

if ("$action" == "add_owner") { // Add new owner in DB
    // TODO: VALIDER les champs de donnees node_id et user_id
    $sql_successful = $db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE user_id='$owner_user_id'", $user_id_result);
    
    if (is_array($user_id_result)) { // If it's an array a valide user_id was return
        $valid_user_id = array_shift($user_id_result);
        $sql_successful = $db->ExecSqlUpdate("INSERT INTO node_owners (node_id, user_id) VALUES ('$node_id','$owner_user_id')");
        if (!$sql_successful)
            $smarty->assign("error_message", _("Internal error"));
    } else {
        $smarty->assign("error_message", _("Invalid user id ") . "($owner_user_id)");
    }
} elseif ("$action" == "del_owner") {
    $db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='$node_id' AND user_id='$owner_user_id'");
    // Maybe print a success action message (like error_message, but not in red)
}

$smarty->assign("title", "Owner hotspot with");
$db->ExecSql("SELECT user_id FROM node_owners WHERE node_id='$node_id'", $node_owner_results);

$tmpArray = array();
if (is_array($node_owner_results)) {
    foreach($node_owner_results as $node_owner_row) {
        $smarty->append("owner_list", $node_owner_row);
        array_push($tmpArray, $node_owner_row['user_id']); // Use in javascript validation
    }
}
$user_id_array = implode('","', $tmpArray);

$smarty->assign("user_id_array", $user_id_array);
$smarty->assign("node_id", $node_id);
$smarty->display("admin/templates/hotspot_owner.html");

?>
