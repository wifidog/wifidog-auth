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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/GenericObject.php');
require_once('classes/Server.php');

/**
 * Administration interface for defining all Virtual hosts on the server running WiFiDog
 *
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class VirtualHost implements GenericObject
{
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();
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
    private $__row;

    /**
     * Constructor
     *
     * @param string $p_server_id Id of the vhost
     *
     * @access private
     */
    private function __construct($p_server_id)
    {
         
        $db = AbstractDb::getObject();

        // Init values
        $_row = null;

        $_serverId = $db->escapeString($p_server_id);
        $sql = "SELECT * FROM virtual_hosts WHERE virtual_host_id='$_serverId'";
        $db->execSqlUniqueRes($sql, $_row, false);

        if ($_row == null) {
            throw new Exception("The virtual host with id $_serverId could not be found in the database!");
        }

        $this->__row = $_row;

        $this->_id = $db->escapeString($_row['virtual_host_id']);
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
    public static function &getObject($id)
    {
        if(!isset(self::$instanceArray[$id]))
        {
            self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
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
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = array ();

        $sql = "SELECT server_id FROM servers ORDER BY is_default_server DESC";
        $serverRows = null;

        $db->execSql($sql, $serverRows, false);

        if ($serverRows == null) {
            throw new Exception(_("Server::getAllServers: Fatal error: No servers in the database!"));
        }

        foreach ($serverRows as $serverRow) {
            $retVal[] = self::getObject($serverRow['server_id']);
        }

        return $retVal;
    }


    /**
     * Get the default server
     *
     * @return object A VirtualHost object, NEVER returns null
     *
     * @static
     * @access public
     */
    public static function &getDefaultVirtualHost()
    {
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = null;
        $serverRow = null;

        $sql = "SELECT default_virtual_host FROM server ORDER BY creation_date LIMIT 1";
        $db->execSqlUniqueRes($sql, $serverRow, false);

        if ($serverRow == null) {
            throw new Exception("Server::getDefaultVirtualHost: Fatal error: Unable to find the default virtual host!");
        }

        $retVal = VirtualHost::getObject($serverRow['default_virtual_host']);
        return $retVal;
    }

    /**
     * Get the current virtual for which the portal is displayed or to which a
     * user is physically connected.
     *
     * @param bool $silent If true, no exception will be thrown
     *
     * @return mixed A server object, returns null if server hasn't been found
     *               in the database
     */
    public static function getCurrentVirtualHost()
    {
        if(empty($_SERVER['SERVER_NAME']))
        {
            return null; //We were probably called from the command line
        }
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = null;
        $serverRow = null;

        $sql = "SELECT virtual_host_id FROM virtual_hosts WHERE hostname='{$_SERVER['SERVER_NAME']}'";
        $db->execSqlUniqueRes($sql, $serverRow, false);

        if ($serverRow == null) {
            return null;
        } else if ($serverRow != null) {
            $retVal = self::getObject($serverRow['virtual_host_id']);
        }
        return $retVal;
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
         
        $db = AbstractDb::getObject();

        if (empty($server_id)) {
            $server_id = get_guid();
        }

        $server_id = $db->escapeString($server_id);

        $sql = "INSERT INTO servers (server_id) VALUES ('$server_id')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new server in the database!'));
        }

        $_object = self::getObject($server_id);

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
    public static function getSelectUI($user_prefix, $pre_selected_server = null, $additional_where = null)
    {

        $db = AbstractDb::getObject();

        // Init values
        $html = "";
        $_serverRows = null;

        $name = $user_prefix;

        $html .= _("Virtual host:")." \n";

        if ($pre_selected_server) {
            $selectedId = $pre_selected_server->getId();
        } else {
            $selectedId = null;
        }

        $additional_where = $db->escapeString($additional_where);

        $sql = "SELECT virtual_host_id, hostname FROM virtual_hosts WHERE 1=1 $additional_where ORDER BY hostname";
        $db->execSql($sql, $_serverRows, false);

        if ($_serverRows == null) {
            throw new Exception("Server::getSelectServerUI: Fatal error: No virtual hosts in the database!");
        }

        $_numberOfServers = count($_serverRows);

        if ($_numberOfServers > 1) {
            $_i = 0;

            foreach ($_serverRows as $_serverRow) {
                $_tab[$_i][0] = $_serverRow['virtual_host_id'];
                $_tab[$_i][1] = $_serverRow['hostname'];
                $_i ++;
            }

            $html .= FormSelectGenerator::generateFromArray($_tab, $selectedId, $name, null, false);
        } else {
            foreach ($_serverRows as $_serverRow) {
                $html .= " $_serverRow[hostname] ";
                $html .= "<input type='hidden' name='$name' value='{$_serverRow['virtual_host_id']}'>";
            }
        }

        return $html;
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
    public static function processSelectUI($user_prefix)
    {
        // Init values
        $retVal = null;

        $name = "{$user_prefix}";

        if (!empty($_REQUEST[$name])) {
            $retVal = self::getObject($_REQUEST[$name]);
        }

        return $retVal;
    }

    /**
     * Get an interface to create a new server
     *
     * @return string HTML markup

     */
    public static function getCreateNewObjectUI()
    {
        // Init values
        $html = '';

        $name = "new_server_id";

        $html .= _("Add a new virtual host with ID") . ": ";
        $html .= "<input type='text' size='10' name='{$name}'>";

        return $html;
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
        $retVal = null;

        $name = "new_server_id";

        if (!empty($_REQUEST[$name])) {
            $_serverId = $_REQUEST[$name];

            if ($_serverId) {
                if (!User::getCurrentUser()->DEPRECATEDisSuperAdmin()) {
                    throw new Exception(_("Access denied"));
                }


                $retVal = self::createNewObject($_serverId);
            }
        }

        return $retVal;
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
        return $this->__row['creation_date'];
    }

    /**
     * Get this vhost's default network
     *
     * @return object A Network object, NEVER returns null.
     */
    public function &getDefaultNetwork()
    {
        return Network::getObject($this->__row['default_network']);
    }

    /**
     * Set this vhost's default network
     *
     * @return bool True on success, false on failure
     */
    public function &setDefaultNetwork(Network $network)
    {
        $db = AbstractDb::getObject();

        // Init values
        $retVal = true;
        if ($network != $this->getDefaultNetwork()) {
            $value = $db->escapeString($network->getId());
            $retVal = $db->execSqlUpdate("UPDATE virtual_hosts SET default_network = '{$value}' WHERE virtual_host_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }

    /**
     * Retreives the servers's hostname
     *
     * @return string Hostname of server
     */
    public function getHostname()
    {
        return $this->__row['hostname'];
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
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = true;

        if ($value != $this->getHostname()) {
            $value = $db->escapeString($value);
            $retVal = $db->execSqlUpdate("UPDATE virtual_hosts SET hostname = '{$value}' WHERE virtual_host_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retVal;
    }

    /**
     * Is this vhost the server's default?
     *
     * @return bool True or false
     */
    public function isDefaultVirtualHost()
    {
        // Init values
        $retVal = false;

        if (VirtualHost::getDefaultVirtualHost()->getId() == $this->getId()) {
            $retVal = true;
        }

        return $retVal;
    }

    /**
     * Does the server serve SSL encryption?
     *
     * @return bool True or false
     */
    public function isSSLAvailable()
    {
        // Init values
        $retVal = false;

        if ($this->__row['ssl_available'] == 't') {
            $retVal = true;
        }

        return $retVal;
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
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = false;

        if ($value != $this->isSSLAvailable()) {
            if ($value) {
                $value = "TRUE";
            } else {
                $value = "FALSE";
            }

            $retVal = $db->execSqlUpdate("UPDATE virtual_hosts SET ssl_available = {$value} WHERE virtual_host_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retVal;
    }

    /**
     * Retreives the servers's Google maps API key
     *
     * @return string Google maps API key of server
     */
    public function getGoogleAPIKey()
    {
        return $this->__row['gmaps_api_key'];
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
         
        $db = AbstractDb::getObject();

        // Init values
        $retVal = true;

        if ($value != $this->getGoogleAPIKey()) {
            $value = $db->escapeString($value);
            $retVal = $db->execSqlUpdate("UPDATE virtual_hosts SET gmaps_api_key = '{$value}' WHERE virtual_host_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retVal;
    }

    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getAdminUI()
    {
        Security::requirePermission(Permission::P('SERVER_PERM_EDIT_ANY_VIRTUAL_HOST'), Server::getServer());
        // Init values
        $html = '';

        $html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
        $html .= "<legend>"._("Virtual hosts management")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";

        // creation_date
        $name = "server_" . $this->getId() . "_creation_date";
        $_value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Creation date") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "$_value\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        // hostname
        $name = "server_" . $this->getId() . "_hostname";
        $_value = htmlspecialchars($this->getHostname(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Hostname") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<input type='text' size='50' value='$_value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        //  default_network
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Default network for this vhost") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "vhost_" . $this->getId() . "_default_network";
        $html .= Network::getSelectUI($name, array('preSelectedObject'=>$this->getDefaultNetwork()));
        $html .= "</div>\n";
        $html .= "</li>\n";

        //  is_default_server
        $name = "vhost_" . $this->getId() . "_is_default_vhost";

        if ($this->isDefaultVirtualHost()) {
            $_checked = "checked='checked'";
        } else {
            $_checked = "";
        }

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Make this Virtual Host the server's default?") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<input type='radio' name='$name' $_checked>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        //  ssl_available
        $name = "server_" . $this->getId() . "_ssl_available";

        if ($this->isSSLAvailable()) {
            $_checked = "checked='checked'";
        } else {
            $_checked = "";
        }

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Use SSL on this server?") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<input type='checkbox' name='$name' $_checked>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        // gmaps_api_key
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $name = "server_" . $this->getId() . "_gmaps_api_key";
            $_value = htmlspecialchars($this->getGoogleAPIKey(), ENT_QUOTES);

            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>" . _("Google public API key") . ":</div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= "<input type='text' size='50' value='$_value' name='$name'>\n";
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        $html .= "</ul>\n";
        $html .= "</fieldset>\n";
        return $html;
    }

    /**
     * Process admin interface of this object
     *
     * @return void
     */
    public function processAdminUI()
    {
        require_once('classes/User.php');

Security::requirePermission(Permission::P('SERVER_PERM_EDIT_ANY_VIRTUAL_HOST'), Server::getServer());
        // hostname
        $name = "server_" . $this->getId() . "_hostname";
        $this->setHostname($_REQUEST[$name]);

        //  default_network
        $name = "vhost_" . $this->getId() . "_default_network";
        $this->setDefaultNetwork(Network::processSelectUI($name));

        //  is_default_server
        $name = "vhost_" . $this->getId() . "_is_default_vhost";
        if (!empty($_REQUEST[$name]) && $_REQUEST[$name] == 'on') {
            $server=Server::getServer();
            $server->setDefaultVirtualHost($this);
        }

        //  ssl_available
        $name = "server_" . $this->getId() . "_ssl_available";
        if (!empty($_REQUEST[$name]) && $_REQUEST[$name] == 'on') {
            $this->setSSLAvailable(true);
        } else {
            $this->setSSLAvailable(false);
        }

        // gmaps_api_key
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $name = "server_" . $this->getId() . "_gmaps_api_key";
            $this->setGoogleAPIKey($_REQUEST[$name]);
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

        $db = AbstractDb::getObject();

        // Init values
        $retVal = false;

        if (!Security::hasPermission(Permission::P('SERVER_PERM_EDIT_ANY_VIRTUAL_HOST'), Server::getServer())) {
            $errmsg = _('Access denied');
        } else {
            if ($this->isDefaultVirtualHost() === true) {
                $errmsg = _('Cannot delete default virtual host, create another one and select it before removing this one.');
            } else {
                $_id = $db->escapeString($this->getId());

                if (!$db->execSqlUpdate("DELETE FROM virtual_host WHERE virtual_host_id='{$_id}'", false)) {
                    $errmsg = _('Could not delete server!');
                } else {
                    $retVal = true;
                }
            }
        }

        return $retVal;
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

    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $server = Server::getServer();
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_ANY_VIRTUAL_HOST'), $server))
        {
            $items[] = array('path' => 'server/virtual_host',
            'title' => _("Virtual Hosts"),
            'url' => BASE_URL_PATH."admin/generic_object_admin.php?object_class=VirtualHost&action=list"
		);
        }
        return $items;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
