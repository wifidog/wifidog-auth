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
  /**@file Node.php
   * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>
   */
   
require_once BASEPATH.'include/common.php';

/** Abstract a Node.  A Node is an actual physical transmitter. */
class Node {
  private $mRow;
  private $mId;
  
  /** Instantiate a node object 
   * @param $id The id of the requested node
   * @return a Node object, or null if there was an error
   */
  static function getNode($id) {
      $object = null;
      $object = new self($id);
      return $object;
    }
  
  /** Create a new Node in the database 
   * @param $id The id to be given to the new node
   * @return the newly created Node object, or null if there was an error
   */
  static function createNode($id) {
      global $db;

      $object = null;
      $id_str = $db->EscapeString($id);
      $sql = "INSERT INTO nodes values (node_id) VALUES ('$id_str')";
      $db->ExecSqlUpdate($sql, false);
      $object = new self($id);
      return $object;
    }
  
/** @param $node_id The id of the node */
  function __construct($node_id) {
    global $db;
    $node_id_str = $db->EscapeString($node_id);
    $sql = "SELECT * FROM nodes WHERE node_id='$node_id_str'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    if ($row == null) {
	    throw new Exception(_("The id $node_id_str could not be found in the database"));
    }
    $this->mRow = $row;  
    $this->mId  = $row['node_id'];
  }//End class
  
  /** Return the name of the node 
   */
  function getName() {
    return $this->mRow['name'];
  }

  function getID() {
    return $this->mRow['node_id'];
  }

  function getRSSURL() {
    return $this->mRow['rss_url'];
  }

  function getEmail() {
    return $this->mRow['public_email'];
  }

  function getDeploymentStatus() {
    return $this->mRow['node_deployment_status'];
  }

  /** Return all the nodes
   */
  static function getAllNodes() {
    global $db;

    $db->ExecSql("SELECT * FROM nodes", $nodes, false);

    if ($nodes == null) {
        throw new Exception(_("No nodes could not be found in the database"));
    }
    return $nodes;
  }

  static function getAllNodesOrdered($order_by) {
    global $db;

    $db->ExecSql("SELECT * FROM nodes ORDER BY $order_by", $nodes, false);

    if ($nodes == null) {
        throw new Exception(_("No nodes could not be found in the database"));
    }
    return $nodes;
  }

  static function getAllNodesWithStatus() {
    global $db;
    $db->ExecSql("SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date FROM nodes ORDER BY node_id", $nodes, false);
    if ($nodes == null) {
        throw new Exception(_("No nodes could not be found in the database"));
    }
    return $nodes;
  }

  static function getAllDeploymentStatus() {
    global $db;

    $db->ExecSql("SELECT * FROM node_deployment_status", $statuses, false);
    if ($statuses == null) {
        throw new Exception(_("No deployment statues  could be found in the database"));
    }
    $statuses_array = array();
    foreach ($statuses as $status) {
        array_push($statuses_array, $status['node_deployment_status']);
    }
    return $statuses_array;
  }

  function getOnlineUsers() {
    global $db;
    $db->ExecSql("SELECT users.user_id FROM users,connections WHERE connections.token_status='" . TOKEN_INUSE . "' AND users.user_id=connections.user_id AND connections.node_id='{$this->mId}'", $users, false);
    return $users;
  }

}// End class
?>
