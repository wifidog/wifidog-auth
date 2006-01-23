<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/User.php');
require_once('classes/GisPoint.php');
require_once('classes/AbstractGeocoder.php');
require_once('classes/Utils.php');
require_once('classes/DateTime.php');

/**
 * Abstract a Node.  A Node is an actual physical transmitter.
 *
 * @todo Make all the setter functions no-op if the value is the same as what
 * was already stored Use setCustomPortalReduirectUrl as an example
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005 Benoit Gregoire, Technologies Coeus inc.
 */
class Node implements GenericObject
{
	private $mRow;
	private $mdB; /**< An AbstractDb instance */
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
			$object = self :: getCurrentRealNode();
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
	public static function getCurrentRealNode()
	{
		global $db;
		$retval = null;
		$sql = "SELECT node_id, last_heartbeat_ip from nodes WHERE last_heartbeat_ip='$_SERVER[REMOTE_ADDR]' ORDER BY last_heartbeat_timestamp DESC";
		$node_rows = null;
		$db->execSql($sql, $node_rows, false);
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
					$db->execSql($sql, $node_rows, false);
					$node_row = $node_rows[0];
					if ($node_row != null && $node_row['last_heartbeat_ip'] == $_SERVER['REMOTE_ADDR'])
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
			global $db;
			$id = $db->escapeString($this->getId());
			if (!$db->execSqlUpdate("DELETE FROM nodes WHERE node_id='{$id}'", false))
			{
				$errmsg = _('Could not delete node!');
			}
			else
			{
				$retval = true;
			}
		}
		else
		{
			$errmsg = _('Access denied!');
		}

