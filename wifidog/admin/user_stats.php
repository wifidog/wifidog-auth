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
echo $style->GetHeader(HOTSPOT_NETWORK_NAME._(' cumulative user statistics'));
    echo "<div id='head'><h1>". HOTSPOT_NETWORK_NAME ._(' cumulative user statistics')."</h1></div>\n";    
echo "<div id='navLeft'>\n";
//echo get_user_management_menu();
echo "</div>\n";

echo "<div id='content'>\n";
$stats=new Statistics();

	echo "<p><table class='spreadsheet'>\n";
	$row = null;
		echo "<thead><tr class='spreadsheet'><th class='spreadsheet' colspan=5>Cumulative user statistics</th></tr></thead>\n";

	echo "<tr class='spreadsheet'><th class='spreadsheet'>Total number of users in the database</th><td class='spreadsheet'>\n";
echo $stats->getNumUsers();
	echo "</td></tr>\n";
	
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Number of validated users</th><td class='spreadsheet'>\n";
echo $stats->getNumValidUsers();

//echo $stats->getNumOnlineUsers(null);
	echo "</td></tr>\n";


	echo "<tr class='spreadsheet'><th class='spreadsheet'>User registration</th><td class='spreadsheet'>\n";

	$results = null;
	$db->ExecSql("
		SELECT COUNT(users) AS num_users, date_trunc('month', reg_date) AS month FROM users  WHERE account_status = ".ACCOUNT_STATUS_ALLOWED." GROUP BY date_trunc('month', reg_date) ORDER BY month DESC
	",$results, false);
	echo "<p>Only validated users are considered in the following table</p>";
	if ($results!=null)
	{
		foreach($results as $row)
		{
		echo "$row[month]: $row[num_users] new users<br />";
		}
	}
	echo "</td></tr>\n";
	
		echo "<tr class='spreadsheet'><th class='spreadsheet'>Ten most mobile users</th><td class='spreadsheet'>\n";

	$results = null;
	$db->ExecSql("
		SELECT COUNT(DISTINCT node_id) AS num_hotspots_visited, user_id FROM users NATURAL JOIN connections WHERE (incoming!=0 OR outgoing!=0) GROUP BY user_id ORDER BY num_hotspots_visited DESC LIMIT 10
	",$results, false);
	if ($results!=null)
	{
	echo "<table  class='spreadsheet'><tr class='spreadsheet'><th class='spreadsheet'>User</th><th class='spreadsheet'>Number of hotspots visited</th></tr>";
		foreach($results as $row)
		{
		echo "<tr class='spreadsheet'><td class='spreadsheet'>$row[user_id]</td><td class='spreadsheet'>$row[num_hotspots_visited]</td><tr>\n";
		}
	echo "</td></tr></table></p>\n";
	}
	echo "</td></tr>\n";

		
		echo "<tr class='spreadsheet'><th class='spreadsheet'>Ten most frequent users</th><td class='spreadsheet'>\n";

	$results = null;
	$db->ExecSql("SELECT COUNT(user_id) AS active_days, user_id FROM (
			SELECT DISTINCT user_id, date_trunc('day', timestamp_in) AS date FROM connections WHERE (incoming!=0 OR outgoing!=0) GROUP BY date,user_id) as user_active_days GROUP BY user_id ORDER BY active_days DESC LIMIT 10
		
	",$results, false);
	if ($results!=null)
	{
	echo "<table  class='spreadsheet'><tr class='spreadsheet'><th class='spreadsheet'>User</th><th class='spreadsheet'>Number of distinct days user has used the network</th></tr>";
		foreach($results as $row)
		{
		echo "<tr class='spreadsheet'><td class='spreadsheet'>$row[user_id]</td><td class='spreadsheet'>$row[active_days]</td><tr>\n";
		}
	echo "</td></tr></table></p>\n";
	}
	echo "</td></tr>\n";

			echo "<tr class='spreadsheet'><th class='spreadsheet'>Ten largest apetite for bandwidth</th><td class='spreadsheet'>\n";

	$results = null;
	$db->ExecSql("
			SELECT DISTINCT user_id, SUM((incoming+outgoing)/1048576) AS total, SUM((incoming/1048576)) AS total_incoming, SUM((outgoing/1048576)) AS total_outgoing FROM connections GROUP BY user_id ORDER BY total DESC limit 10
		
	",$results, false);
	if ($results!=null)
	{
	echo "<table  class='spreadsheet'><tr class='spreadsheet'><th class='spreadsheet'>User</th><th class='spreadsheet'>Total (MB)</th><th class='spreadsheet'>Incoming (MB)</th><th class='spreadsheet'>Outgoing (MB)</th></tr>";
		foreach($results as $row)
		{
		echo "<tr class='spreadsheet'><td class='spreadsheet'>$row[user_id]</td><td class='spreadsheet'>$row[total]</td><td class='spreadsheet'>$row[total_incoming]</td><td class='spreadsheet'>$row[total_outgoing]</td><tr>\n";
		}
	echo "</td></tr></table></p>\n";
	}
	echo "</td></tr>\n";

	echo "</table></p>\n";

    echo "</div>\n";	

echo $style->GetFooter();
?>
