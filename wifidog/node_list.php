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

define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';
require_once (BASEPATH.'include/user_management_menu.php');

$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' node list');
    echo "<div class=content>\n";


	
    echo "<h1>". HOTSPOT_NETWORK_NAME ." node list</h1>\n";


$db->ExecSql("SELECT node_id, name, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip,
 CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes ORDER BY node_id",$node_results, false);
echo "<table class='spreadsheet'>\n";
	echo "<thead><tr class='spreadsheet'><th class='spreadsheet' colspan=5>Status of all nodes of the ".HOTSPOT_NETWORK_NAME." network</th></tr>\n";
	echo "<tr class='spreadsheet'><th class='spreadsheet'>Status</th>\n";
	echo "<th class='spreadsheet'>Id</th>\n";
	echo "<th class='spreadsheet'>Name</th>\n";
	echo "<th class='spreadsheet'>Local content demo</th>\n";
	echo "<th class='spreadsheet'>Opened on</th>\n";
	echo "</tr></thead\n";
	foreach($node_results as $node_row)
	{
		echo "<tr class='spreadsheet'>\n";
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
		echo "</td>\n";
		echo "<td class='spreadsheet'>$node_row[node_id]</td>\n";
		echo "<td class='spreadsheet'>$node_row[name]</td>\n";
		echo "<td class='spreadsheet'>\n";
		echo "<a href='".BASE_SSL_PATH."login/index.php?gw_id=$node_row[node_id]&gw_address=127.0.0.1&gw_port=80'>Login page</a><br />\n";
		echo "<a href='".BASE_URL_PATH."portal/index.php?gw_id=$node_row[node_id]'>Portal page</a>\n";
		echo "</td>\n";
		echo "<td class='spreadsheet'>$node_row[creation_date]</td>\n";
		echo "</tr>\n";
}
echo "</table>\n";

    echo "</div>\n";	
echo "<div id='navLeft'>\n";
echo get_user_management_menu();
echo "</div>\n";
echo $style->GetFooter();
?>