		return $retval;
	}

	/** Create a new Node in the database
	 * @param $node_id The id to be given to the new node.  If not present, a
	 * guid will be assigned.
	 * @param $network Network object.  The node's network.  If not present,
	 * the current Network will be assigned
	 *
	 * @return the newly created Node object, or null if there was an error
	 */
	static function createNewObject($node_id = null, $network = null)
	{
		global $db;
		if (empty ($node_id))
		{
			$node_id = get_guid();
		}
		$node_id = $db->escapeString($node_id);

		if (empty ($network))
		{
			$network = Network :: getCurrentNetwork();
		}
		$network_id = $db->escapeString($network->getId());

		$node_deployment_status = $db->escapeString("IN_PLANNING");
		$node_name = _("New node");
		if (Node :: nodeExists($node_id))
			throw new Exception(_('This node already exists.'));

		$sql = "INSERT INTO nodes (node_id, network_id, creation_date, node_deployment_status, name) VALUES ('$node_id', '$network_id', NOW(),'$node_deployment_status', '$node_name')";

		if (!$db->execSqlUpdate($sql, false))
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
		$html .= _("Node: ");
		$sql = "SELECT node_id, name from nodes WHERE 1=1 $sql_additional_where ORDER BY node_id";
		$node_rows = null;
		$db->execSql($sql, $node_rows, false);
		if ($node_rows != null)
		{
			// Naturally-sorting by node_id
			Utils :: natsort2d($node_rows, "node_id");
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
	 * @return the node object
	 */
	static function processSelectNodeUI($user_prefix)
	{
		$object = null;
		$name = "{$user_prefix}";
		return new self($_REQUEST[$name]);
	}

	/** Get an interface to create a new node.
	* @param $network Optional:  The network to which the new node will belong,
	* if absent, the user will be prompted.
	* @return html markup
	*/
	public static function getCreateNewObjectUI($network = null)
	{
		$html = '';
		$html .= _("Create new node with id")." \n";
		$name = "new_node_id";
		$html .= "<input type='text' size='10' name='{$name}'>\n";
		if ($network)
		{
			$name = "new_node_network_id";
			$html .= "<input type='hidden' name='{$name}' value='{$network->getId()}'>\n";
		}
		else
		{
			$html .= " "._("in network:")." \n";
			$html .= Network :: getSelectNetworkUI('new_node');
		}
		return $html;

	}

	/** Process the new object interface.
	 *  Will return the new object if the user has the credentials and the form was fully filled.
	 * @return the node object or null if no new node was created.
	 */
	static function processCreateNewObjectUI()
	{
		$retval = null;
		$name = "new_node_id";
		if (!empty ($_REQUEST[$name]))
		{
			$node_id = $_REQUEST[$name];
			$name = "new_node_network_id";
			if (!empty ($_REQUEST[$name]))
			{
				$network = Network :: getObject($_REQUEST[$name]);
			}
			else
			{
				$network = Network :: processSelectNetworkUI('new_node');
			}
			if ($node_id && $network)
			{
				if (!$network->hasAdminAccess(User :: getCurrentUser()))
				{
					throw new Exception(_("Access denied"));
				}
				$retval = self :: createNewObject($node_id, $network);
			}
		}
		return $retval;
	}

	/** Get an interface to select the deployment status
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	* @return html markup
	*/
	public function getSelectDeploymentStatus($user_prefix)
	{
		global $db;
		$html = '';
		$name = "{$user_prefix}";
		$status_list = null;
		$db->execSql("SELECT node_deployment_status FROM node_deployment_status", $status_list, false);
		if ($status_list == null)
			throw new Exception(_("No deployment statues  could be found in the database"));

		$tab = array ();
		foreach ($status_list as $status)
			$tab[] = array ($status['node_deployment_status'], $status['node_deployment_status']);
		$html .= FormSelectGenerator :: generateFromArray($tab, $this->getDeploymentStatus(), $name, null, false);
		return $html;
	}

	/** Get the selected deployment status
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the deployment status
	 */
	public function processSelectDeploymentStatus($user_prefix)
	{
		$object = null;
		$name = "{$user_prefix}";
		return $_REQUEST[$name];
	}

	/** @param $node_id The id of the node */
	private function __construct($node_id)
	{
		global $db;
		$this->mDb = & $db;

		$node_id_str = $db->escapeString($node_id);
		$sql = "SELECT * FROM nodes WHERE node_id='$node_id_str'";
		$row = null;
		$db->execSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception(_("The id $node_id_str could not be found in the database"));
		}
		$this->mRow = $row;
		$this->id = $row['node_id'];
	}

	function getId()
	{
		return $this->id;
	}

	/** Changing the id of a Node is supported.
	 *  Be carefull to anly call this when all other changes are processed,
	 * or the id used to generate the form names may no longer match.
	 * @param $id, string, the new node id.
	 * @return true on success, false on failure. Check this,
	 * as it's possible that someone will enter an existing id, especially
	 * if the MAC address is used and hardware is recycled.
	 */
	function setId($id)
	{
		$id = $this->mDb->escapeString($id);
		$retval = $this->mDb->execSqlUpdate("UPDATE nodes SET node_id = '{$id}' WHERE node_id = '{$this->getId()}'");
		if ($retval)
		{
			$this->id = $id;
			$this->refresh();
		}
		return $retval;
	}

	/** Gets the Network to which the node belongs
	 * @return Network object (never returns null)
	 */
	public function getNetwork()
	{
		return Network :: getObject($this->mRow['network_id']);
	}

	/** Get a GisPoint object ; altide is not supported yet
	 */
	function getGisLocation()
	{
		// Altitude is not supported yet
		return new GisPoint($this->mRow['latitude'], $this->mRow['longitude'], 0);
	}

	function setGisLocation($pt)
	{
		if (!empty ($pt))
		{
			$lat = $this->mDb->escapeString($pt->getLatitude());
			$long = $this->mDb->escapeString($pt->getLongitude());

			if (!empty ($lat) && !empty ($long))
				$this->mDb->execSqlUpdate("UPDATE nodes SET latitude = $lat, longitude = $long WHERE node_id = '{$this->getId()}'");
			else
				$this->mDb->execSqlUpdate("UPDATE nodes SET latitude = NULL, longitude = NULL WHERE node_id = '{$this->getId()}'");
			$this->refresh();
		}
	}

	/** Return the name of the node
	 */
	function getName()
	{
		return $this->mRow['name'];
	}

	function setName($name)
	{
		$name = $this->mDb->escapeString($name);
		$this->mDb->execSqlUpdate("UPDATE nodes SET name = '{$name}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getCreationDate()
	{
		return $this->mRow['creation_date'];
	}

	function setCreationDate($creation_date)
	{
		$creation_date = $this->mDb->escapeString($creation_date);
		$this->mDb->execSqlUpdate("UPDATE nodes SET creation_date = '{$creation_date}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getHomePageURL()
	{
		return $this->mRow['home_page_url'];
	}

	function setHomePageUrl($url)
	{
		$url = $this->mDb->escapeString($url);
		$this->mDb->execSqlUpdate("UPDATE nodes SET home_page_url = '{$url}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getDescription()
	{
		return $this->mRow['description'];
	}

	function setDescription($description)
	{
		$description = $this->mDb->escapeString($description);
		$this->mDb->execSqlUpdate("UPDATE nodes SET description = '{$description}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getMapURL()
	{
		return $this->mRow['map_url'];
	}

	function setMapURL($url)
	{
		$url = $this->mDb->escapeString($url);
		$this->mDb->execSqlUpdate("UPDATE nodes SET map_url = '{$url}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCivicNumber()
	{
		return $this->mRow['civic_number'];
	}

	public function setCivicNumber($civic_number)
	{
		$civic_number = $this->mDb->escapeString($civic_number);
		$this->mDb->execSqlUpdate("UPDATE nodes SET civic_number = '{$civic_number}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getStreetName()
	{
		return $this->mRow['street_name'];
	}

	public function setStreetName($street_name)
	{
		$street_name = $this->mDb->escapeString($street_name);
		$this->mDb->execSqlUpdate("UPDATE nodes SET street_name = '{$street_name}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCity()
	{
		return $this->mRow['city'];
	}

	public function setCity($city)
	{
		$city = $this->mDb->escapeString($city);
		$this->mDb->execSqlUpdate("UPDATE nodes SET city = '{$city}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getProvince()
	{
		return $this->mRow['province'];
	}

	public function setProvince($province)
	{
		$province = $this->mDb->escapeString($province);
		$this->mDb->execSqlUpdate("UPDATE nodes SET province = '{$province}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCountry()
	{
		return $this->mRow['country'];
	}

	protected function setCountry($country)
	{
		$country = $this->mDb->escapeString($country);
		$this->mDb->execSqlUpdate("UPDATE nodes SET country = '{$country}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getPostalCode()
	{
		return $this->mRow['postal_code'];
	}

	public function setPostalCode($postal_code)
	{
		$postal_code = $this->mDb->escapeString($postal_code);
		$this->mDb->execSqlUpdate("UPDATE nodes SET postal_code = '{$postal_code}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getTelephone()
	{
		return $this->mRow['public_phone_number'];
	}

	function setTelephone($phone)
	{
		$phone = $this->mDb->escapeString($phone);
		$this->mDb->execSqlUpdate("UPDATE nodes SET public_phone_number = '{$phone}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getTransitInfo()
	{
		return $this->mRow['mass_transit_info'];
	}

	function setTransitInfo($transit_info)
	{
		$transit_info = $this->mDb->escapeString($transit_info);
		$this->mDb->execSqlUpdate("UPDATE nodes SET mass_transit_info = '{$transit_info}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getEmail()
	{
		return $this->mRow['public_email'];
	}

	function setEmail($email)
	{
		$email = $this->mDb->escapeString($email);
		$this->mDb->execSqlUpdate("UPDATE nodes SET public_email = '{$email}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getDeploymentStatus()
	{
		return $this->mRow['node_deployment_status'];
	}

	function setDeploymentStatus($status)
	{
		$status = $this->mDb->escapeString($status);
		$this->mDb->execSqlUpdate("UPDATE nodes SET node_deployment_status = '{$status}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getLastPaged()
	{
		return $this->mRow['last_paged'];
	}

	function setLastPaged($last_paged)
	{
		$this->mDb->execSqlUpdate("UPDATE nodes SET last_paged = {$last_paged}::abstime WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getLastHeartbeatIP()
	{
		return $this->mRow['last_heartbeat_ip'];
	}

	function getLastHeartbeatUserAgent()
	{
		return $this->mRow['last_heartbeat_user_agent'];
	}

	function getLastHeartbeatTimestamp()
	{
		return $this->mRow['last_heartbeat_timestamp'];
	}

	function setLastHeartbeatTimestamp($timestamp)
	{
		$this->mDb->execSqlUpdate("UPDATE nodes SET last_heartbeat_timestamp = '{$timestamp}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	/** Is the node a Splash Only node?  Will only return true if the Network configuration allows it.
	 * @return true or false */
	public function isSplashOnly()
	{
		return $this->getNetwork()->getSplashOnlyNodesAllowed() && $this->isConfiguredSplashOnly();
	}

	/** Is the node configured as a Splash Only node?  This is NOT the same as isSplashOnly().
	 * This is the getter for the configuration set in the database for this node.
	 * For the node to actually be splash only, this AND the network
	 * gonfiguration must match.
	 * @return true or false */
	public function isConfiguredSplashOnly()
	{
		return (($this->mRow['is_splash_only_node'] == 't') ? true : false);
	}

	/** Set if this node should be a splash-only (no login) node (if enabled in Network configuration)
	 * @param $value The new value, true or false
	 * @return true on success, false on failure */
	function setIsConfiguredSplashOnly($value)
	{
		$retval = true;
		if ($value != $this->isConfiguredSplashOnly())
		{
			global $db;
			$value ? $value = 'TRUE' : $value = 'FALSE';
			$retval = $db->execSqlUpdate("UPDATE nodes SET is_splash_only_node = {$value} WHERE node_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** The url to show instead of the portal.  If empty, the portal is shown
	 Must be enabled in the Network configuration to have any effect
	 @return a string */
	function getCustomPortalRedirectUrl()
	{
		return $this->mRow['custom_portal_redirect_url'];
	}

	/** The url to show instead of the portal.  If empty, the portal is shown
	 Must be enabled in the Network configuration to have any effect
	 @return true on success, false on failure */
	function setCustomPortalRedirectUrl($value)
	{
		$retval = true;
		if ($value != $this->getCustomPortalRedirectUrl())
		{
			global $db;
			$value = $db->escapeString($value);
			$retval = $db->execSqlUpdate("UPDATE nodes SET custom_portal_redirect_url = '{$value}' WHERE node_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Retrieves the admin interface of this object.
	 * @return The HTML fragment for this interface */
	public function getAdminUI()
	{
		//TODO: Most of this code will be moved to Hotspot class when the abtraction will be completed

		//pretty_print_r($_REQUEST);
		//pretty_print_r($this->mRow);
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Node (".get_class($this)." instance)</div>\n";
		$html .= "<h3>"._("Edit a node")."</h3>\n";

		// Display stats
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Statistics:")."</div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$this->id."_get_stats";
		$html .= "<input type='submit' name='$name' value='"._("Get access statistics")."'>";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Information about the node
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Information about the node:")."</div>\n";

		// Hashed node_id (this is a workaround since PHP auto-converts HTTP vars var periods, spaces or underscores )
		$hashed_node_id = md5($this->getId());

		// Node ID
		$value = htmlspecialchars($this->getId(), ENT_QUOTES);
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("ID")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_id";
		$html .= "<input type='text' size='20' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Creation date
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Creation date")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_creation_date";
		$html .= DateTime :: getSelectDateTimeUI(new DateTime($this->getCreationDate()), $name, DateTime :: INTERFACE_DATETIME_FIELD);
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Name
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Name")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_name";
		$value = htmlspecialchars($this->getName(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Homepage URL
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Homepage URL")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_homepage_url";
		$value = htmlspecialchars($this->getHomePageURL(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Description
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Description")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_description";
		$value = htmlspecialchars($this->getDescription(), ENT_QUOTES);
		$html .= "<textarea cols='50' rows='5' name='$name'>$value</textarea>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Map URL
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Map URL")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_map_url";
		$value = htmlspecialchars($this->getMapURL(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Civic number
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Civic number")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_civic_number";
		$value = htmlspecialchars($this->getCivicNumber(), ENT_QUOTES);
		$html .= "<input type='text' size ='10' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Street name
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Street name")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_street_name";
		$value = htmlspecialchars($this->getStreetName(), ENT_QUOTES);
		$html .= "<input type='text' size ='25' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// City
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("City")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_city";
		$value = htmlspecialchars($this->getCity(), ENT_QUOTES);
		$html .= "<input type='text' size ='25' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Province
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Province / State")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_province";
		$value = htmlspecialchars($this->getProvince(), ENT_QUOTES);
		$html .= "<input type='text' size ='15' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Postal Code
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Postal code")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_postal_code";
		$value = htmlspecialchars($this->getPostalCode(), ENT_QUOTES);
		$html .= "<input type='text' size ='10' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Country
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Country")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_country";
		$value = htmlspecialchars($this->getCountry(), ENT_QUOTES);
		$html .= "<input type='text' size ='15' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Public phone #
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Public phone number")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_public_phone";
		$value = htmlspecialchars($this->getTelephone(), ENT_QUOTES);
		$html .= "<input type='text' size ='20' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Public mail
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Public email")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_public_email";
		$value = htmlspecialchars($this->getEmail(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Mass transit info
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Mass transit info")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_mass_transit_info";
		$value = htmlspecialchars($this->getTransitInfo(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// End of information section
		$html .= "</div>\n";

		// Node GIS data
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("GIS data")." : </div>\n";

		// Build HTML form fields names & values
		$gis_point = $this->getGisLocation();
		$gis_lat_name = "node_".$hashed_node_id."_gis_latitude";
		$gis_lat_value = htmlspecialchars($gis_point->getLatitude(), ENT_QUOTES);
		$gis_long_name = "node_".$hashed_node_id."_gis_longitude";
		$gis_long_value = htmlspecialchars($gis_point->getLongitude(), ENT_QUOTES);

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Latitude")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= "<input type='text' size ='15' value='$gis_lat_value' id='$gis_lat_name' name='$gis_lat_name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Longitude")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= "<input type='text' size ='15' value='$gis_long_value' id='$gis_long_name' name='$gis_long_name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/*
		 * If Google Maps is enabled, call the geocoding service,
		 * then use Google Maps to let the user choose a more precise location
		 *
		 * otherwise
		 *
		 * Simply use a geocoding service.
		 */

		if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED === true)
		{
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= "<input type='submit' name='geocode_only' value='"._("Geocode only")."'>\n";
			$html .= "<input type='button' name='google_maps_geocode' value='"._("Check using Google Maps")."' onClick='window.open(\"hotspot_location_map.php?node_id={$this->getId()}\", \"hotspot_location\", \"toolbar=0,scrollbars=1,resizable=1,location=0,statusbar=0,menubar=0,width=600,height=600\");'>\n";
			$html .= " ("._("Use a geocoding service, then use Google Maps to pinpoint the exact location.").")";
			$html .= "</div>\n";
			$html .= "</div>\n";
		}
		else
		{
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= "<input type='submit' name='geocode_only' value='"._("Geocode location")."'>\n";
			$html .= " ("._("Use a geocoding service").")";
			$html .= "</div>\n";
			$html .= "</div>\n";
		}

		// End of GIS data
		$html .= "</div>\n";

		// Node configuration section
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node configuration:")."</div>\n";

		$network = $this->getNetwork();

		// Deployment status
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node deployment status")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_deployment_status";
		$html .= self :: getSelectDeploymentStatus($name);
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  is_splash_only_node
		if ($network->getSplashOnlyNodesAllowed())
		{
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Is this node splash-only (no login)?")." : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$name = "node_".$hashed_node_id."_is_splash_only_node";
			$this->isConfiguredSplashOnly() ? $checked = 'CHECKED' : $checked = '';
			$html .= "<input type='checkbox' name='$name' $checked>\n";
			$html .= "</div>\n";
			$html .= "</div>\n";
		}

		// custom_portal_redirect_url
		if ($network->getCustomPortalRedirectAllowed())
		{
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("URL to show instead of the portal (if this is not empty, the portal will be disabled and this URL will be shown instead)")." : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$name = "node_".$hashed_node_id."_custom_portal_redirect_url";
			$value = htmlspecialchars($this->getCustomPortalRedirectUrl(), ENT_QUOTES);
			$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
			$html .= "</div>\n";
			$html .= "</div>\n";
		}
		// End Node configuration section
		$html .= "</div>\n";

		// Owners management
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node owners")." : </div>\n";
		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getOwners() as $owner)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= "{$owner->getUsername()}";
			$html .= "</div>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "node_{$this->getId()}_owner_{$owner->GetId()}_remove";
			$html .= "<input type='submit' name='$name' value='"._("Remove owner")."'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "node_{$this->getId()}_new_owner";
		$html .= User :: getSelectUserUI($name);
		$name = "node_{$this->getId()}_new_owner_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add owner")."'>";
		$html .= "</li>\n";
		$html .= "</ul>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Tech officers management
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Technical officers")." : </div>\n";
		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getTechnicalOfficers() as $tech_officer)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= "{$tech_officer->getUsername()}";
			$html .= "</div>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "node_{$this->getId()}_tech_officer_{$tech_officer->GetId()}_remove";
			$html .= "<input type='submit' name='$name' value='"._("Remove technical officer")."'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "node_{$this->getId()}_new_tech_officer";
		$html .= User :: getSelectUserUI($name);
		$name = "node_{$this->getId()}_new_tech_officer_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add technical officer")."'>";
		$html .= "</li>\n";
		$html .= "</ul>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* Node content */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node content (login):")."</div>\n";
		$name = "node_{$this->id}_content_login";
		$html .= Content :: getLinkedContentUI($name, 'node_has_content', 'node_id', $this->id, $display_location = 'login_page');
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node content (portal):")."</div>\n";
		$name = "node_{$this->id}_content_portal";
		$html .= Content :: getLinkedContentUI($name, 'node_has_content', 'node_id', $this->id, $display_location = 'portal_page');
		$html .= "</div>\n";

		return $html;
	}

	/** Process admin interface of this object.
	*/
	public function processAdminUI()
	{
		$user = User :: getCurrentUser();
		if (!$this->isOwner($user) && !$user->isSuperAdmin())
		{
			throw new Exception(_('Access denied!'));
		}

		// Information about the node

		// Hashed node_id (this is a workaround since PHP auto-converts HTTP vars var periods, spaces or underscores )
		$hashed_node_id = md5($this->getId());

		// Name
		$name = "node_".$hashed_node_id."_name";
		$this->setName($_REQUEST[$name]);

		// Creation date
		$name = "node_".$hashed_node_id."_creation_date";
		$this->setCreationDate(DateTime :: processSelectDateTimeUI($name, DateTime :: INTERFACE_DATETIME_FIELD)->getIso8601FormattedString());

		// Homepage URL
		$name = "node_".$hashed_node_id."_homepage_url";
		$this->setHomePageUrl($_REQUEST[$name]);

		// Description
		$name = "node_".$hashed_node_id."_description";
		$this->setDescription($_REQUEST[$name]);

		// Map URL
		$name = "node_".$hashed_node_id."_map_url";
		$this->setMapUrl($_REQUEST[$name]);

		// Civic number
		$name = "node_".$hashed_node_id."_civic_number";
		$this->setCivicNumber($_REQUEST[$name]);

		// Street name
		$name = "node_".$hashed_node_id."_street_name";
		$this->setStreetName($_REQUEST[$name]);

		// City
		$name = "node_".$hashed_node_id."_city";
		$this->setCity($_REQUEST[$name]);

		// Province
		$name = "node_".$hashed_node_id."_province";
		$this->setProvince($_REQUEST[$name]);

		// Postal Code
		$name = "node_".$hashed_node_id."_postal_code";
		$this->setPostalCode($_REQUEST[$name]);

		// Country
		$name = "node_".$hashed_node_id."_country";
		$this->setCountry($_REQUEST[$name]);

		// Public phone #
		$name = "node_".$hashed_node_id."_public_phone";
		$this->setTelephone($_REQUEST[$name]);

		// Public mail
		$name = "node_".$hashed_node_id."_public_email";
		$this->setEmail($_REQUEST[$name]);

		// Mass transit info
		$name = "node_".$hashed_node_id."_mass_transit_info";
		$this->setTransitInfo($_REQUEST[$name]);

		// GIS data
		// Get a geocoder for a given country
		if (!empty ($_REQUEST['geocode_only']))
		{
			$geocoder = AbstractGeocoder :: getGeocoder($this->getCountry());
			if ($geocoder != null)
			{
				$geocoder->setCivicNumber($this->getCivicNumber());
				$geocoder->setStreetName($this->getStreetName());
				$geocoder->setCity($this->getCity());
				$geocoder->setProvince($this->getProvince());
				$geocoder->setPostalCode($this->getPostalCode());
				if ($geocoder->validateAddress() === true)
				{
					if (($point = $geocoder->getGisLocation()) !== null)
						$this->setGisLocation($point);
					else
						echo _("It appears that the Geocoder could not be reached or could not geocode the given address.");
				}
				else
					echo _("You must enter a valid address.");
			}
		}
		else
		{
			// Use what has been set by the user.
			$gis_lat_name = "node_".$hashed_node_id."_gis_latitude";
			$gis_long_name = "node_".$hashed_node_id."_gis_longitude";
			$this->setGisLocation(new GisPoint($_REQUEST[$gis_lat_name], $_REQUEST[$gis_long_name], .0));
		}

		// Statistics
		$name = "node_{$this->id}_get_stats";
		if (!empty ($_REQUEST[$name]))
			header("Location: stats.php?node_id=".urlencode($this->getId()));

		// Node configuration section

		$network = $this->getNetwork();

		// Deployment status
		$name = "node_".$hashed_node_id."_deployment_status";
		$this->setDeploymentStatus(self :: processSelectDeploymentStatus($name));

		//  is_splash_only_node
		if ($network->getSplashOnlyNodesAllowed())
		{
			$name = "node_".$hashed_node_id."_is_splash_only_node";
			$this->setIsConfiguredSplashOnly(empty ($_REQUEST[$name]) ? false : true);
		}

		// custom_portal_redirect_url
		if ($network->getCustomPortalRedirectAllowed())
		{
			$name = "node_".$hashed_node_id."_custom_portal_redirect_url";
			$this->setCustomPortalRedirectUrl($_REQUEST[$name]);
		}

		// End Node configuration section

		// Owners processing
		// Rebuild user id, and delete if it was selected
		foreach ($this->getOwners() as $owner)
		{
			$name = "node_{$this->getId()}_owner_{$owner->GetId()}_remove";
			if (!empty ($_REQUEST[$name]))
			{
				if ($this->isOwner($owner))
					$this->removeOwner($owner);
				else
					echo _("Invalid user!");
			}
		}

		$name = "node_{$this->getId()}_new_owner_submit";
		if (!empty ($_REQUEST[$name]))
		{
			$name = "node_{$this->getId()}_new_owner";
			$owner = User :: processSelectUserUI($name);
			if ($owner)
			{
				if ($this->isOwner($owner))
					echo _("The user is already an owner of this node.");
				else
					$this->addOwner($owner);
			}
		}

		// Technical officers processing
		// Rebuild user id, and delete if it was selected
		foreach ($this->getTechnicalOfficers() as $tech_officer)
		{
			$name = "node_{$this->getId()}_tech_officer_{$tech_officer->GetId()}_remove";
			if (!empty ($_REQUEST[$name]))
			{
				if ($this->isTechnicalOfficer($tech_officer))
					$this->removeTechnicalOfficer($tech_officer);
				else
					echo _("Invalid user!");
			}
		}

		$name = "node_{$this->getId()}_new_tech_officer_submit";
		if (!empty ($_REQUEST[$name]))
		{
			$name = "node_{$this->getId()}_new_tech_officer";
			$tech_officer = User :: processSelectUserUI($name);
			if ($tech_officer)
			{
				if ($this->isTechnicalOfficer($tech_officer))
					echo _("The user is already a technical officer of this node.");
				else
					$this->addTechnicalOfficer($tech_officer);
			}
		}

		// Content processing
		$name = "node_{$this->id}_content_login";
		Content :: processLinkedContentUI($name, 'node_has_content', 'node_id', $this->id);
		$name = "node_{$this->id}_content_portal";
		Content :: processLinkedContentUI($name, 'node_has_content', 'node_id', $this->id);

		// Name
		$name = "node_".$hashed_node_id."_id";
		$this->setId($_REQUEST[$name]);
	}

	// Redirect to this node's portal page
	public function getUserUI()
	{
		header("Location: ".BASE_SSL_PATH."portal/?gw_id=".$this->getId());
	}

	/** Add content to this node */
	public function addContent(Content $content)
	{
		global $db;
		$content_id = $db->escapeString($content->getId());
		$sql = "INSERT INTO node_has_content (node_id, content_id) VALUES ('$this->id','$content_id')";
		$db->execSqlUpdate($sql, false);
		exit;
	}

	/** Remove content from this node */
	public function removeContent(Content $content)
	{
		global $db;
		$content_id = $db->escapeString($content->getId());
		$sql = "DELETE FROM node_has_content WHERE node_id='$this->id' AND content_id='$content_id'";
		$db->execSqlUpdate($sql, false);
	}

	/**Get an array of all Content linked to this node
	 * @param boolean $exclude_subscribed_content
	* @param User $subscriber The User object used to discriminate the content
	* @param $display_location Only select the content to be displayed in thios
	* area.  Defaults to 'portal_page'
	* @return an array of Content or an empty arrray */
	function getAllContent($exclude_subscribed_content = false, $subscriber = null, $display_location = 'portal_page')
	{
		global $db;
		$retval = array ();
		$content_rows = null;
		// Get all network, but exclude user subscribed content if asked
		if ($exclude_subscribed_content == true && $subscriber)
			$sql = "SELECT content_id FROM node_has_content WHERE node_id='{$this->id}' AND display_location='$display_location' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$subscriber->getId()}') ORDER BY subscribe_timestamp DESC";
		else
			$sql = "SELECT content_id FROM node_has_content WHERE node_id='{$this->id}' AND display_location='$display_location' ORDER BY subscribe_timestamp DESC";
		$db->execSql($sql, $content_rows, false);

		if ($content_rows != null)
		{
			foreach ($content_rows as $content_row)
			{
				$retval[] = Content :: getObject($content_row['content_id']);
			}
		}
		return $retval;
	}

	/** Get an array of all artistic and locative Content for this hotspot
	* @return an array of Content or an empty arrray */
	function getAllLocativeArtisticContent()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM content_group JOIN content ON (content.content_id = content_group.content_group_id) JOIN node_has_content ON (node_has_content.content_id = content_group.content_group_id AND node_has_content.node_id = '{$this->getId()}') WHERE is_persistent = true AND is_artistic_content = true AND is_locative_content = true ORDER BY subscribe_timestamp DESC";
		$content_rows = null;
		$db->execSql($sql, $content_rows, false);
		if ($content_rows != null)
		{
			foreach ($content_rows as $content_row)
			{
				// Create a content group object and grab only those that have content for the current Node
				$content_group = Content :: getObject($content_row['content_group_id']);
				if ($content_group->getDisplayNumElements() >= 1)
				{
					if ($content_group->isDisplayableAt($this))
					{
						// Disable logging and allow content to expand ( if possible )
						$content_group->setExpandStatus(true);
						$content_group->setLoggingStatus(false);
						$retval[] = $content_group;
					}
				}
			}
		}
		return $retval;
	}

	/** The list of users online at this node
	 * @return An array of User object, or en empty array */
	function getOnlineUsers()
	{
		global $db;
		$retval = array ();
		$users = null;
		$db->execSql("SELECT users.user_id FROM users,connections WHERE connections.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND connections.node_id='{$this->id}'", $users, false);
		if ($users != null)
		{
			foreach ($users as $user_row)
			{
				$retval[] = User :: getObject($user_row['user_id']);
			}
		}
		return $retval;
	}

	/** Find out how many users are online this specific Node
	 * @return Number of online users
	 */
	function getNumOnlineUsers()
	{
		global $db;
		$retval = array ();
		$row = null;
		$db->execSqlUniqueRes("SELECT COUNT(DISTINCT users.user_id) as count FROM users,connections WHERE connections.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND connections.node_id='{$this->id}'", $row, false);
		return $row['count'];
	}

	/** The list of all real owners of this node
	 * @return An array of User object, or en empty array */
	function getOwners()
	{
		global $db;
		$retval = array ();
		$owners = null;
		$db->execSql("SELECT user_id FROM node_stakeholders WHERE is_owner = true AND node_id='{$this->id}'", $owners, false);
		if ($owners != null)
		{
			foreach ($owners as $owner_row)
			{
				$retval[] = User :: getObject($owner_row['user_id']);
			}
		}
		return $retval;
	}

	/** The list of all Technical officers of this node.
	 * Technical officers are displayed highlited and in the online user's list,
	 * and are contacted when the Node goes down.
	 * @return An array of User object, or en empty array */
	function getTechnicalOfficers()
	{
		global $db;
		$retval = array ();
		$officers = null;
		$db->execSql("SELECT user_id FROM node_stakeholders WHERE is_tech_officer = true AND node_id='{$this->id}'", $officers, false);
		if ($officers != null)
		{
			foreach ($officers as $officer_row)
			{
				$retval[] = User :: getObject($officer_row['user_id']);
			}
		}
		return $retval;
	}

	/** Associates an owner to this node
	 * @param User
	 */
	function addOwner(User $user)
	{
		global $db;
		$rows = null;
		$db->execSql("SELECT * FROM node_stakeholders WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}'", $rows, false);
		if (!$rows)
		{
			if (!$db->execSqlUpdate("INSERT INTO node_stakeholders (node_id, user_id, is_owner) VALUES ('{$this->getId()}','{$user->getId()}', true)", false))
				throw new Exception(_('Could not add owner'));
		}
		else
			if (!$db->execSqlUpdate("UPDATE node_stakeholders SET is_owner = true WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
				throw new Exception(_('Could not add owner'));
	}

	/** Associates a technical officer ( tech support ) to this node
	 * @param User
	 */
	function addTechnicalOfficer(User $user)
	{
		global $db;
		$rows = null;
		$db->execSql("SELECT * FROM node_stakeholders WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}'", $rows, false);
		if (!$rows)
		{
			if (!$db->execSqlUpdate("INSERT INTO node_stakeholders (node_id, user_id, is_tech_officer) VALUES ('{$this->getId()}','{$user->getId()}', true)", false))
				throw new Exception(_('Could not add tech officer'));
		}
		else
			if (!$db->execSqlUpdate("UPDATE node_stakeholders SET is_tech_officer = true WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
				throw new Exception(_('Could not set existing user as tech officer'));
	}

	/** Remove owner flag for a stakeholder of this node
	 * @param User
	 */
	function removeOwner(User $user)
	{
		global $db;
		if (!$db->execSqlUpdate("UPDATE node_stakeholders SET is_owner = false WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
			throw new Exception(_('Could not remove owner'));
	}

	/** Remove technical officer flag for a stakeholder of this node
	 * @param User
	 */
	function removeTechnicalOfficer(User $user)
	{
		global $db;
		if (!$db->execSqlUpdate("UPDATE node_stakeholders SET is_tech_officer = false WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
			throw new Exception(_('Could not remove tech officer'));
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
			$row = null;
			$db->execSqlUniqueRes("SELECT * FROM node_stakeholders WHERE is_owner = true AND node_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
			if ($row != null)
			{
				$retval = true;
			}
		}
		return $retval;
	}

	/** Is the user a technical officer of the Node?
	 * @return true our false*/
	function isTechnicalOfficer(User $user)
	{
		global $db;
		if ($user != null)
		{
			$user_id = $user->getId();
			$retval = false;
			$row = null;
			$db->execSqlUniqueRes("SELECT * FROM node_stakeholders WHERE is_tech_officer = true AND node_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
			if ($row != null)
			{
				$retval = true;
			}
		}
		return $retval;
	}

	/** Check if an node exists */
	private function nodeExists($id)
	{
		global $db;
		$retval = false;
		$id_str = $db->escapeString($id);
		$sql = "SELECT * FROM nodes WHERE node_id='{$id_str}'";
		$row = null;
		$db->execSqlUniqueRes($sql, $row, false);
		if ($row != null)
		{
			$retval = true;
		}
		return $retval;
	}

	/** Reloads the object from the database.  Should normally be called after a set operation */
	protected function refresh()
	{
		$this->__construct($this->id);
	}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>