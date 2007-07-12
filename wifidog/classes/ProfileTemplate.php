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
 * Defines a profile template
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     François Proulx <francois.proulx@gmail.com>
 * @copyright  2007 François Proulx
 * @link       http://www.wifidog.org/
 */

require_once ('classes/ProfileTemplateField.php');
require_once ('classes/ContentTypeFilter.php');

class ProfileTemplate implements GenericObject {
	/** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();
    
    private $id = null;
    private $_row;
    
    private function __construct($profile_template_id)
	{
		$db = AbstractDb::getObject();

		// Init values
		$row = null;

		$profile_template_id = $db->escapeString($profile_template_id);
		$sql = "SELECT * FROM profile_templates WHERE profile_template_id = '{$profile_template_id}';";
		$db->execSqlUniqueRes($sql, $row, false);

		if ($row == null) {
			throw new Exception("The profile template with id {$profile_template_id} could not be found in the database!");
		}

		$this->_row = $row;
		$this->id = $db->escapeString($row['profile_template_id']);
	}

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
     * Get all profile templates ( can be restricted to a network )
     * @param $network_id
     */
    public static function getAllProfileTemplates($network = null) {
    	$db = AbstractDb :: getObject();

    	// Init values
    	$whereClause = "";
    	$rows = null;
    	$objects = array ();

    	if (!empty ($network) && get_class($network) == "Network") {
    		$db->execSql("SELECT profile_template_id FROM network_has_profile_templates WHERE network_id = '{$network->getId()}'", $rows, false);
    	} else {
    		$db->execSql("SELECT profile_template_id FROM profile_templates", $rows, false);
    	}

    	if ($rows) {
    		foreach ($rows as $row) {
    			$objects[] = self :: getObject($row['profile_template_id']);
    		}
    	}

    	return $objects;
    }
    
    /**
     * Retreives the Id of the object
     *
     * @return string The Id
     */
	public function getId()
	{
		return $this->id;
	}
	
	/**
     * Retrieves the profile template's label
     *
     * @return string profile template's label
     *
     * @access public
     */
    public function getLabel()
    {
        return $this->_row['profile_template_label'];
    }

    /**
     * Set the profile template's label
     *
     * @param string $value The new label
     *
     * @return bool True on success, false on failure
     */
    public function setLabel($value)
    {
        
        $db = AbstractDb::getObject();

        // Init values
        $_retVal = true;

        if ($value != $this->getLabel()) {
            $value = $db->escapeString($value);
            $_retVal = $db->execSqlUpdate("UPDATE profile_templates SET profile_template_label = '{$value}' WHERE profile_template_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $_retVal;
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
        return $this->_row['creation_date'];
    }

    /**
     * Set the profile template's creation date
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     */
    public function setCreationDate($value)
    {
        
        $db = AbstractDb::getObject();

        // Init values
        $_retVal = true;

        if ($value != $this->getCreationDate()) {
            $value = $db->escapeString($value);
            $_retVal = $db->execSqlUpdate("UPDATE profile_templates SET creation_date = '{$value}' WHERE profile_template_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $_retVal;
    }
    
    /* Create a new ProfileTemplate object in the database
	 *
	 * @param string $profile_template_id The id of the new object. If absent,
	 * will be assigned a guid.
	 *
	 * @return mixed The newly created object, or null if there was an error
	 *
	 * @see GenericObject
	 *
	 * @static
	 * @access public
	 */
	public static function createNewObject($profile_template_id = null)
    {
        $db = AbstractDb::getObject();
        if (empty ($profile_template_id)) {
            $profile_template_id = get_guid();
        }
        $profile_template_id = $db->escapeString($profile_template_id);

        $sql = "INSERT INTO profile_templates (profile_template_id, creation_date) VALUES ('{$profile_template_id}', NOW())";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new profile template in the database!'));
        }
        $object = self::getObject($profile_template_id);
        return $object;
    }
	
	/* Get an interface to create a new ProfileTemplate
     * @return string HTML markup
     */
	public static function getCreateNewObjectUI() {}
	
	
    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return string The ProfileTemplate object or null if no new ProfileTemplate was created

     */
	public static function processCreateNewObjectUI() {}
	
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
     *	$userData['additionalWhere'] Additional SQL conditions for the
     *                                    objects to select
     *	$userData['typeInterface'] 'select' or 'add'.  'select' is the default
     * @return string HTML markup

     */
    public static function getSelectUI($user_prefix, $userData=null)
    { 
		$db = AbstractDb::getObject();

        // Init values
		$_html = "";
		$_profile_template_rows = null;
		
		!empty($userData['preSelectedId'])?$selectedId=$userData['preSelectedId']:$selectedId=null;
		!empty($userData['additionalWhere'])?$additional_where=$userData['additionalWhere']:$additional_where=null;
		!empty($userData['typeInterface'])?$type_interface=$userData['typeInterface']:$type_interface=null;
		
		$sql = "SELECT * FROM profile_templates WHERE 1=1 $additional_where ORDER BY profile_template_label ASC";
		$db->execSql($sql, $_profile_template_rows, false);

		if ($_profile_template_rows != null) {
			$_name = $user_prefix;

			$_html .= _("Profile template").": \n";
	
			$_i = 0;
			foreach ($_profile_template_rows as $_profile_template_row) {
				$_tab[$_i][0] = $_profile_template_row['profile_template_id'];
				$_tab[$_i][1] = empty($_profile_template_row['profile_template_label']) ? "["._("No label")."] - ".$_profile_template_row['profile_template_id'] : $_profile_template_row['profile_template_label'];
				$_i ++;
			}

			$_html .= FormSelectGenerator::generateFromArray($_tab, $selectedId, $_name, null, false);
			
			if ($type_interface == "add") {
    			if (isset ($_tab)) {
    				$name = "{$user_prefix}_add";
    				$value = _("Add");
    				$_html .= "<div class='admin_element_tools'>";
    				$_html .= '<input type="submit" class="submit" name="' . $name . '" value="' . $value . '">';
    				$_html .= "</div>";
    			}
    		}
		}
		
		return $_html;
	}
	
	
    /** Get the selected ProfileTemplate object.
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the ProfileTemplate object
     */
    static function processSelectProfileTemplateUI($user_prefix) {
    	$name = "{$user_prefix}";
    	if (!empty ($_REQUEST[$name]))
    		return ProfileTemplate :: getObject($_REQUEST[$name]);
    	else
    		return null;
    }
	
	/**
     * Get a flexible interface to manage a profile template linked to a node, a network
     * or anything else
     *
     * @param string $user_prefix            A identifier provided by the
     *                                       programmer to recognise it's
     *                                       generated HTML form
     * @param string $link_table             Table to link from
     * @param string $link_table_obj_key_col Column in linked table to match
     * @param string $link_table_obj_key     Key to be found in linked table
     * @param string $default_display_page
     * @param string $default_display_area
     * @return string HTML markup
    
     */
    public static function getLinkedProfileTemplateUI($user_prefix, $link_table, $link_table_obj_key_col, $link_table_obj_key) {

    	$db = AbstractDb :: getObject();

    	// Init values
    	$html = "";

    	$link_table = $db->escapeString($link_table);
    	$link_table_obj_key_col = $db->escapeString($link_table_obj_key_col);
    	$link_table_obj_key = $db->escapeString($link_table_obj_key);

    	/* Profile templates already linked */
    	$current_profile_templates_sql = "SELECT * FROM $link_table WHERE $link_table_obj_key_col = '$link_table_obj_key'";
    	$rows = null;
    	$db->execSql($current_profile_templates_sql, $rows, false);

    	$html .= "<table class='content_management_tools'>\n";
    	$html .= "<th>" . _('Profile template label') . '</th><th>' . _('Actions') . '</th>' . "\n";
    	if ($rows)
    	foreach ($rows as $row) {
    		$profile_template = self :: getObject($row['profile_template_id']);
    		$html .= "<tr class='already_linked_content'>\n";
    		$html .= "<td>\n";
    		$html .= $profile_template->getLabel();
    		$html .= "</td>\n";
    		$html .= "<td>\n";
    		$name = "{$user_prefix}_" . $profile_template->GetId() . "_edit";
    		$html .= "<input type='button' class='submit' name='$name' value='" . _("Edit") . "' onClick='window.open(\"" . GENERIC_OBJECT_ADMIN_ABS_HREF . "?object_class=ProfileTemplate&action=edit&object_id=" . $profile_template->GetId() . "\");'>\n";
    		$name = "{$user_prefix}_" . $profile_template->GetId() . "_erase";
    		$html .= "<input type='submit' class='submit' name='$name' value='" . _("Remove") . "'>";
    		$html .= "</td>\n";
    		$html .= "</tr>\n";
    	}

    	/* Add a profile template */
    	$html .= "<tr class='add_existing_content'>\n";
    	$html .= "<td colspan ='2'>\n";
    	$name = "{$user_prefix}_new_existing";
    	$profileTemplateSelector = self :: getSelectUI($name, Array('additionalWhere' => "AND profile_template_id NOT IN (SELECT profile_template_id FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key')", 'typeInterface' => "add"));
    	$html .= $profileTemplateSelector;
    	$html .= "</td>\n";
    	$html .= "</tr>\n";

    	$html .= "</table>\n";
    	return $html;
    }
    
    /** Get the selected ProfileTemplate object
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the ProfileTemplate object or null
     */
    static function processLinkedProfileTemplateUI($user_prefix, $link_table, $link_table_obj_key_col, $link_table_obj_key) {
    	$db = AbstractDb :: getObject();
    	$link_table = $db->escapeString($link_table);
    	$link_table_obj_key_col = $db->escapeString($link_table_obj_key_col);
    	$link_table_obj_key = $db->escapeString($link_table_obj_key);
    	
    	// Profile templates already linked
    	$current_content_sql = "SELECT * FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key'";
    	$rows = null;
    	$db->execSql($current_content_sql, $rows, false);
    	if ($rows)
    	foreach ($rows as $row) {
    		$profile_template = ProfileTemplate :: getObject($row['profile_template_id']);
    		$profile_template_id = $db->escapeString($profile_template->getId());
    		$name = "{$user_prefix}_" . $profile_template->GetId() . "_erase";
    		if (!empty ($_REQUEST[$name])) {
    			$sql = "DELETE FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key' AND profile_template_id = '$profile_template_id';\n";
    			$db->execSqlUpdate($sql, false);
    		}
    	}
    	// Link an existing profile template
    	$name = "{$user_prefix}_new_existing_add";
    	if (!empty ($_REQUEST[$name])) {
    		$name = "{$user_prefix}_new_existing";
    		$profile_template = ProfileTemplate :: processSelectProfileTemplateUI($name);
    		if ($profile_template) {
    			$profile_template_id = $db->escapeString($profile_template->getId());
    			$sql = "INSERT INTO $link_table (profile_template_id, $link_table_obj_key_col) VALUES ('$profile_template_id', '$link_table_obj_key');\n";
    			$db->execSqlUpdate($sql, false);
    		}
    	}
    }
	
	/**Get all fields
     * @return an array of ProfileTemplateField or an empty arrray */
    function getFields($additional_where = null) {
        $db = AbstractDb :: getObject();
        // Init values
        $retval = array ();
        $field_rows = null;

        $sql = "SELECT profile_template_field_id FROM profile_template_fields WHERE profile_template_id = '{$this->getId()}' $additional_where ORDER BY display_order";
        $db->execSql($sql, $field_rows, false);
        if ($field_rows != null) {
            foreach ($field_rows as $field_row) {
                $retval[] = ProfileTemplateField :: getObject($field_row['profile_template_field_id']);
            }
        }
        return $retval;
    }
	
    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
	public function getAdminUI()
	{
	    Security::requirePermission(Permission::P('SERVER_PERM_EDIT_PROFILE_TEMPLATES'), Server::getServer());
	    $db = AbstractDb::getObject();
	    $sql = "SELECT COUNT(*) as num_used_profiles FROM profile_templates JOIN profiles USING (profile_template_id) WHERE profile_template_id = '" . $this->getId() . "'";
	    $db->execSqlUniqueRes($sql, $num_used_profiles, false);
	     
	    // Init values
		$html = '';

		$html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
		$html .= "<legend>"._("Profile template management")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";
        
		// profile_template_id
		$_value = htmlspecialchars($this->getId(), ENT_QUOTES);

		$html .= "<li class='admin_element_item_container'>\n";
		$html .= "<div class='admin_element_label'>" . _("ProfileTemplate ID") . ":</div>\n";
		$html .= "<div class='admin_element_data'>\n";
		$html .= $_value;
		$html .= "</div>\n";
		$html .= "</li>\n";

		// label
		$_name = "profile_template_" . $this->getId() . "_label";
		$_value = htmlspecialchars($this->getLabel(), ENT_QUOTES);

		$html .= "<li class='admin_element_item_container'>\n";
		$html .= "<div class='admin_element_label'>" . _("Label") . ":</div>\n";
		$html .= "<div class='admin_element_data'>\n";
		$html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
		$html .= "</div>\n";
		$html .= "</li>\n";

		// creation date
		$_value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

		$html .= "<li class='admin_element_item_container'>\n";
		$html .= "<div class='admin_element_label'>" . _("Creation date") . ":</div>\n";
		$html .= "<div class='admin_element_data'>\n";
		$html .= $_value;
		$html .= "</div>\n";
		$html .= "</li>\n";

		// profile template fields
		$html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>"._("Profile template fields")."</legend>\n";
        
        $html .= "<ul class='admin_element_list'>\n";
        foreach ($this->getFields() as $field) {
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= $field->getAdminUI(null, sprintf(_("%s %d"), get_class($field), $field->getDisplayOrder()));
            $html .= "<div class='admin_element_tools'>\n";
            $sql = "SELECT COUNT(*) as num_used_fields FROM profile_template_fields JOIN profile_fields USING (profile_template_field_id) WHERE profile_template_field_id = '" . $field->getId() . "'";
            $db->execSqlUniqueRes($sql, $num_used_fields_row, false);
            $name = "profile_template_" . $this->id . "_field_" . $field->GetId() . "_erase";
            $html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s %d, used in %d/%d profiles"), get_class($field), $field->getDisplayOrder(), $num_used_fields_row['num_used_fields'], $num_used_profiles['num_used_profiles']) . "'>";
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= ProfileTemplateField :: getCreateFieldUI("profile_template_{$this->id}_new_field");
        $html .= "</li>\n";
        $html .= "</ul>\n";
        $html .= "</fieldset>\n";
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
	    	    Security::requirePermission(Permission::P('SERVER_PERM_EDIT_PROFILE_TEMPLATES'), Server::getServer());
        require_once('classes/User.php');
        
        $errmsg = "";
        
		// label
		$_name = "profile_template_" . $this->getId() . "_label";
		$this->setLabel($_REQUEST[$_name]);	
		
		foreach ($this->getFields() as $field) {
            $name = "profile_template_" . $this->id . "_field_" . $field->GetId() . "_erase";
            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                $field->delete($errmsg);
            } else {
                $field->processAdminUI();
            }
        }

        ProfileTemplateField :: processCreateFieldUI("profile_template_{$this->id}_new_field", $this);
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
		$_retVal = false;

		if (Security::hasPermission(Permission::P('SERVER_PERM_EDIT_PROFILE_TEMPLATES'), Server::getServer())) {
			$errmsg = _('Access denied');
		} else {
			$_id = $db->escapeString($this->getId());

			if (!$db->execSqlUpdate("DELETE FROM profile_templates WHERE profile_template_id = '{$_id}'", false)) {
				$errmsg = _('Could not delete ProfileTemplate!');
			} else {
				$_retVal = true;
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
		$this->__construct($this->getId());
	}
    
    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $server = Server::getServer();
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_PROFILE_TEMPLATES'), $server))
        {
            $items[] = array('path' => 'server/profile_templates',
            'title' => _("Profile templates"),
            'url' => BASE_URL_PATH."admin/generic_object_admin.php?object_class=ProfileTemplate&action=list"
		);            
        }
        return $items;
    }
} //end class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */