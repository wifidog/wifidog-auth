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

require_once 'admin_common.php';

$user_id = $session->get(SESS_USERNAME_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

empty($_REQUEST['action'])  ? $action  = '' : $action  = $_REQUEST['action'];
empty($_REQUEST['node_id']) ? $node_id = '' : $node_id = $_REQUEST['node_id'];

if ($action=='edit_node') { // Allow node creation or node edition
    $smarty->assign("title", _("Edit a hotspot with"));
    
    $db->ExecSql("SELECT * FROM node_deployment_status", $node_deployment_status_results, false);

    if ("$node_id" != "new") { // Node creation
        $db->ExecSqlUniqueRes("SELECT node_id, name, rss_url, home_page_url, description, map_url, street_address, public_phone_number, public_email, mass_transit_info, node_deployment_status FROM nodes WHERE node_id='$node_id'", $node_result, false);
    }
  
    $smarty->assign("node", $node_result);
    $smarty->assign("user_id", $user_id);
    $smarty->assign("node_id", $node_id);

    foreach($node_deployment_status_results as $status) {
        $smarty->append('node_deployment_status', "$status[node_deployment_status]");
    }
    $smarty->display("admin/templates/hotspot_edit.html");
} elseif ($action=='add_node') { // Display hotspot creation form
    $smarty->assign("title", _("Add a new hotspot with"));

    $db->ExecSql("SELECT * FROM node_deployment_status", $node_deployment_status_results, false);

    /* max() + 1 doesn't work well when max() returns a String
    if ("$node_id" == "new") { // Allow user to get a valide node_id
        $db->ExecSqlUniqueRes("SELECT max(node_id) + 1 FROM nodes", $new_node, false);
        $new_node = array_shift($new_node) or $new_node = 0;
        $javascript = "<input type='button' value='Get valid ID' onclick='javascript:document.auth.new_node_id.value=$new_node; return false;'>";
        $smarty->assign("javascript", $javascript);
    }
    */

    $smarty->assign("node_id", $node_id);

    foreach($node_deployment_status_results as $status) {
        $smarty->append('node_deployment_status', "$status[node_deployment_status]");
    }

    $smarty->display("admin/templates/hotspot_edit.html");
} elseif ($action=='owner') { // Display hotspot owner list and add form
    $smarty->assign("title", "Owner hotspot with");
    $db->ExecSql("SELECT user_id FROM node_owners WHERE node_id='$node_id'", $node_owner_results);

    $smarty->assign('owner_list', $node_owner_results);
    //foreach($node_owner_results as $node_owner_row) {
    //    $smarty->append("owner_list", $node_owner_row);
    //}
  
    $smarty->assign("node_id", $node_id);
    $smarty->display("admin/templates/hotspot_owner.html");
} else {
    if ($action == 'update_node' || $action == 'add_new_node') { // Hotspot DB update or new hotspot creation
        $new_node_id            = $_REQUEST['new_node_id'];
        $name                   = $_REQUEST['name'];
        $rss_url                = $_REQUEST['rss_url'];
        $home_page_url          = $_REQUEST['home_page_url'];
        $description            = $_REQUEST['description'];
        $map_url                = $_REQUEST['map_url'];
        $street_address         = $_REQUEST['street_address'];
        $public_phone_number    = $_REQUEST['public_phone_number'];
        $public_email           = $_REQUEST['public_email'];
        $mass_transit_info      = $_REQUEST['mass_transit_info'];
        $node_deployment_status = $_REQUEST['node_deployment_status'];

        if ($action == 'add_new_node') { // SQL insert query for adding new node
            
            $sql_successful = $db->ExecSqlUpdate("INSERT INTO nodes (node_id, name, rss_url, creation_date, home_page_url, description, map_url, street_address, public_phone_number, public_email, mass_transit_info, node_deployment_status) VALUES ('$new_node_id','$name','$rss_url',NOW(),'$home_page_url','$description','$map_url','$street_address','$public_phone_number','$public_email','$mass_transit_info','$node_deployment_status')");

        } elseif ($action == 'update_node') { // SQL update query for updating old node
            $sql_successful = $db->ExecSqlUpdate("UPDATE nodes SET node_id='$new_node_id',name='$name',rss_url='$rss_url',home_page_url='$home_page_url',description='$description',map_url='$map_url',street_address='$street_address',public_phone_number='$public_phone_number',public_email='$public_email',mass_transit_info='$mass_transit_info',node_deployment_status='$node_deployment_status' WHERE node_id='$node_id'");
            // NOTE IMPORTANTE : Penser de mettre a jour les node_owners d'un node_id modifie
            // NOTE IMPORTANTE : Penser renommer le repertoire de l'ancien node_id vers le nouveau dans local_content 
        } else { 
          echo '<p class="warning">Unexpected results</p>\n'; 
        }

        if (!$sql_successful) {
            echo '<p class="warning">' . _('Internal error.') . '</p>\n';
        }
    } elseif ($action == 'del_node') {
        $db->ExecSqlUpdate("DELETE FROM nodes WHERE node_id='$node_id'", false);
        $db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='$node_id'", false);
        // NOTE IMPORTANTE : Penser d'effacer le contenu du node efface dans local_content/$node_id
    } elseif ("$action" == "add_owner") { // Add new owner in DB
        // TODO: VALIDER les champs de donnees node_id et user_id
        $owner_user_id = $_REQUEST['owner_user_id'];
        $sql_successful = $db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE user_id='$owner_user_id'", $user_id_result, true);
        $valid_user_id = array_shift($user_id_result);
        if (!empty($valid_user_id)) { // Valide user_id
            $sql_successful = $db->ExecSqlUpdate("INSERT INTO node_owners (node_id, user_id) VALUES ('$node_id','$owner_user_id')", true);
            if (!$sql_successful) {
                echo '<p class="warning">' . _('Internal error.') . '</p>\n';
            }
        } else {
            echo '<p class="warning">' . _('Invalid user.') . '</p>\n';
        }
    } elseif ("$action" == 'del_owner') {
      $db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='$node_id' AND user_id='$user_id'");
    }

    $db->ExecSql("SELECT node_id, name, creation_date from nodes", $node_results, false);

    if (is_array($node_results)) { // If no row return, $node_results will be NULL
        $smarty->assign('nodes', $node_results);
        //foreach($node_results as $node_row) {
        //    $smarty->append("nodes", $node_row);
        //}
    } else {
        $smarty->assign("error_message", _("There is not hotspot for this network"));
    }
    $smarty->display("admin/templates/hotspot_display.html");
}

?>
