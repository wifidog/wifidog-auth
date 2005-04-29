<?php
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file index.php Displays all the content associated to a hotspot
 * @author Copyright (C) 2005 François Proulx
 */

define('BASEPATH', '../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/MainUI.php';

if (CONF_USE_CRON_FOR_DB_CLEANUP == false)
{
	garbage_collect();
}

$node = null;
if(!empty($_REQUEST['gw_id']))
    $node = Node :: getObject($_REQUEST['gw_id']);

if ($node == null)
{
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
$tool_html .= "<h1>"._("At this Hotspot")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$tool_html .= _("Local content...")."\n";
$tool_html .= "</p>"."\n";

$tool_html .= "<h1>"._("Users Online")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$current_node = Node :: getCurrentNode();
if ($current_node != null)
{
	$current_node_id = $current_node->getId();
	$online_users = $current_node->getOnlineUsers();
	$num_online_users = count($online_users);
	if ($num_online_users > 0)
	{
		$tool_html .= $num_online_users.' '._("other users online at this hotspot...");
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

$tool_html .= '<p class="indent">'."\n";

$tool_html .= "<a href='content.php?gw_id={$current_node_id}' target='_blank.right'><img src='/images/start.gif'></a>"."\n";
$tool_html .= "</p>"."\n";
$ui->setToolContent($tool_html);

$hotspot_network_name = HOTSPOT_NETWORK_NAME;
$hotspot_network_url = HOTSPOT_NETWORK_URL;
$network_logo_url = COMMON_CONTENT_URL.NETWORK_LOGO_NAME;
$network_logo_banner_url = COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

$hotspot_logo_url = find_local_content_url(HOTSPOT_LOGO_NAME);
$hotspot_logo_banner_url = find_local_content_url(HOTSPOT_LOGO_BANNER_NAME);

$html = '';
$html .= "<div id='portal_container'>\n";

/* Node section */
$html .= "<div class='portal_node_section'>\n";
$html .= "<img class='portal_section_logo' src='{$hotspot_logo_url}' alt=''>\n";
$html .= "<span class='portal_section_title'>"._("Content from:")."</span>";

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

// Get all locative artistic content for this node
$contents = $node->getAllLocativeArtisticContent();
$html .= "<ul>\n";
foreach ($contents as $content)
{
    $html .= "<li>\n";
	$html .= $content->getUserUI();
    $html .= "</li>\n";
}
$html .= "</ul>\n";

$html .= "</div>\n";

$ui->setMainContent($html);
$ui->display();

?>