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
 * @author Copyright (C) 2005 Benoit Gr�goire <bock@step.polymtl.ca>
 */
require_once BASEPATH.'include/common.php';

/** Abstract a Node.  A Node is an actual physical transmitter. */
class Node implements GenericObject
{
	private $mRow;
	private $id;
	private static $current_node_id = null;

	/** Instantiate a node object 
	 * @param $id The id of the requested node
	 * @return a Node object, or null if there was an error
	 */
	static function getObject($id)
	{
		$object = null;
		$object = new self($id);
		return $object;
	}

	/** Get the current node for which the portal is displayed or to which a user is physically connected.
	 * @param $real_node_only true or false.  If true, the real physical node where the user is connected is returned, and the node set by setCurrentNode is ignored.
	 * @return a Node object, or null if it can't be found.
	 */
	static function getCurrentNode($real_node_only = false)
	{
		$object = null;
		if (self :: $current_node_id != null && $real_node_only == false)
		{
			$object = new self(self :: $current_node_id);
		}
		else
		{
			$object = getCurrentRealNode();
		}
		return $object;
	}

	/** Set the current node where the user is to be considered connected to.  (For portal and content display purpuses, among other.
	 * @param $node Node.  The new current node.
	 * @return true	 */
	static function setCurrentNode(Node $node)
	{
		self :: $current_node_id = $node->GetId();
		return true;
	}

	/** Get the current node to which a user is physically connected, if any.  This is done by an IP adress lookup against the last reported IP adress of the node
	 * @param 	 * @return a Node object, or null if it can't be found.
	 */
	public function getCurrentRealNode()
	{
		global $db;
		$retval = null;
		$sql = "SELECT node_id, last_heartbeat_ip from nodes WHERE last_heartbeat_ip='$_SERVER[REMOTE_ADDR]'";
		$db->ExecSql($sql, $node_rows, false);
		$num_match = count($node_rows);
		if ($num_match == 0)
		{

			// User is not physically connected to a node
			$retval = null;
		}
		else
			if ($num_match = 1)
			{
				// Only a single node matches, the user is presumed to be there
				$retval = new self($node_rows[0]['node_id']);
			}
			else
			{
				/* We have more than one node matching the IP (the nodes are behind the same NAT).
				 * We will try to discriminate by finding which node the user last authenticated against.
				 * If the IP matches, we can be pretty certain the user is there. 
				 */
				$retval = null;
				$current_user = User :: getCurrentUser();
				if ($current_user != null)
				{
					$current_user_id = $current_user->getId();
					$_SERVER['REMOTE_ADDR'];
					$sql = "SELECT node_id, last_heartbeat_ip from connections NATURAL JOIN nodes WHERE user_id='$current_user_id' ORDER BY last_updated DESC ";
					$db->ExecSql($sql, $node_rows, false);
					$node_row = $node_rows[0];
					if($node_row!=null && $node_row['last_heartbeat_ip']==$_SERVER['REMOTE_ADDR'])
					{
						$retval = new self($node_row['node_id']);
					}
				}
			}
        return $retval;
	}

	public function delete(& $errmsg)
	{
		$retval = false;
		$user = User :: getCurrentUser();
		if ($this->isOwner($user) || $user->isSuperAdmin())
		{
			$errmsg = _('Access denied!');
		}
		global $db;

		if (!$db->ExecSqlUpdate("DELETE FROM nodes WHERE node_id='{$this->$id}'", false))
		{
			$errmsg = _('Could not delete node!');
		}
		else
		{
			$retval = true;
		}
		return $retval;
	}

