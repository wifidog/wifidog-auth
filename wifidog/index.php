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
  /**@file index.php
   * @author Copyright (C) 2004 Benoit Grégoire
   */

define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';

$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' authentication server');
    echo "<div id='head'><h1>Wifidog authentication server for ". HOTSPOT_NETWORK_NAME ."</h1></div>\n";
    echo "<div id='content'>\n";

$row = null;
//$db->ExecSqlUniqueRes("SELECT COUNT(user_id), account_status FROM users GROUP BY account_status", $row, true);
$db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_status = ".ACCOUNT_STATUS_ALLOWED, $row, false);
$num_valid_users=$row['count'];
$row = null;
$db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM ( SELECT DISTINCT user_id FROM connections " .
	     "WHERE token_status='" . TOKEN_INUSE . "') AS online_users"	     
	     ,$row, false);
$num_online_users=$row['count'];


echo "<p>"._("The network currently has ").$num_valid_users._(" valid users.")." ".$num_online_users._(" user(s) are currently online")."</p>\n";
    echo "<ul>\n";
    echo "<li><a href='node_list.php'>List network nodes</a></li>\n";
    echo "<li><a href='./user_management/index.php'>Personal user management</a></li>\n";
    echo "<li><a href='".BASE_SSL_PATH."admin/index.php'>Administration</a></li>\n";
    echo "</ul>\n";
    echo "</div>\n";	

echo $style->GetFooter();
?>
