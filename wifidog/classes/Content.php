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
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/FormSelectGenerator.php');
require_once('classes/GenericObject.php');
require_once('classes/Cache.php');

/**
 * Defines any type of content
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 */
class Content implements GenericObject {
    /**
     * Id of content
     *
     * @var string
     *
     * @access protected
     */
    protected $id;

    /**
     * Array containg content from database
     *
     * @var array
     *
     * @access protected
     */
    protected $content_row;

    /**
     * Type of content
     *
     * @var string
     *
     * @access private
     */
    private $content_type;

    /**
     * Definesif content is trivial or not
     *
     * @var bool
     *
     * @access private
     */
    private $is_trivial_content;

    /**
     * Defines if logging is enabled or not
     *
     * @var bool
     *
     * @access private
     */
    private $is_logging_enabled;

    /**
     * Constructor
     *
     * @param string $content_id Id of content
     *
     * @return void
     *
     * @access private
     */
    private function __construct($content_id)
    {
        // Define globals
        global $db;

        // Init values
        $_row = null;

        // Get content from database
        $content_id = $db->escapeString($content_id);
        $_sql = "SELECT * FROM content WHERE content_id='$content_id'";
        $db->execSqlUniqueRes($_sql, $_row, false);

        if ($_row == null) {
            throw new Exception(_("The content with the following id could not be found in the database: ") . $content_id);
        }

        $this->content_row = $_row;
        $this->id = $_row['content_id'];
        $this->content_type = $_row['content_type'];

        // By default content display logging is enabled
        $this->setLoggingStatus(true);
    }

    /**
     * A short string representation of the content
     *
     * @return string String representation of the content
     *
     * @access public
     */
    public function __toString()
    {
        if (empty ($this->content_row['title'])) {
            $_string = _("Untitled content");
        } else {
            $_title = self::getObject($this->content_row['title']);
            $_string = $_title->__toString();
        }

        return $_string;
    }

    /**
     * Create a new Content object in the database
     *
     * @param string $content_type The content type to be given to the new object
     * @param string $id           The id to be given to the new Content. If
     *                             null, a new id will be assigned
     *
     * @return object The newly created Content object, or null if there was an
     *                error (an exception is also trown)
     *
     * @static
     * @access public
     */
    public static function createNewObject($content_type = "Content", $id = null)
    {
        // Define globals
        global $db;

        if (empty ($id)) {
            $_contentId = get_guid();
        } else {
            $_contentId = $db->escapeString($id);
        }

        if (empty ($content_type)) {
            throw new Exception(_('Content type is optionnal, but cannot be empty!'));
        } else {
            $content_type = $db->escapeString($content_type);
        }

        $_sql = "INSERT INTO content (content_id, content_type) VALUES ('$_contentId', '$content_type')";

        if (!$db->execSqlUpdate($_sql, false)) {
            throw new Exception(_('Unable to insert new content into database!'));
        }

        $_object = self::getObject($_contentId);

        // At least add the current user as the default owner
        $_object->AddOwner(User::getCurrentUser());

        // By default, make it persistent
        $_object->setIsPersistent(true);

        return $_object;
    }

    /**
     * Get an interface to create a new object.
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getCreateNewObjectUI()
    {
        // Init values
        $_html = "";
        $_i = 0;
        $_tab = array ();

        foreach (self::getAvailableContentTypes() as $_className) {
            $_tab[$_i][0] = $_className;
            $_tab[$_i][1] = $_className;
            $_i ++;
        }

        if (empty ($_tab)) {
            $_html .= _("It appears that you have not installed any Content plugin !");
        } else {
            $_html .= _("You must select a content type: ");
            $_html .= FormSelectGenerator::generateFromArray($_tab, "TrivialLangstring", "new_content_content_type", "Content", false);
        }

        return $_html;
    }

    /**
     * Process the new object interface
     *
     * Will return the new object if the user has the credentials
     * necessary (else an exception is thrown) and if the form was fully
     * filled (else the object returns null).
     *
     * @return object The node object or null if no new node was created
     *
     * @static
     * @access public
     */
    public static function processCreateNewObjectUI()
    {
        // Init values
        $_retVal = null;

        $_contentType = FormSelectGenerator::getResult("new_content_content_type", "Content");

        if ($_contentType) {
            $_retVal = self::createNewObject($_contentType);
        }

        return $_retVal;
    }

    /**
     * Get the content object, specific to it's content type
     *
     * @param string $content_id The content Id
     *
     * @return object The Content object, or null if there was an error
     *                (an exception is also thrown)
     *
     * @static
     * @access public
     */
    public static function getObject($content_id)
    {
        // Define globals
        global $db;

        // Init values
        $_row = null;

        $content_id = $db->escapeString($content_id);
        $_sql = "SELECT content_type FROM content WHERE content_id='$content_id'";
        $db->execSqlUniqueRes($_sql, $_row, false);

        if ($_row == null) {
            throw new Exception(_("The content with the following id could not be found in the database: ") . $content_id);
        }

        $_contentType = $_row['content_type'];
        $_object = new $_contentType($content_id);

        return $_object;
    }

