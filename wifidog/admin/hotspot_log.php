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
define('BASEPATH','../');
require_once 'admin_common.php';

$security = new Security();
$security->requireAdmin();

$specific_node_where = "";
if(!empty($_REQUEST["node_id"]))
{
	$node_id = $db->EscapeString($_REQUEST["node_id"]);
	$specific_node_where = "WHERE node_id = '$node_id'";
}
	
$db->ExecSql("SELECT node_id, name, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes $specific_node_where ORDER BY node_id", $node_results, false);

foreach($node_results as $node_row) {
	$node_row['duration'] = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
	$node_row['num_online_users'] = $stats->getNumOnlineUsers($node_row['node_id']);

	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(conn_id) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	$node_row['cumulative'] = $results['count'];

	$results = null;
	$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1",$results, false);
	$node_row['first'] = $results['timestamp_in'];
	$node_row['last'] = $stats->getLastConnDate($node_row['node_id']);

	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT user_id) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	$node_row['total'] = $results['count'];

	$results = null;
	$db->ExecSqlUniqueRes("SELECT round(CAST( (SELECT SUM(daily_connections) FROM (SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY date_trunc('day', timestamp_in)) AS daily_connections_table) / (EXTRACT(EPOCH FROM (NOW()-(SELECT timestamp_in FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1)) )/(3600*24)) AS numeric),2) AS connections_per_day", $results, false);
	$node_row['average'] = $results['connections_per_day'];

	$results = null;
	$db->ExecSql("SELECT SUM(daily_connections) AS visits, date_trunc('month', date) AS month FROM (SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) AS date FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY date_trunc('day', timestamp_in)) AS daily_connections_table GROUP BY date_trunc('month', date) ORDER BY month DESC", $results, false);

	if ($results != null) {
		$node_row['monthly_visits'] = array();
		foreach($results as $row) {
			array_push($node_row['monthly_visits'], array(
				"month" => $row['month'],
				"visits" => $row['visits']
			));
		}
	}

	$results = null;
	$db->ExecSql("SELECT COUNT(conn_id) AS connections, extract('hour' from timestamp_in) AS hour FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY extract('hour' from timestamp_in) ORDER BY hour",$results, false);
	if ($results != null) {
		$node_row['hourly_visits'] = array();
		foreach($results as $row) {
			array_push($node_row['hourly_visits'], array(
				"hour" => $row['hour'],
				"visits" => $row['connections']
			));
		}
	}

	$results = null;
	$db->ExecSql("SELECT COUNT(conn_id) AS connections, extract('dow' from timestamp_in) AS day FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY extract('dow' from timestamp_in) ORDER BY day",$results, false);
	if ($results!=null) {
		$node_row['daily_visits'] = array();
		foreach($results as $row) {
			array_push($node_row['daily_visits'], array(
				"day" => $row['day'],
				"visits" => $row['connections']
			));
		}
	}
	
	$results = null;
	$db->ExecSqlUniqueRes("SELECT SUM(incoming)/1048576 as in, SUM(outgoing)/1048576 as out FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	$node_row['network_in'] = $results['in'];
	$node_row['network_out'] = $results['out'];

	$smarty->append("nodes", $node_row);
}

$results = null;
$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1",$results, false);
$smarty->assign("first_connection", $results['timestamp_in']);
	
$results = null;
$db->ExecSqlUniqueRes("SELECT COUNT(conn_id) FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
$smarty->assign("total_unique_success", $results['count']);
	
$results = null;
$db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT user_id) FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
$smarty->assign("total_unique_users", $results['count']);

$results = null;
$db->ExecSqlUniqueRes("SELECT SUM(incoming)/1048576 as in, SUM(outgoing)/1048576 as out FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
$smarty->assign("total_network_in", $results['in']);
$smarty->assign("total_network_out", $results['out']);

require_once BASEPATH.'classes/MainUI.php';

$ui=new MainUI();
$ui->setToolSection('ADMIN');
$ui->setMainContent($smarty->fetch("admin/templates/hotspot_log.html"));
$ui->display();

?>