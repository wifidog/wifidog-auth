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
 * This file isn't in Content because it must only be instanciated as part of a
 * ContentGroup
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005 Benoit Gregoire, Technologies Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once('classes/Content/ContentGroup/ContentGroup.php');
require_once('classes/Node.php');

/**
 * The elements of a ContentGroup
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005 Benoit Gregoire, Technologies Coeus inc.
 */
class ContentGroupElement extends Content
{

    /**
     * @var array
     * @access private
     */
    private $content_group_element_row;

    /**
     * Constructor
     *
     * @param string $content_id Content Id
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct($content_id)
    {
        // Define globals
        global $db;

        // Init values
        $row = null;

        parent :: __construct($content_id);

        $this->setIsTrivialContent(true);
        $content_id = $db->escapeString($content_id);

        $sql_select = "SELECT * FROM content_group_element WHERE content_group_element_id='$content_id'";
        $db->execSqlUniqueRes($sql_select, $row, false);

        if ($row == null) {
            // The database was corrupted, let's fix it ...
            $sql = "DELETE FROM content WHERE content_id='$content_id'";
            $db->execSqlUpdate($sql, true);
        }

        $this->content_group_element_row = $row;

        /* A content group element is NEVER persistent */
        parent::setIsPersistent(false);
    }

    /**
     * Replace and delete the old displayed_content (if any) by the new
     * content (or no content)
     *
     * @param object $new_displayed_content Content object or null. If
     *                                      null the old content is still
     *                                      deleted.
     *
     * @return void
     *
     * @access private
     */
    private function replaceDisplayedContent($new_displayed_content)
    {
        // Define globals
        global $db;

        // Init values
        $old_displayed_content = null;
        $errmsg = null;

        if (!empty ($this->content_group_element_row['displayed_content_id'])) {
            $old_displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
        }

        if ($new_displayed_content != null) {
            $new_displayed_content_id_sql = "'".$new_displayed_content->GetId()."'";
        } else {
            $new_displayed_content_id_sql = "NULL";
        }

        $db->execSqlUpdate("UPDATE content_group_element SET displayed_content_id = $new_displayed_content_id_sql WHERE content_group_element_id = '$this->id'", FALSE);

        if ($old_displayed_content != null) {
            $old_displayed_conten->delete($errmsg);
        }
    }

    /**
     * Get the order of the element in the content group
     *
     * @return string the order of the element in the content group
     *
     * @access public
     */
    public function getDisplayOrder()
    {
        return $this->content_group_element_row['display_order'];
    }

    /**
     * Set the order of the element in the content group
     *
     * @param string $order Order how items should be displayed
     *
     * @return void
     *
     * @access public
     */
    public function setDisplayOrder($order)
    {
        // Define globals
        global $db;

        if ($order != $this->getDisplayOrder()) {
            /*
             * Only update database if there is an actual change
             */
            $order = $db->escapeString($order);
            $db->execSqlUpdate("UPDATE content_group_element SET display_order = $order WHERE content_group_element_id = '$this->id'", false);
        }
    }

    /**
     * Like the same method as defined in Content, this method will create a
     * ContentGroupElement based on the content type specified by
     * getNewContentUI OR get an existing element by getSelectContentUI
     *
     * @param string $user_prefix                A identifier provided by the programmer to
     *                                           recognise it's generated form
     * @param string $content_group              Must be present
     * @param bool   $associate_existing_content If set to true, will get an
     *                                           existing element instead of creating a
     *                                           new content.
     *
     * @return object The ContentGroup object, or null if the user didn't greate one
     *
     * @access public
     * @static
     */
    public static function processNewContentUI($user_prefix, ContentGroup $content_group, $associate_existing_content = false)
    {
        // Define globals
        global $db;

        // Init values
        $content_group_element_object = null;
        $max_display_order_row = null;

        if($associate_existing_content == true) {
            $name = "{$user_prefix}_add";
        } else {
            $name = "get_new_content_{$user_prefix}_add";
        }

        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
            /* Get the display order to add the GontentGroupElement at the end */
            $sql = "SELECT MAX(display_order) as max_display_order FROM content_group_element WHERE content_group_id='".$content_group->getId()."'";
            $db->execSqlUniqueRes($sql, $max_display_order_row, false);
            $display_order = $max_display_order_row['max_display_order'] + 1;

            if($associate_existing_content == true) {
                $name = "{$user_prefix}";
            } else {
                $name = "get_new_content_{$user_prefix}_content_type";
            }

            $content_id = get_guid();
            $content_type = 'ContentGroupElement';
            $sql = "INSERT INTO content (content_id, content_type) VALUES ('$content_id', '$content_type');";

            if (!$db->execSqlUpdate($sql, false)) {
                throw new Exception(_('Unable to insert new content into database!'));
            }

            $sql = "INSERT INTO content_group_element (content_group_element_id, content_group_id, display_order) VALUES ('$content_id', '".$content_group->GetId()."', $display_order);";

            if (!$db->execSqlUpdate($sql, false)) {
                throw new Exception(_('Unable to insert new content into database!'));
            }

            $content_group_element_object = self :: getObject($content_id);

            $content_ui_result = FormSelectGenerator :: getResult($name, null);

            if($associate_existing_content == true) {
                $displayed_content_object = self :: getObject($content_ui_result);
            } else {
                $displayed_content_object = self :: createNewObject($content_ui_result);
            }

            $content_group_element_object->replaceDisplayedContent($displayed_content_object);
        }

        return $content_group_element_object;
    }

    /**
     * Shows the administration interface for ContentGroupElement
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     *
     * @access public
     */
    public function getAdminUI($subclass_admin_interface = null)
    {
        // Define globals
        global $db;

        // Init values
        $html = '';
        $allowed_node_rows = null;

        $html .= "<div class='admin_class'>ContentGroupElement (".get_class($this)." instance)</div>\n";

        /* display_order */
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>Display order: </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "content_group_element_".$this->id."_display_order";
        $html .= "<input type='text' name='$name' value='".$this->getDisplayOrder()."' size='2'>\n";
        $html .= _("(Ignored if display type is random)")."\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        /* content_group_element_has_allowed_nodes */
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("AllowedNodes:")."</div>\n";
        $html .= _("(Content can be displayed on ANY node unless one or more nodes are selected)")."\n";
        $html .= "<ul class='admin_section_list'>\n";

        $sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
        $db->execSql($sql, $allowed_node_rows, false);

        if ($allowed_node_rows != null) {
            foreach ($allowed_node_rows as $allowed_node_row) {
                $node = Node :: getObject($allowed_node_row['node_id']);
                $html .= "<li class='admin_section_list_item'>\n";
                $html .= "<div class='admin_section_data'>\n";
                $html .= "".$node->GetId().": ".$node->GetName()."";
                $html .= "</div>\n";
                $html .= "<div class='admin_section_tools'>\n";
                $name = "content_group_element_".$this->id."_allowed_node_".$node->GetId()."_remove";
                $html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
                $html .= "</div>\n";
                $html .= "</li>\n";
            }
        }

        $html .= "<li class='admin_section_list_item'>\n";

        $sql_additional_where = "AND node_id NOT IN (SELECT node_id FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id')";
        $name = "content_group_element_{$this->id}_new_allowed_node";
        $html .= Node :: getSelectNodeUI($name, $sql_additional_where);
        $name = "content_group_element_{$this->id}_new_allowed_node_submit";
        $html .= "<input type='submit' name='$name' value='"._("Add new allowed node")."'>";
        $html .= "</li'>\n";

        $html .= "</ul>\n";
        $html .= "</div>\n";

        /* displayed_content_id */
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<span class='admin_section_title'>"._("Displayed content:")."</span>\n";

        if (empty ($this->content_group_element_row['displayed_content_id'])) {
            $html .= "<b>"._("Add a new displayed content OR select an existing one")."</b><br>";
            $html .= self :: getNewContentUI("content_group_element_{$this->id}_new_displayed_content")."<br>";
            $html .= self :: getSelectContentUI("content_group_element_{$this->id}_new_displayed_existing_element", "AND content_id != '$this->id'");
            $html .= "<input type='submit' name='content_group_element_{$this->id}_new_displayed_existing_element_add' value='"._("Add")."'>";
        } else {
            $displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
            $html .= $displayed_content->getAdminUI();
            $html .= "<div class='admin_section_tools'>\n";
            $name = "content_group_element_{$this->id}_erase_displayed_content";
            $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
            $html .= "</div>\n";
        }

        $html .= "</div>\n";

        $html .= $subclass_admin_interface;

        return parent :: getAdminUI($html);
    }

    /**
     * Processes the input of the administration interface for ContentGroupElement
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        // Define globals
        global $db;

        // Init values
        $allowed_node_rows = null;
        $errmsg = null;

        parent::processAdminUI();

        /* display_order */
        $name = "content_group_element_".$this->id."_display_order";
        $this->setDisplayOrder($_REQUEST[$name]);

        /* content_group_element_has_allowed_nodes */
        $sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
        $db->execSql($sql, $allowed_node_rows, false);

        if ($allowed_node_rows != null) {
            foreach ($allowed_node_rows as $allowed_node_row) {
                $node = Node :: getObject($allowed_node_row['node_id']);
                $name = "content_group_element_".$this->id."_allowed_node_".$node->GetId()."_remove";

                if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                    $sql = "DELETE FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id' AND node_id='".$node->GetId()."'";
                    $db->execSqlUpdate($sql, false);
                }
            }
        }

        $name = "content_group_element_{$this->id}_new_allowed_node_submit";

        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
            $name = "content_group_element_{$this->id}_new_allowed_node";
            $node = Node :: processSelectNodeUI($name);
            $node_id = $node->GetId();
            $db->execSqlUpdate("INSERT INTO content_group_element_has_allowed_nodes (content_group_element_id, node_id) VALUES ('$this->id', '$node_id')", FALSE);
        }

        /* displayed_content_id */
        if (empty ($this->content_group_element_row['displayed_content_id'])) {
            // Could be either a new content or existing content ( try both successively )
            $displayed_content = Content :: processNewContentUI("content_group_element_{$this->id}_new_displayed_content");

            if ($displayed_content == null) {
                $displayed_content = Content :: processNewContentUI("content_group_element_{$this->id}_new_displayed_existing_element", true);
            }

            if ($displayed_content != null) {
                $displayed_content_id = $displayed_content->GetId();
                $db->execSqlUpdate("UPDATE content_group_element SET displayed_content_id = '$displayed_content_id' WHERE content_group_element_id = '$this->id'", FALSE);
                $displayed_content->setIsPersistent(false);
            }
        } else {
            $displayed_content = self::getObject($this->content_group_element_row['displayed_content_id']);
            $name = "content_group_element_{$this->id}_erase_displayed_content";

            if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                if($displayed_content->delete($errmsg) != false) {
                    $db->execSqlUpdate("UPDATE content_group_element SET displayed_content_id = NULL WHERE content_group_element_id = '$this->id'", FALSE);
                } else {
                    echo $errmsg;
                }
            } else {
                $displayed_content->processAdminUI();
            }
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI($subclass_user_interface = null)
    {
        // Init values
        $html = '';

        if (!empty ($this->content_group_element_row['displayed_content_id'])) {
            $displayed_content = self::getObject($this->content_group_element_row['displayed_content_id']);

            // If the content group logging is disabled, all the children will inherit this property temporarly
            if($this->getLoggingStatus() == false) {
                $displayed_content->setLoggingStatus(false);
            }

            $displayed_content_html = $displayed_content->getUserUI();
        }

        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>ContentGroupElement (".get_class($this)." instance)</div>\n";
        $html .= $displayed_content_html;
        $html .= $subclass_user_interface;
        $html .= "</div>\n";

        return parent :: getUserUI($html);
    }

    /**
     * Returns if this this Content element is displayable at this hotspot
     *
     * @param string $node Node Id
     *
     * @return bool True if it is displayable
     *
     * @access public
     */
    public function isDisplayableAt($node)
    {
        // Define globals
        global $db;

        // Init values
        $retval = false;
        $allowed_node_rows = null;

        $sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
        $db->execSql($sql, $allowed_node_rows, false);

        if ($allowed_node_rows != null) {
            if ($node) {
                $node_id = $node->getId();
                /**
                 * @todo  Proper algorithm, this is a dirty and slow hack
                 */
                foreach ($allowed_node_rows as $allowed_node_row) {
                    if ($allowed_node_row['node_id'] == $node_id) {
                        $retval = true;
                    }
                }
            } else {
                /* There are allowed nodes, but we don't know at which node we want to display */
                $retval = false;
            }
        } else {
            /* No allowed node means all nodes are allowed */
            $retval = true;
        }

        return $retval;
    }

    /**
     * Detects if a user is owner of a ContentGroupElement
     *
     * Override the method in Content.
     *
     * The owners of the content element are always considered to be the ContentGroup's
     *
     * @param object $user User object: the user to be tested.
     *
     * @return bool True if the user is a owner, false if he isn't or if the user is null
     *
     * @access public
     */
    public function isOwner($user)
    {
        $content_group = Content :: getObject($this->content_group_element_row['content_group_id']);
        return $content_group->isOwner($user);
    }

    /**
     * Deletes a ContentGroupElement object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     *
     * @access public
     * @internal Persistent content will not be deleted
     *
     * @todo Implement proper access control
     */
    public function delete(& $errmsg)
    {
        if ($this->isPersistent() == false && !empty ($this->content_group_element_row['displayed_content_id'])) {
            $displayed_content = self::getObject($this->content_group_element_row['displayed_content_id']);
            print_r($displayed_content);
        }
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
