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
 * @author     François Proulx <francois.proulx@gmail.com>, Benoit Grégoire
 * @copyright  2007 François Proulx, 2007 Technologies Coeus inc.
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
     * This method contains the interface to add an additional element to a
     * content object.  (For example, a new string in a Langstring)
     * It is called when getNewContentUI has only a single possible object type.
     * It may also be called by the object getAdminUI to avoid code duplication.
     *
     * @param string $contentId      The id of the (possibly not yet created) content object.
     *
     * @param string $userData=null Understood values are:
     * contentTypeFilter
     *
     *
     * @return HTML markup or false.  False means that this object does not support this interface.
     */
    public static function getNewUI($contentId, $userData=null) {
        $html = '';
        $futureContentId = get_guid();
        $name = "profile_field_{$contentId}_content_future_id";
        $html .= '<input type="hidden" name="' . $name . '" value="' . $futureContentId . '">';
        //echo "Profile::getNewUI: userData";pretty_print_r($userData);
        $html .= Content::getNewUI($futureContentId, $userData);
        return $html;
    }

    /**
     *
     *
     * @param string $contentId  The id of the (possibly not yet created) content object.
     *
     * @param string $checkOnly  If true, only check if there is data to be processed.
     * 	Will be used to decide if an object is to be created.  If there is
     * processNewUI will typically be called again with $checkOnly=false
     *
     * @return true if there was data to be processed, false otherwise

     */
    public static function processNewUI($contentId, $checkOnly=false) {
        $name = "profile_field_{$contentId}_content_future_id";
        $futureContentId = $_REQUEST[$name];

        $contentNewUIRetval = Content::processNewUI($futureContentId, true);
        if($contentNewUIRetval && $checkOnly==false) {
            $object = Content :: createNewObject('Content', $futureContentId);//The true content type will be set by processNewUI()
            //If there was data to processs, process it for real
            Content::processNewUI($futureContentId, false);
            $field = self::getObject($contentId);
            $field->setContentField(Content::getObject($futureContentId));
        }
        return $contentNewUIRetval;
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
        $retval = null;
        $retval = ProfileTemplateField :: getObject($this->profile_field_row['profile_template_field_id']);
        return $retval;
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
        $retval = null;
        if($this->profile_field_row['content_id'] != null)
        $retval = Content :: getObject($this->profile_field_row['content_id']);
        return $retval;
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

    /**
     * Create a new ProfileField in the database
     *
     * @param string $profile MANDATORY The profile this field belongs to
     * @param string $templateField MANDATORY The template this field is based on
     * @param string $id Optionnal The id to be given to the new Object. If
     *                             null, a new id will be assigned
     *
     * @return object The newly created object, or null if there was an
     *                error (an exception is also trown)

     */
    public static function createNewObject(Profile $profile = null, ProfileTemplateField $templateField = null, $id = null) {
        $db = AbstractDb :: getObject();
        $profileId = $db->escapeString($profile->getId());
        $templateFieldId = $db->escapeString($templateField->getId());
        if (empty ($id)) {
            $fieldId = get_guid();
        } else {
            $fieldId = $db->escapeString($id);
        }
        $sql = "INSERT INTO profile_fields (profile_id, profile_field_id, profile_template_field_id) VALUES ('$profileId', '$fieldId', '$templateFieldId');\n";
        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new profile fields in the database!'));
        }
        return self::getObject($fieldId);
    }
    public static function getCreateNewObjectUI() {}
    public static function processCreateNewObjectUI() {}

    /**
     * Shows the administration interface for ProfileField
     * @return string HTML code for the administration interface
     */
    public function getAdminUI() {
        $html = "";
        $title = null;
        $admin_label = $this->getProfileTemplateField()->getAdminLabelContent();
        if($admin_label != null) {
            $title =  $admin_label->__toString();
        }

        if($this->getProfileTemplateField()->getContentTypeFilter() != null) {
            $content = $this->getContentField();
            if($content == null) {
                $html .= Content :: getNewContentUI("profile_field_{$this->id}_new_content", $this->getProfileTemplateField()->getContentTypeFilter());
            }
            else {
                $html .= "<div class='admin_element_data'>\n";
                $html .= $content->getAdminUI(null, $title);
                $html .= "</div>\n";
                $html .= "<div class='admin_element_tools'>\n";
                $name = "profile_field_" . $this->id . "_erase";
                $html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s"), get_class($content)) . "'>";
                $html .= "</div>\n";
            }
        }
        else
        throw new Exception("Could not retrieve the associated content type filter.");

         
        return $html;
    }

    /**
     * Processes the input of the administration interface for ProfileField
     *
     * @return void
     */
    public function processAdminUI() {
        //echo "ProfileField::processAdminUI()<br/>\n";
        $content = $this->getContentField();
        if ($content == null) {
            $new_content = Content :: processNewContentUI("profile_field_{$this->id}_new_content");
            if ($new_content != null) {
                $this->setContentField($new_content);
            }
        } else {
            $content->processAdminUI();
            $name = "profile_field_" . $this->id . "_erase";
            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                $errmsg = null;
                $content->delete($errmsg);
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
        //echo "ProfileField::getUserUI()";
        $html = "";
        $content_field = $this->getContentField();
        if(!empty($content_field)) {
            $template_field = $this->getProfileTemplateField();
            $html .=  "<div class='user_ui_profile_field ".$template_field->getSemanticId()."'>\n";
            $content_label = $template_field->getDisplayLabelContent();
            if(!empty($content_label)){
                $html .=  "<div class='profile_field_label'>".$content_label->getUserUI()."</div>\n";
            }
            // Display the actual value from the profile field
            $html .=  "<div class='profile_field_content'>".$content_field->getUserUI()."</div>\n";
            $html .=  "</div>\n";
        }

        return $html;
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