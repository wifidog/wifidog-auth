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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: Network.php 963 2006-02-21 11:16:38Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/GenericObject.php');

/**
 * Administration interface for defining all servers WiFiDog is running on
 *
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class Server implements GenericObject
{
    /**
     * The server Id
     *
     * @var string

     */
	private $_id;

	/**
	 * All information about the server
	 *
	 * @var array
	 *
	 * @access private
	 */
	private $_mRow;

	/**
	 * Constructor
	 *
	 * @param string $p_server_id Id of the server
	 *
	 * @access private
	 */
	private function __construct($p_server_id)
	{
	    // Define globals
		global $db;

		// Init values
		$_row = null;

		$_serverId = $db->escapeString($p_server_id);
		$_sql = "SELECT * FROM servers WHERE server_id='$_serverId'";
		$db->execSqlUniqueRes($_sql, $_row, false);

		if ($_row == null) {
			throw new Exception("The server with id $_serverId could not be found in the database!");
		}

		$this->_mRow = $_row;

		$this->_id = $db->escapeString($_row['server_id']);
	}

    /**
     * Retreives the Id of the object
     *
     * @return string The Id
     */
	public function getId()
	{
		return $this->_id;
	}

	/** Get an instance of the object
	 *
     * @param string $id The object id
     *
     * @return object The object, or null if there was an error (an exception
     *                is also thrown)
     *
     * @see GenericObject

     */
	public static function getObject($id)
	{
		return new self($id);
	}

	/**
	 * Get all servers configured on this WiFiDog instance
	 *
	 * @return array An array of server objects. The default server is returned
	 *               first
	 *
	 * @static
	 * @access public
	 */
	public static function getAllServers()
	{
	    // Define globals
		global $db;

	    // Init values
		$_retVal = array ();

		$_sql = "SELECT server_id FROM servers ORDER BY is_default_server DESC";
		$_server_rows = null;

		$db->execSql($_sql, $_server_rows, false);

		if ($_server_rows == null) {
			throw new Exception(_("Server::getAllServers: Fatal error: No servers in the database!"));
		}

		foreach ($_server_rows as $_server_row) {
			$_retVal[] = new self($_server_row['server_id']);
		}

		return $_retVal;
	}

	/**
	 * Get the default server
	 *
	 * @return object A server object, NEVER returns null
	 *
	 * @static
	 * @access public
	 */
	public static function getDefaultServer($silent=false)
	{
	    // Define globals
		global $db;

		// Init values
		$_retVal = null;
		$_server_row = null;

		$sql = "SELECT server_id FROM servers WHERE is_default_server=TRUE ORDER BY creation_date LIMIT 1";
		$db->execSqlUniqueRes($sql, $_server_row, false);

		if ($_server_row == null) {
		    if($silent) {
		        return null;
		    }
		    else {
			throw new Exception(_("Server::getDefaultServer: Fatal error: Unable to find the default server!"));
		    }
		}

		$_retVal = new self($_server_row['server_id']);

		return $_retVal;
	}

	/**
	 * Get the current server for which the portal is displayed or to which a
	 * user is physically connected.
	 *
	 * @param bool $silent If true, no exception will be thrown
	 *
	 * @return mixed A server object, returns null if server hasn't been found
	 *               in the database
	 *
	 * @static
	 * @access public
	 */
	public static function getCurrentServer($silent = false)
	{
        if(empty($_SERVER['SERVER_NAME']))
        {
            return null; //We were probably called from the command line
        }
	    // Define globals
		global $db;

		// Init values
		$_retVal = null;
		$_server_row = null;

		$sql = "SELECT server_id FROM servers WHERE hostname='{$_SERVER['SERVER_NAME']}' ORDER BY creation_date LIMIT 1";
		$db->execSqlUniqueRes($sql, $_server_row, false, $silent);

		if ($_server_row == null && !$silent) {
			throw new Exception(sprintf(_("Server::getCurrentServer: Fatal error: Unable to find a server matching hostname %s in the database!"), $_SERVER['SERVER_NAME']));
		} else if ($_server_row != null) {
    		$_retVal = new self($_server_row['server_id']);
		}

		return $_retVal;
	}

	/**
	 * Create a new Content object in the database
	 *
	 * @param string $server_id The server id of the new server. If absent,
	 *                           will be assigned a guid.
	 *
	 * @return mixed The newly created object, or null if there was an error
	 *
	 * @see GenericObject
	 *
	 * @static
	 * @access public
	 */
	public static function createNewObject($server_id = null)
	{
	    // Define globals
		global $db;

		if (empty($server_id)) {
			$server_id = get_guid();
		}

		$server_id = $db->escapeString($server_id);

		$sql = "INSERT INTO servers (server_id) VALUES ('$server_id')";

		if (!$db->execSqlUpdate($sql, false)) {
			throw new Exception(_('Unable to insert the new server in the database!'));
		}

		$_object = new self($server_id);

		return $_object;

	}

	/**
	 * Get an interface to pick a server
	 *
	 * If there is only one server available, no interface is actually shown
	 *
     * @param string $user_prefix         A identifier provided by the
     *                                    programmer to recognise it's generated
     *                                    html form
     * @param object $pre_selected_server An optional server object. The server
     *                                    to be pre-selected in the form object
     * @param string $additional_where    Additional SQL conditions for the
     *                                    servers to select
     *
     * @return string HTML markup

     */
    public static function getSelectServerUI($user_prefix, $pre_selected_server = null, $additional_where = null)
    {
        // Define globals
		global $db;

        // Init values
		$_html = "";
		$_serverRows = null;

		$_name = $user_prefix;

		$_html .= _("Server:")." \n";

		if ($pre_selected_server) {
			$_selectedId = $pre_selected_server->getId();
		} else {
			$_selectedId = null;
		}

		$additional_where = $db->escapeString($additional_where);

		$_sql = "SELECT server_id, name FROM servers WHERE 1=1 $additional_where ORDER BY is_default_server DESC";
		$db->execSql($_sql, $_serverRows, false);

		if ($_serverRows == null) {
			throw new Exception(_("Server::getSelectServerUI: Fatal error: No servers in the database!"));
		}

		$_numberOfServers = count($_serverRows);

		if ($_numberOfServers > 1) {
			$_i = 0;

			foreach ($_serverRows as $_serverRow) {
				$_tab[$_i][0] = $_serverRow['server_id'];
				$_tab[$_i][1] = $_serverRow['name'];
				$_i ++;
			}

			$_html .= FormSelectGenerator::generateFromArray($_tab, $_selectedId, $_name, null, false);
		} else {
			foreach ($_serverRows as $_serverRow) {
				$_html .= " $_serverRow[name] ";
				$_html .= "<input type='hidden' name='$_name' value='{$_serverRow['server_id']}'>";
			}
		}

		return $_html;
	}

	/**
	 * Get the selected server object.
	 *
	 * @param string $user_prefix A identifier provided by the programmer to
	 *                            recognise it's generated form
	 *
	 * @return mixed The server object or null
	 *
	 * @static
	 * @access public
	 */
	public static function processSelectServerUI($user_prefix)
	{
	    // Init values
	    $_retVal = null;

		$_name = "{$user_prefix}";

		if (!empty($_REQUEST[$_name])) {
			$_retVal = new self($_REQUEST[$_name]);
		}

		return $_retVal;
	}

	/**
     * Get an interface to create a new server
     *
     * @return string HTML markup

     */
	public static function getCreateNewObjectUI()
	{
	    // Init values
		$_html = '';

		$_name = "new_server_id";

		$_html .= _("Add a new server with ID") . ": ";
		$_html .= "<input type='text' size='10' name='{$_name}'>";

		return $_html;
	}

    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return string The server object or null if no new server was created

     */
	public static function processCreateNewObjectUI()
	{
        require_once('classes/User.php');
	    // Init values
		$_retVal = null;

		$_name = "new_server_id";

		if (!empty($_REQUEST[$_name])) {
			$_serverId = $_REQUEST[$_name];

			if ($_serverId) {
    				if (!User::getCurrentUser()->isSuperAdmin()) {
    					throw new Exception(_("Access denied"));
    				}


				$_retVal = self::createNewObject($_serverId);
			}
		}

		return $_retVal;
	}

    /**
     * Retrieves the server name
     *
     * @return string Name of server
     */
	public function getName()
	{
		return $this->_mRow['name'];
	}

    /**
     * Set the server's name
     *
     * @param string $value The new name of server
     *
     * @return bool True on success, false on failure
     */
	public function setName($value)
	{
	    // Define globals
		global $db;

	    // Init values
		$_retVal = true;

		if ($value != $this->getName()) {
			$value = $db->escapeString($value);
			$_retVal = $db->execSqlUpdate("UPDATE servers SET name = '{$value}' WHERE server_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
	}

	/**
	 * Retrieves the servers's creation date
	 *
	 * @return string Creation date of server
	 *
	 * @access public
	 */
	public function getCreationDate()
	{
		return $this->_mRow['creation_date'];
	}

    /**
     * Set the server's creation date
     *
     * @param string $value The new creation date of server
     *
     * @return bool True on success, false on failure
     */
	public function setCreationDate($value)
	{
	    // Define globals
		global $db;

	    // Init values
		$_retVal = true;

		if ($value != $this->getCreationDate()) {
			$value = $db->escapeString($value);
			$_retVal = $db->execSqlUpdate("UPDATE servers SET creation_date = '{$value}' WHERE server_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
	}

    /**
     * Retreives the servers's hostname
     *
     * @return string Hostname of server
     */
	public function getHostname()
	{
		return $this->_mRow['hostname'];
	}

    /**
     * Sets the servers's hostname
     *
     * @param string $value The new hostname of server
     *
     * @return bool True on success, false on failure
     */
	public function setHostname($value)
	{
	    // Define globals
		global $db;

		// Init values
		$_retVal = true;

		if ($value != $this->getHostname()) {
			$value = $db->escapeString($value);
			$_retVal = $db->execSqlUpdate("UPDATE servers SET hostname = '{$value}' WHERE server_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
	}

    /**
     * Is the server the default server?
     *
     * @return bool True or false
     */
	public function isDefaultServer()
	{
	    // Init values
	    $_retVal = false;

		if ($this->_mRow['is_default_server'] == 't') {
		    $_retVal = true;
		}

		return $_retVal;
	}

    /**
     * Set as the default server
     *
     * There can only be one default server, so this method will unset
     * is_default_server for all other servers
     *
     * @return bool True on success, false on failure
     */
	public function setAsDefaultServer()
	{
	    // Define globals
		global $db;

	    // Init values
		$_retVal = false;

		if (!$this->isDefaultServer()) {
			$_sql  = "UPDATE servers SET is_default_server = FALSE;\n";
			$_sql .= "UPDATE servers SET is_default_server = TRUE WHERE server_id = '{$this->getId()}';\n";
			$_retVal = $db->execSqlUpdate($_sql, false);
			$this->refresh();
		}

		return $_retVal;
	}

    /**
     * Does the server serve SSL encryption?
     *
     * @return bool True or false
     */
	public function isSSLAvailable()
	{
	    // Init values
	    $_retVal = false;

	    if ($this->_mRow['ssl_available'] == 't') {
	        $_retVal = true;
	    }

		return $_retVal;
	}

    /**
     * Set if the server serves SSL encryption
     *
     * @param bool $value The new value if the server serves SSL encryption
     *
     * @return bool True on success, false on failure
     */
	public function setSSLAvailable($value)
	{
	    // Define globals
		global $db;

	    // Init values
		$_retVal = false;

		if ($value != $this->isSSLAvailable()) {
		    if ($value) {
		        $value = "TRUE";
		    } else {
		        $value = "FALSE";
		    }

			$_retVal = $db->execSqlUpdate("UPDATE servers SET ssl_available = {$value} WHERE server_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
	}

    /**
     * Retreives the servers's Google maps API key
     *
     * @return string Google maps API key of server
     */
	public function getGoogleAPIKey()
	{
		return $this->_mRow['gmaps_api_key'];
	}

    /**
     * Sets the servers's Google maps API key
     *
     * @param string $value The new Google maps API key of server
     *
     * @return bool True on success, false on failure
     */
	public function setGoogleAPIKey($value)
	{
	    // Define globals
		global $db;

		// Init values
		$_retVal = true;

		if ($value != $this->getGoogleAPIKey()) {
			$value = $db->escapeString($value);
			$_retVal = $db->execSqlUpdate("UPDATE servers SET gmaps_api_key = '{$value}' WHERE server_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
	}

    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
	public function getAdminUI()
	{
	    // Init values
		$_html = '';

		$_html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
		$_html .= "<legend>"._("Server management")."</legend>\n";
        $_html .= "<ul class='admin_element_list'>\n";
		// server_id
		$_value = htmlspecialchars($this->getId(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Server ID") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= $_value;
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// creation_date
		$_name = "server_" . $this->getId() . "_creation_date";
		$_value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Creation date") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// name
		$_name = "server_" . $this->getId() . "_name";
		$_value = htmlspecialchars($this->getName(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Server name") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// hostname
		$_name = "server_" . $this->getId() . "_hostname";
		$_value = htmlspecialchars($this->getHostname(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Hostname") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		//  is_default_server
		$_name = "server_" . $this->getId() . "_is_default_server";

		if ($this->isDefaultServer()) {
		    $_checked = "checked='checked'";
		} else {
		    $_checked = "";
		}

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Is this server the default server?") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='checkbox' name='$_name' $_checked>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		//  ssl_available
		$_name = "server_" . $this->getId() . "_ssl_available";

		if ($this->isSSLAvailable()) {
		    $_checked = "checked='checked'";
		} else {
		    $_checked = "";
		}

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Use SSL on this server?") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='checkbox' name='$_name' $_checked>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// gmaps_api_key
		if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
    		$_name = "server_" . $this->getId() . "_gmaps_api_key";
    		$_value = htmlspecialchars($this->getGoogleAPIKey(), ENT_QUOTES);

    		$_html .= "<li class='admin_element_item_container'>\n";
    		$_html .= "<div class='admin_element_label'>" . _("Google public API key") . ":</div>\n";
    		$_html .= "<div class='admin_element_data'>\n";
    		$_html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
    		$_html .= "</div>\n";
    		$_html .= "</li>\n";
		}
        $_html .= "</ul>\n";
        $_html .= "</fieldset>\n";
		return $_html;
	}

    /**
     * Process admin interface of this object
     *
     * @return void
     */
	public function processAdminUI()
	{
        require_once('classes/User.php');

        try {
    		if (!User::getCurrentUser()->isSuperAdmin()) {
    			throw new Exception(_('Access denied!'));
    		}
        } catch (Exception $e) {
            $ui = new MainUI();
            $ui->setToolSection('ADMIN');
            $ui->displayError($e->getMessage(), false);
            exit;
        }

		// creation_date
		$_name = "server_" . $this->getId() . "_creation_date";
		$this->setCreationDate($_REQUEST[$_name]);

		// name
		$_name = "server_" . $this->getId() . "_name";
		$this->setName($_REQUEST[$_name]);

		// hostname
		$_name = "server_" . $this->getId() . "_hostname";
		$this->setHostname($_REQUEST[$_name]);

		//  is_default_server
		$_name = "server_" . $this->getId() . "_is_default_server";
		if (!empty($_REQUEST[$_name]) && $_REQUEST[$_name] == 'on') {
			$this->setAsDefaultServer();
		}

		//  ssl_available
		$_name = "server_" . $this->getId() . "_ssl_available";
		if (!empty($_REQUEST[$_name]) && $_REQUEST[$_name] == 'on') {
			$this->setSSLAvailable(true);
		} else {
			$this->setSSLAvailable(false);
		}

		// gmaps_api_key
		if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
        	$_name = "server_" . $this->getId() . "_gmaps_api_key";
        	$this->setGoogleAPIKey($_REQUEST[$_name]);
		}
	}

    /**
     * Delete this Object form the it's storage mechanism
     *
     * @param string &$errmsg Returns an explanation of the error on failure
     *
     * @return bool True on success, false on failure or access denied
     */
	public function delete(&$errmsg)
	{
	    require_once('classes/User.php');
        // Define globals
		global $db;

	    // Init values
		$_retVal = false;

		if (!User::getCurrentUser()->isSuperAdmin()) {
			$errmsg = _('Access denied (must have super admin access)');
		} else {
			if ($this->isDefaultServer() === true) {
				$errmsg = _('Cannot delete default server, create another one and select it before removing this one.');
			} else {
				$_id = $db->escapeString($this->getId());

				if (!$db->execSqlUpdate("DELETE FROM servers WHERE server_id='{$_id}'", false)) {
					$errmsg = _('Could not delete server!');
				} else {
					$_retVal = true;
				}
			}
		}

		return $_retVal;
	}

    /**
     * Reloads the object from the database
     *
     * Should normally be called after a set operation
     *
     * @return void     */
	protected function refresh()
	{
		$this->__construct($this->_id);
	}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
