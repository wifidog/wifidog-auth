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
/**@file Network.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>
 */
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/GenericObject.php';
require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/Node.php';

/** Abstract a Network.  A network is an administrative entity with it's own users, nodes and authenticator. */
class Network implements GenericObject
{
	private $id; /**< The network id */
	private $mRow;

	/** Get an instance of the object
	* @see GenericObject
	* @param $id The object id
	* @return the Content object, or null if there was an error (an exception is also thrown)
	*/
	static public function getObject($id)
	{
		return new self($id);
	}

	/** Get all the Networks configured on this server
	 * @return an array of Network objects.  The default network is returned
	 * first
	 */
	static function getAllNetworks()
	{
		$retval = array ();
		global $db;
		$sql = "SELECT network_id FROM networks ORDER BY is_default_network DESC";
		$network_rows = null;
		$db->ExecSql($sql, $network_rows, false);
		if ($network_rows == null)
		{
			throw new Exception(_("Network::getAllNetworks:  Fatal error: No networks in the database!"));
		}
		foreach ($network_rows as $network_row)
		{
			$retval[] = new self($network_row['network_id']);
		}
		return $retval;
	}

	/** Get the default network
	 * @return a Network object, NEVER returns null.
	 */
	static function getDefaultNetwork($real_network_only = false)
	{
		$retval = null;
		global $db;
		$sql = "SELECT network_id FROM networks WHERE is_default_network=TRUE ORDER BY creation_date LIMIT 1";
		$network_row = null;
		$db->ExecSqlUniqueRes($sql, $network_row, false);
		if ($network_row == null)
		{
			throw new Exception(_("Network::getDefaultNetwork:  Fatal error: Unable to find the default network!"));
		}
		$retval = new self($network_row['network_id']);
		return $retval;
	}

	/** Get the current network for which the portal is displayed or to which a user is physically connected.
	 * @param $real_network_only NOT IMPLEMENTED YET true or false.  If true,
	 * the real physical network where the user is connected is returned, and
	 * the node set by setCurrentNode is ignored.
	 * @return a Network object, NEVER returns null.
	 */
	static function getCurrentNetwork($real_network_only = false)
	{
		$retval = null;
		$current_node = Node :: getCurrentNode();
		if ($current_node != null)
		{
			$retval = $current_node->getNetwork();
		}
		else
		{
			$retval = Network :: getDefaultNetwork();
		}
		return $retval;
	}

