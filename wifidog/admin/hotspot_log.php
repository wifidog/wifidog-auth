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
   * @author Copyright (C) 2004 Benoit Grégoire
   */

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';
require_once (BASEPATH.'include/user_management_menu.php');
require_once BASEPATH.'classes/Statistics.php';
require_once BASEPATH.'classes/Security.php';
$security=new Security();
$security->requireAdmin();

$style = new Style();
$stats=new Statistics();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' node statistics');
    echo "<div id='head'><h1>". HOTSPOT_NETWORK_NAME ." hotspot statistics</h1></div>\n";    
echo "<div id='navLeft'>\n";
//echo get_user_management_menu();
echo "</div>\n";

echo "<div id='content'>\n";

$db->ExecSql("SELECT node_id, name, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip,
 CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes ORDER BY node_id",$node_results, false);

	foreach($node_results as $node_row)
	{
	echo "<p><table class='spreadsheet'>\n";
	echo "<thead><tr class='spreadsheet'><th class='spreadsheet' colspan=5>Node $node_row[node_id]: $node_row[name]</th></tr>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Status</th>\n";
	echo "<td class='spreadsheet'>\n";
	if($node_row['is_up']=='t')
	{
	echo "<img src='".BASE_URL_PATH . "images/hotspot_status_up.png'>";
	}
	else
	{
	echo "<img src='".BASE_URL_PATH . "images/hotspot_status_down.png'>";
	$duration = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
	echo $duration['days'].'days '.$duration['hours'].'h '.$duration['minutes'].'min';
	}
	echo "</td></tr>\n";

	echo "<tr class='spreadsheet'><th class='spreadsheet'>Local content demo</th>\n";
	echo "<td class='spreadsheet'>\n";
	echo "<a href='".BASE_SSL_PATH."login/index.php?gw_id=$node_row[node_id]&gw_address=127.0.0.1&gw_port=80'>Login page</a><br />\n";
	echo "<a href='".BASE_URL_PATH."portal/index.php?gw_id=$node_row[node_id]'>Portal page</a>\n";
	echo "</td></tr>\n";
		
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Opened on</th>\n";
	echo "<td class='spreadsheet'>$node_row[creation_date]</td></tr>\n";

	echo "<tr class='spreadsheet'><th class='spreadsheet'>Successfull connections<br />(successfull means with data actually transferred)</th><td class='spreadsheet'>\n";
	$num_online_users = $stats->getNumOnlineUsers($node_row['node_id']);
	echo "Currently online: $num_online_users<br />\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(conn_id) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	echo "Cumulative: $results[count]<br />\n";

	$results = null;
	$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1",$results, false);
	echo "First: $results[timestamp_in]<br />\n";
	$last_conn = $stats->getLastConnDate($node_row['node_id']);
echo "Last: $last_conn</td></tr>\n";

	echo "<tr class='spreadsheet'><th class='spreadsheet'>Unique users (successfull connections only)</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT user_id) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	echo "Total: $results[count]<br />";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT round(CAST( (SELECT SUM(daily_connections) FROM (SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY date_trunc('day', timestamp_in)) AS daily_connections_table) / (EXTRACT(EPOCH FROM (NOW()-(SELECT timestamp_in FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1)) )/(3600*24)) AS numeric),2) AS connections_per_day",$results, false);
echo "Average: $results[connections_per_day] per day<br/>\n";

	$results = null;
	$db->ExecSql("
		SELECT SUM(daily_connections) AS visits, date_trunc('month', date) AS month FROM (
			SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) AS date FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY date_trunc('day', timestamp_in)
		) AS daily_connections_table GROUP BY date_trunc('month', date) ORDER BY month DESC
	",$results, false);
	echo "Monthly visits:<br />";
	if ($results!=null)
	{
		foreach($results as $row)
		{
		echo "$row[month]: $row[visits] visits<br />";
		}
	}
	echo "</td></tr>\n";

	echo "<tr class='spreadsheet'><th class='spreadsheet'>Busyest times<br />Repeat connection from users are counted</th><td class='spreadsheet'>\n";
	/* Hour of the day */
	$results = null;
	$db->ExecSql("
		SELECT COUNT(conn_id) AS connections, extract('hour' from timestamp_in) AS hour FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY extract('hour' from timestamp_in)
		 ORDER BY hour
	",$results, false);
	if ($results!=null)
	{
	echo "<table class='spreadsheet'><thead>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet' colspan=2>Hour of the day</th></tr>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Start hour</th><th class='spreadsheet'>Number of connections</th></thead>\n";
		foreach($results as $row)
		{
		echo "<tr><td class='spreadsheet'>$row[hour]</td><td class='spreadsheet'>$row[connections]</td></tr>\n";
		}
	echo "</table>\n";
	}
	
	/*Day of the week*/
		$results = null;
	$db->ExecSql("
		SELECT COUNT(conn_id) AS connections, extract('dow' from timestamp_in) AS day FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0) GROUP BY extract('dow' from timestamp_in)
		 ORDER BY day
	",$results, false);
	if ($results!=null)
	{
	echo "<table class='spreadsheet'><thead>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet' colspan=2>Day of the week (0 is sunday)</th></tr>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Start day</th><th class='spreadsheet'>Number of connections</th></thead>\n";
		foreach($results as $row)
		{
		echo "<tr><td class='spreadsheet'>$row[day]</td><td class='spreadsheet'>$row[connections]</td></tr>\n";
		}
	echo "</table>\n";
	}
	echo "</td></tr>\n";

	
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Cumulative network traffic</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT SUM(incoming)/1048576 as in, SUM(outgoing)/1048576 as out FROM connections WHERE node_id='$node_row[node_id]' AND (incoming!=0 OR outgoing!=0)",$results, false);
	echo "$results[in] MB in / $results[out] MB out</td></tr>\n";
			
	echo "</table></p>\n";
}

	echo "<p><table class='spreadsheet'>\n";
	echo "<thead><tr class='spreadsheet'><th class='spreadsheet' colspan=5>Cumulative network statistics</th></tr>\n";
	
	echo "<tr class='spreadsheet'><th class='spreadsheet'>First successfull connection</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1",$results, false);
	echo "$results[timestamp_in]</td></tr>\n";

	
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Total number of unique successfull connections</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(conn_id) FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
	echo "$results[count]</td></tr>\n";

	
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Total number of unique users (successfull connections only)</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT user_id) FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
	echo "$results[count]</td></tr>\n";


	echo "<tr class='spreadsheet'><th class='spreadsheet'>Network traffic</th><td class='spreadsheet'>\n";
	$results = null;
	$db->ExecSqlUniqueRes("SELECT SUM(incoming)/1048576 as in, SUM(outgoing)/1048576 as out FROM connections WHERE (incoming!=0 OR outgoing!=0)",$results, false);
	echo "$results[in] MB in / $results[out] MB out</td></tr>\n";
			
			
	echo "</table></p>\n";

    echo "</div>\n";	

echo $style->GetFooter();
?>
