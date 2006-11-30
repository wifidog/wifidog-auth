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
 * A simple interface to edit any object that implements the GenericObject
 * interface.
 *
 * The php file takes the following params:
 * - $_REQUEST['action']: new, edit, delete, preview, process_new_ui, new_ui
 *  (also   save, but not meant for calling from outside this file)
 * - $_REQUEST ['object_id']: The id of the object ot be edited
 * - $_REQUEST ['object_class']: The class name of the object ot be edited
 * - $_REQUEST ['node_id']: In preview mode, the current node to simulate
 *  display
 * -$_REQUEST  ['debug']: If present and non empty, the $_REQUEST variables will
 * be displayed
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load common include file
 */
require_once ('admin_common.php');

require_once ('classes/GenericObject.php');
require_once ('classes/MainUI.php');
require_once ('classes/User.php');
require_once ('classes/Node.php');
require_once ('classes/Network.php');
require_once ('classes/Server.php');
require_once ('classes/InterfaceElements.php');

// Init values
$ui = MainUI :: getObject();
$html = "";
$errmsg = "";
$common_input = "";
$displayEditButton = true;
$displayShowAllButton = false;
$supportsPreview = true;
$supportsDeletion = true;
/*
 * Check for the object class to use
 */
if (empty ($_REQUEST['object_class'])) {
    echo "<div class='errormsg'>" . _("Sorry, the 'object_class' parameter must be specified") . "</div>\n";
    exit;
} else {
    $class = $_REQUEST['object_class'];
}
// Init text values
$createText = sprintf(_("Create %s"), $_REQUEST['object_class']);
$addText = sprintf(_("Add %s"), $_REQUEST['object_class']);
$createLongText = sprintf(_("Create a new %s"), $_REQUEST['object_class']);
$addLongText = sprintf(_("Add a new %s"), $_REQUEST['object_class']);
$listAllText = sprintf(_("Show all %s"), $_REQUEST['object_class']);
$listPersistantText = sprintf(_("Show only persistant %s"), $_REQUEST['object_class']);
$editText = sprintf(_("Edit %s"), $_REQUEST['object_class']);

$newText = $createText;
$newLongText = $createLongText;

/*
 * Check for debugging requests
 */
if (!empty ($_REQUEST['debug'])) {
    echo "<pre>";
    print_r($_REQUEST);
    echo "</pre>";
}

/*
 * Check for action requests
 */
if (!isset ($_REQUEST['action'])) {
    $_REQUEST['action'] = "";
}

if (!isset ($_REQUEST['action_delete'])) {
    $_REQUEST['action_delete'] = "";
}

/*
 * Pre-process action requests (load required objects)
 */
switch ($_REQUEST['action']) {
    case "new" :
        $object = call_user_func(array (
            $class,
            'createNewObject'
        ));
        $_REQUEST['action'] = 'edit';
        break;

    case "process_new_ui" :
        $object = call_user_func(array (
            $class,
            'processCreateNewObjectUI'
        ));

        if (!$object) {
            echo "<div class='errormsg'>" . _("Sorry, the object couldn't be created.  You probably didn't fill the form properly") . "</div>\n";
            exit;
        }

        $_REQUEST['action'] = 'edit';
        break;

    case "list" :
    case "new_ui" :
        // No need for an object
        break;

    default :
        $object = call_user_func(array (
            $class,
            'getObject'
        ), $_REQUEST['object_id']);
        break;
}

/*
 * Process action requests (saving, previewing and deleting)
 */
