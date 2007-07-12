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
 * Defines a profile
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     François Proulx <francois.proulx@gmail.com>, Benoit Grégoire
 * @copyright  2007 François Proulx, 2007 Technologies Coeus inc.
 * @link       http://www.wifidog.org/
 */

require_once ('classes/ContentTypeFilter.php');
require_once ('classes/ProfileTemplateField.php');
require_once ('classes/ProfileTemplate.php');
require_once ('classes/ProfileField.php');

class Profile implements GenericObject {
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();

    private $id = null;
    private $_row;

    private function __construct($profile_id)
    {
        $db = AbstractDb::getObject();

        // Init values
        $row = null;

        $profile_id = $db->escapeString($profile_id);
        $sql = "SELECT * FROM profiles WHERE profile_id = '{$profile_id}';";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row == null) {
            throw new Exception("The profile with id {$profile_id} could not be found in the database!");
        }

        $this->_row = $row;
        $this->id = $db->escapeString($row['profile_id']);
    }

    /**
     * Get an instance of the object
     *
     * @param string $id The object id
     *
     * @return mixed The Profile object, or null if there was an error
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
     * Retreives the Id of the object
     *
     * @return string The Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retrieves the profile's creation date
     *
     * @return string profile's creation date
     *
     * @access public
     */
    public function getCreationDate()
    {
        return $this->_row['creation_date'];
    }

    /**
     * Set the profile's creation date
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
            $_retVal = $db->execSqlUpdate("UPDATE profiles SET creation_date = '{$value}' WHERE profile_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $_retVal;
    }

    /**
     * Retrieves the profile's visibility
     *
     * @return boolean
     *
     * @access public
     */
    public function isVisible()
    {
        if($this->_row['is_visible'] == "f")
        return false;
        else
        return true;
    }

    /**
     * Set the profile's visibility
     *
     * @param boolean $value
     *
     * @return bool True on success, false on failure
     */
    public function setVisible($value)
    {
        $db = AbstractDb::getObject();

        // Init values
        $_retVal = true;

        if ($value != $this->isVisible()) {
            if($value == true)
            $_retVal = $db->execSqlUpdate("UPDATE profiles SET is_visible = true WHERE profile_id = '{$this->getId()}'", false);
            else
            $_retVal = $db->execSqlUpdate("UPDATE profiles SET is_visible = false WHERE profile_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $_retVal;
    }

    /* Create a new Profile object in the database
     * @param string $profile_id the id of the new profile object
     * @param string $profile_template
     *
     * @return mixed The newly created object, or null if there was an error
     *
     * @see GenericObject
     *
     * @static
     * @access public
     */
    public static function createNewObject($profile_id = null, $profile_template = null)
    {
        if(!empty($profile_template) && get_class($profile_template) == "ProfileTemplate") {
            $db = AbstractDb::getObject();
            if (empty ($profile_id)) {
                $profile_id = get_guid();
            }
             
            $profile_template_id = $profile_template->getId();
            $sql = "INSERT INTO profiles (profile_id, profile_template_id, creation_date) VALUES ('{$profile_id}', '{$profile_template_id}', NOW());\n";
            if (!$db->execSqlUpdate($sql, false)) {
                throw new Exception(_('Unable to insert the new profile in the database!'));
            }
             
            $object = self::getObject($profile_id);
            return $object;
        } else
        throw new Exception("You must provide a profile template object");
    }

    public static function getCreateNewObjectUI() {}
    public static function processCreateNewObjectUI() {}

    /**
     * Retrieves the associated profile template
     *
     * @return ProfileTemplate object
     *
     * @access public
     */
    public function getTemplate()
    {
        $retval = ProfileTemplate :: getObject($this->_row['profile_template_id']);
        return $retval;
    }

    /** Get all fields matching the given semantic ID
     * @return an array of Content (realized ProfileTemplateField) or an empty arrray */
    public function getFieldsBySemanticId($semantic_id)
    {
        $db = AbstractDb :: getObject();
        // Init values
        $retval = array ();
        $field_rows = null;

        $semantic_id = $db->escapeString($semantic_id);
        $sql = "SELECT profile_field_id FROM profile_fields NATURAL JOIN profile_template_fields WHERE profile_id = '{$this->getId()}' AND semantic_id = '{$semantic_id}';";
        $db->execSql($sql, $field_rows, false);
        if ($field_rows != null) {
            foreach ($field_rows as $field_row) {
                $retval[] = ProfileField :: getObject($field_row['profile_field_id']);
            }
        }

        return $retval;
    }

    /** Get all fields
     * @return an array of ProfileField (realized ProfileTemplateField) or an empty array.
     * The array id is the template field used for the ProfileField */
    function getFields()
    {
        $db = AbstractDb :: getObject();
        // Init values
        $retval = array ();
        $field_rows = null;
        $sql = "SELECT profile_field_id, profile_template_field_id FROM profile_fields NATURAL JOIN profile_template_fields WHERE profile_id = '{$this->getId()}' ORDER BY display_order";
        $db->execSql($sql, $field_rows, false);
        if ($field_rows != null) {
            foreach ($field_rows as $field_row) {
                $retval[$field_row['profile_template_field_id']] = ProfileField :: getObject($field_row['profile_field_id']);
            }
        }

        return $retval;
    }

    /** This method will remove a field by semantic ID from the array of ProfileFields passed as parameter,
     * and return the poped field (or null)
     * @param $id Semantic ID that is looked for
     * @param $fields Array of ProfileFields, passed by reference
     * @return a ProfileField object or null.
     * The array id is the template field used for the ProfileField */
    static public function popFieldBySemanticId($id, &$fields)
    {
        $db = AbstractDb :: getObject();
            foreach ($fields as $key => $field) {
                $candidateId = $field->getProfileTemplateField()->getSemanticId();
                //echo "candidateId: $candidateId, id: $id<br/>\n";
                if($candidateId==$id){
                    unset($fields[$key]);
                    //pretty_print_r($field);
                    return $field;
                }
            }
        return null;
    }
    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getAdminUI()
    {
        // Init values
        $html = '';

        // All sections
        $profileSections = array();

        // Metadata section
        $profileMetadataItems = array();

        //  is_visible
        $title = _("Should this profile be publicly visible?");
        $name = "profile_" . $this->getId() . "_is_visible";
        $data = InterfaceElements::generateInputCheckbox($name, "", _("Yes"), $this->isVisible(), "profile_is_visible_radio");
        $profileMetadataItems[] = InterfaceElements::genSectionItem($data, $title);

        $profileSections[] = InterfaceElements::genSection($profileMetadataItems, _("Profile preferences"));

        // Fields section
        $profileFieldsUI = array();

        $template = $this->getTemplate();
        // Aggregate the fields UI
        $profileFields = $this->getFields();
        foreach ($template->getFields() as $templateField) {
            if(!empty($profileFields[$templateField->getId()])) {
                //We already have a real field instanciated
                $field=$profileFields[$templateField->getId()];
                $profileFieldsUI[] = InterfaceElements::genSectionItem($field->getAdminUI());
            }
            else {
                //show the template admin UI
                //$profileFieldsUI[] = InterfaceElements::genSectionItem($templateField->getUserUI());

                // Init values
                $tmp_html = '';
                $admin_label = $templateField->getAdminLabelContent();
                if($admin_label != null) {
                    $title = $admin_label->__toString();
                }
                else {
                    $title = null;
                }
                $tmp_html .= "<fieldset class='admin_container " . get_class($this) . "'>\n";
                if (!empty ($title)) {
                    $tmp_html .= "<legend>$title</legend>\n";
                }
                $futureProfileFieldId = get_guid();
                $name = "profile_template_field_{$templateField->getId()}_field_future_id";
                $tmp_html .= '<input type="hidden" name="' . $name . '" value="' . $futureProfileFieldId . '">';
                $userData['contentTypeFilter'] = $templateField->getContentTypeFilter();
                //echo "Profile::getAdminUI: userData";pretty_print_r($userData);
                $tmp_html .= ProfileField :: getNewUI($futureProfileFieldId, $userData);
                $tmp_html .= "</fieldset>\n";
                $profileFieldsUI[] = $tmp_html;
            }
        }
        $profileSections[] = InterfaceElements::genSection($profileFieldsUI, _("Profile fields"));

        $html .= InterfaceElements::genSection($profileSections, _("Profile"), false, false, get_class($this));

        return $html;
    }

    /**
     * Process admin interface of this object
     *
     * @return void
     */
    public function processAdminUI()
    {
        //  is_visible
        $name = "profile_" . $this->getId() . "_is_visible";
        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == 'on')
        $this->setVisible(true);
        else
        $this->setVisible(false);

        $template = $this->getTemplate();
        // Aggregate the fields UI
        $profileFields = $this->getFields();
        foreach ($template->getFields() as $templateField) {
            if(!empty($profileFields[$templateField->getId()])) {
                //We already have a real field instanciated
                $field=$profileFields[$templateField->getId()];
                $field->processAdminUI();
            }
            else {
                //Create a new fielreate a new field (if appropriate) 
                $name = "profile_template_field_{$templateField->getId()}_field_future_id";
                $futureProfileFieldId = $_REQUEST[$name];
                if(ProfileField :: processNewUI($futureProfileFieldId, true)==true)
                {
                    ProfileField::createNewObject($this, $templateField, $futureProfileFieldId);
                    ProfileField::processNewUI($futureProfileFieldId, false);
                }
            }
        }
    }

    public function getUserUI()
    {
        $html = "";
        $html .= "<div class='Profile'>\n";
        if($this->isVisible()) {
            $fields = $this->getFields();
            $field = self::popFieldBySemanticId('foaf:img', $fields);
            if($field){
                $html .= $field->getUserUI();
            }
            $field = self::popFieldBySemanticId('foaf:nick', $fields);
            if($field){
                $html .= $field->getUserUI();
            }
            $field = self::popFieldBySemanticId('foaf:name', $fields);
            if($field){
                $html .= $field->getUserUI();
            }
            $html .= "<br class='clearbr' />";
            //Print all the other fields
            foreach ($fields as $field) {
                $html .= $field->getUserUI();
            }
        }
        else {
            $html .= "<h1>"._("Sorry, this user has hidden his profile  temporarily.")."</h1>\n";
        }
        $html .= "</div>\n";

        return $html;
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

        if (!User::getCurrentUser()->DEPRECATEDisSuperAdmin()) {
            $errmsg = _('Access denied (must have super admin access)');
        } else {
            $_id = $db->escapeString($this->getId());

            if (!$db->execSqlUpdate("DELETE FROM profiles WHERE profile_id = '{$_id}'", false)) {
                $errmsg = _('Could not delete Profile!');
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

} //end class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */