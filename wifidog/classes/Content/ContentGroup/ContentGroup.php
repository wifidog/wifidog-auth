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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load ContentGroupElement class
 */
require_once ('classes/Content/ContentGroup/ContentGroupElement.php');
require_once ('classes/ContentTypeFilter.php');

/**
 * A generic content group
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class ContentGroup extends Content {

    private $CONTENT_ORDERING_MODES = array (
        'RANDOM' => "Pick content elements randomly",
        'PSEUDO_RANDOM' => "Pick content elements randomly, but not twice until all elements have been seen",
        'SEQUENTIAL' => "Pick content elements in sequential order"
    );
    private $CONTENT_CHANGES_ON_MODES = array (
        'ALWAYS' => "Content always rotates",
        'NEXT_DAY' => "Content rotates once per day",
        'NEXT_LOGIN' => "Content rotates once per session",
        'NEXT_NODE' => "Content rotates each time you change node",
        'NEVER' => "Content never rotates.  Usefull when showing all elements simultaneously in a specific order."
    );
    private $ALLOW_REPEAT_MODES = array (
        'YES' => "Content can be shown more than once",
        'NO' => "Content can only be shown once",
        'ONCE_PER_NODE' => "Content can be shown more than once, but not at the same node"
    );

    // is_expandable is ONLY for internal use, it use normally only set by the constructor
    private $is_expandable = true;
    // this is the actual publicly available status ( so if is_expandable == true it CANNOT be true )
    private $expand_status = false;
    private $temporary_display_num_elements;
    private $display_elements;
    private $content_selection_mode;
    private $content_group_row;
    /** ContentTypeFilter object */
    protected $allowed_content_types;

    protected function __construct($content_id) {

        $db = AbstractDb :: getObject();

        // Init values
        $row = null;

        parent :: __construct($content_id);

        $content_id = $db->escapeString($content_id);

        $sql = "SELECT * FROM content_group WHERE content_group_id='$content_id'";
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            /*Since the parent Content exists, the necessary data in content_group had not yet been created */
            $sql = "INSERT INTO content_group (content_group_id) VALUES ('$content_id')";
            $db->execSqlUpdate($sql, false);
            $sql = "SELECT * FROM content_group WHERE content_group_id='$content_id'";
            $db->execSqlUniqueRes($sql, $row, false);
            if ($row == null) {
                throw new Exception(_("The content with the following id could not be found in the database: ") . $content_id);
            }

        }

        $this->content_group_row = $row;

        // These are for internal use only ( private and protected methods ) for dealing with expanding content
        $this->setTemporaryDisplayNumElements(null);
    }

    /** Set the allowed content types for the group,
    * @param $allowed_content_types ContentTypeFilter*/
    public function setAllowedContentTypes(ContentTypeFilter $allowed_content_types) {
        $this->allowed_content_types = $allowed_content_types;

    }

    /** In what order is the content displayed to the user
    * @return string, a key of CONTENT_SELECTION_MODES */
    public function getContentOrderingMode() {
        return $this->content_group_row['content_ordering_mode'];
    }

    /** In what order is the content displayed to the user
     * @param $content_ordering_mode One of the CONTENT_ORDERING_MODES constants defined in the class
     * @return true if successfull
     * */
    protected function setContentOrderingMode($content_ordering_mode, & $errormsg = null) {
        $retval = false;
        if (isset ($this->CONTENT_ORDERING_MODES[$content_ordering_mode]) && $content_ordering_mode != $this->getContentOrderingMode()) /* Only update database if the mode is valid and there is an actual change */ {
            $db = AbstractDb :: getObject();
            $content_ordering_mode = $db->escapeString($content_ordering_mode);
            $db->execSqlUpdate("UPDATE content_group SET content_ordering_mode = '$content_ordering_mode' WHERE content_group_id = '$this->id'", false);
            $this->refresh();
            $retval = true;
        }
        elseif (!isset ($this->CONTENT_ORDERING_MODES[$content_ordering_mode])) {
            $errormsg = _("Invalid content selection mode (must be part of CONTENT_ORDERING_MODES)");
            $retval = false;
        } else {
            /* Successfull, but nothing modified */
            $retval = true;
        }
        return $retval;
    }

    /** When does the content rotate?
    * @return string, a key of CONTENT_SELECTION_MODES */
    public function getContentChangesOnMode() {
        return $this->content_group_row['content_changes_on_mode'];
    }

    /** When does the content rotate?
     * @param $content_changes_on_mode One of the content_changes_on_modeS constants defined in the class
     * @return true if successfull
     * */
    protected function setContentChangesOnMode($content_changes_on_mode, & $errormsg = null) {
        $retval = false;
        if (isset ($this->CONTENT_CHANGES_ON_MODES[$content_changes_on_mode]) && $content_changes_on_mode != $this->getContentChangesOnMode()) /* Only update database if the mode is valid and there is an actual change */ {
            $db = AbstractDb :: getObject();
            $content_changes_on_mode = $db->escapeString($content_changes_on_mode);
            $db->execSqlUpdate("UPDATE content_group SET content_changes_on_mode = '$content_changes_on_mode' WHERE content_group_id = '$this->id'", false);
            $this->refresh();
            $retval = true;
        }
        elseif (!isset ($this->CONTENT_CHANGES_ON_MODES[$content_changes_on_mode])) {
            $errormsg = _("Invalid content selection mode (must be part of CONTENT_CHANGES_ON_MODES)");
            $retval = false;
        } else {
            /* Successfull, but nothing modified */
            $retval = true;
        }
        return $retval;
    }

    /** Can the same content be shown twice
     * @return 'YES', 'NO', 'ONCE_PER_NODE' */
    public function getAllowRepeat() {
        return $this->content_group_row['allow_repeat'];
    }

    /** When does the content rotate?
     * @param $allow_repeat One of the allow_repeatS constants defined in the class
     * @return true if successfull
     * */
    protected function setAllowRepeat($allow_repeat, & $errormsg = null) {
        $retval = false;
        if (isset ($this->ALLOW_REPEAT_MODES[$allow_repeat]) && $allow_repeat != $this->getAllowRepeat()) /* Only update database if the mode is valid and there is an actual change */ {
            $db = AbstractDb :: getObject();
            $allow_repeat = $db->escapeString($allow_repeat);
            $db->execSqlUpdate("UPDATE content_group SET allow_repeat = '$allow_repeat' WHERE content_group_id = '$this->id'", false);
            $this->refresh();
            $retval = true;
        }
        elseif (!isset ($this->ALLOW_REPEAT_MODES[$allow_repeat])) {
            $errormsg = _("Invalid content selection mode (must be part of ALLOW_REPEAT_MODES)");
            $retval = false;
        } else {
            /* Successfull, but nothing modified */
            $retval = true;
        }
        return $retval;
    }

    /** How many element should be picked for display at once?
    * @return integer */
    public function getDisplayNumElements() {
        if ($this->temporary_display_num_elements == null)
            return $this->content_group_row['display_num_elements'];
        else
            return $this->temporary_display_num_elements;
    }

    /** How many element should be picked for display at once?
    * @param $display_num_elements integer, must be greater than zero.
    * @return true if successfull
    * */
    protected function setDisplayNumElements($display_num_elements, & $errormsg = null) {
        $retval = false;
        if (($display_num_elements > 0) && $display_num_elements != $this->getDisplayNumElements()) /* Only update database if the mode is valid and there is an actual change */ {
            $db = AbstractDb :: getObject();
            $display_num_elements = $db->escapeString($display_num_elements);
            $db->execSqlUpdate("UPDATE content_group SET display_num_elements = '$display_num_elements' WHERE content_group_id = '$this->id'", false);
            $this->refresh();
            $retval = true;
        }
        elseif ($display_num_elements <= 0) {
            $errormsg = _("You must display at least one element");
            $retval = false;
        } else {
            /* Successfull, but nothing modified */
            $retval = true;
        }
        return $retval;
    }

    /**
     * This will a temporary limit ( NOT ACTUALLY STORED IN DATABASE )
     * Use getDisplayNumElements to get the number of elements that can be shown
     * at once
     */
    private function setTemporaryDisplayNumElements($temporary_num_elements) {
        $this->temporary_display_num_elements = $temporary_num_elements;
    }

    public function getAdminUI($subclass_admin_interface = null, $title = null) {
        $html = '';
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . sprintf(_("%s configuration"), get_class($this)) . "</legend>\n";

        /* content_ordering_mode */
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("In what order should the content displayed?") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "content_group_" . $this->id . "_content_ordering_mode";
        $html .= FormSelectGenerator :: generateFromKeyLabelArray($this->CONTENT_ORDERING_MODES, $this->getContentOrderingMode(), $name, null, false);
        $html .= "</div>\n";
        $html .= "</li>\n";

        /*content_changes_on_mode */
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("When does the content rotate?") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "content_group_" . $this->id . "_content_changes_on_mode";
        $html .= FormSelectGenerator :: generateFromKeyLabelArray($this->CONTENT_CHANGES_ON_MODES, $this->getContentChangesOnMode(), $name, null, false);
        $html .= "</div>\n";
        $html .= "</li>\n";

        /* allow_repeat*/
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Can content be shown more than once to the same user?") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "content_group_" . $this->id . "_allow_repeat";
        $html .= FormSelectGenerator :: generateFromKeyLabelArray($this->ALLOW_REPEAT_MODES, $this->getAllowRepeat(), $name, null, false);
        $html .= "</div>\n";
        $html .= "</li>\n";

        /*display_num_elements*/
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . ("Pick how many elements for each display?") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "content_group_" . $this->id . "_display_num_elements";
        $value = $this->getDisplayNumElements();
        $html .= "<input type='text' size='2' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        /* Subclass UI */
        $html .= $subclass_admin_interface;

        $html .= "</fieldset>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . sprintf(_("%s display element list"), get_class($this)) . "</legend>\n";

        /* content_group_element*/
        $name = "content_group_" . $this->id . "_show_expired_elements_request";
        if (empty ($_REQUEST[$name])) {
            $showExpired = false;
            $additionalWhere = "AND (valid_until_timestamp IS NULL OR valid_until_timestamp >= CURRENT_TIMESTAMP) \n";
            $title_str = _("Show expired group elements");
            $html .= "<a name='group_select' onclick=\"document.getElementById('$name').value='" . !$showExpired . "';document.generic_object_form.submit();\">{$title_str}</a>\n";

        } else {
            $showExpired = true;
            $additionalWhere = null;
            $title_str = _("Hide expired group elements");
            $html .= "<a name='group_select' onclick=\"document.getElementById('$name').value='" . !$showExpired . "';document.generic_object_form.submit();\">{$title_str}</a>\n";

        }
        $html .= "<input type='hidden' name='$name' id='$name' value='$showExpired'>\n";
        $name = "content_group_" . $this->id . "_expired_elements_shown";
        $html .= "<input type='hidden' name='$name' id='$name' value='$showExpired'>\n";
        
        $html .= "<ul class='admin_element_list'>\n";
        foreach ($this->getElements($additionalWhere) as $element) {
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= $element->getAdminUI(null, sprintf(_("%s %d"), get_class($element), $element->getDisplayOrder()));
            $html .= "<div class='admin_element_tools'>\n";
            $name = "content_group_" . $this->id . "_element_" . $element->GetId() . "_erase";
            $html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s %d"), get_class($element), $element->getDisplayOrder()) . "'>";
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= self :: getNewContentUI("content_group_{$this->id}_new_element", $this->allowed_content_types);
        $html .= "</li>\n";
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= self :: getSelectExistingContentUI("content_group_{$this->id}_existing_element", "AND content_id != '$this->id' AND is_persistent=TRUE", $this->allowed_content_types);
        $html .= "</li>\n";
        $html .= "</ul>\n";
        $html .= "</fieldset>\n";
        $html .= "</li>\n";
        return parent :: getAdminUI($html, $title);
    }

    function processAdminUI() {
        // Init values
        $errmsg = null;

        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();

            /* content_ordering_mode */
            $name = "content_group_" . $this->id . "_content_ordering_mode";
            $this->setContentOrderingMode(FormSelectGenerator :: getResult($name, null));

            /*content_changes_on_mode */
            $name = "content_group_" . $this->id . "_content_changes_on_mode";
            $this->setContentChangesOnMode(FormSelectGenerator :: getResult($name, null));

            /* allow_repeat*/
            $name = "content_group_" . $this->id . "_allow_repeat";
            $this->setAllowRepeat(FormSelectGenerator :: getResult($name, null));

            /*display_num_elements*/
            $name = "content_group_" . $this->id . "_display_num_elements";
            $this->setDisplayNumElements($_REQUEST[$name]);

            /* content_group_element */
         $name = "content_group_" . $this->id . "_expired_elements_shown";
        if (empty ($_REQUEST[$name])) {
            $additionalWhere = "AND (valid_until_timestamp IS NULL OR valid_until_timestamp >= CURRENT_TIMESTAMP) \n";
        } else {
            $additionalWhere = null;
       }
            foreach ($this->getElements($additionalWhere) as $element) {
                $name = "content_group_" . $this->id . "_element_" . $element->GetId() . "_erase";
                if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                    $element->delete($errmsg);
                } else {
                    $element->processAdminUI();
                }
            }

            // The two following calls will either add a new element or add an existing one ( depending on what button the user clicked
            /* We explicitely call the ContentGroupElement version of processNewContentUI */
            ContentGroupElement :: processNewContentUI("content_group_{$this->id}_new_element", $this);
            // Last parameters allows for existing content ( if any was selected )
            ContentGroupElement :: processNewContentUI("content_group_{$this->id}_existing_element", $this, true);
        }
    }

    /** Is this Content element displayable at this hotspot
     * @param $node Node, optionnal
     * @return true or false */
    public function isDisplayableAt($node) {
        $old_curent_node = Node :: getCurrentNode();
        Node :: setCurrentNode($node);

        if (count($this->getDisplayElements()) > 0) {
            $retval = true;
        } else {
            $retval = false;
        }

        if ($old_curent_node != null) {
            Node :: setCurrentNode($old_curent_node);
        }

        return $retval;
    }

    /**Get the next element or elements to be displayed, depending on the display mode
    * @return an array of ContentGroupElement or an empty arrray */
    function getDisplayElements() {
        //This function is very expensive, cache the results
        if (!is_array($this->display_elements)) {

            $db = AbstractDb :: getObject();

            // Init values
            $retval = array ();
            $user = User :: getCurrentUser();
            $redisplay_rows = null;
            $last_order_row = null;
            $element_rows = null;

            if ($user) {
                $user_id = $user->getId();
            } else {
                $user_id = '';
            }
            $node = Node :: getCurrentNode();
            if ($node) {
                $node_id = $node->getId();
            } else {
                $node_id = '';
            }
            $display_num_elements = $this->getDisplayNumElements();
            /*  'ALWAYS' => "Content always rotates"
             *  'NEXT_DAY' => "Content rotates once per day"
             *  'NEXT_LOGIN' => "Content rotates once per session"
             *  'NEXT_NODE' => "Content rotates each time you change node"
             *  'NEVER' => "Content never rotates" */
            $content_changes_on_mode = $this->getContentChangesOnMode();

            $sql_time_restrictions = " AND (valid_from_timestamp IS NULL OR valid_from_timestamp <= CURRENT_TIMESTAMP) AND (valid_until_timestamp IS NULL OR valid_until_timestamp >= CURRENT_TIMESTAMP) \n";
            /** First, find if we have content to display again because we haven't passed the rotation period */
            $redisplay_objects = array ();
            $sql_redisplay = null;
            if ($content_changes_on_mode != 'ALWAYS' && $content_changes_on_mode != 'NEVER') {
                $sql_redisplay .= "SELECT content_group_element_id FROM content_group_element \n";
                $sql_redisplay .= "JOIN content_display_log ON (content_group_element_id=content_id) \n";
                $sql_redisplay .= " WHERE content_group_id='$this->id' \n";
                $sql_redisplay .= $sql_time_restrictions;

                if ($content_changes_on_mode == 'NEXT_DAY') {
                    $sql_redisplay .= "AND date_trunc('day', last_display_timestamp) = date_trunc('day', CURRENT_DATE) \n";
                }
                if ($content_changes_on_mode == 'NEXT_LOGIN') {
                    /**@todo Must fix, this will fail if the user never really connected from a hotspot... */
                    $sql_redisplay .= "AND last_display_timestamp > (SELECT timestamp_in FROM connections WHERE user_id='$user_id' ORDER BY timestamp_in DESC LIMIT 1) \n";
                }
                if ($content_changes_on_mode == 'NEXT_NODE') {
                    /** We find the close time of the last connection from another node */
                    $sql_redisplay .= "AND last_display_timestamp > (SELECT timestamp_out FROM connections WHERE user_id='$user_id' AND node_id != '$node_id' ORDER BY timestamp_in DESC LIMIT 1) \n";
                }
                /* There usually won't be more than one, but if there is, we want the most recents */
                $sql_redisplay .= " ORDER BY last_display_timestamp DESC ";
                $db->execSql($sql_redisplay, $redisplay_rows, false);
                $redisplay_objects = array ();
                if ($redisplay_rows != null) {
                    foreach ($redisplay_rows as $redisplay_row) {
                        $object = self :: getObject($redisplay_row['content_group_element_id']);
                        if ($object->isDisplayableAt(Node :: GetCurrentNode()) == true) /** Only content available at this hotspot are considered */
                            {
                            $redisplay_objects[] = $object;
                        }
                    }
                }
                /* Pick the proper number of elements to be re-displayed */
                $redisplay_objects = array_slice($redisplay_objects, 0, $display_num_elements);

            }

            $new_objects = array ();
            if (count($redisplay_objects) < $display_num_elements) {
                /* There aren't enough elements to redisplay, We need new content */

                $sql_base = "SELECT content_group_element_id FROM content_group_element WHERE content_group_id='$this->id' \n";
                $sql_base .= $sql_time_restrictions;
                $sql = $sql_base;

                /*'YES' => "Content can be shown more than once", 'NO' => "Content can only be shown once", 'ONCE_PER_NODE' => "Content can be shown more than once, but not at the same node"*/
                $allow_repeat = $this->getAllowRepeat();
                if ($allow_repeat == 'NO') {
                    $sql_repeat = "AND content_group_element_id NOT IN (SELECT content_id FROM content_display_log WHERE user_id = '$user_id') \n";
                }
                elseif ($allow_repeat == 'ONCE_PER_NODE') {
                    $sql_repeat = "AND content_group_element_id NOT IN (SELECT content_id FROM content_display_log WHERE user_id = '$user_id' AND  node_id = '$node_id') \n";
                } else {
                    $sql_repeat = null;
                }
                $sql .= $sql_repeat;
                if ($sql_redisplay) {
                    //We don't want the same content twice...
                    $sql_repeat_redisplay = " AND content_group_element_id NOT IN ($sql_redisplay) \n";
                    $sql .= $sql_repeat_redisplay;
                }

                $content_ordering_mode = $this->getContentOrderingMode();
                if ($content_ordering_mode == 'SEQUENTIAL') {
                    $order_by = ' ORDER BY display_order ';
                    //Find the last content displayed
                    $sql_last_order = "SELECT display_order FROM content_group_element \n";
                    $sql_last_order .= "JOIN content_display_log ON (content_group_element_id=content_id) \n";
                    $sql_last_order .= " WHERE content_group_id='$this->id' \n";
                    $sql_last_order .= " AND user_id='$user_id' \n";

                    $sql_last_order .= " ORDER BY last_display_timestamp DESC LIMIT 1";
                    $db->execSqlUniqueRes($sql_last_order, $last_order_row, false);
                    if ($last_order_row['display_order'] != null) {
                        $last_order = $last_order_row['display_order'];
                    } else {
                        $last_order = 0;
                    }
                } else {
                    $order_by = ' ';
                    $last_order = 0;
                }
                $sql .= $order_by;

                $element_rows = null;
                if ($content_ordering_mode == 'PSEUDO_RANDOM') {
                    //Special case, first get only the rows that haven't been displayed before'
                    $sql_no_repeat = " AND content_group_element_id NOT IN (SELECT content_id FROM content_display_log WHERE user_id = '$user_id') \n";
                    $db->execSql($sql_base . $sql_no_repeat, $element_rows, false);
                }
                //Normal case, or there wasn't any undisplayed content in PSEUDO_RANDOM
                if ($element_rows == null) {
                    $db->execSql($sql, $element_rows, false);
                }
                if ($element_rows == null) {
                    $element_rows = array ();
                }
                foreach ($element_rows as $element_row) {
                    $object = self :: getObject($element_row['content_group_element_id']);
                    if ($object->isDisplayableAt(Node :: GetCurrentNode()) == true) /** Only content available at this hotspot are considered */
                        {
                        $new_objects[] = $object;
                    }
                }

                if ($content_ordering_mode == 'RANDOM' || $content_ordering_mode == 'PSEUDO_RANDOM') {
                    shuffle($new_objects);
                }
                elseif ($content_ordering_mode == 'SEQUENTIAL') {
                    foreach ($new_objects as $object) {
                        if ($object->getDisplayOrder() <= $last_order) {
                            array_push($new_objects, array_shift($new_objects));
                            //echo " Pushed ".$object->getDisplayOrder();
                        }
                    }
                }

                /** Pick the proper number of elements */
                $num_to_pick = $display_num_elements -count($redisplay_objects);
                $new_objects = array_slice($new_objects, 0, $num_to_pick);
            }
            
            /*echo "<pre>Redisplay: ";
            print_r($redisplay_objects);
            echo "New objects: ";
            print_r($new_objects);
            echo "</pre>";*/
            
            $retval = array_merge($new_objects, $redisplay_objects);
            //echo count($retval).' returned <br>';
            $this->display_elements = $retval;
        }
        return $this->display_elements;
    }

    /**
     * This attribute is for internal use ( to tell if a certain class could be
     * expanded )
     * @param $status boolean
     */
    protected function setIsExpandable($status) {
        if (is_bool($status))
            $this->is_expandable = $status;
    }

    /**
     * Tells if this object could be expanded
     */
    protected function isExpandable() {
        return $this->is_expandable;
    }

    /**
     * Will expand content ONLY if allowed by isExpandable (which is protected)
     * @param $status boolean
     */
    public function setExpandStatus($status) {
        if ($this->isExpandable() && is_bool($status)) {
            //TODO: Try to find a better solution to this problem...
            if ($status == true)
                $this->setTemporaryDisplayNumElements(3000);
            else
                $this->setTemporaryDisplayNumElements(null);
            $this->expand_status = $status;
        }
    }

    /**
     * Get the expand status
     *
     * WARNING
     * NON expandable contents ie PatternLanguage will NEVER return true
     */
    public function getExpandStatus() {
        if ($this->expand_status == null)
            return false;
        return $this->expand_status;
    }

    /** This function will be called by MainUI for each Content BEFORE any getUserUI function is called to allow two pass Content display.
     * Two pass Content display allows such things as modyfying headers, title, creating content type that accumulate content from other pieces (like RSS feeds)
     * @return null
     */
    public function prepareGetUserUI() {
        $display_elements = $this->getDisplayElements();
        foreach ($display_elements as $display_element) {
            $display_element->prepareGetUserUI();
        }
        return parent :: prepareGetUserUI();
    }

    /** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @param boolean $hide_elements allows the child class ( for example
     * Pattern Language) to tell the content group not to display elements ) for
     * elements that need to be hidden before subscription
     * @return The HTML fragment for this interface */
    public function getUserUI($hide_elements = false) {
        $html = '';
        if ($hide_elements == false) {
            $display_elements = $this->getDisplayElements();
            if (count($display_elements) > 0) {
                foreach ($display_elements as $display_element) {
                    // If the content group logging is disabled, all the children will inherit this property temporarly
                    if ($this->getLoggingStatus() == false)
                        $display_element->setLoggingStatus(false);
                    $html .= $display_element->getUserUI();
                }
            } else {
                //$html .= '<p class="warningmsg">' . _("Sorry, this content-group is empty") . "</p>\n";
            }
        }
		$this->setUserUIMainDisplayContent($html);
        return parent :: getUserUI();
    }

    /**Get all elements
     * @return an array of ContentGroupElement or an empty arrray */
    function getElements($additional_where = null) {
        $db = AbstractDb :: getObject();
        // Init values
        $retval = array ();
        $element_rows = null;

        $sql = "SELECT content_group_element_id FROM content_group_element WHERE content_group_id='$this->id' $additional_where ORDER BY display_order";
        $db->execSql($sql, $element_rows, false);
        if ($element_rows != null) {
            foreach ($element_rows as $element_row) {
                $retval[] = self :: getObject($element_row['content_group_element_id']);
            }
        }
        return $retval;
    }

    /**
     * Delete this Content from the database
     */
    public function delete(& $errmsg) {
        if ($this->isPersistent() == false) {
            foreach ($this->getElements() as $element) {
                $element->delete($errmsg);
            }
        }
        return parent :: delete($errmsg);
    }

    /** Reloads the object from the database.  Should normally be called after a set operation.
    * This function is private because calling it from a subclass will call the
    * constructor from the wrong scope */
    private function refresh() {
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