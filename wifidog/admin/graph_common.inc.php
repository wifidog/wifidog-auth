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
  /**@file graph_common.inc.php
   * @author Copyright (C) 2005 Philippe April
   */

define('BASEPATH','../');
require_once 'admin_common.php';
require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

if (!$_REQUEST["date_from"])
    $_REQUEST["date_from"] = strftime("%Y-%m-%d 00:00");
if (!$_REQUEST["date_to"])
    $_REQUEST["date_to"] = strftime("%Y-%m-%d 11:59");

$date_constraint = "AND timestamp_in >= '{$_REQUEST['date_from']}' AND timestamp_in <= '{$_REQUEST['date_to']}'";

$node_id = isset($_REQUEST["node_id"]) ? $db->EscapeString($_REQUEST["node_id"]) : null;
$user_id = isset($_REQUEST["user_id"]) ? $db->EscapeString($_REQUEST["user_id"]) : null;
$network_id = isset($_REQUEST["network_id"]) ? $db->EscapeString($_REQUEST["network_id"]) : null;

?>
