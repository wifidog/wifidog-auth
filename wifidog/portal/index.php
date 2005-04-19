<?php
  // $Id$
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
  /**@file index.php Displays the portal page
   * @author Copyright (C) 2004 Benoit Gr�goire et Philippe April
   */

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';

if (CONF_USE_CRON_FOR_DB_CLEANUP == false) {
    garbage_collect();
}
$node = Node::getObject($_REQUEST['gw_id']);

if ($node==null) {
    $smarty->display("templates/message_unknown_hotspot.html");
    exit;
}
$node_id=$node->getId();
$portal_template = $node_id . ".html";

if ($node == null) {
    $smarty->assign("gw_id", $_REQUEST['gw_id']);
    $smarty->display("templates/message_unknown_hotspot.html");
    exit;
}

Node::setCurrentNode($node);

$smarty->assign('hotspot_name', $node->getName());
$node_name= $node->getName();

/* Find out who is online */
$smarty->assign("online_users", $node->getOnlineUsers());


if (isset($session)) {
    $smarty->assign("original_url_requested", $session->get(SESS_ORIGINAL_URL_VAR));
}
	$hotspot_network_name=HOTSPOT_NETWORK_NAME;
	$hotspot_network_url=HOTSPOT_NETWORK_URL;
	$network_logo_url=COMMON_CONTENT_URL.NETWORK_LOGO_NAME;
	$network_logo_banner_url=COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

     $hotspot_logo_url= find_local_content_url(HOTSPOT_LOGO_NAME);
     $hotspot_logo_banner_url=find_local_content_url(HOTSPOT_LOGO_BANNER_NAME);


$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."header.html");
$html='';
$html .= "<div id='portal_container'>\n";

/* Network section */
$html .= "<div class='portal_network_section'>\n";
$html .= "<a href='{$hotspot_network_url}'><img class='portal_section_logo' src='{$network_logo_banner_url}' alt='{$hotspot_network_name} logo' border='0'></a>\n";
$html .= "Content from \"<a href='{$hotspot_network_url}'>{$hotspot_network_name}</a>\"\n";
$contents = Network::getCurrentNetwork()->getAllContent();
foreach ($contents as $content)
{
	$html .= $content->getUserUI();
}
$html .= "</div>\n";

/* Node section */
$html .= "<div class='portal_node_section'>\n";
$html .= "<img class='portal_section_logo' src='{$hotspot_logo_url}' alt=''>\n";
$html .= "Content from \"<a href='{$hotspot_logo_url}'>{$node_name}</a>\"\n";
$contents = $node->getAllContent();
foreach ($contents as $content)
{
	$html .= $content->getUserUI();
}
$html .= "</div>\n";

/* User section */
$html .= "<div class='portal_user_section'>\n";
$html .= _("My content")."\n";
$html .= "</div>\n";
$html .= "</div>\n"; /* end portal_container */
		echo $html;
/* If we have local content, display it. Otherwise, display default */
/*if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.PORTAL_PAGE_NAME)) {
    $smarty->assign("local_content_path", NODE_CONTENT_SMARTY_PATH);
    $smarty->display(NODE_CONTENT_SMARTY_PATH.PORTAL_PAGE_NAME);
} else {
    $smarty->assign("local_content_path", DEFAULT_CONTENT_SMARTY_PATH);
    $smarty->display(DEFAULT_CONTENT_SMARTY_PATH.PORTAL_PAGE_NAME);
}
*/

$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."footer.html");
?>
