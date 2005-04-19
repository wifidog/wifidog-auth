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
/**@file index.php
 * @author Copyright (C) 2005 Philippe April
 */

define('BASEPATH', '../');
require_once 'admin_common.php';
require_once BASEPATH.'classes/Content.php';

$smarty->display("templates/header.html");
$html = '';
$html .= "<ul>\n";
$html .= "<li><a href='user_log.php'>"._("User logs")."</a></li>\n";
$html .= "<li><a href='online_users.php'>"._("Online Users")."</a></li>\n";
$html .= "<li><a href='user_stats.php'>"._("Cumulative user statistics")."</a></li>\n";
$html .= "<li><a href='hotspot_log.php'>"._("Hotspot logs")."</a></li>\n";
$html .= "<li><a href='import_user_database.php'>"._("Import NoCat user database")."</a></li>\n";
$html .= "<li><a href='hotspot.php'>"._("Hotspot creation and configuration")."</a> - Beta</li>\n";
$html .= "<li><a href='owner_sendfiles.php'>"._("Hotspot owner administration")."</a> - Beta</li>\n";

/* Node admin */
$html .= "<div class='admin_section_container'>\n";
$html .= '<form action="'.GENERIC_OBJECT_ADMIN_ABS_HREF.'" method="get">';
$html .= "<div class='admin_section_title'>"._("Node administration:")." </div>\n";

$html .= "<div class='admin_section_data'>\n";
$html .= "<input type='hidden' name='action' value='edit'>\n";
$html .= "<input type='hidden' name='object_class' value='Node'>\n";

$current_user = User :: getCurrentUser();
if ($current_user->isSuperAdmin())
{
	$sql_additional_where = '';
}
else
{
	$sql_additional_where = "AND node_id IN (SELECT node_id from node_owners WHERE user_id='".$current_user->getId()."')";
}
$html .= Node :: getSelectNodeUI('object_id', $sql_additional_where);
$html .= "</div>\n";
$html .= "<div class='admin_section_tools'>\n";

$html .= "<input type=submit name='edit_submit' value='"._("Edit")."'>\n";
$html .= "</div>\n";
$html .= '</form>';
$html .= "</div>\n";

/* Network admin */
$html .= "<div class='admin_section_container'>\n";
$html .= '<form action="'.GENERIC_OBJECT_ADMIN_ABS_HREF.'" method="post">';
$html .= "<div class='admin_section_title'>"._("Network administration:")." </div>\n";

$html .= "<div class='admin_section_data'>\n";
$html .= "<input type='hidden' name='action' value='edit'>\n";
$html .= "<input type='hidden' name='object_class' value='Network'>\n";
$html .= Network :: getSelectNetworkUI('object_id');
$html .= "</div>\n";
$html .= "<div class='admin_section_tools'>\n";

$html .= "<input type=submit name='edit_submit' value='"._("Edit")."'>\n";
$html .= "</div>\n";
$html .= '</form>';
$html .= "</div>\n";

$html .= "<li><a href='content_admin.php'>"._("Content manager")."</a></li>\n";
$html .= "</ul>\n";

echo $html;

$smarty->display("templates/footer.html");
?>