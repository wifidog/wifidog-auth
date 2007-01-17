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
 * Fields of a profile template
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     François Proulx <francois.proulx@gmail.com>
 * @copyright  2007 François Proulx
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/ProfileTemplate.php');
require_once ('classes/Content.php');

class ProfileTemplateField implements GenericObject {
	private static $instanceArray = array();
	
    private $profile_template_field_row;
    private $admin_label_content = null;
    private $display_label_content = null;
    private $content_type_filter = null;
    
    private $id = null;

    /**
     * Constructor
     *
     * @param string $profile_template_field_id Field ID
     */
    protected function __construct($profile_template_field_id) {
        $db = AbstractDb::getObject();

        // Init values
        $row = null;

        $profile_template_field_id = $db->escapeString($profile_template_field_id);

        $sql = "SELECT * FROM profile_template_fields WHERE profile_template_field_id = '{$profile_template_field_id}'";
        $db->execSqlUniqueRes($sql, $row, false);

		$this->id = $row['profile_template_field_id'];
        $this->profile_template_field_row = $row;
    }
    
    public static function getObject($id)
    {
    	if(!isset(self::$instanceArray[$id]))
        {
        	self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
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
    
    public static function createNewObject() {}
    public static function getCreateNewObjectUI() {}
    public static function processCreateNewObjectUI() {}
    
    /**
     * Get a flexible interface to generate new ProfileTemplateField
     *
     * @param string $user_prefix      A identifier provided by the programmer
     *                                 to recognise it's generated HTML form
     * @return string HTML markup
     */
    public static function getCreateFieldUI($user_prefix, $title = null) {

        $db = AbstractDb :: getObject();

        // Init values
        $html = "";
        $html .= "<fieldset class='admin_container Content'>\n";
        if (!empty ($title)) {
            $html .= "<legend>$title</legend>\n";
        }

        $availableContentTypeFilters = ContentTypeFilter :: getAllContentTypeFilters();

        $name = "get_new_profile_template_field_{$user_prefix}_content_type_filter";

        $i = 0;
        $tab = array ();
        foreach ($availableContentTypeFilters as $filter) {
            $tab[$i][0] = $filter->getId();
            $tab[$i][1] = $filter->getLabel() == null ? "["._("No label")."] - ".$filter->getId() : $filter->getLabel();
            $i++;
        }
        if (count($tab) > 1) {
            $label = _("Add new profile template field filtered by") . ": ";
            $html .= "<div class='admin_element_data content_add'>";
            $html .= $label;
            $html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);
            $html .= "</div>";
        } else
            if (count($tab) == 1) {
                $html .= '<input type="hidden" name="' . $name . '" value="' . $tab[0][0] . '">';
            } else {
                $html .= "<div class='errormsg'>"._("Sorry, no content type filter exists.")."</div>\n";
            }

        $name = "get_new_profile_template_field_{$user_prefix}_add";

        if (count($tab) >= 1) {
            $value = _("Add");
            $html .= "<div class='admin_element_tools'>";
	        $html .= '<input type="submit" class="submit" name="' . $name . '" value="' . $value . '">';
	        $html .= "</div>";
        }
        
        $html .= "</fieldset>\n";
        return $html;
    }
    
    /**
     * This method will create a ProfileTemplateField based on the content type filter specified
     *
     * @param string $user_prefix                A identifier provided by the programmer to
     *                                           recognise it's generated form
     * @param string $profile_template              Must be present
     *
     * @return object The ProfileTemplateField object, or null if the user didn't create one
     * @static
     */
    public static function processCreateFieldUI($user_prefix, ProfileTemplate $profile_template) {
        $db = AbstractDb::getObject();

        // Init values
        $profile_template_field_object = null;
        $max_display_order_row = null;

       	$name = "get_new_profile_template_field_{$user_prefix}_add";

        if (!empty ($_REQUEST[$name])) {
            /* Get the display order to add the ProfileTemplateField at the end */
            $sql = "SELECT MAX(display_order) as max_display_order FROM profile_template_fields WHERE profile_template_id = '" . $profile_template->getId() . "'";
            $db->execSqlUniqueRes($sql, $max_display_order_row, false);
            $display_order = $max_display_order_row['max_display_order'] + 1;

            $profile_template_field_id = get_guid();
            $sql = "INSERT INTO profile_template_fields (profile_template_field_id, profile_template_id, display_order) VALUES ('$profile_template_field_id', '{$profile_template->getId()}', $display_order);";

            if (!$db->execSqlUpdate($sql, false)) {
                throw new Exception(_('Unable to insert new content into database!'));
            }

            $profile_template_field_object = self :: getObject($profile_template_field_id);
            $name = "get_new_profile_template_field_{$user_prefix}_content_type_filter";
            $content_type_filter_ui_result = FormSelectGenerator :: getResult($name, null);

			if(empty($content_type_filter_ui_result))
			{
				throw new exception("Unable to retrieve the content type filter to associate with the new field");
			}
			
            $content_type_filter = ContentTypeFilter :: getObject($content_type_filter_ui_result);
            $profile_template_field_object->replaceContentTypeFilter($content_type_filter);
        }

        return $profile_template_field_object;
    }
    
    /**
     * Replace and delete the old content_type_filter_id (if any) by the new
     * content type filter 
     *
     * @param object $new_content_type_filter ContentTypeFilter object or null.
     *
     * @return void
     */
    private function replaceContentTypeFilter($new_content_type_filter) {
        $db = AbstractDb::getObject();

        if ($new_content_type_filter != null && get_class($new_content_type_filter) == "ContentTypeFilter") {
            $new_content_type_filter_id_sql = "'" . $new_content_type_filter->getId() . "'";
            $this->content_type_filter = $new_content_type_filter;
        } else {
            $new_content_type_filter_id_sql = "NULL";
            $this->content_type_filter = null;
        }

        $db->execSqlUpdate("UPDATE profile_template_fields SET content_type_filter_id = $new_content_type_filter_id_sql WHERE profile_template_field_id = '{$this->getId()}'", false);

        $this->refresh();
    }

    /**
     * Get the ContentTypeFilter object for this field
     *
     * @return ContentTypeFilter object
     */
    public function getContentTypeFilter() {
        if ($this->content_type_filter == null) {
            $this->content_type_filter = ContentTypeFilter :: getObject($this->profile_template_field_row['content_type_filter_id']);
        }
        return $this->content_type_filter;
    }
    
    /**
     * Replace and delete the old admin label content (if any) by the new
     * content
     *
     * @param object $new_admin_label_content Content object or null.
     *
     * @return void
     */
    private function replaceAdminLabelContent($new_admin_label_content) {
        $db = AbstractDb::getObject();

        // Init values
        $old_admin_label_content = null;
        $errmsg = null;

        if (!empty ($this->profile_template_field_row['admin_label_content_id'])) {
            $old_admin_label_content = Content :: getObject($this->profile_template_field_row['admin_label_content_id']);
        }

        if ($new_admin_label_content != null) {
            $new_admin_label_content_id_sql = "'" . $new_admin_label_content->getId() . "'";
            $this->admin_label_content = $new_admin_label_content;
        } else {
            $new_admin_label_content_id_sql = "NULL";
            $this->admin_label_content = null;
        }

        $db->execSqlUpdate("UPDATE profile_template_fields SET admin_label_content_id = $new_admin_label_content_id_sql WHERE profile_template_field_id = '$this->id'", false);

        if ($old_admin_label_content != null) {
            $old_admin_label_content->delete($errmsg);
        }
        $this->refresh();
    }
    
    /**
     * Get the admin label content object for this field
     *
     * @return Content object
     */
    public function getAdminLabelContent() {
        if ($this->admin_label_content == null) {
            $this->admin_label_content = Content :: getObject($this->profile_template_field_row['admin_label_content_id']);
        }
        return $this->admin_label_content;
    }
    
    /**
     * Replace and delete the old display label content (if any) by the new
     * content
     *
     * @param object $new_display_label_content Content object or null.
     *
     * @return void
     */
    private function replaceDisplayLabelContent($new_display_label_content) {
        $db = AbstractDb::getObject();

        // Init values
        $old_display_label_content = null;
        $errmsg = null;

        if (!empty ($this->profile_template_field_row['display_label_content_id'])) {
            $old_display_label_content = Content :: getObject($this->profile_template_field_row['display_label_content_id']);
        }

        if ($new_display_label_content != null) {
            $new_display_label_content_id_sql = "'" . $new_display_label_content->getId() . "'";
            $this->display_label_content = $new_display_label_content;
        } else {
            $new_display_label_content_id_sql = "NULL";
            $this->display_label_content = null;
        }

        $db->execSqlUpdate("UPDATE profile_template_fields SET display_label_content_id = $new_display_label_content_id_sql WHERE profile_template_field_id = '$this->id'", false);

        if ($old_display_label_content != null) {
            $old_display_label_content->delete($errmsg);
        }
        $this->refresh();
    }
    
    /**
     * Get the display label content object for this field
     *
     * @return Content object
     */
    public function getDisplayLabelContent() {
        if ($this->display_label_content == null) {
            $this->display_label_content = Content :: getObject($this->profile_template_field_row['display_label_content_id']);
        }
        return $this->display_label_content;
    }
    
    /**
     * Get the order of the field in the profile template
     *
     * @return string the order of the field in the profile template
     */
    public function getDisplayOrder() {
        return $this->profile_template_field_row['display_order'];
    }

    /**
     * Set the order of the field in the profile template
     *
     * @param string $order Order how fields should be displayed
     *
     * @return void
     */
    public function setDisplayOrder($order) {
        
        $db = AbstractDb::getObject();

        if ($order != $this->getDisplayOrder()) {
            /*
             * Only update database if there is an actual change
             */
            $order = $db->escapeString($order);
            $db->execSqlUpdate("UPDATE profile_template_fields SET display_order = $order WHERE profile_template_field_id = '$this->id'", false);
            $this->refresh();
        }
    }
    
    /**
     * Get the semantic ID of the field in the profile template
     *
     * @return string the semantic ID of the field in the profile template
     */
    public function getSemanticId() {
        return $this->profile_template_field_row['semantic_id'];
    }

    /**
     * Set the semantic ID of the field in the profile template
     *
     * @param string $semantic_id
     *
     * @return void
     */
    public function setSemanticId($semantic_id) {
        
        $db = AbstractDb::getObject();

        if ($semantic_id != $this->getSemanticId()) {
            /*
             * Only update database if there is an actual change
             */
            $semantic_id = $db->escapeString($semantic_id);
            $db->execSqlUpdate("UPDATE profile_template_fields SET semantic_id = '{$semantic_id}' WHERE profile_template_field_id = '$this->id'", false);
            $this->refresh();
        }
    }

    /**
     * Shows the administration interface for ContentGroupElement
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     */
    public function getAdminUI($subclass_admin_interface = null, $title = null) {
        
        $db = AbstractDb::getObject();

        // Init values
        $html = '';
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>".get_class($this)." [" . $this->getDisplayOrder() . "]</legend>\n";

        $html .= "<ul class='admin_element_list'>\n";
        
        // display_order
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>"._("Display order").":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "profile_template_field_{$this->id}_display_order";
        $html .= "<input type='text' name='$name' value='" . $this->getDisplayOrder() . "' size='2'>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";
        
        // content_type_filter_id 
        $html .= "<li class='admin_element_item_container'>\n";
        if ($this->getContentTypeFilter() == null) {
            $html .= "<div class='errormsg'>"._("Sorry, content type filter is missing.")."</div>\n";
        } else {
        	$html .= "<div class='admin_element_label'>"._("Content type filter").":</div>\n";
        	$html .= "<div class='admin_element_data'>\n";
        	$label = $this->getContentTypeFilter()->getLabel();
            $html .= empty($label) ? "["._("No label")."] - ".$this->getContentTypeFilter()->getId() : $label;
            $html .= "</div>\n";
        }
        $html .= "</li>\n";
        
        // display_label_content_id
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Display label") . ":</div>\n";
        if (empty ($this->profile_template_field_row['display_label_content_id'])) {
	        $html .= Content :: getNewContentUI("profile_template_field_{$this->id}_new_display_label_content");
	        $html .= "</li>\n";
        } else {
            $display_label_content = Content :: getObject($this->profile_template_field_row['display_label_content_id']);
            $html .= $display_label_content->getAdminUI(null, sprintf(_("%s display label (%s)"), get_class($this), get_class($display_label_content)));
            
            $html .= "<div class='admin_element_tools'>\n";
            $name = "profile_template_field_{$this->id}_erase_display_label_content";
            $html .= "<input type='submit' name='$name' value='".sprintf(_("Delete %s (%s)"), _("display label"), get_class($display_label_content))."'>";
            $html .= "</div>\n";
        }
        $html .= "</li>\n";
        
        // admin_label_content_id
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Admin label") . ":</div>\n";
        if (empty ($this->profile_template_field_row['admin_label_content_id'])) {
            $html .= "<li class='admin_element_item_container'>\n";
	        $html .= Content :: getNewContentUI("profile_template_field_{$this->id}_new_admin_label_content");
	        $html .= "</li>\n";
        } else {
            $admin_label_content = Content :: getObject($this->profile_template_field_row['admin_label_content_id']);
            $html .= $admin_label_content->getAdminUI(null, sprintf(_("%s admin label (%s)"), get_class($this), get_class($admin_label_content)));
            
            $html .= "<div class='admin_element_tools'>\n";
            $name = "profile_template_field_{$this->id}_erase_admin_label_content";
            $html .= "<input type='submit' name='$name' value='".sprintf(_("Delete %s (%s)"), _("admin label"), get_class($admin_label_content))."'>";
            $html .= "</div>\n";
        }
        $html .= "</li>\n";
        
        // semantic_id
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>"._("Semantic ID").":</div>\n";
        $html .= "<div class='admin_element_data'>\n";
        
        $name = "profile_template_field_{$this->id}_semantic_id";
        $html .= "<input type='text' name='$name' value='" . $this->getSemanticId() . "' size='15'>\n";
        
        $semantic_id_presets = array (
            "---" => "", 
            "foaf:name - "._("Full name") => "foaf:name", 
            "foaf:nick - "._("Nickname") => "foaf:nick", 
            "foaf:mbox - "._("E-mail") => "foaf:mbox", 
            "foaf:mbox_sha1sum - "._("Hashed e-mail") => "foaf:mbox_sha1sum", 
            "foaf:img - "._("Picture") => "foaf:img", 
            "foaf:weblog - "._("URL of a blog") => "foaf:weblog", 
            "foaf:homepage - "._("URL of a homepage") => "foaf:homepage"
        );
        
        $html .= "<select onchange=\"this.form.{$name}.value = this.value;\">";

        foreach ($semantic_id_presets as $label => $value) {
            $value == $this->getSemanticId() ? $selected = 'SELECTED' : $selected = '';
            $html .= "<option value=\"{$value}\" $selected>{$label}";
        }

        $html .= "</select>\n";
        
        $html .= "</div>\n";
        $html .= "</li>\n";
        
        $html .= "</ul>\n";

        $html .= "</li>\n";
        $html .= "</fieldset>\n";
        $html .= "</li>\n";

        return $html;
    }

    /**
     * Processes the input of the administration interface for ContentGroupElement
     *
     * @return void
     */
    public function processAdminUI() {
        $errmsg = "";
        $db = AbstractDb::getObject();

        // display_order 
        $name = "profile_template_field_{$this->id}_display_order";
        $this->setDisplayOrder($_REQUEST[$name]);

        // display_label_content_id 
        if (empty($this->profile_template_field_row['display_label_content_id'])) {
            // Could be either a new content or existing content ( try both successively )
            $display_label_content = Content :: processNewContentUI("profile_template_field_{$this->id}_new_display_label_content");

            if ($display_label_content != null) {
            	$this->replaceDisplayLabelContent($display_label_content);
            }
        } else {
            $display_label_content = Content :: getObject($this->profile_template_field_row['display_label_content_id']);
            
            $name = "profile_template_field_{$this->id}_erase_display_label_content";
            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
            	$this->replaceDisplayLabelContent(null);
            } else {
                $display_label_content->processAdminUI();
            }
        }
        
        // admin_label_content_id 
        if (empty($this->profile_template_field_row['admin_label_content_id'])) {
            // Could be either a new content or existing content ( try both successively )
            $admin_label_content = Content :: processNewContentUI("profile_template_field_{$this->id}_new_admin_label_content");

            if ($admin_label_content != null) {
            	$this->replaceAdminLabelContent($admin_label_content);
            }
        } else {
            $admin_label_content = Content :: getObject($this->profile_template_field_row['admin_label_content_id']);
            
            $name = "profile_template_field_{$this->id}_erase_admin_label_content";
            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
            	$this->replaceAdminLabelContent(null);
            } else {
                $admin_label_content->processAdminUI();
            }
        }
        