switch ($_REQUEST['action']) {
    case "save" :
        $object->processAdminUI();
        $_REQUEST['action'] = 'edit';
        break;

    case "preview" :
        if (empty ($_REQUEST['node_id'])) {
            $node_id = null;
            $node = null;
        } else {
            $node_id = $_REQUEST['node_id'];
            $node = Node :: getObject($node_id);
            Node :: setCurrentNode($node);

            $html .= "<h1>" . _("Showing preview as it would appear at ") . $node->getName() . "</h1><br><br>";
        }

        if (!empty ($_REQUEST['debug'])) {
            $common_input .= "<input type='hidden' name='debug' value='true'>";
        }

        $common_input .= "<input type='hidden' name='object_id' value='" . $object->GetId() . "'>";
        $common_input .= "<input type='hidden' name='object_class' value='" . get_class($object) . "'>";
        $common_input .= "<input type='hidden' name='node_id' value='" . $node_id . "'>";

        $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' target='_top' method='post'>";
        $html .= $common_input;

        $name = "node_id";
        $html .= _("Node");
        $html .= ": ";
        $html .= Node :: getSelectNodeUI($name);

        if (method_exists($object, "getUserUI")) {
            $ui->addContent('main_area_middle', $object, 1);
        }

        $html .= "<input type='hidden' name='action' value='preview'>";
        $html .= "<input type='submit' name='preview_submit' value='" . _("Preview") . " " . get_class($object) . "'>";
        $html .= '</form>';

        $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
        $html .= $common_input;
        $html .= "<input type='hidden' name='action' value='edit'>";
        $html .= "<input type=submit name='edit_submit' value='" . _("Edit") . " " . get_class($object) . "'>";
        $html .= '</form>';
        break;

    case "delete" :
        // Gets called only if no JavaScript was enabled in the browser
        if ($object->delete($errmsg) == true) {
            $html .= "<div class='successmsg'>" . _("Object successfully deleted") . "</div>";
        } else {
            $html .= "<div class='errormsg'>" . _("Deletion failed, error was: ") . "<br />$errmsg</div>";
            $_REQUEST['action'] = 'edit';
        }
        break;

    default :
        // Do nothing
        break;
}

/*
 * Process action requests (deleting with enabled JavaScript)
 */
switch ($_REQUEST['action_delete']) {
    case "delete" :
        // First save the object so we can catch any "persistent content" changes
        $object->processAdminUI();

        // Now try to delete the content
        if ($object->delete($errmsg) == true) {
            $html .= "<div class='successmsg'>" . _("Object successfully deleted") . "</div>";
            $_REQUEST['action'] = "";
        } else {
            $html .= "<div class='errormsg'>" . _("Deletion failed, error was: ") . "<br />$errmsg</div>";
            $_REQUEST['action'] = 'edit';
        }
        break;

    default :
        // Do nothing
        break;
}

/*
 * Process action requests (new and edit)
 */