	/** Create a new Node in the database
	 * @deprecated version - 18-Apr-2005
	 * @param $id The id to be given to the new node
	 * @return the newly created Node object, or null if there was an error
	 */
	static function createNewObject()
	{
		global $db;

		$node_id = $db->EscapeString(get_guid());
		$name = $db->EscapeString('New node');

		$sql = "INSERT INTO nodes (node_id, name) VALUES ('$node_id','$name')";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to insert new node into database!'));
		}
		$object = new self($node_id);
		return $object;
	}

	/** Create a new Node in the database 
	 * @deprecated version - 18-Apr-2005
	 * @param $id The id to be given to the new node
	 * @return the newly created Node object, or null if there was an error
	 */
	static function createNode($node_id, $name, $rss_url, $home_page_url, $description, $map_url, $street_address, $public_phone_number, $public_email, $mass_transit_info, $node_deployment_status)
	{
		global $db;

		$node_id = $db->EscapeString($node_id);
		$name = $db->EscapeString($name);
		$rss_url = $db->EscapeString($rss_url);
		$home_page_url = $db->EscapeString($home_page_url);
		$description = $db->EscapeString($description);
		$map_url = $db->EscapeString($map_url);
		$street_address = $db->EscapeString($street_address);
		$public_phone_number = $db->EscapeString($public_phone_number);
		$public_email = $db->EscapeString($public_email);
		$mass_transit_info = $db->EscapeString($mass_transit_info);
		$node_deployment_status = $db->EscapeString($node_deployment_status);

		if (Node :: nodeExists($node_id))
			throw new Exception(_('This node already exists.'));

		$sql = "INSERT INTO nodes (node_id, name, rss_url, creation_date, home_page_url, description, map_url, street_address, public_phone_number, public_email, mass_transit_info, node_deployment_status) VALUES ('$node_id','$name','$rss_url',NOW(),'$home_page_url','$description','$map_url','$street_address','$public_phone_number','$public_email','$mass_transit_info','$node_deployment_status')";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to insert new node into database!'));
		}
		$object = new self($node_id);
		return $object;
	}

	/** Get an interface to pick a node.
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	* @param $sql_additional_where Addidional where conditions to restrict the candidate objects
	* @return html markup
	*/
	public static function getSelectNodeUI($user_prefix, $sql_additional_where = null)
	{
		global $db;
		$html = '';
		$name = "{$user_prefix}";
		$html .= "Node: \n";
		$sql = "SELECT node_id, name from nodes WHERE 1=1 $sql_additional_where ORDER BY node_id";
		$db->ExecSql($sql, $node_rows, false);
		if ($node_rows != null)
		{
			$i = 0;
			foreach ($node_rows as $node_row)
			{
				$tab[$i][0] = $node_row['node_id'];
				$tab[$i][1] = $node_row['node_id'].": ".$node_row['name'];
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);
		}
		return $html;
	}

	/** Get the selected Network object.
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the Network object
	 */
	static function processSelectNodeUI($user_prefix)
	{
		$object = null;
		$name = "{$user_prefix}";
		return new self($_REQUEST[$name]);
	}

	/** @param $node_id The id of the node */
	function __construct($node_id)
	{
		global $db;

		$node_id_str = $db->EscapeString($node_id);
		$sql = "SELECT * FROM nodes WHERE node_id='$node_id_str'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception(_("The id $node_id_str could not be found in the database"));
		}
		$this->mRow = $row;
		$this->id = $row['node_id'];
	}

	/** Return the name of the node 
	 */
	function getName()
	{
		return $this->mRow['name'];
	}

	function getID()
	{
		return $this->mRow['node_id'];
	}

	function getRSSURL()
	{
		return $this->mRow['rss_url'];
	}

	function getHomePageURL()
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

	function getTelephone()
	{
		return $this->mRow['public_phone_number'];
	}

	function getTransitInfo()
	{
		return $this->mRow['mass_transit_info'];
	}

	function getEmail()
	{
		return $this->mRow['public_email'];
	}

	function getDeploymentStatus()
	{
		return $this->mRow['node_deployment_status'];
	}

	function setInfos($info_array)
	{
		global $db;

		$infos_to_add = array ();
		if ($info_array)
		{
			foreach ($info_array as $column => $value)
			{
				$value = $db->EscapeString($value);
				array_push($infos_to_add, "$column='$value'");
			}
			$sql = "UPDATE nodes SET ";
			$sql .= implode(",", $infos_to_add);
			$sql .= " WHERE node_id='{$this->id}'";
			if (!$db->ExecSqlUpdate($sql, false))
			{
				throw new Exception(_('Unable to update database!'));
			}
		}
		else
		{
			throw new Exception(_('No info to update node with!'));
		}
	}

	/** Retreives the admin interface of this object.
	 * @return The HTML fragment for this interface */
	public function getAdminUI()
	{
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Node (".get_class($this)." instance)</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node content:")."</div>\n";

		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getAllContent() as $content)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= $content->getListUI();
			$html .= "</div'>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "node_".$this->id."_content_".$content->GetId()."_erase";
			$html .= "<input type='submit' name='$name' value='"._("Remove")."' onclick='submit();'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "node_{$this->id}_new_content";
		$html .= Content :: getSelectContentUI($name, "AND content_id NOT IN (SELECT content_id FROM node_has_content WHERE node_id='$this->id')");
		$name = "node_{$this->id}_new_content_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add")."' onclick='submit();'>";
		$html .= "</li>\n";
		$html .= "</ul>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		return $html;
	}

	/** Process admin interface of this object.
	*/
	public function processAdminUI()
	{
		$user = User::getCurrentUser();
		if (!$this->isOwner($user) || !$user->isSuperAdmin())
		{
			throw new Exception(_('Access denied!'));
		}

		foreach ($this->getAllContent() as $content)
		{
			$name = "node_".$this->id."_content_".$content->GetId()."_erase";
			if (!empty ($_REQUEST[$name]))
			{
				$this->removeContent($content);
			}
		}

		$name = "node_{$this->id}_new_content_submit";
		if (!empty ($_REQUEST[$name]))
		{
			$name = "node_{$this->id}_new_content";
			$content = Content :: processSelectContentUI($name);
			$this->addContent($content);
		}
	}

	/** Add content to this node */
	public function addContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "INSERT INTO node_has_content (node_id, content_id) VALUES ('$this->id','$content_id')";
		$db->ExecSqlUpdate($sql, false);
	}

	/** Remove content from this node */
	public function removeContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "DELETE FROM node_has_content WHERE node_id='$this->id' AND content_id='$content_id'";
		$db->ExecSqlUpdate($sql, false);
	}

	/**Get an array of all Content linked to this node
	* @return an array of Content or an empty arrray */
	function getAllContent()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM node_has_content WHERE node_id='$this->id' ORDER BY subscribe_timestamp";
		$db->ExecSql($sql, $content_rows, false);
		if ($content_rows != null)
		{
			foreach ($content_rows as $content_row)
			{
				$retval[] = Content :: getObject($content_row['content_id']);
			}
		}
		return $retval;
	}

	/** Return all the nodes
	 */
	static function getAllNodes()
	{
		global $db;

		$db->ExecSql("SELECT * FROM nodes", $nodes, false);

		if ($nodes == null)
			throw new Exception(_("No nodes could not be found in the database"));

		return $nodes;
	}

	static function getAllNodesOrdered($order_by)
	{
		global $db;

		$db->ExecSql("SELECT * FROM nodes ORDER BY $order_by", $nodes, false);

		if ($nodes == null)
			throw new Exception(_("No nodes could not be found in the database"));

		return $nodes;
	}

	static function getAllNodesWithStatus()
	{
		global $db;

		$db->ExecSql("SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date FROM nodes ORDER BY node_id", $nodes, false);

		if ($nodes == null)
			throw new Exception(_("No nodes could not be found in the database"));

		return $nodes;
	}

	static function getAllDeploymentStatus()
	{
		global $db;

		$db->ExecSql("SELECT * FROM node_deployment_status", $statuses, false);
		if ($statuses == null)
			throw new Exception(_("No deployment statues  could be found in the database"));

		$statuses_array = array ();
		foreach ($statuses as $status)
			array_push($statuses_array, $status['node_deployment_status']);

		return $statuses_array;
	}

	function getOnlineUsers()
	{
		global $db;

		$db->ExecSql("SELECT users.user_id, users.username, users.account_origin FROM users,connections WHERE connections.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND connections.node_id='{$this->id}'", $users, false);
		return $users;
	}

	function getOwners()
	{
		global $db;

		$db->ExecSql("SELECT user_id FROM node_owners WHERE node_id='{$this->id}'", $owners, false);
		return $owners;
	}

	function addOwner($user_id)
	{
		/* TODO: VALIDER les champs de donnees node_id et user_id */

		global $db;
		if (!$db->ExecSqlUpdate("INSERT INTO node_owners (node_id, user_id) VALUES ('{$this->id}','{$user_id}')", false))
			throw new Exception(_('Could not add owner'));
	}

	function removeOwner($user_id)
	{
		global $db;
		if (!$db->ExecSqlUpdate("DELETE FROM node_owners WHERE node_id='{$this->id}' AND user_id='{$user_id}'", false))
			throw new Exception(_('Could not remove owner'));
	}

	/** Is the user an owner of the Node? 
	 * @return true our false*/
	function isOwner(User $user)
	{
		global $db;
		if ($user != null)
		{
			$user_id = $user->getId();
			$retval = false;
			$db->ExecSqlUniqueRes("SELECT * FROM node_owners WHERE node_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
			if ($row != null)
			{
				$retval = true;
			}
		}
		return $retval;
	}

	function nodeExists($id)
	{
		global $db;
		$id_str = $db->EscapeString($id);
		$sql = "SELECT * FROM nodes WHERE node_id='{$id_str}'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		return $row;
	}

	function getInfoArray()
	{
		return $this->mRow;
	}

	public static function getAllOnlineUsers()
	{
		global $db;
		$db->ExecSql("SELECT * FROM connections,users,nodes WHERE token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY timestamp_in DESC", $online_users);
		return $online_users;
	}

} // End class
?>