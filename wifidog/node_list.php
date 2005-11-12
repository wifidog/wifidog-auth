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
/**@file node_list.php
 * Network status page
 * @author Copyright (C) 2004 Benoit Gr�goire
 */

define('BASEPATH', './');
define('DEFAULT_SORT_BY_PARAM', "name");

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/Utils.php';

global $db;

// Set the sort parameter, defaults to name
if (empty ($_REQUEST["sort_by"]))
{
	$sort_by_param = DEFAULT_SORT_BY_PARAM;
	$sort_by_using_sql = true;
}
else
	// Validate sort parameters
	switch ($_REQUEST["sort_by"])
	{
		// SQL sort parameters
		case "name" :
		case "creation_date" :
			// Fall-through valid parameters
			$sort_by_param = $_REQUEST["sort_by"];
			$sort_by_using_sql = true;
			break;
			// Abstraction-driven sort parameters
		case "node_id" :
		case "num_online_users" :
			$sort_by_param = $_REQUEST["sort_by"];
			$sort_by_using_sql = false;
			break;
		default :
			$sort_by_param = DEFAULT_SORT_BY_PARAM;
			$sort_by_using_sql = true;
	}

// Sort according to above instructions
if ($sort_by_using_sql === true)
	$sql = "SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date, node_deployment_status FROM nodes WHERE node_deployment_status != 'PERMANENTLY_CLOSED' ORDER BY {$sort_by_param}";
else
	$sql = "SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date, node_deployment_status FROM nodes WHERE node_deployment_status != 'PERMANENTLY_CLOSED' ORDER BY ".DEFAULT_SORT_BY_PARAM;
$nodes_results = null;
$db->ExecSql($sql, $nodes_results, false);

if ($nodes_results == null)
	throw new Exception(_("No nodes could not be found in the database"));

$nodes_list = array ();
foreach ($nodes_results as $node_row)
{
	$node = Node :: getObject($node_row['node_id']);
	$node_row['duration'] = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
	$node_row['num_online_users'] = $node->getNumOnlineUsers();
	$nodes_list[] = $node_row;
}

// Sort using PHP	
if ($sort_by_using_sql === false)
{
	// Using natural-sort algorithm .
	switch ($sort_by_param)
	{
		case "node_id" :
			Utils::natsort2d($nodes_list, "node_id");
			break;
		case "num_online_users" :
			Utils::natsort2d($nodes_list, "num_online_users");
			break;
	}
}

// Pass values to Smarty
$smarty->assign("nodes", $nodes_list);
$smarty->assign("sort_by_param", $sort_by_param);

require_once BASEPATH.'classes/MainUI.php';
$ui = new MainUI();
$ui->setMainContent($smarty->fetch("templates/node_list.html"));
$ui->display();

?>