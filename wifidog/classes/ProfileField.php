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
 * Field of a profile
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

class ProfileField implements GenericObject {
	private static $instanceArray = array();
	
    private $profile_field_row;
    private $profile_template_field = null;
    private $content_field = null;
    
    private $id = null;

    /**
     * Constructor
     *
     * @param string $profile_field_id Field ID
     */
    protected function __construct($profile_field_id) {
        $db = AbstractDb::getObject();

        // Init values
        $row = null;

        $profile_field_id = $db->escapeString($profile_field_id);

        $sql = "SELECT * FROM profile_fields WHERE profile_field_id = '{$profile_field_id}'";
        $db->execSqlUniqueRes($sql, $row, false);

		$this->id = $row['profile_field_id'];
        $this->profile_field_row = $row;
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
	
	
    /**
     * Retrieves the profile field's modification date
     *
     * @return string profile field's modification date
     *
     * @access public
     */
    public function getLastModificationDate()
    {
        return $this->mRow['last_modified'];
    }

    /**
     * Update the last modification date
     */
    public function touch()
    {
        $db = AbstractDb::getObject();

        // Init values
        $_retVal = true;
        
        $_retVal = $db->execSqlUpdate("UPDATE profile_fields SET last_modified = NOW() WHERE profile_field = '{$this->getId()}'", false);
        $this->refresh();
        
        return $_retVal;
    }
    
    /**
     * Retrieves the associated profile template field
     *
     * @return ProfileTemplateField object
     *
     * @access public
     */
    public function getProfileTemplateField() 
    {
    	if($this->profile_template_field == null && $this->profile_field_row['profile_template_field_id'] != null)
    		$this->profile_template_field = ProfileTemplateField :: getObject($this->profile_field_row['profile_template_field_id']);
    	return $this->profile_template_field;
    }
    
    /**
     * Retrieves the associated content field
     *
     * @return Content object
     *
     * @access public
     */
    public function getContentField() 
    {
    	if($this->content_field == null && $this->profile_field_row['content_id'] != null)
    		$this->content_field = Content :: getObject($this->profile_field_row['content_id']);
    	return $this->content_field;
    }
    
    /**
     * Set the content field
     * @param a content object to associate with the field
     */
    public function setContentField($content)
    {
        $db = AbstractDb::getObject();

        // Init values
        $_retVal = true;
        
        if($content == null)
        	$content_id = "NULL";
        else
        	$content_id = "'".$db->escapeString($content->getId())."'";
        	
        $_retVal = $db->execSqlUpdate("UPDATE profile_fields SET content_id = $content_id WHERE profile_field_id = '{$this->getId()}'", false);
        $this->refresh();
        
        return $_retVal;
    }
    
    public static function createNewObject() {}
    public static function getCreateNewObjectUI() {}
    public static function processCreateNewObjectUI() {}
    
    /**
     * Shows the administration interface for ProfileField
     * @return string HTML code for the administration interface
     */
    public function getAdminUI() {
    	$html = "";
    	
    	if($this->getProfileTemplateField() != null) {
	    	$admin_label = $this->getProfileTemplateField()->getAdminLabelContent();
	    	if($admin_label != null) {
	    		$html .= "<div class='admin_element_label'>\n";
	    		$html .= $admin_label->getUserUI();
	    		$html .= "</div>\n";
	    	}
	    	
	    	if($this->getProfileTemplateField()->getContentTypeFilter() != null) {
	    		if($this->getContentField() == null) {
		    		$html .= Content :: getNewContentUI("profile_field_{$this->id}_new_content", $this->getProfileTemplateField()->getContentTypeFilter());
	    		}
	    		else {
	    			$content = $this->getContentField();
	    			$html .= "<div class='admin_element_data'>\n";
	    			$html .= $content->getAdminUI();
	    			$html .= "</div>\n";
		            $html .= "<div class='admin_element_tools'>\n";
		            $name = "profile_field_" . $this->id . "_content_" . $content->getId() . "_erase";
		            $html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s"), get_class($content)) . "'>";
		            $html .= "</div>\n";
	    		}
	    	}
	    	else
	    		throw new Exception("Could not retrieve the associated content type filter.");
    	}
    	else
    		throw new Exception("Could not retrieve the associated profile template.");
    	
        return $html;
    }

    /**
     * Processes the input of the administration interface for ProfileField
     *
     * @return void
     */
    public function processAdminUI() {
    	if ($this->getContentField() == null) {
			$new_content = Content :: processNewContentUI("profile_field_{$this->id}_new_content");
			if ($new_content != null) {
				$this->setContentField($new_content);
			}
		} else {
			$content = $this->getContentField();
			$name = "profile_field_" . $this->id . "_content_" . $content->getId() . "_erase";
			if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
				$this->setContentField(null);
				$errmsg = null;
				$content->delete($errmsg);
			} else {
				$content->processAdminUI();
			}
		}
		
		$this->refresh();
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

			if (!$db->execSqlUpdate("DELETE FROM profile_fields WHERE profile_field_id = '{$_id}'", false)) {
				$errmsg = _('Could not delete ProfileField!');
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