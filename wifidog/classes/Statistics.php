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
  /**@file Statistics.php
   * @author Copyright (C) 2004 Technologies Coeus inc.
   */
   
require_once BASEPATH.'include/common.php';

/* Gives various statistics about the status of the network or of a specific node */
class Statistics{

  function Statistics() {
  }
  
  /**
   * Find out how many users are online at a HotSpot or on the network
   * @param $node_id Optionnal.  The id of the node used for which you want the number of users.  Leave null to get the number of users online on the entire network
   * @return Number of online users
   */
  function getNumOnlineUsers($node_id = null) {
    global $db;
    if ($node_id != null) {
      $node_id = $db->EscapeString($node_id);
      $sql_node = " AND connections.node_id='$node_id'";
    } else {
      $sql_node = "";
    }

    $db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT users.user_id) FROM users,connections " .
	     "WHERE connections.token_status='" . TOKEN_INUSE . "' " .
	     "AND users.user_id=connections.user_id $sql_node ",$row, false);
    return $row['count'];
  }
  
  /**
   * Find out how many users are valid in the database
   * @return Number of valid users
   */
  function getNumValidUsers() {
    global $db;

    $db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_status = " . ACCOUNT_STATUS_ALLOWED, $row, false);
    return $row['count'];
  }
    
  /**
   * Find out the total number of users in the database
   * @return Number of users
   */
  function getNumUsers() {
    global $db;
    $db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM users", $row, false);
    return $row['count'];
  }

  /**
   * Find out the date of the most recent successfull (meaning with data transferred) connection to a HotSpot.
   * @param $node_id Optionnal.  The id of the node used for which you want the last successfull connection date
   * @return Textual date
   */
  function getLastConnDate($node_id = null) {
    global $db;

    if ($node_id != null) {
      $node_id = $db->EscapeString($node_id);
      $sql_node = " AND connections.node_id='$node_id'";
    } else {
      $sql_node="";
    }

    $db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE incoming!=0 $sql_node ORDER BY timestamp_in DESC LIMIT 1", $row, false);
    return $row['timestamp_in'];
  }
  
}//End class
?>
