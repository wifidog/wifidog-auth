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
define('BASEPATH','../');
require_once 'admin_common.php';
require_once BASEPATH.'classes/MainUI.php';

$security = new Security();
$security->requireAdmin();

require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/User.php';

$user_id = $session->get(SESS_USER_ID_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

empty($_REQUEST['action'])  ? $action  = '' : $action  = $_REQUEST['action'];
empty($_REQUEST['node_id']) ? $node_id = '' : $node_id = $_REQUEST['node_id'];

if ($action == 'edit_node') { // Allow node creation or node edition
    $smarty->assign("title", _("Edit a hotspot"));
    
    if ($node_id != "new") { // Node creation
        $node = Node::getObject($node_id);
    }

    $smarty->assign('node_id', $node->getID());
    $smarty->assign('node_deployment_status', $node->getDeploymentStatus());
    $smarty->assign('node_info', $node->getInfoArray());
    $smarty->assign('all_deployment_status', Node::getAllDeploymentStatus());

    $ui = new MainUI();
    $ui->setToolSection('ADMIN');
    $ui->setMainContent($smarty->fetch("admin/templates/hotspot_edit.html"));
    $ui->display();
    //$smarty->display('admin/templates/hotspot_edit.html');

} else if ($action == 'add_node') { // Display hotspot creation form
    /* max() + 1 doesn't work well when max() returns a String
    if ("$node_id" == "new") { // Allow user to get a valide node_id
        $db->ExecSqlUniqueRes("SELECT max(node_id) + 1 FROM nodes", $new_node, false);
        $new_node = array_shift($new_node) or $new_node = 0;
        $javascript = "<input type='button' value='Get valid ID' onclick='javascript:document.auth.new_node_id.value=$new_node; return false;'>";
        $smarty->assign("javascript", $javascript);
    }
    */

    $smarty->assign('title', _('Add a new hotspot'));
    $smarty->assign('all_deployment_status', Node::getAllDeploymentStatus());
    $ui = new MainUI();
    $ui->setToolSection('ADMIN');
    $ui->setMainContent($smarty->fetch("admin/templates/hotspot_edit.html"));
    $ui->display();
    //$smarty->display('admin/templates/hotspot_edit.html');

} else if ($action == 'owner') { // Display hotspot owner list and add form
    $smarty->assign('title', _('Owner hotspot with'));
    try {
        $node = Node::getObject($node_id);
        $smarty->assign('node_id', $node->getName());
        $smarty->assign('owner_list', $node->getOwners());
        $ui = new MainUI();
        $ui->setToolSection('ADMIN');
        $ui->setMainContent($smarty->fetch("admin/templates/hotspot_owner.html"));
        $ui->display();
     //   $smarty->display('admin/templates/hotspot_owner.html');
    } catch (Exception $e) {
        echo $e->getMessage();
        exit;
    }

    //foreach($node_owner_results as $node_owner_row) {
    //    $smarty->append("owner_list", $node_owner_row);
    //}
  
} else {
    if ($action == 'update_node' || $action == 'add_new_node') {
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

        switch ($action) {
            case 'add_new_node':
                try {
                    $node = Node::createNode(
                            $new_node_id,
                            $name,
                            $rss_url,
                            $home_page_url,
                            $description,
                            $map_url,
                            $street_address,
                            $public_phone_number,
                            $public_email,
                            $mass_transit_info,
                            $node_deployment_status
                        );
                } catch (Exception $e) {
                    echo '<p class="warning">'.$e->getMessage().'</p>'; 
                }
                break;

            case 'update_node':
                if ($new_node_id) {
                  try {
                    $node = Node::getObject($new_node_id);
                    $node->setInfos( array(
                            'name'                   => $name,
                            'rss_url'                => $rss_url,
                            'home_page_url'          => $home_page_url,
                            'description'            => $description,
                            'map_url'                => $map_url,
                            'street_address'         => $street_address,
                            'public_phone_number'    => $public_phone_number,
                            'public_email'           => $public_email,
                            'mass_transit_info'      => $mass_transit_info,
                            'node_deployment_status' => $node_deployment_status
                        ));
                  } catch (Exception $e) {
                    echo '<p class="warning">'.$e->getMessage().'</p>'; 
                  }
                } else {
                    echo "NO NODE ID, this is a bug";
                }
                break;
                /*
                NOTE IMPORTANTE : Penser de mettre a jour les node_owners d'un node_id modifie
                NOTE IMPORTANTE : Penser renommer le repertoire de l'ancien node_id vers le nouveau dans local_content 
                */

            default:
                echo '<p class="warning">Unexpected results</p>'; 
                exit;
                break;
        }

    } elseif ($action == 'del_node') {
        try {
        	$node = Node::getObject($node_id);
            $node->delete($errmsg);
        } catch (Exception $e) {
            echo '<p class="warning">'.$e->getMessage().'</p>'; 
        }
        // NOTE IMPORTANTE : Penser d'effacer le contenu du node efface dans local_content/$node_id
    } elseif ($action == 'add_owner') {
        try {
            if (User::UserExists($_REQUEST['owner_user_id'])) {
                $node = Node::getObject($_REQUEST['node_id']);
                $node->addOwner($_REQUEST['owner_user_id']);
            } else {
                throw new Exception(_('Invalid user!'));
            }
        } catch (Exception $e) {
            echo '<p class="warning">' . $e->getMessage() . '</p>';
        }
    } elseif ($action == 'del_owner') {
        try {
            $node = Node::getObject($_REQUEST['node_id']);
            $node->removeOwner($_REQUEST['owner_user_id']);
        } catch (Exception $e) {
            echo '<p class="warning">' . $e->getMessage() . '</p>';
        }
    }

    $nodes = Node::getAllNodesOrdered("node_id");
    if (is_array($nodes)) {
        $smarty->assign('nodes', $nodes);
    } else {
        $smarty->assign('error_message', _('There are no hotspot on this network.'));
    }
    
    $ui = new MainUI();
    $ui->setToolSection('ADMIN');
    $ui->setMainContent($smarty->fetch("admin/templates/hotspot_display.html"));
    $ui->display();
    //$smarty->display('admin/templates/hotspot_display.html');
}

?>