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
 * Displays all the content associated to a hotspot
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../include/common.php');

require_once('classes/MainUI.php');
require_once('include/common_interface.php');
require_once('classes/Node.php');

if (CONF_USE_CRON_FOR_DB_CLEANUP == false)
{
    garbage_collect();
}

$node = null;
if(!empty($_REQUEST['gw_id']))
    $node = Node :: getObject($_REQUEST['gw_id']);

if ($node == null)
{
    $smarty->assign("tech_support_email", Network::getCurrentNetwork()->getTechSupportEmail());
    $smarty->display("templates/message_unknown_hotspot.html");
    exit;
}

$node_id = $node->getId();
$portal_template = $node_id.".html";
Node :: setCurrentNode($node);

$ui = new MainUI();
if (isset ($session))
{
    $smarty->assign("original_url_requested", $session->get(SESS_ORIGINAL_URL_VAR));
}

$tool_html = '';

$tool_html .= "<h1>"._("Online users")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$current_node = Node :: getCurrentNode();
if ($current_node != null)
{
    $current_node_id = $current_node->getId();
    $online_users = $current_node->getOnlineUsers();
    $num_online_users = count($online_users);
    if ($num_online_users > 0)
    {
        //$tool_html .= $num_online_users.' '._("other users online at this hotspot...");
        $tool_html .= "<ul class='users_list'>\n";
        foreach($online_users as $online_user)
            $tool_html .= "<li>{$online_user->getUsername()}</li>\n";
        $tool_html .= "</ul>\n";
    }
    else
    {
        $tool_html .= _("Nobody is online at this hotspot...");
    }
}
else
{
    $current_node_id = null;
    $tool_html .= _("You are not currently at a hotspot...");
}
$tool_html .= "</p>"."\n";


/*
$tool_html .= '<p class="indent">'."\n";
$tool_html .= "<a href='content.php?gw_id={$current_node_id}' target='_blank.right'><img src='/images/start.gif'></a>"."\n";
$tool_html .= "</p>"."\n";
*/
$ui->setToolContent($tool_html);


$html = '';
$html .= "<div id='portal_container'>\n";

/* Node section */
// Get all locative artistic content for this node
$contents = $node->getAllLocativeArtisticContent();
if(!empty($contents))
{
    $html .= "<div class='portal_node_section'>\n";
    $html .= "<span class='portal_section_title'>"._("Content from:")." ";

    $node_homepage = $node->getHomePageURL();
    if(!empty($node_homepage))
    {
        $html .= "<a href='$node_homepage'>";
    }
    $html .= $node->getName();
    if(!empty($node_homepage))
    {
        $html .= "</a>\n";
    }
    $html .= "</span>";

    foreach ($contents as $content)
    {
        $html .= "<div class='portal_content'>\n";
        $html .= $content->getUserUI();
        $html .= "</div>";
    }
    $html .= "</div>\n";
}

$ui->setMainContent($html);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