switch ($_REQUEST['action']) {
    case "list" :
        $createAllowed = false;

        switch ($_REQUEST['object_class']) {
            case "Content" :
                $displayShowAllButton = true;
                ((isset ($_REQUEST['display_content']) && $_REQUEST['display_content'] == "all_content") ? $sql_additional_where = null : $sql_additional_where = " AND is_persistent=TRUE ");
                $objectSelector = Content :: getSelectExistingContentUI('object_id', $sql_additional_where, null, "content_type", "table");
                $displayEditButton = false;
                break;

            case "Node" :
                $newLongText = $addLongText;
                $objectSelector = Node :: getSelectNodeUI('object_id', null, null, null, "table");
                $displayEditButton = false;
                break;

            case "Network" :
                $objectSelector = Network :: getSelectNetworkUI('object_id');
                break;

            case "Server" :
                $newLongText = $addLongText;
                $objectSelector = Server :: getSelectServerUI('object_id');
                break;

            default :
                $objectSelector = "";
                break;
        }

        $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
        $html .= "<input type='hidden' name='object_class' value='$class'>";
        $html .= "<input type='hidden' name='action' value='new_ui'>";
        $html .= "<input type='submit' name='new_submit' value='$newLongText'>\n";
        $html .= '</form>';

        if ($displayShowAllButton) {
            $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
            $html .= "<input type='hidden' name='object_class' value='$class'>";
            $html .= "<input type='hidden' name='action' value='list'>\n";

            if (isset ($_REQUEST['display_content']) && $_REQUEST['display_content'] == "all_content") {
                $html .= "<input type='submit' name='list_submit' value='$listPersistantText'>\n";
            } else {
                $html .= "<input type='hidden' name='display_content' value='all_content'>\n";
                $html .= "<input type='submit' name='list_submit' value='$listAllText'>\n";
            }

            $html .= '</form>';
        }

        if ($objectSelector != "") {
            if ($displayEditButton) {
                $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
                $html .= "<input type='hidden' name='object_class' value='$class'>";
                $html .= "<input type='hidden' name='action' value='edit'>";
                $html .= $objectSelector;
                $html .= "<input type='submit' name='edit_submit' value='$editText'>\n";
                $html .= '</form>';
            } else {
                $html .= $objectSelector;
            }
        }
        break;

    case "new_ui" :
        switch ($_REQUEST['object_class']) {
            case "Node" :
            case "Server" :
            case "Content" :
                $newText = $addText;
                break;

            default :
                break;
        }

        $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
        $html .= "<input type='hidden' name='object_class' value='$class'>";
        $html .= call_user_func(array (
            $class,
            'getCreateNewObjectUI'
        ));
        $html .= "<input type='hidden' name='action' value='process_new_ui'>";
        $html .= "<input type=submit name='new_ui_submit' value='$newText'>";
        $html .= '</form>';
        break;

    case "edit" :
        // Process preview abilities
        switch ($_REQUEST['object_class']) {
            case "Network" :
            case "Server" :
            case "User" :
                $supportsPreview = false;
                break;

            default :
                break;
        }

        // Process deletion abilities
        switch ($_REQUEST['object_class']) {
            case "User" :
                $supportsDeletion = false;
                break;
            case "Network" :
            case "Node" :
            case "Server" :
                if (!User :: getCurrentUser()->isSuperAdmin()) {
                    $supportsDeletion = false;
                }
                break;

            default :
                break;
        }

        if (!$object) {
            echo "<div class='errormsg'>" . _("Sorry, the 'object_id' parameter must be specified") . "</div>";
            exit;
        }

        if (!empty ($_REQUEST['debug'])) {
            $common_input .= "<input type='hidden' name='debug' value='true'>";
        }

        $common_input .= "<input type='hidden' name='object_id' value='" . $object->GetId() . "'>";
        $common_input .= "<input type='hidden' name='object_class' value='" . get_class($object) . "'>";

        $html .= "<form name='generic_object_form' enctype='multipart/form-data' action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
        $html .= $common_input;
        $html .= $object->getAdminUI();
        $html .= "<div class='generic_object_admin_edit'>";
        $html .= "<input type='hidden' name='action' value='save'>";
        $html .= "<input type='submit' class='submit' name='save_submit' value='" . _("Save") . " " . get_class($object) . "'>";

        if ($supportsDeletion) {
            $html .= "<script type='text/javascript'>";
            $html .= "document.write(\"<input type='hidden' name='action_delete' value='no' id='form_action_delete' />\");";
            $html .= "document.write(\"<input type='submit' class='submit' name='action_delete_submit' onmouseup='document.getElementById(\\\"form_action_delete\\\").value = \\\"delete\\\"' onkeyup='document.getElementById(\\\"form_action_delete\\\").value = \\\"delete\\\"' value='" . _("Delete") . " " . get_class($object) . "' />\");";
            $html .= "</script>";
        }

        $html .= '</form>';

        if ($supportsPreview) {
            $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' target='_blank' method='post'>";
            $html .= $common_input;
            $html .= "<input type='hidden' name='action' value='preview'>";
            $html .= "<input type='submit' class='submit' name='preview_submit' value='" . _("Preview") . " " . get_class($object) . "'>";
            $html .= '</form>';
        }

        // Display delete button (without check for unchecked persitant switch) only if JavaScript has been disabled
        if ($supportsDeletion) {
            $html .= "<noscript>";
            $html .= "<form action='" . GENERIC_OBJECT_ADMIN_ABS_HREF . "' method='post'>";
            $html .= $common_input;
            $html .= "<input type='hidden' name='action' value='delete'>";
            $html .= "<input type='submit' class='submit'  name='delete_submit' value='" . _("Delete") . " " . get_class($object) . "'>";
            $html .= '</form>';
            $html .= "</noscript>";
        }

        $html .= "<div class='clearbr'></div>";
        $html .= "</div>";
        break;

    default :
        // Do nothing
        break;
}

/*
 * Define JavaScripts
 */

$_htmlHeader = "<script type='text/javascript' src='" . BASE_SSL_PATH . "js/interface.js'></script>";
$_htmlHeader .= "<script type='text/javascript' src='" . BASE_SSL_PATH . "js/interface.js'></script>";

/*
 * Render output
 */

$ui->setTitle(_("Generic object editor"));
$ui->appendHtmlHeadContent($_htmlHeader);
$ui->setToolSection('ADMIN');
$ui->addContent('main_area_middle', "<div>" . $html . "</div>");
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>