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

// TODO : URGENT create a search field to get user_id based on Network + username 
// All the needed methods should already be in User.php
// See very good example in /Classes/Content.php around line 708
// User::getSelectUserUI()
define('BASEPATH','../');
require_once 'admin_common.php';

$security = new Security();
$security->requireAdmin();

require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/User.php';

$user_id = $session->get(SESS_USER_ID_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

empty($_REQUEST['action'])        ? $action        = '' : $action        = $_REQUEST['action'];
empty($_REQUEST['node_id'])       ? $node_id       = '' : $node_id       = $_REQUEST['node_id'];
empty($_REQUEST['owner_user_id']) ? $owner_user_id = '' : $owner_user_id = $_REQUEST['owner_user_id'];

$node = Node::getObject($_REQUEST['node_id']);

if ($action) {
    switch ($action) {
        case 'add_owner':
        try {
                if (User::UserExists($_REQUEST['owner_user_id'])) {
                    $node->addOwner($_REQUEST['owner_user_id']);
                } else {
                    throw new Exception(_('Invalid user!'));
                }
            } catch (Exception $e) {
                echo '<p class="warning">' . $e->getMessage() . '</p>';
            }
            break;
        case 'del_owner':
            try {
                $node->removeOwner($_REQUEST['owner_user_id']);
            } catch (Exception $e) {
                echo '<p class="warning">' . $e->getMessage() . '</p>';
            }
            break;

        default:
            echo '<p class="warning">Unknown action</p>';
            break;
    }
}

$smarty->assign('title', _('Owner hotspot with'));
$smarty->assign('owner_list', $node->getOwners());
$smarty->assign('node_id', $node_id);
$smarty->display('admin/templates/hotspot_owner.html');
?>