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
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';

if (CONF_USE_CRON_FOR_DB_CLEANUP == false) {
    garbage_collect();
}

if (isset($_REQUEST['gw_id'])) {
    $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);
} else if ($session->get(SESS_GW_ID_VAR)) {
    $_REQUEST['gw_id'] = $session->get(SESS_GW_ID_VAR);
} else {
    try {
        $node = Node::getNodeByIP($_SERVER['REMOTE_ADDR']);
        $_REQUEST['gw_id'] = $node->getID();
        $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);
    } catch (Exception $e) {
        $smarty->assign("error", $e->getMessage());
        $smarty->display("templates/generic_error.html");
        exit;
    }
}

$portal_template = $_REQUEST['gw_id'] . ".html";
$node_id = $db->EscapeString($_REQUEST['gw_id']);

$node = Node::getNode($node_id);
if ($node == null) {
    $smarty->assign("gw_id", $_REQUEST['gw_id']);
    $smarty->display("templates/message_unknown_hotspot.html");
    exit;
}

$smarty->assign('hotspot_name', $node->getName());
$smarty->assign('num_nodes', Node::getNumNodes());

$smarty->display("templates/portal_header.html");
$smarty->display("templates/portal_hotspots.html");
$smarty->display("templates/portal_footer.html");
?>
