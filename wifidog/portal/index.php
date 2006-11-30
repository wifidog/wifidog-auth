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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once ('../include/common.php');

require_once ('include/common_interface.php');
require_once ('classes/Node.php');
require_once ('classes/MainUI.php');
require_once ('classes/Session.php');
$smarty = SmartyWifidog::getObject();
$db = AbstractDb::getObject(); 
/*
 * Check for missing URL switch
 */
if (isset ($_REQUEST['missing']) && $_REQUEST['missing'] == "url") {
    $ui = MainUI::getObject();
    $ui->displayError(_('For some reason, we were unable to determine the web site you initially wanted to see.  You should now enter a web address in your URL bar.'), false);
    exit;
}

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

/*
 * If this node has a custom portal defined, and the network config allows it,
 * redirect to the custom portal
 */
$custom_portal_url = $node->getCustomPortalRedirectUrl();

if (!empty ($custom_portal_url) && $network->getCustomPortalRedirectAllowed()) {
    header("Location: {$custom_portal_url}");
}

$node_id = $node->getId();
$portal_template = $node_id.".html";
Node :: setCurrentNode($node);

if (isset ($session)) {
    if ($node) {
        $session->set(SESS_NODE_ID_VAR, $node_id);
    }
}

$current_node = Node :: getCurrentNode();
$current_node_id = $current_node->getId();

// Init ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Init ALL smarty values
$smarty->assign('currentNode', null);
$smarty->assign('numOnlineUsers', 0);
$smarty->assign('onlineUsers', array ());
$smarty->assign('userIsAtHotspot', false);
$smarty->assign('noUrl', true);
$smarty->assign('url', "");
$smarty->assign('currentUrl', "");
$smarty->assign('accountValidation', false);
$smarty->assign('validationTime', 20);
$smarty->assign('hotspotNetworkUrl', "");
$smarty->assign('hotspotNetworkName', "");
$smarty->assign('networkLogoBannerUrl', "");
$smarty->assign('networkContents', false);
$smarty->assign('networkContentArray', array ());
$smarty->assign('nodeHomepage', false);
$smarty->assign('nodeURL', "");
$smarty->assign('nodeName', "");
$smarty->assign('nodeContents', false);
$smarty->assign('nodeContentArray', array ());
$smarty->assign('userContents', false);
$smarty->assign('userContentArray', array ());

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);

// Set details about node
$smarty->assign('currentNode', $current_node);

// Set details about onlineusers
$online_users = $current_node->getOnlineUsers();
$num_online_users = count($online_users);

foreach ($online_users as $online_user) {

    $online_user_array[] = $online_user->getListUI();
}
/* 
			    		    if ($this->isConfiguredSplashOnly() && $anonUsers == 1) {
			    $retval[] = "One anonymous user";
		    } else if ($this->isConfiguredSplashOnly() && $anonUsers > 1) {
			    $retval[] = sprintf("%d anonymous users", $anonUsers);
		    }

 */
$smarty->assign('numOnlineUsers', $num_online_users);

if ($num_online_users > 0) {
    $smarty->assign('onlineUsers', $online_user_array);
}

// Check for requested URL and if user is at a hotspot
$original_url_requested = $session->get(SESS_ORIGINAL_URL_VAR);

$smarty->assign('userIsAtHotspot', Node :: getCurrentRealNode() != null ? true : false);

if (empty ($original_url_requested)) {
    $smarty->assign('noUrl', true);
    $smarty->assign('url', "?missing=url");
} else {
    $smarty->assign('noUrl', true);
    $smarty->assign('url', $original_url_requested);
}

// Assign current request url
$smarty->assign('currentUrl', CURRENT_REQUEST_URL);

// Compile HTML code
$tool_html = $smarty->fetch("templates/sites/portal.tpl");

/*
 * Render output
 */

$ui = MainUI::getObject();
$ui->setTitle(sprintf(_("%s portal for %s"), $network->getName(), $node->getName()));
$ui->setPageName('portal');
$ui->addContent('left_area_middle', $tool_html);
/*
 * Main content
 */
 $welcome_msg = sprintf("<span>%s</span><em>%s</em>",_("Welcome to"), $node->getName());
 $ui->addContent('page_header', "<h1>$welcome_msg</h1>");
// While in validation period, alert user that he should validate his account ASAP
if ($current_user && $current_user->getAccountStatus() == ACCOUNT_STATUS_VALIDATION) {
    $validationMsgHtml = "<div id='warning_message_area'>\n";
    $validationMsgHtml .= _("An email with confirmation instructions was sent to your email address.");
    $validationMsgHtml .= sprintf(_("Your account has been granted %s minutes of access to retrieve your email and validate your account."), ($current_user->getNetwork()->getValidationGraceTime() / 60));
    $validationMsgHtml .= "</div>\n";
    $ui->addContent('page_header', $validationMsgHtml);
}

// Get all network content, but exclude user subscribed content if user is known
$content_rows = array ();
$network_id = $db->escapeString($network->getId());
if ($current_user) {
    $user_id = $db->escapeString($current_user->getId());
    $sql = "SELECT content_id, display_area, display_order FROM network_has_content WHERE network_id='$network_id' AND display_page='portal' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}') ORDER BY display_area, display_order, subscribe_timestamp DESC";
} else {
    $sql = "SELECT content_id, display_area, display_order FROM network_has_content WHERE network_id='$network_id'  AND display_page='portal' ORDER BY display_area, display_order, subscribe_timestamp DESC";
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

// Get all node content and EXCLUDE user subscribed content
$content_rows = array ();
$node_id = $db->escapeString($node->getId());
if ($current_user) {
    $user_id = $db->escapeString($current_user->getId());
    $sql = "SELECT content_id, display_area, display_order FROM node_has_content WHERE node_id='$node_id' AND display_page='portal' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$user_id}') ORDER BY display_area, display_order, subscribe_timestamp DESC";
} else {
    $sql = "SELECT content_id, display_area, display_order FROM node_has_content WHERE node_id='$node_id'  AND display_page='portal' ORDER BY display_area, display_order, subscribe_timestamp DESC";
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
}

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