    /**
     * Get the list of available content type on the system
     *
     * @return array An array of class names
     *
     * @static
     * @access public
     */
    public static function getAvailableContentTypes()
    {
        // Init values
        $_contentTypes = array();
        $_useCache = false;
        $_cachedData = null;

        // Create new cache object with a lifetime of one week
        $_cache = new Cache("ContentClasses", "ClassFileCaches", 604800);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData("mixed");

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_contentTypes = $_cachedData;
            }
        }

        if (!$_useCache) {
            $_dir = WIFIDOG_ABS_FILE_PATH . "classes/Content";
            $_dirHandle = @opendir($_dir);

            if ($_dirHandle) {
                // Loop over the directory
                while (false !== ($_subDir = readdir($_dirHandle))) {
                    // Loop through sub-directories of Content
                    if ($_subDir != '.' && $_subDir != '..' && is_dir("{$_dir}/{$_subDir}")) {
                        // Only add directories containing corresponding initial Content class
                        if (is_file("{$_dir}/{$_subDir}/{$_subDir}.php")) {
                            $_contentTypes[] = $_subDir;
                        }
                    }
                }

                closedir($_dirHandle);
            } else {
                throw new Exception(_('Unable to open directory ') . $_dir);
            }

            // Cleanup PHP file extensions and sort the result array
            $_contentTypes = str_ireplace('.php', '', $_contentTypes);
            sort($_contentTypes);

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save results into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_contentTypes, "mixed");
            }
        }

        return $_contentTypes;
    }

    /**
     * Get all content
     *
     * Can be restricted to a given content type
     *
     * @param string $content_type Type of content
     *
     * @return mixed Requested content
     *
     * @static
     * @access public
     */
    public static function getAllContent($content_type = "")
    {
        // Define globals
        global $db;

        // Init values
        $_whereClause = "";
        $_rows = null;
        $_objects = array();

        if (!empty ($content_type)) {
            $content_type = $db->escapeString($content_type);
            $_whereClause = "WHERE content_type = '$content_type'";
        }

        $db->execSql("SELECT content_id FROM content $_whereClause", $_rows, false);

        if ($_rows) {
            foreach ($_rows as $_row) {
                $_objects[] = self::getObject($_row['content_id']);
            }
        }

        return $_objects;
    }

    /**
     * Get a flexible interface to generate new content objects
     *
     * @param string $user_prefix  A identifier provided by the programmer to
     *                             recognise it's generated html form
     * @param string $content_type If set, the created content will be of this
     *                             type, otherwise, the user will have to choose
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getNewContentUI($user_prefix, $content_type = null)
    {
        // Define globals
        global $db;

        // Init values
        $_html = "";

        $_availableContentTypes = self::getAvailableContentTypes();

        $_name = "get_new_content_{$user_prefix}_content_type";

        if (empty ($content_type)) {
            $_html .= _("Content type: ");
            $_i = 0;

            foreach ($_availableContentTypes as $_className) {
                $_tab[$_i][0] = $_className;
                $_tab[$_i][1] = $_className;
                $_i++;
            }

            $_html .= FormSelectGenerator::generateFromArray($_tab, 'TrivialLangstring', $_name, null, false);
        } else {
            if (false === array_search($content_type, $_availableContentTypes, true)) {
                throw new Exception(_("The following content type isn't valid: ") . $content_type);
            }

            $_html .= '<input type="hidden" name="' . $_name . '" value="' . $content_type . '">';
        }

        $_name = "get_new_content_{$user_prefix}_add";

        if ($content_type) {
            $_value = sprintf(_("Add a %s"), $content_type);
        } else {
            $_value = _("Add");
        }

        $_html .= '<input type="submit" name="' . $_name . '" value="' . $_value . '">';

        return $_html;
    }

    /**
     * Get the created content object, IF one was created OR get existing
     * content (depending on what the user clicked)
     *
     * @param string $user_prefix                A identifier provided by the
     *                                           programmer to recognise it's
     *                                           generated form
     * @param bool   $associate_existing_content If true it allows to get
     *                                           existing object
     *
     * @return object The Content object, or null if the user didn't create one
     *
     * @static
     * @access public
     */
    public static function processNewContentUI($user_prefix, $associate_existing_content = false)
    {
        // Init values
        $_object = null;

        if ($associate_existing_content == true) {
            $_name = "{$user_prefix}_add";
        } else {
            $_name = "get_new_content_{$user_prefix}_add";
        }

        if (!empty ($_REQUEST[$_name]) && $_REQUEST[$_name] == true) {
            if ($associate_existing_content == true) {
                $_name = "{$user_prefix}";
            } else {
                $_name = "get_new_content_{$user_prefix}_content_type";
            }

            /*
             * The result can be either a content type or a content ID
             * depending on the form (associate_existing_content or NOT)
             */
            $_contentUiResult = FormSelectGenerator::getResult($_name, null);

            if ($associate_existing_content == true) {
                $_object = self::getObject($_contentUiResult);
            } else {
                $_object = self::createNewObject($_contentUiResult);
            }
        }

        return $_object;
    }

    /**
     * Get a flexible interface to manage content linked to a node, a network
     * or anything else
     *
     * @param string $user_prefix            A identifier provided by the
     *                                       programmer to recognise it's
     *                                       generated HTML form
     * @param string $link_table             Table to link from
     * @param string $link_table_obj_key_col Column in linked table to match
     * @param string $link_table_obj_key     Key to be found in linked table
     * @param string $display_location       Location to be displayed
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getLinkedContentUI($user_prefix, $link_table, $link_table_obj_key_col, $link_table_obj_key, $display_location)
    {
        // Define globals
        global $db;

        // Init values
        $html = "";

        $link_table = $db->escapeString($link_table);
        $link_table_obj_key_col = $db->escapeString($link_table_obj_key_col);
        $link_table_obj_key = $db->escapeString($link_table_obj_key);
        $display_location = $db->escapeString($display_location);
        $name = "{$user_prefix}_display_location";

        $html .= "<input type='hidden' name='{$name}' value='{$display_location}'>\n";
        $current_content_sql = "SELECT * FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key' AND display_location='$display_location' ORDER BY subscribe_timestamp DESC";
        $rows = null;
        $db->execSql($current_content_sql, $rows, false);
        $html .= "<ul class='admin_section_list'>\n";
        if ($rows)
            foreach ($rows as $row)
            {
                $content = Content :: getObject($row['content_id']);
                $html .= "<li class='admin_section_list_item'>\n";
                $html .= "<div class='admin_section_data'>\n";
                $html .= $content->getListUI();
                $html .= "</div>\n";
                $html .= "<div class='admin_section_tools'>\n";
                $name = "{$user_prefix}_".$content->GetId()."_edit";
                $html .= "<input type='button' name='$name' value='"._("Edit")."' onClick='window.location.href = \"".GENERIC_OBJECT_ADMIN_ABS_HREF."?object_class=Content&action=edit&object_id=".$content->GetId()."\";'>\n";
                $name = "{$user_prefix}_".$content->GetId()."_erase";
                $html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
                $html .= "</div>\n";
                $html .= "</li>\n";
            }
        $html .= "<li class='admin_section_list_item'>\n";
        $name = "{$user_prefix}_new_existing";
        $html .= Content :: getSelectContentUI($name, "AND content_id NOT IN (SELECT content_id FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key')");
        $name = "{$user_prefix}_new_display_location";

        $html .= "<input type='hidden' name='{$name}' value='{$display_location}'>\n";
        $name = "{$user_prefix}_new_existing_submit";
        $html .= "<input type='submit' name='$name' value='"._("Add")."'>";
        $html .= "</li>\n";
        $html .= "<li class='admin_section_list_item'>\n";
        $html .= "Add new content: ";
        $name = "{$user_prefix}_new";
        $html .= self :: getNewContentUI($name, $content_type = null);
        $html .= "</li>\n";
        $html .= "</ul>\n";

        return $html;
    }

    /** Get the created Content object, IF one was created
     * OR Get existing content ( depending on what the user clicked )
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @param $associate_existing_content boolean if true allows to get existing
     * object
     * @return the Content object, or null if the user didn't greate one
     */
    static function processLinkedContentUI($user_prefix, $link_table, $link_table_obj_key_col, $link_table_obj_key)
    {
        global $db;
        $link_table = $db->escapeString($link_table);
        $link_table_obj_key_col = $db->escapeString($link_table_obj_key_col);
        $link_table_obj_key = $db->escapeString($link_table_obj_key);
        $name = "{$user_prefix}_display_location";
        $display_location = $db->escapeString($_REQUEST[$name]);
        $name = "{$user_prefix}_new_display_location";
        $display_location_new = $db->escapeString($_REQUEST[$name]);
        $current_content_sql = "SELECT * FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key' AND display_location='$display_location' ORDER BY subscribe_timestamp DESC";
        $rows = null;
        $db->execSql($current_content_sql, $rows, false);
        if ($rows)
            foreach ($rows as $row)
            {
                $content = Content :: getObject($row['content_id']);
                $content_id = $db->escapeString($content->getId());
                $name = "{$user_prefix}_".$content->GetId()."_erase";
                if (!empty ($_REQUEST[$name]))
                {
                    $sql = "DELETE FROM $link_table WHERE $link_table_obj_key_col='$link_table_obj_key' AND content_id = '$content_id'";
                    $db->execSqlUpdate($sql, false);
                }
            }

        $name = "{$user_prefix}_new_existing_submit";
        if (!empty ($_REQUEST[$name]))
        {
            $name = "{$user_prefix}_new_existing";
            $content = Content :: processSelectContentUI($name);
            if ($content)
            {
                $content_id = $db->escapeString($content->getId());
                $sql = "INSERT INTO $link_table (content_id, $link_table_obj_key_col, display_location) VALUES ('$content_id', '$link_table_obj_key', '$display_location_new');\n";
                $db->execSqlUpdate($sql, false);
            }
        }
        $name = "{$user_prefix}_new";
        $content = self :: processNewContentUI($name);
        if ($content)
        {
            $content_id = $db->escapeString($content->getId());
            $sql = "INSERT INTO $link_table (content_id, $link_table_obj_key_col, display_location) VALUES ('$content_id', '$link_table_obj_key', '$display_location_new');\n";
            $db->execSqlUpdate($sql, false);
        }

    }

    /**
     * Get an interface to pick content from all persistent content
     *
     * It either returns a select box or an extended table
     *
     * @param string $user_prefix             An identifier provided by the
     *                                        programmer to recognise it's
     *                                        generated HTML form
     * @param string $sql_additional_where    Addidional where conditions to
     *                                        restrict the candidate objects
     * @param bool   $show_persistant_content Defines if to list persistant
     *                                        content, only
     * @param string $order                   Order of output (default: by
     *                                        creation time)
     * @param string $type_interface          Type of interface:
     *                                          - "select": default, shows a
     *                                            select box
     *                                          - "table": showsa table with
     *                                            extended information
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getSelectContentUI($user_prefix, $sql_additional_where = null, $show_persistant_content = true, $order = "creation_timestamp", $type_interface = "select")
    {
        // Define globals
        global $db;

        // Init values
        $_html = '';
        $_retVal = array();
        $_contentRows = null;

        if ($type_interface != "table") {
            $_html .= _("Select existing Content: ")."\n";
        }

        $_name = "{$user_prefix}";

        if ($show_persistant_content) {
            $_sql = "SELECT * FROM content WHERE is_persistent=TRUE $sql_additional_where ORDER BY $order";
        } else {
            $_sql = "SELECT * FROM content $sql_additional_where ORDER BY $order";
        }

        $db->execSql($_sql, $_contentRows, false);

        if ($_contentRows != null) {
            $_i = 0;

            if ($type_interface == "table") {
                $_html .= "<table class='content_admin'>\n";
                $_html .= "<tr><th>" . _("Title") . "</th><th>" . _("Content type") . "</th><th>" . _("Description") . "</th><th></th></tr>\n";
            }

            foreach ($_contentRows as $_contentRow) {
                $_content = Content::getObject($_contentRow['content_id']);

                if (User::getCurrentUser()->isSuperAdmin() || $_content->isOwner(User::getCurrentUser())) {
                    if ($type_interface != "table") {
                        $_tab[$_i][0] = $_content->getId();
                        $_tab[$_i][1] = $_content->__toString() . " (" . get_class($_content) . ")";
                        $_i ++;
                    } else {
                        if (!empty($_contentRow['title'])) {
                            $_title = Content::getObject($_contentRow['title']);
                            $_titleUI = $_title->__toString();
                        } else {
                            $_titleUI = "";
                        }

                        if (!empty($_contentRow['description'])) {
                            $_description = Content::getObject($_contentRow['description']);
                            $_descriptionUI = $_description->__toString();
                        } else {
                            $_descriptionUI = "";
                        }

                        $_href = GENERIC_OBJECT_ADMIN_ABS_HREF . "?object_id={$_contentRow['content_id']}&object_class=Content&action=edit";
                        $_html .= "<tr><td>$_titleUI</td><td><a href='$_href'>{$_contentRow['content_type']}</a></td><td>$_descriptionUI</td>\n";

                        $_href = GENERIC_OBJECT_ADMIN_ABS_HREF . "?object_id={$_contentRow['content_id']}&object_class=Content&action=delete";
                        $_html .= "<td><a href='$_href'>" . _("Delete") . "</a></td>";

                        $_html .= "</tr>\n";
                    }
                }
            }

            if ($type_interface != "table") {
                $_html .= FormSelectGenerator::generateFromArray($_tab, null, $_name, null, false);
            } else {
                $_html .= "</table>\n";
            }
        } else {
            $_html .= "<div class='warningmsg'>" . _("Sorry, no content available in the database") . "</div>\n";
        }

        return $_html;
    }

    /** Get the selected Content object.
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the Content object
     */
    static function processSelectContentUI($user_prefix)
    {
        $name = "{$user_prefix}";
        if (!empty ($_REQUEST[$name]))
            return Content :: getObject($_REQUEST[$name]);
        else
            return null;
    }

    /** Get the true object type represented by this isntance
     * @return an array of class names */
    public function getObjectType()
    {
        return $this->content_type;
    }

    /**
     * Get content title
     * @return content a content sub-class
     */
    public function getTitle()
    {
        try
        {
            return self :: getObject($this->content_row['title']);
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get content description
     * @return content a content sub-class
     */
    public function getDescription()
    {
        try
        {
            return self :: getObject($this->content_row['description']);
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get content long description
     * @return content a content sub-class
     */
    public function getLongDescription()
    {
        try
        {
            return self :: getObject($this->content_row['long_description']);
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get content project info
     * @return content a content sub-class
     */
    public function getProjectInfo()
    {
        try
        {
            return self :: getObject($this->content_row['project_info']);
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get content sponsor info
     * @return content a content sub-class
     */
    public function getSponsorInfo()
    {
        try
        {
            return self :: getObject($this->content_row['sponsor_info']);
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /** Set the object type of this object
     * Note that after using this, the object must be re-instanciated to have the right type
     * */
    private function setContentType($content_type)
    {
        global $db;
        $content_type = $db->escapeString($content_type);
        $available_content_types = self :: getAvailableContentTypes();
        if (false === array_search($content_type, $available_content_types, true))
        {
            throw new Exception(_("The following content type isn't valid: ").$content_type);
        }
        $sql = "UPDATE content SET content_type = '$content_type' WHERE content_id='$this->id'";

        if (!$db->execSqlUpdate($sql, false))
        {
            throw new Exception(_("Update was unsuccessfull (database error)"));
        }

    }

    /** Check if a user is one of the owners of the object
     * @param $user The user to be added to the owners list
     * @param $is_author Optionnal, true or false.  Set to true if the user is one of the actual authors of the Content
     * @return true on success, false on failure */
    public function addOwner(User $user, $is_author = false)
    {
        global $db;
        $content_id = "'".$this->id."'";
        $user_id = "'".$db->escapeString($user->getId())."'";
        $is_author ? $is_author = 'TRUE' : $is_author = 'FALSE';
        $sql = "INSERT INTO content_has_owners (content_id, user_id, is_author) VALUES ($content_id, $user_id, $is_author)";

        if (!$db->execSqlUpdate($sql, false))
        {
            throw new Exception(_('Unable to insert the new Owner into database.'));
        }

        return true;
    }

    /** Remove an owner of the content
     * @param $user The user to be removed from the owners list
     */
    public function deleteOwner(User $user, $is_author = false)
    {
        global $db;
        $content_id = "'".$this->id."'";
        $user_id = "'".$db->escapeString($user->getId())."'";

        $sql = "DELETE FROM content_has_owners WHERE content_id=$content_id AND user_id=$user_id";

        if (!$db->execSqlUpdate($sql, false))
        {
            throw new Exception(_('Unable to remove the owner from the database.'));
        }

        return true;
    }

    /**
     * Indicates display logging status
     */
    public function getLoggingStatus()
    {
        return $this->is_logging_enabled;
    }

    /**
     * Sets display logging status
     */
    public function setLoggingStatus($status)
    {
        if (is_bool($status))
            $this->is_logging_enabled = $status;
    }

    /** Get the PHP timestamp of the last time this content was displayed
     * @param $user User, Optional, if present, restrict to the selected user
     * @param $node Node, Optional, if present, restrict to the selected node
     * @return PHP timestamp (seconds since UNIX epoch) if the content has been
     * displayed before, an empty string otherwise.
     */
    public function getLastDisplayTimestamp($user = null, $node = null)
    {
        global $db;
        $retval = '';
        $sql = "SELECT EXTRACT(EPOCH FROM last_display_timestamp) as last_display_unix_timestamp FROM content_display_log WHERE content_id='{$this->id}' \n";

        if ($user)
        {
            $user_id = $db->escapeString($user->getId());
            $sql .= " AND user_id = '{$user_id}' \n";
        }
        if ($node)
        {
            $node_id = $db->escapeString($node->getId());
            $sql .= " AND node_id = '{$node_id}' \n";
        }
        $sql .= " ORDER BY last_display_timestamp DESC ";
        $db->execSql($sql, $log_rows, false);
        if ($log_rows)
        {
            $retval = $log_rows[0]['last_display_unix_timestamp'];
        }

        return $retval;
    }

    /** Is this Content element displayable at this hotspot, many classer override this
     * @param $node Node, optionnal
     * @return true or false */
    public function isDisplayableAt($node)
    {
        return true;
    }

    /** Check if a user is one of the owners of the object
     * @param $user User object:  the user to be tested.
     * @return true if the user is a owner, false if he isn't of the user is null */
    public function isOwner($user)
    {
        global $db;
        $retval = false;
        if ($user != null)
        {
            $user_id = $db->escapeString($user->GetId());
            $sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id' AND user_id='$user_id'";
            $db->execSqlUniqueRes($sql, $content_owner_row, false);
            if ($content_owner_row != null)
            {
                $retval = true;
            }
        }

        return $retval;
    }
    /** Get the authors of the Content
     * @return null or array of User objects */
    public function getAuthors()
    {
        global $db;
        $retval = array ();
        $sql = "SELECT user_id FROM content_has_owners WHERE content_id='$this->id' AND is_author=TRUE";
        $db->execSqlUniqueRes($sql, $content_owner_row, false);
        if ($content_owner_row != null)
        {
            $user = User :: getObject($content_owner_row['user_id']);
            $retval[] = $user;
        }

        return $retval;
    }
    /** @see GenricObject
     * @return The id */
    public function getId()
    {
        return $this->id;
    }

    /** When a content object is set as trivial, it means that is is used merely to contain it's own data.  No title, description or other data will be set or displayed, during display or administration
     * @param $is_trivial true or false */
    public function setIsTrivialContent($is_trivial)
    {
        $this->is_trivial_content = $is_trivial;
    }

    /** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @return The HTML fragment for this interface */
    public function getUserUI($subclass_user_interface = null)
    {
        $html = '';
        $html .= "<div class='user_ui_main_outer'>\n";
        $html .= "<div class='user_ui_main_inner'>\n";
        $html .= "<div class='user_ui_object_class'>Content (".get_class($this)." instance)</div>\n";

        if (!empty ($this->content_row['title']))
        {
            $html .= "<div class='user_ui_title'>\n";
            $title = self :: getObject($this->content_row['title']);
            // If the content logging is disabled, all the children will inherit this property temporarly
            if ($this->getLoggingStatus() == false)
                $title->setLoggingStatus(false);
            $html .= $title->getUserUI();
            $html .= "</div>\n";
        }

        $html .= "<table><tr>\n";
        $html .= "<td>\n$subclass_user_interface</td>\n";

        $html .= "<td>\n";
        $authors = $this->getAuthors();
        if (count($authors) > 0)
        {
            $html .= "<div class='user_ui_authors'>\n";
            $html .= _("Author(s):");
            foreach ($authors as $user)
            {
                $html .= $user->getUsername()." ";
            }
            $html .= "</div>\n";
        }

        if (!empty ($this->content_row['description']))
        {
            $html .= "<div class='user_ui_description'>\n";
            $description = self :: getObject($this->content_row['description']);
            // If the content logging is disabled, all the children will inherit this property temporarly
            if ($this->getLoggingStatus() == false)
                $description->setLoggingStatus(false);
            $html .= $description->getUserUI();
            $html .= "</div>\n";
        }

        if (!empty ($this->content_row['project_info']) || !empty ($this->content_row['sponsor_info']))
        {
            if (!empty ($this->content_row['project_info']))
            {
                $html .= "<div class='user_ui_projet_info'>\n";
                $html .= "<b>"._("Project information:")."</b>";
                $project_info = self :: getObject($this->content_row['project_info']);
                // If the content logging is disabled, all the children will inherit this property temporarly
                if ($this->getLoggingStatus() == false)
                    $project_info->setLoggingStatus(false);
                $html .= $project_info->getUserUI();
                $html .= "</div>\n";
            }

            if (!empty ($this->content_row['sponsor_info']))
            {
                $html .= "<div class='user_ui_sponsor_info'>\n";
                $html .= "<b>"._("Project sponsor:")."</b>";
                $sponsor_info = self :: getObject($this->content_row['sponsor_info']);
                // If the content logging is disabled, all the children will inherit this property temporarly
                if ($this->getLoggingStatus() == false)
                    $sponsor_info->setLoggingStatus(false);
                $html .= $sponsor_info->getUserUI();
                $html .= "</div>\n";
            }
        }

        $html .= "</td>\n";
        $html .= "</tr></table>\n";

        $html .= "</div>\n";
        $html .= "</div>\n";
        $this->logContentDisplay();
        return $html;
    }

    /** Log that this content has just been displayed to the user.  Will only log if the user is logged in */
    private function logContentDisplay()
    {
        if ($this->getLoggingStatus() == true)
        {
            // DEBUG::
            //echo "Logging ".get_class($this)." :: ".$this->__toString()."<br>";
            $user = User :: getCurrentUser();
            $node = Node :: getCurrentNode();
            if ($user != null && $node != null)
            {
                $user_id = $user->getId();
                $node_id = $node->getId();
                global $db;

                $sql = "SELECT * FROM content_display_log WHERE user_id='$user_id' AND node_id='$node_id' AND content_id='$this->id'";
                $db->execSql($sql, $log_rows, false);
                if ($log_rows != null)
                {
                    $sql = "UPDATE content_display_log SET last_display_timestamp = NOW() WHERE user_id='$user_id' AND content_id='$this->id' AND node_id='$node_id'";
                }
                else
                {
                    $sql = "INSERT INTO content_display_log (user_id, content_id, node_id) VALUES ('$user_id', '$this->id', '$node_id')";
                }
                $db->execSqlUpdate($sql, false);
            }
        }
    }

    /** Retreives the list interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @return The HTML fragment for this interface */
    public function getListUI($subclass_list_interface = null)
    {
        $html = '';
        $html .= "<div class='list_ui_container'>\n";
        $html .= $this->__toString()." (".get_class($this).")\n";
        $html .= $subclass_list_interface;
        $html .= "</div>\n";
        return $html;
    }

    /**
     * Retreives the admin interface of this object. Anything that overrides
     * this method should call the parent method with it's output at the END of
     * processing.
     * @param string $subclass_admin_interface HTML content of the interface
     * element of a children.
     * @return string The HTML fragment for this interface.
     */
    public function getAdminUI($subclass_admin_interface = null) {
        global $db;

        $html = '';
        $html .= "<div class='admin_container'>\n";
        $html .= "<div class='admin_class'>Content (".get_class($this)." instance)</div>\n";

        if ($this->getObjectType() == 'Content') {
            // The object hasn't yet been typed.
            $html .= _("You must select a content type: ");
            $i = 0;

            foreach (self :: getAvailableContentTypes() as $classname) {
                $tab[$i][0] = $classname;
                $tab[$i][1] = $classname;
                $i ++;
            }

            $html .= FormSelectGenerator :: generateFromArray($tab, null, "content_".$this->id."_content_type", "Content", false);
        } else {
            if ($this->is_trivial_content == false) {
//                The next lines are a preview of a new suggested input mode
//                ==========================================================
//
//                // title
//                $html .= "<fieldset class='admin_section_container'>\n";
//                $html .= "<legend class='admin_section_title'>"._("Title:")."</legend>\n";
//                $html .= "<div class='admin_section_data'>\n";
//
//                if (empty ($this->content_row['title'])) {
//                    $html .= self :: getNewContentUI("title_{$this->id}_new");
//                    $html .= "</div>\n";
//                } else {
//                    $title = self :: getObject($this->content_row['title']);
//                    $html .= $title->getAdminUI("SMALL");
//                    $html .= "</div>\n";
//                    $html .= "<div class='admin_section_tools'>\n";
//                    $name = "content_".$this->id."_title_erase";
//                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
//                    $html .= "</div>\n";
//                }
//
//                $html .= "</fieldset>\n";

                /* title */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>"._("Title:")."</div>\n";
                $html .= "<div class='admin_section_data'>\n";
                if (empty ($this->content_row['title']))
                {
                    $html .= self :: getNewContentUI("title_{$this->id}_new");
                    $html .= "</div>\n";
                }
                else
                {
                    $title = self :: getObject($this->content_row['title']);
                    $html .= $title->getAdminUI();
                    $html .= "</div>\n";
                    $html .= "<div class='admin_section_tools'>\n";
                    $name = "content_".$this->id."_title_erase";
                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
                    $html .= "</div>\n";
                }
                $html .= "</div>\n";

                /* is_persistent */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>Is persistent (reusable and read-only)?: </div>\n";
                $html .= "<div class='admin_section_data'>\n";
                $name = "content_".$this->id."_is_persistent";
                $this->isPersistent() ? $checked = 'CHECKED' : $checked = '';
                $html .= "<input type='checkbox' name='$name' $checked>\n";
                $html .= "</div>\n";
                $html .= "</div>\n";

                /* description */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>"._("Description:")."</div>\n";
                $html .= "<div class='admin_section_data'>\n";
                if (empty ($this->content_row['description']))
                {
                    $html .= self :: getNewContentUI("description_{$this->id}_new");
                    $html .= "</div>\n";
                }
                else
                {
                    $description = self :: getObject($this->content_row['description']);
                    $html .= $description->getAdminUI();
                    $html .= "</div>\n";
                    $html .= "<div class='admin_section_tools'>\n";
                    $name = "content_".$this->id."_description_erase";
                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
                    $html .= "</div>\n";
                }
                $html .= "</div>\n";

                /* long description */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>"._("Long description:")."</div>\n";
                $html .= "<div class='admin_section_data'>\n";
                if (empty ($this->content_row['long_description']))
                {
                    $html .= self :: getNewContentUI("long_description_{$this->id}_new");
                    $html .= "</div>\n";
                }
                else
                {
                    $description = self :: getObject($this->content_row['long_description']);
                    $html .= $description->getAdminUI();
                    $html .= "</div>\n";
                    $html .= "<div class='admin_section_tools'>\n";
                    $name = "content_".$this->id."_long_description_erase";
                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
                    $html .= "</div>\n";
                }
                $html .= "</div>\n";

                /* project_info */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>"._("Information on this project:")."</div>\n";
                $html .= "<div class='admin_section_data'>\n";
                if (empty ($this->content_row['project_info']))
                {
                    $html .= self :: getNewContentUI("project_info_{$this->id}_new");
                    $html .= "</div>\n";
                }
                else
                {
                    $project_info = self :: getObject($this->content_row['project_info']);
                    $html .= $project_info->getAdminUI();
                    $html .= "</div>\n";
                    $html .= "<div class='admin_section_tools'>\n";
                    $name = "content_".$this->id."_project_info_erase";
                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
                    $html .= "</div>\n";
                }
                $html .= "</div>\n";

                /* sponsor_info */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<div class='admin_section_title'>"._("Sponsor of this project:")."</div>\n";
                $html .= "<div class='admin_section_data'>\n";
                if (empty ($this->content_row['sponsor_info']))
                {
                    $html .= self :: getNewContentUI("sponsor_info_{$this->id}_new");
                    $html .= "</div>\n";
                }
                else
                {
                    $sponsor_info = self :: getObject($this->content_row['sponsor_info']);
                    $html .= $sponsor_info->getAdminUI();
                    $html .= "</div>\n";
                    $html .= "<div class='admin_section_tools'>\n";
                    $name = "content_".$this->id."_sponsor_info_erase";
                    $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
                    $html .= "</div>\n";
                }
                $html .= "</div>\n";

                /* content_has_owners */
                $html .= "<div class='admin_section_container'>\n";
                $html .= "<span class='admin_section_title'>"._("Content owner list")."</span>\n";
                $html .= "<ul class='admin_section_list'>\n";

                global $db;
                $sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id'";
                $db->execSql($sql, $content_owner_rows, false);
                if ($content_owner_rows != null)
                {
                    foreach ($content_owner_rows as $content_owner_row)
                    {
                        $html .= "<li class='admin_section_list_item'>\n";
                        $html .= "<div class='admin_section_data'>\n";
                        $user = User :: getObject($content_owner_row['user_id']);

                        $html .= $user->getUserListUI();
                        $name = "content_".$this->id."_owner_".$user->GetId()."_is_author";
                        $html .= " Is content author? ";

                        $content_owner_row['is_author'] == 't' ? $checked = 'CHECKED' : $checked = '';
                        $html .= "<input type='checkbox' name='$name' $checked>\n";
                        $html .= "</div>\n";
                        $html .= "<div class='admin_section_tools'>\n";
                        $name = "content_".$this->id."_owner_".$user->GetId()."_remove";
                        $html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
                        $html .= "</div>\n";
                        $html .= "</li>\n";
                    }
                }

                $html .= "<li class='admin_section_list_item'>\n";
                $html .= "<div class='admin_section_data'>\n";
                $html .= User :: getSelectUserUI("content_{$this->id}_new_owner");
                $html .= "</div>\n";
                $html .= "<div class='admin_section_tools'>\n";
                $name = "content_{$this->id}_add_owner_submit";
                $value = _("Add owner");
                $html .= "<input type='submit' name='$name' value='$value'>";
                $html .= "</div>\n";
                $html .= "</li>\n";
                $html .= "</ul>\n";
                $html .= "</div>\n";
            }
        }
        $html .= $subclass_admin_interface;
        $html .= "</div>\n";
        return $html;
    }
    /** Process admin interface of this object.  When an object overrides this method, they should call the parent processAdminUI at the BEGINING of processing.

    */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
        {
            global $db;
            if ($this->getObjectType() == 'Content') /* The object hasn't yet been typed */
            {
                $content_type = FormSelectGenerator :: getResult("content_".$this->id."_content_type", "Content");
                $this->setContentType($content_type);
            }
            else
                if ($this->is_trivial_content == false)
                {
                    /* title */
                    if (empty ($this->content_row['title']))
                    {
                        $title = self :: processNewContentUI("title_{$this->id}_new");
                        if ($title != null)
                        {
                            $title_id = $title->GetId();
                            $db->execSqlUpdate("UPDATE content SET title = '$title_id' WHERE content_id = '$this->id'", FALSE);
                        }
                    }
                    else
                    {
                        $title = self :: getObject($this->content_row['title']);
                        $name = "content_".$this->id."_title_erase";
                        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
                        {
                            $db->execSqlUpdate("UPDATE content SET title = NULL WHERE content_id = '$this->id'", FALSE);
                            $title->delete($errmsg);
                        }
                        else
                        {
                            $title->processAdminUI();
                        }
                    }

                    /* is_persistent */
                    $name = "content_".$this->id."_is_persistent";
                    !empty ($_REQUEST[$name]) ? $this->setIsPersistent(true) : $this->setIsPersistent(false);

                    /* description */
                    if (empty ($this->content_row['description']))
                    {
                        $description = self :: processNewContentUI("description_{$this->id}_new");
                        if ($description != null)
                        {
                            $description_id = $description->GetId();
                            $db->execSqlUpdate("UPDATE content SET description = '$description_id' WHERE content_id = '$this->id'", FALSE);
                        }
                    }
                    else
                    {
                        $description = self :: getObject($this->content_row['description']);
                        $name = "content_".$this->id."_description_erase";
                        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
                        {
                            $db->execSqlUpdate("UPDATE content SET description = NULL WHERE content_id = '$this->id'", FALSE);
                            $description->delete($errmsg);
                        }
                        else
                        {
                            $description->processAdminUI();
                        }
                    }

                    /* long description */
                    if (empty ($this->content_row['long_description']))
                    {
                        $long_description = self :: processNewContentUI("long_description_{$this->id}_new");
                        if ($long_description != null)
                        {
                            $long_description_id = $long_description->GetId();
                            $db->execSqlUpdate("UPDATE content SET long_description = '$long_description_id' WHERE content_id = '$this->id'", FALSE);
                        }
                    }
                    else
                    {
                        $long_description = self :: getObject($this->content_row['long_description']);
                        $name = "content_".$this->id."_long_description_erase";
                        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
                        {
                            $db->execSqlUpdate("UPDATE content SET long_description = NULL WHERE content_id = '$this->id'", FALSE);
                            $long_description->delete($errmsg);
                        }
                        else
                        {
                            $long_description->processAdminUI();
                        }
                    }

                    /* project_info */
                    if (empty ($this->content_row['project_info']))
                    {
                        $project_info = self :: processNewContentUI("project_info_{$this->id}_new");
                        if ($project_info != null)
                        {
                            $project_info_id = $project_info->GetId();
                            $db->execSqlUpdate("UPDATE content SET project_info = '$project_info_id' WHERE content_id = '$this->id'", FALSE);
                        }
                    }
                    else
                    {
                        $project_info = self :: getObject($this->content_row['project_info']);
                        $name = "content_".$this->id."_project_info_erase";
                        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
                        {
                            $db->execSqlUpdate("UPDATE content SET project_info = NULL WHERE content_id = '$this->id'", FALSE);
                            $project_info->delete($errmsg);
                        }
                        else
                        {
                            $project_info->processAdminUI();
                        }
                    }

                    /* sponsor_info */
                    if (empty ($this->content_row['sponsor_info']))
                    {
                        $sponsor_info = self :: processNewContentUI("sponsor_info_{$this->id}_new");
                        if ($sponsor_info != null)
                        {
                            $sponsor_info_id = $sponsor_info->GetId();
                            $db->execSqlUpdate("UPDATE content SET sponsor_info = '$sponsor_info_id' WHERE content_id = '$this->id'", FALSE);
                        }
                    }
                    else
                    {
                        $sponsor_info = self :: getObject($this->content_row['sponsor_info']);
                        $name = "content_".$this->id."_sponsor_info_erase";
                        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
                        {
                            $db->execSqlUpdate("UPDATE content SET sponsor_info = NULL WHERE content_id = '$this->id'", FALSE);
                            $sponsor_info->delete($errmsg);
                        }
                        else
                        {
                            $sponsor_info->processAdminUI();
                        }
                    }
                    /* content_has_owners */
                    $sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id'";
                    $db->execSql($sql, $content_owner_rows, false);
                    if ($content_owner_rows != null)
                    {
                        foreach ($content_owner_rows as $content_owner_row)
                        {
                            $user = User :: getObject($content_owner_row['user_id']);
                            $user_id = $user->getId();
                            $name = "content_".$this->id."_owner_".$user->GetId()."_remove";
                            if (!empty ($_REQUEST[$name]))
                            {
                                $this->deleteOwner($user);
                            }
                            else
                            {
                                $name = "content_".$this->id."_owner_".$user->GetId()."_is_author";
                                $content_owner_row['is_author'] == 't' ? $is_author = true : $is_author = false;
                                !empty ($_REQUEST[$name]) ? $should_be_author = true : $should_be_author = false;
                                if ($is_author != $should_be_author)
                                {
                                    $should_be_author ? $is_author_sql = 'TRUE' : $is_author_sql = 'FALSE';
                                    $sql = "UPDATE content_has_owners SET is_author=$is_author_sql WHERE content_id='$this->id' AND user_id='$user_id'";

                                    if (!$db->execSqlUpdate($sql, false))
                                    {
                                        throw new Exception(_('Unable to set as author in the database.'));
                                    }

                                }

                            }
                        }
                    }
                    $user = User :: processSelectUserUI("content_{$this->id}_new_owner");
                    $name = "content_{$this->id}_add_owner_submit";
                    if (!empty ($_REQUEST[$name]) && $user != null)
                    {
                        $this->addOwner($user);
                    }

                }
            $this->refresh();
        }
    }

    /**
     * Tell if a given user is already subscribed to this content
     * @param User the given user
     * @return boolean
     */
    public function isUserSubscribed(User $user)
    {
        global $db;
        $sql = "SELECT content_id FROM user_has_content WHERE user_id = '{$user->getId()}' AND content_id = '{$this->getId()}';";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row)
            return true;
        else
            return false;
    }

    /** Subscribe to the project
     * @return true on success, false on failure */
    public function subscribe(User $user)
    {
        return $user->addContent($this);
    }
    /** Unsubscribe to the project
     * @return true on success, false on failure */
    public function unsubscribe(User $user)
    {
        return $user->removeContent($this);
    }

    /** Persistent (or read-only) content is meant for re-use.  It will not be deleted when the delete() method is called.  When a containing element (ContentGroup, ContentGroupElement) is deleted, it calls delete on all the content it includes.  If the content is persistent, only the association will be removed.
    * @return true or false */
    public function isPersistent()
    {
        if ($this->content_row['is_persistent'] == 't')
        {
            $retval = true;
        }
        else
        {
            $retval = false;
        }
        return $retval;
    }

    /** Set if the content group is persistent
     * @param $is_locative_content true or false
     * */
    public function setIsPersistent($is_persistent)
    {
        if ($is_persistent != $this->isPersistent()) /* Only update database if there is an actual change */
        {
            $is_persistent ? $is_persistent_sql = 'TRUE' : $is_persistent_sql = 'FALSE';

            global $db;
            $db->execSqlUpdate("UPDATE content SET is_persistent = $is_persistent_sql WHERE content_id = '$this->id'", false);
            $this->refresh();
        }

    }

    /** Reloads the object from the database.  Should normally be called after a set operation.
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope */
    private function refresh()
    {
        $this->__construct($this->id);
    }

    /**
     * @see GenericObject
     * @internal Persistent content will not be deleted
     */
    public function delete(& $errmsg)
    {
        $retval = false;
        if ($this->isPersistent())
        {
            $errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
        }
        else
        {
            global $db;
            if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
            {
                $sql = "DELETE FROM content WHERE content_id='$this->id'";
                $db->execSqlUpdate($sql, false);
                $retval = true;
            }
            else
            {
                $errmsg = _("Access denied (not owner of content)");
            }
        }
        return $retval;
    }

} // End class

/* This allows the class to enumerate it's children properly */
$class_names = Content :: getAvailableContentTypes();

foreach ($class_names as $class_name) {
    /**
     * Load requested content class
     */
    require_once('classes/Content/' . $class_name . '/' . $class_name . '.php');
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>