        // semantic_id 
        $name = "profile_template_field_{$this->id}_semantic_id";
        $this->setSemanticId($_REQUEST[$name]);
        
        $this->refresh();
    }

    /** This function will be called by MainUI for each Content BEFORE any getUserUI function is called to allow two pass Content display.
     * Two pass Content display allows such things as modyfying headers, title, creating content type that accumulate content from other pieces (like RSS feeds)
     * @return null
     */
    public function prepareGetUserUI() {
        $displayed_content = $this->getDisplayedContent();
        $displayed_content->prepareGetUserUI();
        return parent :: prepareGetUserUI();
    }

    /**
     * Retreives the user interface of this object.
     *
     * @return string The HTML fragment for this interface
     */
    public function getUserUI() {
        // Init values
        $html = '';

        if (!empty ($this->content_group_element_row['displayed_content_id'])) {
            $displayed_content = $this->getDisplayedContent();

            // If the content group logging is disabled, all the children will inherit this property temporarly
            if ($this->getLoggingStatus() == false) {
                $displayed_content->setLoggingStatus(false);
            }

            $html .= $displayed_content->getUserUI();
        }
        $this->setUserUIMainDisplayContent($html);
        return parent :: getUserUI($html);
    }

    /**
     * Deletes a ProfileTemplateField object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     * @internal Persistent content will not be deleted
     *
     * @todo Implement proper access control
     */
    public function delete(& $errmsg) {
    	require_once('classes/User.php');
        
		$db = AbstractDb::getObject();

	    // Init values
		$_retVal = false;

		if (!User::getCurrentUser()->isSuperAdmin()) {
			$errmsg = _('Access denied (must have super admin access)');
		} else {
			$_id = $db->escapeString($this->getId());

			if (!$db->execSqlUpdate("DELETE FROM profile_template_fields WHERE profile_template_field_id = '{$_id}'", false)) {
				$errmsg = _('Could not delete ProfileTemplateField!');
			} else {
				$_retVal = true;
			}
		}

		return $_retVal;
    }
    /** Reloads the object from the database.  Should normally be called after a set operation */
    protected function refresh() {
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