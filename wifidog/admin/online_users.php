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
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @copyright  2004-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load common include file
 */
require_once('admin_common.php');

require_once('classes/Node.php');
require_once('classes/MainUI.php');

Security::requirePermission(Permission::P('NETWORK_PERM_VIEW_ONLINE_USERS'), Network::getCurrentNetwork());

$db = AbstractDb::getObject();
$smarty = SmartyWifidog::getObject();
$online_users = null;
$db->execSql("SELECT connections.user_id, name, username, account_origin, timestamp_in, incoming, outgoing FROM connections,users,nodes WHERE token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND nodes.node_id=connections.node_id ORDER BY account_origin, timestamp_in DESC", $online_users);
$smarty->assign("users_array", $online_users);

$ui = MainUI::getObject();
$ui->addContent('main_area_middle', $smarty->fetch("admin/templates/online_users.html"));
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>