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
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('classes/StakeholderType.php');

/**
 * This class represent the different permissions one can use in the system
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class Permission extends GenericDataObject
{
    /** Never use this directly, always use &getPermissionArray() */
    private static $_permissionArray;
    private $_permissionId;
    private $_permissionInfo;
    private static $instanceArray = array();
    /** Get's (and setup when called for the first time) the array of permissions */
    static private function &getPermissionArray()
    {
        if(empty(self::$_permissionArray)) {
            /*
             * $PERMISSIONS['PERMISSION_ID'] = array(
             * _("permission description"), //The description of the permission
             * Stakeholder::Node, //The permission type
             * // Wether or not all existing roles of that type should have that permission granted.
             * //This is only used when syncing the permissions. It is NOT if logically
             * //all roles should have that permission by default.
             * //It is meant to maintain prior functionnality of a server untill the
             * // administrators can review the different roles.
             * // In general, when adding new permission types to restrict existing functionnality
             * // this should be set to true
             * // When adding brand new functionnality, this should be set to whatever is logical
             * // as default behaviour.
             * true
             * );
             */
            $PERMISSIONS['NETWORK_PERM_VIEW_ONLINE_USERS'] = array(_("User is allowed to view online users troughout the network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_EDIT_ANY_USER'] = array(_("User is allowed to edit any user for this network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_EDIT_NETWORK_CONFIG'] = array(_("User is allowed to edit the configuration of this network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_DELETE_NETWORK'] = array(_("User is allowed to delete this network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_VIEW_STATISTICS'] = array(_("User is allowed to view all statistics for this network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_EDIT_ANY_NODE_CONFIG'] = array(_("User is allowed to edit any configuration of any node on the network"), StakeholderType::Network, true);
            $PERMISSIONS['NETWORK_PERM_ADD_NODE'] = array(_("User is allowed to create a new Node on this network"), StakeholderType::Network, true);
            
            $PERMISSIONS['SERVER_PERM_EDIT_ROLES'] = array(_("User is allowed to edit user role definitions"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_EDIT_ANY_VIRTUAL_HOST'] = array(_("User is allowed to edit any virtual host definition"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_EDIT_SERVER_CONFIG'] = array(_("User is allowed to edit general server configuration"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_EDIT_PROFILE_TEMPLATES'] = array(_("User is allowed to edit the profile templates"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_EDIT_CONTENT_TYPE_FILTERS'] = array(_("User is allowed to edit the content type filters on the network"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_EDIT_CONTENT_LIBRARY'] = array(_("User is allowed to create reusable content"), StakeholderType::Server, true);
            $PERMISSIONS['SERVER_PERM_ADD_NEW_NETWORK'] = array(_("User is allowed to create a new Network on this server"), StakeholderType::Server, true);
            
            $PERMISSIONS['NODE_PERM_EDIT_GATEWAY_ID'] = array(_("User is allowed to change the gateway id of this node"), StakeholderType::Node, false);
            $PERMISSIONS['NODE_PERM_EDIT_NAME'] = array(_("User is allowed to change the public name of this node"), StakeholderType::Node, false);
            $PERMISSIONS['NODE_PERM_EDIT_DEPLOYMENT_DATE'] = array(_("User is allowed to change the deployment date of this node"), StakeholderType::Node, false);
            
            $PERMISSIONS['NODE_PERM_EDIT_CONFIG'] = array(_("TEMPORARY:  User is allowed to edit general configuration for this node.  This will be replaced with more granular permissions in the future"), StakeholderType::Node, false);

            self::$_permissionArray = $PERMISSIONS;
        }
        else {
            $PERMISSIONS = self::$_permissionArray;
        }
        return $PERMISSIONS;
    }

    /** Get an array of all permissions
     *  @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     *
     * This method understands:
     *	$userData['stakeholderTypeId'] Limit to roles applicable to this stakeholder type
     * @return array of Permission objects
     * */
    static public function &getPermissions($userData = null)
    {
        !empty($userData['stakeholderTypeId'])?$stakeholderTypeId = $userData['stakeholderTypeId']:$stakeholderTypeId=null;

        $permissionArray = self::getPermissionArray();
        $retval = array();
        foreach($permissionArray as $permId => $permData) {
            $retval[] = self::getObject($permId);
        }
        return $retval;
    }

    /** Syncs the permissions defined in the permissions array with the database.  This allows adding (and removing)
     * permissions without requiring a schema update
     * @return null
     */
    static function syncPermissions()
    {
        $db=AbstractDb::getObject();
        $permissionArray = self::getPermissionArray();
        $sql = "SELECT * FROM permissions";
        $db->execSql($sql, $permission_rows, false);
        $sql = null;
        if($permission_rows) {
            foreach($permission_rows as $row) {
                if(empty($permissionArray[$row['permission_id']])) {
                    //echo "Must delete {$row['permission_id']}";
                    $permissionIdStr = $db->escapeString($row['permission_id']);
                    $sql .= "DELETE FROM permissions WHERE permission_id='$permissionIdStr';\n";

                } else {
                    $permissionArray[$row['permission_id']]['is_in_db'] = true;
                }
            }
        }
        foreach($permissionArray as $permissionId => $permissionData) {
            if(empty($permissionData['is_in_db'])) {
                //echo "Must add {$permissionId}<br/>\n";
                $permissionIdStr = $db->escapeString($permissionId);
                $stakeholderTypeStr = $db->escapeString($permissionData[1]);
                $sql .= "INSERT INTO permissions (permission_id, stakeholder_type_id) VALUES ('$permissionIdStr', '$stakeholderTypeStr');\n";
                if($permissionData[2]) {
                    //echo "Must add {$permissionId} to all stakeholders of type {$permissionData[1]}<br/>\n";
                    $sql .= "INSERT INTO role_has_permissions (role_id, permission_id) (SELECT role_id, '$permissionIdStr' FROM roles WHERE stakeholder_type_id='$stakeholderTypeStr');\n";

                }
            }
        }
        if($sql) {
            $db->execSqlUpdate("BEGIN;\n{$sql}COMMIT;", true);
        }

    }



    /** Instantiate a user object
     * @param $id The id of the requested permission
     * @return a Permission object, or null if there was an error
     */
    public static function &getObject($id) {
        if(!isset(self::$instanceArray[$id]))
        {
            self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
    }
    /** Shorthand for getObject
     * @param $id The id of the requested permission
     * @return a Permission object, or null if there was an error
     */
    public static function &P($id) {
        return self::getObject($id);
    }

    private function __construct($id)
    {
        $permissionArray = self::getPermissionArray();
        $db = AbstractDb::getObject();
        if (empty($permissionArray[$id])) {
            self::syncPermissions();
            if (empty($permissionArray[$id])) {
                throw new Exception(sprintf("Permission %s does not exist!", $id));
            }
        }
        $idStr=$db->escapeString($id);
        $sql = "SELECT * FROM permissions WHERE permission_id='$idStr'";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if($row == null) {
            self::syncPermissions();
            $db->execSqlUniqueRes($sql, $row, false);
            if($row == null) {
                throw new Exception(sprintf("Permission %s does not exist, even after synching!", $id));
            }
        }

        $this->_id = $id;
        $this->_permissionInfo = $permissionArray[$id];
    }

    /** @return A string describing the permission */
    public function getDescription()
    {
        return $this->_permissionInfo[0];
    }

    /** @return A string, the classname the objects this permission applies to */
    public function getTargetObjectClass()
    {
        return $this->_permissionInfo[1];
    }

    /** Get a html representation a list. */
    function getListUI() {
        $html = null;
        $title = htmlspecialchars($this->getDescription());
        $html .= "<a href=\"#\" title=\"$title\">".$this->getId()."</a>\n";
        return $html;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

