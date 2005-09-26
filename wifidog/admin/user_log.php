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
/**@file
 * @author Copyright (C) 2004 Philippe April.
 */
define('BASEPATH', '../');
require_once BASEPATH.'admin/admin_common.php';
require_once BASEPATH.'classes/MainUI.php';

$security = new Security();
$security->requireAdmin();

require_once BASEPATH.'classes/User.php';

$total = array ();
$total['incoming'] = 0;
$total['outgoing'] = 0;

// Process user ID searchbox
$user = User::processSelectUserUI("user_id_searchbox");

if (!empty ($_REQUEST['user_id']) || !empty($user))
{
    header("Location: stats.php?user_id=" . $user->getId());
    exit;
}
else
{
	$smarty->assign('sort_ids', array ('username', 'account_origin', 'reg_date'));
	$smarty->assign('direction_ids', array ('asc', 'desc'));

	$sort = isset ($_REQUEST['sort']) ? $_REQUEST['sort'] : "username";
	$direction = isset ($_REQUEST['direction']) ? $_REQUEST['direction'] : "asc";

	$smarty->assign("sort", $sort);
	$smarty->assign("direction", $direction);

	if (isset ($_REQUEST["page"]) && is_numeric($_REQUEST["page"]))
	{
		$current_page = $_REQUEST["page"];
	}
	else
	{
		$current_page = 1;
	}
	$smarty->assign("page", $current_page);

	$per_page = 100;
	$offset = (($current_page * $per_page) - $per_page);
	$db->ExecSqlUniqueRes("SELECT count(user_id) as count FROM users", $count);

	$pages = $count['count'] / $per_page;
	for ($i = 1; $i <= $pages +1; $i ++)
	{
		$smarty->append("pages", array ('number' => $i, 'selected' => ($i == $current_page),));
	}

	$db->ExecSql("SELECT user_id,username,account_origin,reg_date,account_status FROM users ORDER BY $sort $direction LIMIT $per_page OFFSET $offset", $users_res);
	if ($users_res)
	{
		$smarty->assign("users_array", $users_res);
	}
	else
	{
		$smarty->assign("error", _('Internal error.'));
	}

	$smarty->assign("account_status_to_text", $account_status_to_text);
	
	// Display user selection form
	$html = "";
	$html .= "<fieldset class=\"pretty_fieldset\">";
	$html .= "<legend>" . _("Find a user ID : ") . "</legend>";
	$html .= "<form method='POST' action=''>\n";
	$html .= User::getSelectUserUI("user_id_searchbox")."<br>\n";
	$html .= "<input type='submit'>\n";
	$html .= "</form><p>\n";
	$html .= "</fieldset>";
	$html .= $smarty->fetch("admin/templates/user_log.html");
	
    $ui=new MainUI();
    $ui->setToolSection('ADMIN');
    $ui->setMainContent($html);
    $ui->display();
}
?>
