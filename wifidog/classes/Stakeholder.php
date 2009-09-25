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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/User.php');
require_once('classes/Role.php');

/**
 * This class represent the different stakeholder types for permissions.
 * The stakeholder id is actually the table name storing the object to which the role applies
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class Stakeholder
{
    /**
     * Retrieves the interface to assign stakeholders to objects
     *
     * @return string The HTML fragment for this interface
     *
     * @param $targetObject The Object on which the permssion applies (Network, Server, etc.)
     */
    static public function getAssignStakeholdersUI($targetObject)
    {
        require_once('classes/Role.php');
        if (User::getCurrentUser()->DEPRECATEDisSuperAdmin()) {
            $listData = "";
            $db = AbstractDb::getObject();
            $object_class = get_class($targetObject);
            $table = strtolower($object_class).'_stakeholders';
            $object_id = $db->escapeString($targetObject->getId());
            $sql = "SELECT * FROM $table JOIN roles USING (role_id) WHERE object_id = '$object_id';";
            $stakeholder_rows = null;
            $db->execSql($sql, $stakeholder_rows, false);
            if($stakeholder_rows) {
                foreach ($stakeholder_rows as $stakeholder_row) {
                    $user = User::getObject($stakeholder_row['user_id']);
                    $role = Role::getObject($stakeholder_row['role_id']);
                    $roleStr = htmlspecialchars($role->getLabelStr());
                    $name = $object_id . "_stakeholder_" . $stakeholder_row['user_id'] . "_". $stakeholder_row['role_id'] . "_remove";
                    $listDataContents = InterfaceElements::generateAdminSection("", $user->getListUI() . ' '. $roleStr, InterfaceElements::generateInputSubmit($name, _("Remove stakeholder")));
                    $listData .= "<li class='admin_element_item_container node_owner_list'>".$listDataContents."</li>\n";
                }
            }
            $listData .= "<li class='admin_element_item_container'>";
            $listData .= Role::getSelectUI($object_id . "_new_stakeholder_role", Array('stakeholderTypeId' => $object_class));
            $listData .= User::getSelectUserUI($object_id . "_new_stakeholder", $object_id . "_new_stakeholder_submit", _("Add stakeholder"));
            $listData .= "<br class='clearbr' /></li>\n";

            $html = "<ul id='node_owner_ul' class='admin_element_list'>\n".$listData."</ul>\n";
        }
        return $html;
    }

    /**
     * Process the interface to assign stakeholders to objects
     * @param &$errMsg An error message will be appended to this is the username is not empty, but the user doesn't exist.
     *
     * @return null
     *
     * @param $targetObject The Object on which the permssion applies (Network, Server, etc.)
     */
    static public function add($user=null, Role $role, $targetObject)
    {
        $db = AbstractDb::getObject();
        $object_id = $db->escapeString($targetObject->getId());
        $object_class = get_class($targetObject);
        $table = strtolower($object_class).'_stakeholders';
        if(!$user) {
            $user = User::getCurrentUser();
        }
         
        if(Security::hasRole($role, $targetObject, $user)){
            throw new Exception(_("User %s already has role %s for this object"), $user->getUsername(), $role->getId());
        }
        else {// the user doesn't already have that role
            $sql = "INSERT INTO $table (object_id, user_id, role_id) VALUES ('$object_id', '{$user->getId()}', '{$role->getId()}');";
            $db->execSqlUpdate($sql, false);
        }
    }
    /**
     * Process the interface to assign stakeholders to objects
     * @param &$errMsg An error message will be appended to this is the username is not empty, but the user doesn't exist.
     *
     * @return null
     *
     * @param $targetObject The Object on which the permssion applies (Network, Server, etc.)
     */
    static public function processAssignStakeholdersUI($targetObject, &$errMsg)
    {
        $db = AbstractDb::getObject();
        $object_id = $db->escapeString($targetObject->getId());
        $object_class = get_class($targetObject);
        $table = strtolower($object_class).'_stakeholders';
        $user = User::processSelectUserUI($object_id . "_new_stakeholder", $errMsg);
        $role = Role::processSelectAvailableRoleUI($object_id . "_new_stakeholder_role");
        if ($user && $role) {
            //The user and role exist
            if(Security::hasRole($role, $targetObject, $user)){
                $errMsg .= sprintf(_("User %s already has role %s for this object"), $user->getUsername(), $role->getId());
            }
            else {// the user doesn't already have that role
                $sql = "INSERT INTO $table (object_id, user_id, role_id) VALUES ('$object_id', '{$user->getId()}', '{$role->getId()}');";
                $stakeholder_rows = null;
                $db->execSqlUpdate($sql, false);

            }
        }
        $stakeholder_rows = null;
        $sql = "SELECT * FROM $table JOIN roles USING (role_id) WHERE object_id = '$object_id';";
        $db->execSql($sql, $stakeholder_rows, false);
        if($stakeholder_rows) {
            foreach ($stakeholder_rows as $stakeholder_row) {
                $user = User::getObject($stakeholder_row['user_id']);
                $name = $object_id . "_stakeholder_" . $stakeholder_row['user_id'] . "_". $stakeholder_row['role_id'] . "_remove";
                if(!empty($_REQUEST[$name])) {
                    $userIdStr=$db->escapeString($stakeholder_row['user_id']);
                    $roleIdStr=$db->escapeString($stakeholder_row['role_id']);
                    $sql = "DELETE FROM $table WHERE object_id='$object_id' AND user_id='$userIdStr' AND role_id = '$roleIdStr';";
                    $db->execSqlUpdate($sql, false);
                }
            }
        }

        return null;
    }


}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */