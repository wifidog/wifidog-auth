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
require_once('classes/Session.php');
require_once('classes/User.php');
require_once('classes/Permission.php');
/**
 * This class represent the different user roles (groups of permisions) one can have towards a specific object
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class Role extends GenericDataObject
{
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();
    private $id;

    private $_row;
    /**
     * Get an instance of the object
     *
     * @param string $id The object id
     *
     * @return mixed The Content object, or null if there was an error
     *               (an exception is also thrown)
     *
     * @see GenericObject
     * @static
     * @access public
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
     * Constructor
     *
     * @param string $p_network_id
     *
     * @return void
     *
     * @access private
     */
    private function __construct($roleId)
    {
        $db = AbstractDb::getObject();
        $roleIdStr = $db->escapeString($roleId);
        $sql = "SELECT * from roles WHERE role_id='$roleIdStr'";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            throw new Exception("The role with id $roleIdStr could not be found in the database");
        }
        $this->_row = $row;
        $this->id = $db->escapeString($row['role_id']);
    }

    /**
     * Retreives the id of the object
     *
     * @return string The id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get an interface to pick an object of this class
     *
     * If there is only one server available, no interface is actually shown
     *
     * @param string $user_prefix         A identifier provided by the
     *                                    programmer to recognise it's generated
     *                                    html form
     *  @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     *
     * This method understands:
     *  $userData['preSelectedId'] An optional ProfileTemplate object id.
     *	$userData['stakeholderTypeId'] Limit to roles applicable to this stakeholder type
     * @return string HTML markup

     */
    public static function getSelectUI($user_prefix, $userData = null)
    {
        $html = '';
        $db = AbstractDb::getObject();
        $html .= "<div class='role_select_role_ui_container'>\n";
        $name = $user_prefix;

        !empty($userData['preSelectedId'])?$selected_id=$userData['preSelectedId']:$selected_id=null;
        !empty($userData['stakeholderTypeId'])?$targetObjectClassSql = " AND stakeholder_type_id = '".$db->escapeString($userData['stakeholderTypeId'])."' ":$targetObjectClassSql=null;

        $sql = "SELECT role_id, stakeholder_type_id FROM roles WHERE 1=1 $targetObjectClassSql ORDER BY stakeholder_type_id, role_id";
        $role_rows = null;
        $db->execSql($sql, $role_rows, false);
        if ($role_rows == null) {
            $html .= sprintf(_("Sorry: No available roles in the database for stakeholder type: %s!"),$userData['stakeholderTypeId']);
        }
        else {
            if (count($role_rows) > 1) {
                $i = 0;
                foreach ($role_rows as $role_row) {
                    $tab[$i][0] = $role_row['role_id'];
                    empty($userData['stakeholderTypeId'])?$tab[$i][1]=$role_row['stakeholder_type_id'].'; ':$tab[$i][1]=null;
                    $tab[$i][1] .= $role_row['role_id'];
                    $i ++;
                }
                $html .= _("Role:")." \n";
                $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, false);

            } else {
                foreach ($role_rows as $role_row) //iterates only once...
                {
                    $html .= _("Role:")." \n";
                    $html .= " {$role_row['role_id']} ";
                    $html .= "<input type='hidden' name='$name' value='".htmlspecialchars($role_row['role_id'], ENT_QUOTES, 'UTF-8')."'>";
                }
            }
        }
        $html .= "</div'>\n";
        return $html;
    }


    /**
     * Get the selected Role object.
     *
     * @param string $user_prefix A identifier provided by the programmer to
     *                            recognise it's generated form
     *
     * @return mixed The Role object or null

     */
    public static function processSelectAvailableRoleUI($user_prefix)
    {
        $name = "{$user_prefix}";

        if (!empty ($_REQUEST[$name])) {
            return self::getObject($_REQUEST[$name]);
        } else {
            return null;
        }
    }
    /* Create a new ContentTypeFilter object in the database
     *
     * @param string $id The id of the new object. If absent,
     * will be assigned a guid.
     *
     * @return mixed The newly created object, or null if there was an error
     */
    public static function createNewObject(StakeholderType $stakeholderType, $id = null)
    {
        $db = AbstractDb::getObject();

        if (empty($id)) {
            $id = get_guid();
        }
        $idStr = $db->escapeString($id);
        $stakeholderTypeIdStr = $db->escapeString($stakeholderType->getId());
        $sql = "INSERT INTO roles (role_id, stakeholder_type_id) VALUES ('$idStr', '$stakeholderTypeIdStr')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new ContentTypeFilter in the database!'));
        }
        return self::getObject($id);
    }
    /* Get an interface to create a new Object
     *
     * @return string HTML markup

     */
    public static function getCreateNewObjectUI()
    {
        // Init values
        $html = '';




        $name = "new_role_stakeholder_type";
        $stakeholderTypeSelect = StakeholderType::getSelectUI($name);
        $name = "new_role_id";
        $idInput = "<input type='text' name='{$name}'/><br/>";
        $html .= sprintf(_("Add a new role of type %s with id %s"),$stakeholderTypeSelect,$idInput);
        return $html;
    }


    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return string The ContentTypeFilter object or null if no new ContentTypeFilter was created

     */
    public static function processCreateNewObjectUI()
    {
        // Init values
        $_retVal = null;

        $name = "new_role_id";
        if (!empty($_REQUEST[$name])) {
            $name = "new_role_stakeholder_type";
            $stakeholderType = StakeholderType::processSelectUI($name);
            $name = "new_role_id";
            $_retVal = self::createNewObject($stakeholderType, $_REQUEST[$name]);
        }
        return $_retVal;
    }
    /**
     * Is this role a system role (a role that exists on every wifidog install)
     *
     * @return true or false
     */
    public function isSystemRole()
    {
        $this->_row['stakeholder_type_id']=='t'?$retval=true:$retval=false;
        return $retval;
    }
    /**
     * Retrieves the profile template's creation date
     *
     * @return string profile template's creation date
     *
     * @access public
     */
    public function getCreationDate()
    {
        return $this->_row['role_creation_date'];
    }

    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getAdminUI()
    {
        Security::requirePermission(Permission::P('SERVER_PERM_EDIT_ROLES'), Server::getServer());
        $db = AbstractDb::getObject();

        // Init values
        $html = '';

        $html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
        $html .= "<legend>"._("User roles management")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";

        //stakeholder_type
        $_value = htmlspecialchars($this->_row['stakeholder_type_id'], ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Stakeholder type") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $_value;
        $html .= "</div>\n";
        $html .= "</li>\n";

        //is_system_role
        if($this->isSystemRole()) {
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>" . _("This role is a system role") . ":</div>\n";
            $html .= "</li>\n";
        }
        // role_id
        $_value = htmlspecialchars($this->getId(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Role ID") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $_value;
        $html .= "</div>\n";
        $html .= "</li>\n";

        // role_description_content_id
        $criteria_array = array (
        array (
        'isSimpleContent'
                        )
        );
        $description_allowed_content_types = ContentTypeFilter :: getObject($criteria_array);

        $html .= "<li class='admin_element_item_container admin_section_edit_description'>\n";
        $html .= "<div class='admin_element_data'>\n";

        if (empty ($this->_row['role_description_content_id'])) {
            $name = "role_{$this->id}_description_new";
            $html .= Content :: getNewContentUI($name, $description_allowed_content_types, _("Description:"));
            $html .= "</div>\n";
        } else {
            $description = Content :: getObject($this->_row['role_description_content_id']);
            $html .= $description->getAdminUI(null, _("Description:"));
            $html .= "</div>\n";
            $html .= "<div class='admin_element_tools'>\n";
            $name = "role_{$this->id}_description_erase";
            $html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s (%s)"), _("description"), get_class($description)) . "'>";
            $html .= "</div>\n";
        }
        $html .= "</li>\n";


        // creation date
        $_value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Creation date") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $_value;
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "</ul>\n";

        // Permissions
        $permissionsArray=Permission::getPermissions(array('stakeholderTypeId'=>$this->_row['stakeholder_type_id']));
        $idStr = $db->escapeString($this->getId());
        $stakeholderTypeIdStr = $db->escapeString($this->_row['stakeholder_type_id']);
        $sql = "SELECT permissions.permission_id, stakeholder_type_id, role_id FROM permissions LEFT JOIN role_has_permissions  ON (role_has_permissions.permission_id = permissions.permission_id AND role_id = '$idStr') WHERE stakeholder_type_id='$stakeholderTypeIdStr'";
        $db->execSql($sql, $permission_rows, false);


        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Permissions") . ":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<ul class='admin_element_list'>\n";
        if($permission_rows) {
            foreach($permission_rows as $row) {
                $permission=Permission::getObject($row['permission_id']);
                $html .= "<li class='admin_element_item_container'>\n";
                $name = "role_{$this->id}_permission_".htmlspecialchars($row['permission_id'], ENT_QUOTES)."_included";
                !empty($row['role_id'])?$checked = 'CHECKED':$checked = '';
                $html .= "<input type='checkbox' name='$name' value='included' $checked/>";

                $html .= $permission->getListUI();
                $html .= "</li>\n";
            }
        }
        $html .= "</ul>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

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
        Security::requirePermission(Permission::P('SERVER_PERM_EDIT_ROLES'), Server::getServer());
        $db = AbstractDb::getObject();

        $errmsg = "";

        // role_id
        $value = htmlspecialchars($this->getId(), ENT_QUOTES);

        // role_description_content_id
        if (empty ($this->_row['role_description_content_id'])) {
            $name = "role_{$this->id}_description_new";
            $description = Content :: processNewContentUI($name);
            if ($description != null) {
                $description_id = $description->GetId();
                $db->execSqlUpdate("UPDATE roles SET role_description_content_id = '$description_id' WHERE role_id = '$this->id'", FALSE);
            }
        } else {
            $description = Content :: getObject($this->_row['role_description_content_id']);
            $name = "role_{$this->id}_description_erase";
            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                $db->execSqlUpdate("UPDATE roles SET role_description_content_id = NULL WHERE role_id = '$this->id'", FALSE);
                $description->delete($errmsg);
            } else {
                $description->processAdminUI();
            }
        }

        // Permissions
        $permissionsArray=Permission::getPermissions(array('stakeholderTypeId'=>$this->_row['stakeholder_type_id']));
        $idStr = $db->escapeString($this->getId());
        $stakeholderTypeIdStr = $db->escapeString($this->_row['stakeholder_type_id']);
        $sql = "SELECT permissions.permission_id, stakeholder_type_id, role_id FROM permissions LEFT JOIN role_has_permissions  ON (role_has_permissions.permission_id = permissions.permission_id AND role_id = '$idStr') WHERE stakeholder_type_id='$stakeholderTypeIdStr'";
        $db->execSql($sql, $permission_rows, false);
        $sql=null;
        if($permission_rows) {
            foreach($permission_rows as $row) {
                $permissionIdStr = $db->escapeString($row['permission_id']);
                $name = "role_{$this->id}_permission_".htmlspecialchars($row['permission_id'], ENT_QUOTES)."_included";
                if(empty($row['role_id']) && !empty($_REQUEST[$name]) && $_REQUEST[$name]=='included') {
                    $sql = "INSERT INTO role_has_permissions (permission_id, role_id) VALUES ('$permissionIdStr','$idStr');\n";

                }
                else if (!empty($row['role_id']) && empty($_REQUEST[$name])) {
                    $sql = "DELETE FROM role_has_permissions WHERE permission_id='$permissionIdStr' AND role_id='$idStr';\n";

                }
                else {
                    //echo "Do nothing for {$row['permission_id']}<br/>";
                }
            }
        }
        if($sql) {
            $db->execSqlUpdate("BEGIN;\n{$sql}COMMIT;", false);
        }
        $this->refresh();
    }
    /**
     * Reloads the object from the database
     *
     * Should normally be called after a set operation
     *
     * @return void     */
    protected function refresh()
    {
        $this->__construct($this->getId());
    }
    public function delete(& $errmsg)
    {
                
        $retval = false;
        if (Security::hasPermission('SERVER_PERM_EDIT_ROLES', Server::getServer())) {
            $db = AbstractDb::getObject();
            $id = $db->escapeString($this->getId());
            if (!$db->execSqlUpdate("DELETE FROM roles WHERE role_id='{$id}'", false))
            {
                $errmsg = _('Could not delete object!');
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

    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $server = Server::getServer();
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_ROLES'), $server))
        {
            $items[] = array('path' => 'server/roles',
            'title' => _("User roles"),
            'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=Role&action=list")
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