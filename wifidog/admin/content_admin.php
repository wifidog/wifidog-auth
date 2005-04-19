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
/**@file content_admin.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */
define('BASEPATH', '../');
require_once 'admin_common.php';
define('CONTENT_ADMIN_HREF', 'content_admin.php');
require_once BASEPATH.'classes/Content.php';

$smarty->display("templates/header.html");
$html = '';

if (empty ($_REQUEST['action']))
{
	$_REQUEST['action'] = 'list_all_content';
}

if ($_REQUEST['action'] == 'list_all_content')
{
	$sql = "SELECT * FROM content";
	$db->ExecSql($sql, $results, false);
	if ($results != null)
	{
		$html.= "<table>\n";
		$html.=  "<tr><th>"._("Title")."</th><th>"._("Content type")."</th><th>"._("Description")."</th></tr>\n";

		foreach ($results as $row)
		{
			$content=Content :: getObject($row['content_id']);
			if (!empty ($row['title']))
			{
				$title = Content :: getObject($row['title']);
				$title_ui = $title->getUserUI();
			}
			else
			{
				$title_ui = null;
			}

			if (!empty ($row['description']))
			{
				$description = Content :: getObject($row['description']);
				$description_ui = $description->getUserUI();
			}
			else
			{
				$description_ui = null;
			}
			$href = GENERIC_OBJECT_ADMIN_ABS_HREF."?object_id=$row[content_id]&object_class=Content&action=edit";
			$html.=  "<tr><td>$title_ui</td><td><a href='$href'>$row[content_type]</a></td><td>$description_ui</td>\n";
			$href = GENERIC_OBJECT_ADMIN_ABS_HREF."?object_id=$row[content_id]&object_class=Content&action=delete";
			if($content->isOwner(User::getCurrentUser()))
			$html.=  "<td><a href='$href'>Delete</a></td>";
			$html.= "</tr>\n";

		}
		$html.= "</table>\n";
	}
	else
	{
		$html.=  "<p>No results found</p>";
	}
	$html .= '<form action="'.GENERIC_OBJECT_ADMIN_ABS_HREF.'" method="get">';
	$html .= "<input type='hidden' name='action' value='new'>\n";
	$html .= "<input type='hidden' name='object_class' value='Content'>\n";
	$html .= "<input type=submit name='new_submit' value='"._("Add new content")."'>\n";
	$html .= '</form>';
}

echo $html;
$smarty->display("templates/footer.html");
?>


