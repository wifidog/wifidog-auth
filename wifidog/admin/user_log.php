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
   * @author Copyright (C) 2004 Philippe April.
   */
  
require_once 'admin_common.php';

$total = array();
$total['incoming'] = 0;
$total['outgoing'] = 0;

if (!empty($_REQUEST['user_id'])) {
    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$_REQUEST[user_id]'",$userinfo,false);
    if (!$userinfo) {
        $smarty->assign("error", _("Unable to locate ") . $_REQUEST['user_id'] . _(" in the database."));
    } else {
	    $userinfo['account_status_description'] = $account_status_to_text[$userinfo['account_status']]; 
	    $smarty->assign("userinfo", $userinfo);
	
	    $db->ExecSql("SELECT * FROM connections,nodes WHERE user_id='{$_REQUEST['user_id']}' AND nodes.node_id=connections.node_id ORDER BY timestamp_in", $connection_array, false);
	    if ($connection_array) {
	        foreach($connection_array as $connection) {
	            $total['incoming'] += $connection['incoming'];
                $total['outgoing'] += $connection['outgoing'];
	            $connection['token_status_description'] = $token_to_text[$connection['token_status']];
	            $smarty->append("connections", $connection);
	        }
	        $smarty->assign("total", $total);
	    } else {
	        //No connections from user yet
	    }
    }
    $smarty->display("admin/templates/user_log_detailed.html");
} else {
    $smarty->assign('sort_ids', array('user_id','email','reg_date'));
    $smarty->assign('direction_ids', array('asc','desc'));

    $sort      = isset($_REQUEST['sort'])      ? $_REQUEST['sort']      : "user_id";
    $direction = isset($_REQUEST['direction']) ? $_REQUEST['direction'] : "asc";

    $smarty->assign("sort",      $sort);
    $smarty->assign("direction", $direction);

    if (isset($_REQUEST["page"]) && is_numeric($_REQUEST["page"])) {
        $current_page = $_REQUEST["page"];
    } else {
        $current_page = 1;
    }
    $smarty->assign("page", $current_page);

    $per_page = 100;
    $offset = (($current_page * $per_page) - $per_page + 1);
    $pages = $stats->getNumUsers() / $per_page;
    for ($i = 1; $i <= $pages+1; $i++) {
        $smarty->append("pages", array(
                'number' => $i,
                'selected' => ($i == $current_page),
            ));
    }

    $db->ExecSql("SELECT user_id,email,reg_date,account_status FROM users ORDER BY $sort $direction LIMIT $per_page OFFSET $offset", $users_res);
    if ($users_res) {
	    $smarty->assign("users_array", $users_res);
    } else {
        $smarty->assign("error", _('Internal error.'));
    }

    $smarty->assign("account_status_to_text", $account_status_to_text);
    $smarty->display("admin/templates/user_log.html");
}
?>
