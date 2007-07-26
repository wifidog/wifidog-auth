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
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/GenericObject.php');
require_once('classes/Security.php');
define('SERVER_ID', 'SERVER_ID');
/**
 * Administration interface for configuring Server wide settings for the server WiFiDog is running on
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class Server extends GenericDataObject
{
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();

    /**
     * Constructor
     *
     * @param string $p_server_id Id of the server

     */
    private function __construct($p_server_id)
    {
         
        $db = AbstractDb::getObject();

        // Init values
        $row = null;

        $serverId = $db->escapeString($p_server_id);
        $sql = "SELECT * FROM server WHERE server_id='$serverId'";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row == null) {
            throw new Exception("The server with id $serverId could not be found in the database!");
        }

        $this->_row = $row;

        $this->_id = $db->escapeString($row['server_id']);
    }

    public function __toString() {
        return _("Main server object");
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
     * Get the server object (there is only one)
     *
     * @return object A VirtualHost object, NEVER returns null
     *
     * @static
     * @access public
     */
    public static function &getServer()
    {
        return self::getObject(SERVER_ID);
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

    /** Get an interface to create a new object.
     * @return html markup
     */
    public static function getCreateNewObjectUI()
    {
        throw new Exception ("Unsupported method");
    }

    /** Process the new object interface.
     *  Will       return the new object if the user has the credentials
     * necessary (Else an exception is thrown) and and the form was fully
     * filled (Else the object returns null).
     * @return the node object or null if no new node was created.
     */
    static function processCreateNewObjectUI()
    {
        throw new Exception ("Unsupported method");
    }



    /**
     * Set as the default server
     *
     * @param VirtualHost $vhost
     *
     * @return bool True on success, false on failure
     */
    public function setDefaultVirtualHost(VirtualHost $vhost)
    {
         
        $db = AbstractDb::getObject();
        $vhostIdStr = $db->escapeString($vhost->getId());
        // Init values
        $_retVal = false;

        if ($vhostIdStr != VirtualHost::getDefaultVirtualHost()->getId()) {
            $sql  = "UPDATE server SET default_virtual_host = '$vhostIdStr';\n";
            $_retVal = $db->execSqlUpdate($sql, false);
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
        return $this->_row['creation_date'];
    }

    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getAdminUI()
    {
        Security::requirePermission(Permission::P('SERVER_PERM_EDIT_SERVER_CONFIG'), $this);
        // Init values
        $db = AbstractDb::getObject();
        $html = '';

        $html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
        $html .= "<legend>"._("Server management")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";
        // server_id
        /*$value = htmlspecialchars($this->getId(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Server ID") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $value;
        $html .= "</div>\n";
        $html .= "</li>\n";*/

        // creation_date
        $name = "server_" . $this->getId() . "_creation_date";
        $value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Creation date") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $value."\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        //timezone check

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Timezone check:  The following must be in the same timezone") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<p>";
        $db->execSqlUniqueRes("SHOW timezone", $row, false);
        $html .= " ".sprintf(_("Timezone from postgresql: %s"), $row['TimeZone'])."</p>"; // Version < 5.0.0
        $date_default_timezone_get = 'date_default_timezone_get';
        is_callable($date_default_timezone_get)?$phpTimezone = date_default_timezone_get():$phpTimezone = "Requires PHP 5.1 to tell";
        $html .= " ".sprintf(_("Timezone from PHP: %s"), $phpTimezone)."</p>"; // Version < 5.0.0
         
        $html .= "</div>\n";
        $html .= "</li>\n";



        /*
         * Access rights
         */
        if (true) {
            require_once('classes/Stakeholder.php');
            $html_access_rights = Stakeholder::getAssignStakeholdersUI($this);
            $html .= InterfaceElements::generateAdminSectionContainer("access_rights", _("Access rights"), $html_access_rights);
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
        Security::requirePermission(Permission::P('SERVER_PERM_EDIT_SERVER_CONFIG'), $this);
        // Access rights
        require_once('classes/Stakeholder.php');
        Stakeholder::processAssignStakeholdersUI($this, $errMsg);
        if(!empty($errMsg)) {
            echo $errMsg;
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
        $errmsg = _('The server can never be deleted!');
        return false;
    }
    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $server = self::getServer();
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_SERVER_CONFIG'), $server))
        {
            $items[] = array('path' => 'server/admin',
            'title' => _("Server access control"),
            'url' => BASE_URL_PATH."admin/generic_object_admin.php?object_class=Server&action=edit&object_id=".SERVER_ID.""
		);
        }
        $items[] = array('path' => 'server',
        'title' => _('Server administration'),
        'type' => MENU_ITEM_GROUPING);

        return $items;
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
