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
  
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/SmartyWifidog.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/Statistics.php';
$security=new Security();
$security->requireAdmin();

$smarty = new SmartyWifidog;
$session = new Session;
$stats = new Statistics();

include BASEPATH.'include/language.php';

$db->ExecSql("SELECT * FROM connections,users,nodes WHERE token_status='" . TOKEN_INUSE . "' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY timestamp_in DESC", $users_res);
if ($users_res) {
	$smarty->assign("users_array", $users_res);
} else {
	$smarty->assign("error", _('Internal error.'));
}

$smarty->display("admin/templates/online_users.html");
?>