	/** Create a new Content object in the database 
	 * @see GenericObject
	 * @param $network_id The network id of the new network.  If absent, will be
	 * assigned a guid.
	 * @return the newly created object, or null if there was an error
	 */
	static function createNewObject($network_id = null)
	{
		global $db;
		if(empty($network_id))
		{
			$network_id = get_guid();
		}
		$network_id = $db->EscapeString($network_id);
		
		$sql = "INSERT INTO networks (network_id, network_authenticator_class) VALUES ('$network_id', 'AuthenticatorLocalUser')";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to insert the new network in the database!'));
		}
		$object = new self($network_id);
		return $object;

	}

	/** Get an interface to pick a network.  If there is only one network available, no interface is actually shown
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	* @return html markup
	*/
	public static function getSelectNetworkUI($user_prefix)
	{
		$html = '';
		$name = $user_prefix;
		$html .= _("Network:")." \n";

		global $db;
		$sql = "SELECT network_id, name FROM networks ORDER BY is_default_network DESC";
		$network_rows = null;
		$db->ExecSql($sql, $network_rows, false);
		if ($network_rows == null)
		{
			throw new Exception(_("Network::getAllNetworks:  Fatal error: No networks in the database!"));
		}

		$network_array = self :: getAllNetworks();
		$number_of_networks = count($network_rows);
		if ($number_of_networks > 1)
		{
			$i = 0;
			foreach ($network_rows as $network_row)
			{
				$tab[$i][0] = $network_row['network_id'];
				$tab[$i][1] = $network_row['name'];
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);

		}
		else
		{
			foreach ($network_rows as $network_row) //iterates only once...
			{
				$html .= " $network_row[name] ";
				$html .= "<input type='hidden' name='$name' value='$network_row[network_id]'>";
			}
		}
		return $html;
	}

	/** Get the selected Network object.
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the Network object
	 */
	static function processSelectNetworkUI($user_prefix)
	{
		$object = null;
		$name = "{$user_prefix}";
		if (!empty ($_REQUEST[$name]))
			return new self($_REQUEST[$name]);
		else
			return null;
	}

	/** Get an interface to create a new network.
	* @return html markup
	*/
	public static function getCreateNewObjectUI()
	{
		$html = '';
		$html .= _("Create new network with id")." \n";
		$name = "new_network_id";
		$html .= "<input type='text' size='10' name='{$name}'>\n";
		return $html;

	}

	/** Process the new object interface. 
	 *  Will return the new object if the user has the credentials and the form was fully filled.
	 * @return the Network object or null if no new Network was created.
	 */
	static function processCreateNewObjectUI()
	{
		$retval = null;
		$name = "new_network_id";
		if (!empty ($_REQUEST[$name]))
		{
			$network_id = $_REQUEST[$name];
			if ($network_id)
			{
				if (!User :: getCurrentUser()->isSuperAdmin())
				{
					throw new Exception(_("Access denied"));
				}
				$retval = self :: createNewObject($network_id);
			}
		}
		return $retval;
	}

	private function __construct($p_network_id)
	{
		global $db;

		$network_id_str = $db->EscapeString($p_network_id);
		$sql = "SELECT *, EXTRACT(EPOCH FROM validation_grace_time) as validation_grace_time_seconds FROM networks WHERE network_id='$network_id_str'";
		$row = null;
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception("The network with id $network_id_str could not be found in the database");
		}
		$this->mRow = $row;
		$this->id = $db->EscapeString($row['network_id']);
	}

	/** Retreives the id of the object 
	 * @return The id */
	public function getId()
	{
		return $this->id;
	}

	/** Retreives the network name 
	 * @return The id */
	public function getTechSupportEmail()
	{
		return $this->mRow['tech_support_email'];
	}

	/** Set the network's tech support and information email address
	 * @param $value The new value 
	 * @return true on success, false on failure */
	function setTechSupportEmail($value)
	{
		$retval = true;
		if ($value != $this->getName())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET tech_support_email = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/**
	 * Retrieves the network name 
	 * @return A string
	 */
	public function getName()
	{
		return $this->mRow['name'];
	}

	/** Set the network's name
	 * @param $value The new value 
	 * @return true on success, false on failure
	 */
	function setName($value)
	{
		$retval = true;
		if ($value != $this->getName())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET name = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/**
	 * Retrieves the network's creation date
	 * @return A string
	 */
	public function getCreationDate()
	{
		return $this->mRow['creation_date'];
	}

	/** Retreives the network's homepage url 
	 * @return The id */
	public function getHomepageURL()
	{
		return $this->mRow['homepage_url'];
	}

	/** Set the network's homepage url
	 * @param $value The new value 
	 * @return true on success, false on failure */
	function setHomepageURL($value)
	{
		$retval = true;
		if ($value != $this->getName())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET homepage_url = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Retreives the network's authenticator's class.
	 *  @return    A string */
	public function getAuthenticatorClassName()
	{
		return $this->mRow['network_authenticator_class'];
	}

	/** Set the network's authenticator's class.  The subclass of Authenticator to be used for user authentication (ex: AuthenticatorRadius)
	 * @param $value a string, the class name of a  subclass of Authenticator 
	 * @return true on success, false on failure */
	function setAuthenticatorClassName($value)
	{
		$retval = true;
		if ($value != $this->getAuthenticatorClassName())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET network_authenticator_class = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Retreives the authenticator's parameters
	 * @return A string */
	public function getAuthenticatorConstructorParams()
	{
		return $this->mRow['network_authenticator_params'];
	}

	/** The explicit parameters to be passed to the authenticator's constructor (ex: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key', 'CHAP_MD5')
	 * @param $value The new value 
	 * @return true on success, false on failure */
	function setAuthenticatorConstructorParams($value)
	{
		$retval = true;
		if ($value != $this->getAuthenticatorConstructorParams())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET network_authenticator_params = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Get the Authenticator object for this network 
	 * @todo:  Reimplement this using the muich safer call_user_func_array
	 * instead of eval()  Benoitg 2005-09-10
	 * @return a subclass of Authenticator */
	public function getAuthenticator()
	{
		require_once BASEPATH.'classes/Authenticator.php';
		// Include only the authenticator we are about to use
		require_once BASEPATH.'classes/'.$this->mRow['network_authenticator_class'].'.php';
		if (strpos($this->mRow['network_authenticator_params'], ';') != false)
		{
			throw new Exception("Network::getAuthenticator():  Security error:  The parameters passed to the constructor of the authenticator are potentially unsafe");
		}
		$objstring = 'return new '.$this->mRow['network_authenticator_class']."(".$this->mRow['network_authenticator_params'].");";
		return eval ($objstring);
	}

	/** Is the network the default network?
	 * @return true or false */
	public function isDefaultNetwork()
	{
		($this->mRow['is_default_network'] == 't') ? $retval = true : $retval = false;
		return $retval;
	}

	/** Set as the default network.  The can only be one default network, so this method will unset is_default_network for all other network 
	 * @return true on success, false on failure */
	function setAsDefaultNetwork()
	{
		$retval = true;
		if (!$this->isDefaultNetwork())
		{
			global $db;
			$sql = "UPDATE networks SET is_default_network = FALSE;\n";
			$sql .= "UPDATE networks SET is_default_network = TRUE WHERE network_id = '{$this->getId()}';\n";
			$retval = $db->ExecSqlUpdate($sql, false);
			$this->refresh();
		}
		return $retval;
	}

	/** Retreives the network's validation grace period
	 * @return An integer (seconds) */
	public function getValidationGraceTime()
	{
		return $this->mRow['validation_grace_time_seconds'];
	}

	/** Set the network's validation grace period in seconds.  A new user is granted Internet access for this period check his email and validate his account.
	 * @param $value The new value 
	 * @return true on success, false on failure */
	function setValidationGraceTime($value)
	{
		$retval = true;
		if ($value != $this->getValidationGraceTime())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET validation_grace_time = '{$value} seconds' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Retreives the FROM adress of the validation email
	 * @return A string */
	public function getValidationEmailFromAddress()
	{
		return $this->mRow['validation_email_from_address'];
	}

	/** Set the FROM adress of the validation email
	 * @param $value The new value 
	 * @return true on success, false on failure */
	function setValidationEmailFromAddress($value)
	{
		$retval = true;
		if ($value != $this->getValidationEmailFromAddress())
		{
			global $db;
			$value = $db->EscapeString($value);
			$retval = $db->ExecSqlUpdate("UPDATE networks SET validation_email_from_address = '{$value}' WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Can an account be connected more than once at the same time?
	 * @return true or false */
	public function getMultipleLoginAllowed()
	{
		return ($this->mRow['allow_multiple_login'] == 't') ? true : false;
	}

	/** Set if a account be connected more than once at the same time?
	 * @param $value The new value, true or false
	 * @return true on success, false on failure */
	function setMultipleLoginAllowed($value)
	{
		$retval = true;
		if ($value != $this->getMultipleLoginAllowed())
		{
			global $db;
			$value ? $value = 'TRUE' : $value = 'FALSE';
			$retval = $db->ExecSqlUpdate("UPDATE networks SET allow_multiple_login = {$value} WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Are nodes allowed to be set as splash-only (no login)?
	 * @return true or false */
	public function getSplashOnlyNodesAllowed()
	{
		return (($this->mRow['allow_splash_only_nodes'] == 't') ? true : false);
	}

	/** Set if nodes are allowed to be set as splash-only (no login)
	 * @param $value The new value, true or false
	 * @return true on success, false on failure */
	function setSplashOnlyNodesAllowed($value)
	{
		$retval = true;
		if ($value != $this->getSplashOnlyNodesAllowed())
		{
			global $db;
			$value ? $value = 'TRUE' : $value = 'FALSE';
			$retval = $db->ExecSqlUpdate("UPDATE networks SET allow_splash_only_nodes = {$value} WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Get's the splash-only user.  This is the user that people logged-in at a splash-only hotspot will show up as.  This user always has multiple-login capabilities.
	* @param $username The username of the user
	* @param $account_origin The account origin
	* @return a User object
	*/
	public function getSplashOnlyUser()
	{
		$username = 'SPLASH_ONLY_USER';

		$user = User :: getUserByUsernameAndOrigin($username, $this);
		if (!$user)
		{
			$user = User :: createUser(get_guid(), $username, $this, '', '');
			$user->setAccountStatus(ACCOUNT_STATUS_ALLOWED);
		}
		return $user;
	}
	/** Are nodes allowed to redirect users to an arbitrary web page instead of the portal?
	 * @return true or false */
	public function getCustomPortalRedirectAllowed()
	{
		return (($this->mRow['allow_custom_portal_redirect'] == 't') ? true : false);
	}

	/** Set if nodes are allowed to redirect users to an arbitrary web page instead of the portal?
	 * @param $value The new value, true or false
	 * @return true on success, false on failure */
	function setCustomPortalRedirectAllowed($value)
	{
		$retval = true;
		if ($value != $this->getCustomPortalRedirectAllowed())
		{
			global $db;
			$value ? $value = 'TRUE' : $value = 'FALSE';
			$retval = $db->ExecSqlUpdate("UPDATE networks SET allow_custom_portal_redirect = {$value} WHERE network_id = '{$this->getId()}'", false);
			$this->refresh();
		}
		return $retval;
	}

	/** Does the user have admin access to this network? 
	 * @return true our false*/
	function hasAdminAccess(User $user)
	{
		global $db;
		$retval = false;
		if ($user != null)
		{
			$user_id = $user->getId();
			$retval = false;
			$db->ExecSqlUniqueRes("SELECT * FROM network_stakeholders WHERE is_admin = true AND network_id='{$this->id}' AND user_id='{$user_id}'", $row, false);
			if ($row != null)
			{
				$retval = true;
			}
			else
				if ($user->isSuperAdmin())
				{
					$retval = true;
				}
		}
		return $retval;
	}

	/**Get an array of all Content linked to the network
	* @param boolean $exclude_subscribed_content
	* @param User $subscriber The User object used to discriminate the content
	* @return an array of Content or an empty arrray */
	function getAllContent($exclude_subscribed_content = false, $subscriber = null)
	{
		global $db;
		$retval = array ();
		// Get all network, but exclude user subscribed content if asked
		if ($exclude_subscribed_content == true && $subscriber)
			$sql = "SELECT content_id FROM network_has_content WHERE network_id='$this->id' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$subscriber->getId()}') ORDER BY subscribe_timestamp DESC";
		else
			$sql = "SELECT content_id FROM network_has_content WHERE network_id='$this->id' ORDER BY subscribe_timestamp DESC";
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

	/** Retreives the admin interface of this object.
	 * @return The HTML fragment for this interface */

	public function getAdminUI()
	{
		$html = '';
		$html .= "<h3>"._("Network management")."</h3>\n";
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Network (".get_class($this)." instance)</div>\n";

		// network_id
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network ID")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_name";
		$value = htmlspecialchars($this->getId(), ENT_QUOTES);
		$html .= $value;
		//$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// name
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network name")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_name";
		$value = htmlspecialchars($this->getName(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// homepage_url
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network's web site")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_homepage_url";
		$value = htmlspecialchars($this->getHomepageURL(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// tech_support_email
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Technical support email")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_tech_support_email";
		$value = htmlspecialchars($this->getTechSupportEmail(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  network_authenticator_class
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network authenticator class.  The subclass of Authenticator to be used for user authentication (ex: AuthenticatorRadius)")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_network_authenticator_class";
		$value = htmlspecialchars($this->getAuthenticatorClassName(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  network_authenticator_params
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("The explicit parameters to be passed to the authenticator (ex: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key', 'CHAP_MD5')")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_network_authenticator_params";
		$value = htmlspecialchars($this->getAuthenticatorConstructorParams(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  is_default_network
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Is this network the default network?")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_is_default_network";
		$this->isDefaultNetwork() ? $checked = 'CHECKED' : $checked = '';
		$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  validation_grace_time
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("The length of the validation grace period in seconds.  A new user is granted Internet access for this period check his email and validate his account.")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_validation_grace_time";
		$value = htmlspecialchars($this->getValidationGraceTime(), ENT_QUOTES);
		$html .= "<input type='text' size ='5' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  validation_email_from_address
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("This will be the from adress of the validation email")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_validation_email_from_address";
		$value = htmlspecialchars($this->getValidationEmailFromAddress(), ENT_QUOTES);
		$html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  allow_multiple_login
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Can an account be connected more than once at the same time?")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_allow_multiple_login";
		$this->getMultipleLoginAllowed() ? $checked = 'CHECKED' : $checked = '';
		$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  allow_splash_only_nodes
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Are nodes allowed to be set as splash-only (no login)?")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_allow_splash_only_nodes";
		$this->getSplashOnlyNodesAllowed() ? $checked = 'CHECKED' : $checked = '';
		$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//  allow_custom_portal_redirect
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Are nodes allowed to redirect users to an arbitrary web page instead of the portal?")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "network_".$this->getId()."_allow_custom_portal_redirect";
		$this->getCustomPortalRedirectAllowed() ? $checked = 'CHECKED' : $checked = '';
		$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//	network_stakeholders
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network stakeholders")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		//$name = "network_".$this->getId()."_allow_custom_portal_redirect";
		//$this->getCustomPortalRedirectAllowed()? $checked='CHECKED': $checked='';
		//$html .= "<input type='checkbox' name='$name' $checked>\n";
		$html .= "WRITEME!";
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Create new nodes
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("New node ID")." : </div>\n";

		$html .= "<div class='admin_section_data'>\n";

		$html .= Node :: getCreateNewObjectUI($this);

		$html .= "<div class='admin_section_tools'>\n";
		$name = "network_{$this->getId()}_create_node";
		$html .= "<input type='submit' name='{$name}' value='"._("Create a new node")."'>\n";
		$html .= "</div>\n";

		$html .= "</div>\n";
		$html .= "</div>\n";

		// Content management		
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network content:")."</div>\n";
		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getAllContent() as $content)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= $content->getListUI();
			$html .= "</div'>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "node_".$this->id."_content_".$content->GetId()."_edit";
			$html .= "<input type='button' name='$name' value='"._("Edit")."' onClick='window.location.href = \"".GENERIC_OBJECT_ADMIN_ABS_HREF."?object_class=Content&action=edit&object_id=".$content->GetId()."\";'>\n";
			$name = "content_group_".$this->id."_element_".$content->GetId()."_erase";
			$html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "network_{$this->id}_new_content";
		$html .= Content :: getSelectContentUI($name, "AND content_id NOT IN (SELECT content_id FROM network_has_content WHERE network_id='$this->id')");
		$name = "network_{$this->id}_new_content_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add")."'>";
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
		//pretty_print_r($_REQUEST);
		$user = User :: getCurrentUser();
		if (!$this->hasAdminAccess($user))
		{
			throw new Exception(_('Access denied!'));
		}

		// name
		$name = "network_".$this->getId()."_name";
		$this->setName($_REQUEST[$name]);

		// homepage_url
		$name = "network_".$this->getId()."_homepage_url";
		$this->setHomepageURL($_REQUEST[$name]);

		// tech_support_email
		$name = "network_".$this->getId()."_tech_support_email";
		$this->setTechSupportEmail($_REQUEST[$name]);

		//  network_authenticator_class
		$name = "network_".$this->getId()."_network_authenticator_class";
		$this->setAuthenticatorClassName($_REQUEST[$name]);

		//  network_authenticator_params
		$name = "network_".$this->getId()."_network_authenticator_params";
		$this->setAuthenticatorConstructorParams($_REQUEST[$name]);

		//  is_default_network
		$name = "network_".$this->getId()."_is_default_network";
		if (!empty($_REQUEST[$name]) && $_REQUEST[$name] == 'on')
			$this->setAsDefaultNetwork();
		

		//  validation_grace_time
		$name = "network_".$this->getId()."_validation_grace_time";
		$this->setValidationGraceTime($_REQUEST[$name]);

		//  validation_email_from_address
		$name = "network_".$this->getId()."_validation_email_from_address";
		$this->setValidationEmailFromAddress($_REQUEST[$name]);

		//  allow_multiple_login
		$name = "network_".$this->getId()."_allow_multiple_login";
		$this->setMultipleLoginAllowed(empty ($_REQUEST[$name]) ? false : true);

		//  allow_splash_only_nodes
		$name = "network_".$this->getId()."_allow_splash_only_nodes";
		$this->setSplashOnlyNodesAllowed(empty ($_REQUEST[$name]) ? false : true);

		//  allow_custom_portal_redirect
		$name = "network_".$this->getId()."_allow_custom_portal_redirect";
		$this->setCustomPortalRedirectAllowed(empty ($_REQUEST[$name]) ? false : true);

		// Node creation
		$new_node = Node :: processCreateNewObjectUI();
		if ($new_node)
		{
			$url = GENERIC_OBJECT_ADMIN_ABS_HREF."?".http_build_query(array ("object_class" => "Node", "action" => "edit", "object_id" => $new_node->getId()));
			header("Location: {$url}");
		}
		// Content management
		foreach ($this->getAllContent() as $content)
		{
			$name = "content_group_".$this->id."_element_".$content->GetId()."_erase";
			if (!empty ($_REQUEST[$name]))
			{
				$this->removeContent($content);
			}
		}

		$name = "network_{$this->id}_new_content_submit";
		if (!empty ($_REQUEST[$name]))
		{
			$name = "network_{$this->id}_new_content";
			$content = Content :: processSelectContentUI($name);
			if ($content)
				$this->addContent($content);
		}
	}

	/** Add network-wide content to this network */
	public function addContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "INSERT INTO network_has_content (network_id, content_id) VALUES ('$this->id','$content_id')";
		$db->ExecSqlUpdate($sql, false);
	}

	/** Remove network-wide content from this network */
	public function removeContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "DELETE FROM network_has_content WHERE network_id='$this->id' AND content_id='$content_id'";
		$db->ExecSqlUpdate($sql, false);
	}

	/** Delete this Object form the it's storage mechanism 
	 * @param &$errmsg Returns an explanation of the error on failure
	 * @return true on success, false on failure or access denied */
	public function delete(& $errmsg)
	{
		$retval = false;
		$user = User :: getCurrentUser();
		if (!$user->isSuperAdmin())
		{
			$errmsg = _('Access denied (must have super admin access)');
		}
		else
		{
		global $db;
		$id = $db->EscapeString($this->getId());
		if (!$db->ExecSqlUpdate("DELETE FROM networks WHERE network_id='{$id}'", false))
		{
			$errmsg = _('Could not delete network!');
		}
		else
		{
			$retval = true;
		}
		}
		return $retval;
	}
	/** Reloads the object from the database.  Should normally be called after a set operation */
	protected function refresh()
	{
		$this->__construct($this->id);
	}

} //End class
?>