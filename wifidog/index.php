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
require_once BASEPATH.'classes/Statistics.php';
require_once (BASEPATH.'include/user_management_menu.php');

$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' authentication server');
    echo "<div id='head'><h1>Wifidog authentication server for ". HOTSPOT_NETWORK_NAME ."</h1></div>\n";
  echo "<div id='navLeft'>\n";
echo get_user_management_menu();
echo "</div>\n";
    echo "<div id='content'>\n";

$stats=new Statistics();
$num_valid_users=$stats->getNumValidUsers();

$num_online_users=$stats->getNumOnlineUsers($node_id=null);

echo "<p>"._("The network currently has ").$num_valid_users._(" valid users.")." ".$num_online_users._(" user(s) are currently online")."</p>\n";
    echo "<ul>\n";
    echo "<li><a href='hotspot_status.php'>"._("Deployed HotSpots status with coordinates")."</a></li>\n";
    echo "<li><a href='node_list.php'>"._("Full node technical status (includes non-deployed nodes)")."</a></li>\n";
    echo "<li><a href='./user_management/index.php'>"._("Personal user management")."</a></li>\n";
    echo "<li><a href='".BASE_SSL_PATH."admin/index.php'>"._("Administration")."</a></li>\n";
    echo "</ul>\n";
    echo "</div>\n";	

echo $style->GetFooter();
?>
