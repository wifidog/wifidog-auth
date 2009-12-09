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
 * Displays the portal page
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once ('../include/common.php');

require_once ('classes/Node.php');
require_once ('classes/MainUI.php');
require_once ('classes/Session.php');
$smarty = SmartyWifidog::getObject();
$db = AbstractDb::getObject();

// Init values
$node = null;
$rolenames = "";
$show_more_link = false;

// Init session
$session = Session::getObject();

// Get the current user
$current_user = User :: getCurrentUser();

/*
 * Start general request parameter processing section
 */
if (!empty ($_REQUEST['node_id'])) {
    try {
        $node = Node :: getObject($_REQUEST['node_id']);
        $network = $node->getNetwork();
    } catch (Exception $e) {
        $ui = MainUI::getObject();
        $ui->displayError($e->getMessage());
        exit;
    }
}
else if (!empty ($_REQUEST['gw_id'])) {//This section MUST remain, the gw_id calling convention is hard_coded into the gateway
    try {
        $node = Node :: getObjectByGatewayId($_REQUEST['gw_id']);
        $network = $node->getNetwork();
    } catch (Exception $e) {
        $ui = MainUI::getObject();
        $ui->displayError($e->getMessage());
        exit;
    }
} else {
    $ui = MainUI::getObject();
    $ui->displayError(_("No Hotspot specified!"));
    exit;
}

$node_id = $node->getId();
Node :: setCurrentNode($node);

if (isset ($session)) {
    if ($node) {
        $session->set(SESS_NODE_ID_VAR, $node_id);
    }
}

/*
 * If this node allows redirection to original URL, and the network config allows it,
 * redirect to original URL
 */
$session_original_url = $session->get(SESS_ORIGINAL_URL_VAR);

if ($node->getPortalOriginalUrlAllowed() && $network->getPortalOriginalUrlAllowed() && !empty ($session_original_url)) {
    /**
     * If the database doesn't get cleaned up by a cron job, we'll do now (normally this is done in ManiUI, but for custom URLs, MainUI may never be instanciated
     */
    if (CONF_USE_CRON_FOR_DB_CLEANUP == false) {
        garbage_collect();
    }
    header("Location: {$session_original_url}");
    exit;
}


/*
 * If this node has a custom portal defined, and the network config allows it,
 * redirect to the custom portal
 */
$custom_portal_url = $node->getCustomPortalRedirectUrl();

if (!empty ($custom_portal_url) && $network->getCustomPortalRedirectAllowed()) {
    /**
     * If the database doesn't get cleaned up by a cron job, we'll do now (normally this is done in ManiUI, but for custom URLs, MainUI may never be instanciated
     */
    if (CONF_USE_CRON_FOR_DB_CLEANUP == false) {
        garbage_collect();
    }
    header("Location: {$custom_portal_url}");
    exit;
}

$smarty->assign('sectionMAINCONTENT', false);

/*
 * Render output
 */

$ui = MainUI::getObject();
$ui->setTitle(sprintf(_("%s portal for %s"), $network->getName(), $node->getName()));
$ui->setPageName('portal');
/*
 * Main content
 */
$welcome_msg = sprintf("<span>%s</span> <em>%s</em>",_("Welcome to"), $node->getName());
$ui->addContent('page_header', "<div class='welcome_msg'><div class='welcome_msg_inner'>$welcome_msg</div></div>");
// While in validation period, alert user that he should validate his account ASAP
if ($current_user && $current_user->getAccountStatus() == ACCOUNT_STATUS_VALIDATION) {
    $validationMsgHtml = "<div id='warning_message_area'>\n";
    $validationMsgHtml .= _("You NEED to confirm your account.  An email with confirmation instructions was sent to your email address.  If you don't see it in your inbox, make sure to look in your spam folder.");
    $validationMsgHtml .= sprintf(_("Your account has been granted %s minutes of access to retrieve your email and validate your account."), ($current_user->getNetwork()->getValidationGraceTime() / 60));
    $validationMsgHtml .= "</div>\n";
    $ui->addContent('page_header', $validationMsgHtml);
}

