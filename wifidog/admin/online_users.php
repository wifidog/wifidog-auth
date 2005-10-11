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
require_once BASEPATH.'classes/Node.php';

$security = new Security();
$security->requireAdmin();

global $db;
$online_users = null;
$db->ExecSql("SELECT name, username, account_origin, timestamp_in, incoming, outgoing FROM connections,users,nodes WHERE token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY account_origin, name, timestamp_in DESC", $online_users);
$smarty->assign("users_array", $online_users);

require_once BASEPATH.'classes/MainUI.php';
$ui = new MainUI();
$ui->setToolSection('ADMIN');
$ui->setMainContent($smarty->fetch("admin/templates/online_users.html"));
$ui->display();
?>