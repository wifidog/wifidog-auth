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
   * This will respond to the gateway to tell them that the gateway is still up, and also log the gateway checking in for network monitoring
   * @author Copyright (C) 2004 Alexandre Carmel-Veilleux <acv@acv.ca>
   */
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';

echo "Pong";
    $node_id = $db->EscapeString($_REQUEST['gw_id']);
$db->ExecSqlUpdate("UPDATE nodes SET last_heartbeat_ip='$_SERVER[REMOTE_ADDR]', last_heartbeat_timestamp=NOW() WHERE node_id='$node_id'");
	
?>
