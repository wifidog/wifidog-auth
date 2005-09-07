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
require_once 'Content/ContentGroup.php';
require_once 'User.php';
require_once 'GisPoint.php';
require_once 'AbstractGeocoder.php';

/** Abstract a Node.  A Node is an actual physical transmitter.
 * @todo:  Make all the setter functions no-op if the value is the same as what
 * was already stored Use setCustomPortelReduirectUrl as an example*/
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
			$errmsg = _('Access denied!');
		}

		global $db;
		$id = $db->EscapeString($this->getId());
		if (!$db->ExecSqlUpdate("DELETE FROM nodes WHERE node_id='{$id}'", false))
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
	 * @param $id The id to be given to the new node
	 * @return the newly created Node object, or null if there was an error
	 */
	static function createNewObject()
	{
		global $db;

		$node_id = $db->EscapeString(get_guid());
		$object = self::createNewNode($node_id, Network::getCurrentNetwork());
		return $object;
	}

	/** Create a new Node in the database 
	 * @param $node_id The id to be given to the new node
	 * @param $network Network object.  The node's network 
	 * @todo Implement network 
	 * @return the newly created Node object, or null if there was an error
	 */
	static function createNewNode($node_id, Network $network)
	{
		global $db;
		$node_id = $db->EscapeString($node_id);
		$node_deployment_status = $db->EscapeString("IN_PLANNING");
		$node_name = _("New node");
		if (Node :: nodeExists($node_id))
			throw new Exception(_('This node already exists.'));

		$sql = "INSERT INTO nodes (node_id, creation_date, node_deployment_status, name) VALUES ('$node_id', NOW(),'$node_deployment_status', '$node_name')";

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
	 * @return the node object
	 */
	static function processSelectNodeUI($user_prefix)
	{
		$object = null;
		$name = "{$user_prefix}";
		return new self($_REQUEST[$name]);
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
		$status_list = self :: getAllDeploymentStatus();
		if ($status_list != null)
		{
			$tab = array ();
			foreach ($status_list as $status)
				$tab[] = array ($status, $status);
			$html .= FormSelectGenerator :: generateFromArray($tab, $this->getDeploymentStatus(), $name, null, false);
		}
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

	function getId()
	{
		return $this->id;
	}

	/** Gets the Network to which the node belongs 
	 * @return Network object (never returns null)
	 */
	public function getNetwork()
	{
			return Network::getObject($this->mRow['network_id']);
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
			$lat = $this->mDb->EscapeString($pt->getLatitude());
			$long = $this->mDb->EscapeString($pt->getLongitude());

			if (!empty ($lat) && !empty ($long))
				$this->mDb->ExecSqlUpdate("UPDATE nodes SET latitude = $lat, longitude = $long WHERE node_id = '{$this->getId()}'");
			else
				$this->mDb->ExecSqlUpdate("UPDATE nodes SET latitude = NULL, longitude = NULL WHERE node_id = '{$this->getId()}'");
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
		$name = $this->mDb->EscapeString($name);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET name = '{$name}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getHomePageURL()
	{
		return $this->mRow['home_page_url'];
	}

	function setHomePageUrl($url)
	{
		$url = $this->mDb->EscapeString($url);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET home_page_url = '{$url}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getDescription()
	{
		return $this->mRow['description'];
	}

	function setDescription($description)
	{
		$description = $this->mDb->EscapeString($description);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET description = '{$description}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getMapURL()
	{
		return $this->mRow['map_url'];
	}

	function setMapURL($url)
	{
		$url = $this->mDb->EscapeString($url);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET map_url = '{$url}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCivicNumber()
	{
		return $this->mRow['civic_number'];
	}

	public function setCivicNumber($civic_number)
	{
		$civic_number = $this->mDb->EscapeString($civic_number);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET civic_number = '{$civic_number}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getStreetName()
	{
		return $this->mRow['street_name'];
	}

	public function setStreetName($street_name)
	{
		$street_name = $this->mDb->EscapeString($street_name);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET street_name = '{$street_name}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCity()
	{
		return $this->mRow['city'];
	}

	public function setCity($city)
	{
		$city = $this->mDb->EscapeString($city);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET city = '{$city}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getProvince()
	{
		return $this->mRow['province'];
	}

	public function setProvince($province)
	{
		$province = $this->mDb->EscapeString($province);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET province = '{$province}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getCountry()
	{
		return $this->mRow['country'];
	}

	protected function setCountry($country)
	{
		$country = $this->mDb->EscapeString($country);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET country = '{$country}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	public function getPostalCode()
	{
		return $this->mRow['postal_code'];
	}

	public function setPostalCode($postal_code)
	{
		$postal_code = $this->mDb->EscapeString($postal_code);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET postal_code = '{$postal_code}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getTelephone()
	{
		return $this->mRow['public_phone_number'];
	}

	function setTelephone($phone)
	{
		$phone = $this->mDb->EscapeString($phone);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET public_phone_number = '{$phone}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getTransitInfo()
	{
		return $this->mRow['mass_transit_info'];
	}

	function setTransitInfo($transit_info)
	{
		$transit_info = $this->mDb->EscapeString($transit_info);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET mass_transit_info = '{$transit_info}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getEmail()
	{
		return $this->mRow['public_email'];
	}

	function setEmail($email)
	{
		$email = $this->mDb->EscapeString($email);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET public_email = '{$email}' WHERE node_id = '{$this->getId()}'");
		$this->refresh();
	}

	function getDeploymentStatus()
	{
		return $this->mRow['node_deployment_status'];
	}

	function setDeploymentStatus($status)
	{
		$status = $this->mDb->EscapeString($status);
		$this->mDb->ExecSqlUpdate("UPDATE nodes SET node_deployment_status = '{$status}' WHERE node_id = '{$this->getId()}'");
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
		return (($this->mRow['is_splash_only_node']=='t') ? true : false);
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
			$value?$value='TRUE':$value='FALSE';
			$retval = $db->ExecSqlUpdate("UPDATE nodes SET is_splash_only_node = {$value} WHERE node_id = '{$this->getId()}'", false);
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
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE nodes SET custom_portal_redirect_url = '{$value}' WHERE node_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}
	
	/** Retreives the admin interface of this object.
	 * @return The HTML fragment for this interface */
	public function getAdminUI()
	{
		//TODO: Most of this code will be moved to Hotspot class when the abtraction will be completed

//pretty_print_r($_REQUEST);
//pretty_print_r($this->mRow);
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Node (".get_class($this)." instance)</div>\n";
		$html .= "<h3>"._("Edit a hotspot")."</h3>\n";

		// Information about the node
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Information about the node:")."</div>\n";

		// Node ID
		$value = htmlspecialchars($this->getId(), ENT_QUOTES);
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("ID")." : {$value}</div>\n";
		//$html .= "<div class='admin_section_data'>\n";
		//$name = "node_".$this->getId()."_id";
		//$html .= "<input type='text' readonly='' size='10' value='$value' name='$name'>\n";
		//$html .= "</div>\n";
		$html .= "</div>\n";

		// Hashed node_id (this is a workaround since PHP auto-converts HTTP vars var periods, spaces or underscores )
		$hashed_node_id = md5($this->getId());
		
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
		if($network->getSplashOnlyNodesAllowed())
		{
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Is this node splash-only (no login)?")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$hashed_node_id."_is_splash_only_node";
		$this->isConfiguredSplashOnly()? $checked='CHECKED': $checked='';
		$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		}
		
				// custom_portal_redirect_url
		if($network->getCustomPortalRedirectAllowed())
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

		// Display stats
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Statistics:")."</div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "node_".$this->id."_get_stats";
		$html .= "<input type='submit' name='$name' value='"._("Get access statistics")."'>";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Node content:")."</div>\n";

		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getAllContent() as $content)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= $content->getListUI();
			$html .= "</div>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "node_".$this->id."_content_".$content->GetId()."_edit";
			$html .= "<input type='button' name='$name' value='"._("Edit")."' onClick='window.location.href = \"".GENERIC_OBJECT_ADMIN_ABS_HREF."?object_class=Content&action=edit&object_id=".$content->GetId()."\";'>\n";
			$name = "node_".$this->id."_content_".$content->GetId()."_erase";
			$html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "node_{$this->id}_new_content";
		$html .= Content :: getSelectContentUI($name, "AND content_id NOT IN (SELECT content_id FROM node_has_content WHERE node_id='$this->id')");
		$name = "node_{$this->id}_new_content_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add")."'>";
		$html .= "</li>\n";
		$html .= "</ul>\n";
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
			header("Location: hotspot_log.php?node_id=".urlencode($this->getId()));

		// Node configuration section
		
		$network = $this->getNetwork();

		// Deployment status
		$name = "node_".$hashed_node_id."_deployment_status";
		$this->setDeploymentStatus(self :: processSelectDeploymentStatus($name));

		//  is_splash_only_node
		if($network->getSplashOnlyNodesAllowed())
		{
		$name = "node_".$hashed_node_id."_is_splash_only_node";
		$this->setIsConfiguredSplashOnly(empty($_REQUEST[$name])?false:true);	
		}
		
		// custom_portal_redirect_url
		if($network->getCustomPortalRedirectAllowed())
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
		// Rebuild content id and deleting if it was selected for deletion )
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
			if ($content)
				$this->addContent($content);
		}
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
	 * @param boolean $exclude_subscribed_content
	* @param User $subscriber The User object used to discriminate the content
	* @return an array of Content or an empty arrray */
	function getAllContent($exclude_subscribed_content = false, $subscriber = null)
	{
		global $db;
		$retval = array ();
		// Get all network, but exclude user subscribed content if asked
		if ($exclude_subscribed_content == true && $subscriber)
			$sql = "SELECT content_id FROM node_has_content WHERE node_id='$this->id' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$subscriber->getId()}') ORDER BY subscribe_timestamp DESC";
		else
			$sql = "SELECT content_id FROM node_has_content WHERE node_id='$this->id' ORDER BY subscribe_timestamp DESC";
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

	/** Get an array of all artistic and locative Content for this hotspot
	* @return an array of Content or an empty arrray */
	function getAllLocativeArtisticContent()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM content_group JOIN content ON (content.content_id = content_group.content_group_id) JOIN node_has_content ON (node_has_content.content_id = content_group.content_group_id AND node_has_content.node_id = '{$this->getId()}') WHERE is_persistent = true AND is_artistic_content = true AND is_locative_content = true ORDER BY subscribe_timestamp DESC";
		$db->ExecSql($sql, $content_rows, false);
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

		$db->ExecSql("SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date, node_deployment_status FROM nodes ORDER BY node_id", $nodes, false);

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

	/** The list of users online at this node
	 * @return An array of User object, or en empty array */
	function getOnlineUsers()
	{
		global $db;
		$retval = array ();
		$db->ExecSql("SELECT users.user_id FROM users,connections WHERE connections.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND connections.node_id='{$this->id}'", $users, false);
		if ($users != null)
		{
			foreach ($users as $user_row)
			{
				$retval[] = User :: getObject($user_row['user_id']);
			}
		}
		return $retval;
	}

	function getOwners()
	{
		global $db;
		$retval = array ();
		$db->ExecSql("SELECT user_id FROM node_stakeholders WHERE is_owner = true AND node_id='{$this->id}'", $owners, false);
		if ($owners != null)
		{
			foreach ($owners as $owner_row)
			{
				$retval[] = User :: getObject($owner_row['user_id']);
			}
		}
		return $retval;
	}

	function getTechnicalOfficers()
	{
		global $db;
		$retval = array ();
		$db->ExecSql("SELECT user_id FROM node_stakeholders WHERE is_tech_officer = true AND node_id='{$this->id}'", $officers, false);
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
		$db->ExecSql("SELECT * FROM node_stakeholders WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}'", $rows, false);
		if (!$rows)
		{
			if (!$db->ExecSqlUpdate("INSERT INTO node_stakeholders (node_id, user_id, is_owner) VALUES ('{$this->getId()}','{$user->getId()}', true)", false))
				throw new Exception(_('Could not add owner'));
		}
		else
			if (!$db->ExecSqlUpdate("UPDATE node_stakeholders SET is_owner = true WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
				throw new Exception(_('Could not add owner'));
	}

	/** Associates a technical officer ( tech support ) to this node
	 * @param User
	 */
	function addTechnicalOfficer(User $user)
	{
		global $db;
		$db->ExecSql("SELECT * FROM node_stakeholders WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}'", $rows, false);
		if (!$rows)
		{
			if (!$db->ExecSqlUpdate("INSERT INTO node_stakeholders (node_id, user_id, is_tech_officer) VALUES ('{$this->getId()}','{$user->getId()}', true)", false))
				throw new Exception(_('Could not add tech officer'));
		}
		else
			if (!$db->ExecSqlUpdate("UPDATE node_stakeholders SET is_tech_officer = true WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
				throw new Exception(_('Could not add tech officer'));
	}

	/** Remove a technical officer ( tech support ) from this node
	 * @param User
	 */
	function removeOwner(User $user)
	{
		global $db;
		if (!$db->ExecSqlUpdate("UPDATE node_stakeholders SET is_owner = false WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
			throw new Exception(_('Could not remove owner'));
	}

	/** Remove a technical officer ( tech support ) from this node
	 * @param User
	 */
	function removeTechnicalOfficer(User $user)
	{
		global $db;
		if (!$db->ExecSqlUpdate("UPDATE node_stakeholders SET is_tech_officer = false WHERE node_id = '{$this->getId()}' AND user_id = '{$user->getId()}';", false))
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
			$db->ExecSqlUniqueRes("SELECT * FROM node_stakeholders WHERE is_owner = true AND node_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
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
			$db->ExecSqlUniqueRes("SELECT * FROM node_stakeholders WHERE is_tech_officer = true AND node_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
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
		$id_str = $db->EscapeString($id);
		$sql = "SELECT * FROM nodes WHERE node_id='{$id_str}'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if($row!=null)
		{
			$retval = true;
		}
		return $retval;
	}

	/** Warning, the semantics of this function will change *
	 * @deprecated version - 2005-04-29 USE getOnlineUsers instead
	 * */
	public static function getAllOnlineUsers()
	{
		global $db;
		$db->ExecSql("SELECT * FROM connections,users,nodes WHERE token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY timestamp_in DESC", $online_users);
		return $online_users;
	}

	/** Reloads the object from the database.  Should normally be called after a set operation */
	protected function refresh()
	{
		$this->__construct($this->id);
	}

} // End class
?>