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
define ('CONTENT_ADMIN_HREF', 'content_admin.php');
require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/Style.php';

$smarty->display("templates/header.html");
$html = '';

if (empty ($_REQUEST['action']))
{
	$_REQUEST['action']='list_all_content';
}

if ($_REQUEST['action'] == 'list_all_content')
{
	$sql = "SELECT * FROM content";
	$db->ExecSql($sql, $results, false);
	if($results!=null)
	{
	echo "<table>\n";
	echo "<tr><th>"._("Title")."</th><th>"._("Content type")."</th><th>"._("Description")."</th></tr>\n";
		
	foreach ($results as $row)
	{
		if(!empty($row['title']))
		{
		$title = Content::getContent($row['title']);
		$title_ui = $title->getUserUI();
		}
		else
		{
			$title_ui =null;
		}
		
				if(!empty($row['description']))
		{
		$description = Content::getContent($row['description']);
		$description_ui = $description->getUserUI();
		}
		else
		{
			$description_ui =null;
		}
		$href = "?content_id=$row[content_id]&action=edit";
		echo "<tr><td>$title_ui</td><td><a href='$href'>$row[content_type]</a></td><td>$description_ui</td></tr>\n";
		
	}
			echo "</table>\n";
	}
	else
	{
		echo "<p>No results found</p>";
	}
	$html .= '<form action="" method="get">';
	$html .= "<input type='hidden' name='action' value='edit'>\n";
	$html .= "<input type=submit name='new_submit' value='"._("Add new content")."'>\n";
	$html .= '</form>';
}
	if ($_REQUEST['action'] == 'save')
	{
			$content = Content :: getContent($_REQUEST['content_id']);
			$html .= $content->processAdminInterface();
			$_REQUEST['action'] = 'edit';
	}


	if ($_REQUEST['action'] == 'edit')
	{
		if (!empty ($_REQUEST['new_submit']))
		{
			$content = Content :: createNewContent();
		}
		else
		{
			$content = Content :: getContent($_REQUEST['content_id']);
		}
	$html .= "<form action='".CONTENT_ADMIN_HREF."' method='post'>";
	$html .= "<input type='hidden' name='content_id' value='".$content->GetId()."'>\n";
	$html .= $content->getAdminInterface();
	$html .= "<input type='hidden' name='action' value='save'>\n";
	$html .= "<input type=submit name='save_submit' value='"._("Save")."'>\n";
	$html .= '</form>';

	}
	
	if(false)
	{
		if ($user == null)
		{
			echo "<H1>Erreur, l'usager ".$_REQUEST['user_admin_username_orig']." est introuvable</H1>\n";
		}
		else
		{
			if (!empty ($_REQUEST['action']) && $_REQUEST['action'] == 'save')
			{
				$user->TraiterInterfaceAdmin();
			}

			if ($_REQUEST['action'] == 'save' && !empty ($_REQUEST['delete_action']) && !empty ($_REQUEST['delete_confirm']) && $_REQUEST['delete_confirm'] == 'true')
			{
				echo "<H1>Je tente d'effacer la l'administrateur</H1>\n";
				$user->Delete();
				echo "<H1>Terminé, si vous ne voyez rien plus haut, c'est que l'administrateur a été effacée avec succès.</H1>\n";
			}
			else
			{
				echo '<form action="" method="get">';
				$user->AfficherInterfaceAdmin();
				echo "<input type='hidden' name='action' value='save'>\n";
				echo "<input type='hidden' name='user_admin_username_orig' value='".$user->GetId()."'>\n";
				echo "<input type=submit name='save_action' value='Enregistrer'>\n";
				echo "<input type=submit name='delete_action' value='Effacer'><input type='checkbox' name='delete_confirm' value='true'>Oui, je suis certain.\n";

				echo '</form>';
			}
		}

	}
			echo $html;
		$smarty->display("templates/footer.html");
?>