// Get all the parent objects of the node
$parents = HotspotGraph::getAllParents(HotspotGraphElement::getObjectFor($node));

// Get the contents for all elements parents of and including the node, but exclude user subscribed content if user is known
foreach($parents as $parentid) {
    $content_rows = array ();
    $parent_id = $db->escapeString($parentid);
    if ($current_user) {
        $user_id = $db->escapeString($current_user->getId());
        $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
        $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
        $sql .= "WHERE hge.hotspot_graph_element_id='$parent_id' AND display_page='portal' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}') ORDER BY display_area, display_order, subscribe_timestamp DESC";
    } else {
        $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
        $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
        $sql .= "WHERE hge.hotspot_graph_element_id='$parent_id' AND display_page='portal' ORDER BY display_area, display_order, subscribe_timestamp DESC";
    }
    $db->execSql($sql, $content_rows, false);
    if ($content_rows) {
        foreach ($content_rows as $content_row) {
            $content = Content :: getObject($content_row['content_id']);
            if ($content->isDisplayableAt($node)) {
                $ui->addContent($content_row['display_area'], $content, $content_row['display_order']);
            }
        }
    }
}
$showMoreLink = false;

// Get all network content, but exclude user subscribed content if user is known
/*$content_rows = array ();
$network_id = $db->escapeString($network->getId());
if ($current_user) {
    $user_id = $db->escapeString($current_user->getId());
    $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
    $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
    $sql .= "WHERE hge.element_id='$network_id' AND hge.element_type = 'Network' AND display_page='portal' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}') ORDER BY display_area, display_order, subscribe_timestamp DESC";
} else {
    $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
    $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
    $sql .= "WHERE hge.element_id='$network_id' AND hge.element_type = 'Network' AND display_page='portal' ORDER BY display_area, display_order, subscribe_timestamp DESC";
}
$db->execSql($sql, $content_rows, false);
if ($content_rows) {
    foreach ($content_rows as $content_row) {
        $content = Content :: getObject($content_row['content_id']);
        if ($content->isDisplayableAt($node)) {
            $ui->addContent($content_row['display_area'], $content, $content_row['display_order']);
        }
    }
}*/

// Get all node content and EXCLUDE user subscribed content
/*$content_rows = array ();
$node_id = $db->escapeString($node->getId());
if ($current_user) {
    $user_id = $db->escapeString($current_user->getId());
    $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
    $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
    $sql .= "WHERE hge.element_id='$node_id' AND hge.element_type = 'Node' AND display_page='portal' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}') ORDER BY display_area, display_order, subscribe_timestamp DESC";
} else {
    $sql = "SELECT content_id, display_area, display_order FROM hotspot_graph_element_has_content hgehc ";
    $sql .= "INNER JOIN hotspot_graph_elements hge on hgehc.hotspot_graph_element_id = hge.hotspot_graph_element_id ";
    $sql .= "WHERE hge.element_id='$node_id' AND hge.element_type = 'Node' AND display_page='portal' ORDER BY display_area, display_order, subscribe_timestamp DESC";
}
$db->execSql($sql, $content_rows, false);
$showMoreLink = false;
if ($content_rows) {
    foreach ($content_rows as $content_row) {
        $content = Content :: getObject($content_row['content_id']);
        if ($content->isDisplayableAt($node)) {
            $ui->addContent($content_row['display_area'], $content, $content_row['display_order']);
        }
    }
}*/

// Get all user content
$content_rows = array ();
if ($current_user) {
    $user_id = $db->escapeString($current_user->getId());
    $sql = "SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}' ORDER BY subscribe_timestamp DESC";
    $db->execSql($sql, $content_rows, false);
    if ($content_rows) {
        foreach ($content_rows as $content_row) {
            $content = Content :: getObject($content_row['content_id']);
            if ($content->isDisplayableAt($node)) {
                $ui->addContent('main_area_middle', $content);
            }
        }
    }
}

if ($showMoreLink) {
    $link_html = "<a href='{$base_ssl_path}content/?node_id={$currentNodeId}'>"._("Show all available contents for this hotspot")."</a>\n";
    $ui->addContent('main_area_middle', $link_html);
}
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
