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
class Node
{
  private $mRow;
  private $mId;

  /** Instantiate a node object 
   * @param $id The id of the requested node
   * @return a Node object, or null if there was an error
   */
  static function getNode ($id)
  {
    $object = null;
    $object = new self ($id);
    return $object;
  }

  static function deleteNode($node_id) {
    global $db;

    if (!$db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='{$node_id}'", false))
        throw new Exception(_('Could not delete node owners!'));

    if (!$db->ExecSqlUpdate("DELETE FROM nodes WHERE node_id='{$node_id}'", false))
        throw new Exception(_('Could not delete node!'));
  }

  /** Create a new Node in the database 
   * @param $id The id to be given to the new node
   * @return the newly created Node object, or null if there was an error
   */
  static function createNode ($node_id, $name, $rss_url, $home_page_url, $description, $map_url, $street_address, $public_phone_number, $public_email, $mass_transit_info, $node_deployment_status)
  {
    global $db;

    $node_id = $db->EscapeString ($node_id);
    $name = $db->EscapeString ($name);
    $rss_url = $db->EscapeString ($rss_url);
    $home_page_url = $db->EscapeString ($home_page_url);
    $description = $db->EscapeString ($description);
    $map_url = $db->EscapeString ($map_url);
    $street_address = $db->EscapeString ($street_address);
    $public_phone_number = $db->EscapeString ($public_phone_number);
    $public_email = $db->EscapeString ($public_email);
    $mass_transit_info = $db->EscapeString ($mass_transit_info);
    $node_deployment_status = $db->EscapeString ($node_deployment_status);

    if (Node::nodeExists($node_id))
        throw new Exception(_('This node already exists.'));

    $sql = "INSERT INTO nodes (node_id, name, rss_url, creation_date, home_page_url, description, map_url, street_address, public_phone_number, public_email, mass_transit_info, node_deployment_status) VALUES ('$node_id','$name','$rss_url',NOW(),'$home_page_url','$description','$map_url','$street_address','$public_phone_number','$public_email','$mass_transit_info','$node_deployment_status')";

    if (!$db->ExecSqlUpdate ($sql, false)) {
        throw new Exception(_('Unable to insert new node into database!'));
    }
    $object = new self ($node_id);
    return $object;
  }

  /** @param $node_id The id of the node */
  function __construct ($node_id)
  {
    global $db;

    $node_id_str = $db->EscapeString ($node_id);
    $sql = "SELECT * FROM nodes WHERE node_id='$node_id_str'";
    $db->ExecSqlUniqueRes ($sql, $row, false);
    if ($row == null)
      {
	throw new
	  Exception (_
		     ("The id $node_id_str could not be found in the database"));
      }
    $this->mRow = $row;
    $this->mId = $row['node_id'];
  }				//End class

  /** Return the name of the node 
   */
  function getName ()
  {
    return $this->mRow['name'];
  }

  function getID ()
  {
    return $this->mRow['node_id'];
  }

  function getRSSURL ()
  {
    return $this->mRow['rss_url'];
  }

  function getHomePageURL ()
  {
    return $this->mRow['home_page_url'];
  }

  function getDescription()
  {
    return $this->mRow['description'];
  }

  function getMapURL()
  {
    return $this->mRow['map_url'];
  }

  function getAddress()
  {
    return $this->mRow['street_address'];
  }

  function getTelephone ()
  {
    return $this->mRow['public_phone_number'];
  }

  function getTransitInfo ()
  {
    return $this->mRow['mass_transit_info'];
  }

  function getEmail ()
  {
    return $this->mRow['public_email'];
  }

  function getDeploymentStatus ()
  {
    return $this->mRow['node_deployment_status'];
  }

  /** Return all the nodes
   */
  static function getAllNodes ()
  {
    global $db;

    $db->ExecSql ("SELECT * FROM nodes", $nodes, false);

    if ($nodes == null)
      throw new Exception(_("No nodes could not be found in the database"));

    return $nodes;
  }

  static function getAllNodesOrdered ($order_by)
  {
    global $db;

    $db->ExecSql ("SELECT * FROM nodes ORDER BY $order_by", $nodes, false);

    if ($nodes == null)
      throw new Exception(_("No nodes could not be found in the database"));

    return $nodes;
  }

  static function getAllNodesWithStatus ()
  {
    global $db;

    $db->
      ExecSql
      ("SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date FROM nodes ORDER BY node_id",
       $nodes, false);

    if ($nodes == null)
      throw new Exception(_("No nodes could not be found in the database"));

    return $nodes;
  }

  static function getAllDeploymentStatus ()
  {
    global $db;

    $db->ExecSql ("SELECT * FROM node_deployment_status", $statuses, false);
    if ($statuses == null)
      throw new Exception(_("No deployment statues  could be found in the database"));

    $statuses_array = array ();
    foreach ($statuses as $status)
      array_push ($statuses_array, $status['node_deployment_status']);

    return $statuses_array;
  }

  function getOnlineUsers ()
  {
    global $db;

    $db->ExecSql("SELECT users.user_id FROM users,connections WHERE connections.token_status='". TOKEN_INUSE. "' AND users.user_id=connections.user_id AND connections.node_id='{$this->mId}'", $users, false);
    return $users;
  }

  function getOwners() {
    global $db;

    $db->ExecSql("SELECT user_id FROM node_owners WHERE node_id='{$this->mId}'", $owners, false);
    return $owners;
  }

  function addOwner($user_id) {
    /* TODO: VALIDER les champs de donnees node_id et user_id */

    global $db;
    if (!$db->ExecSqlUpdate("INSERT INTO node_owners (node_id, user_id) VALUES ('{$this->mId}','{$user_id}')", false))
        throw new Exception(_('Could not add owner'));
  }

  function removeOwner($user_id) {
    global $db;
    if (!$db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='{$this->mId}' AND user_id='{$user_id}'", false))
        throw new Exception(_('Could not remove owner'));
  }

  function nodeExists($id) {
    global $db;
    $id_str = $db->EscapeString($id);
    $sql = "SELECT * FROM nodes WHERE node_id='{$id_str}'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    return $row;
  }

  public static function getAllOnlineUsers() {
    global $db;
    $db->ExecSql("SELECT * FROM connections,users,nodes WHERE token_status='" . TOKEN_INUSE . "' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY timestamp_in DESC", $online_users);
    return $online_users;
  }

}				// End class

?>
