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
/**@file stats_node.inc.php
 * @author Copyright (C) 2005 Philippe April
 */

require_once BASEPATH."admin/graph_common.inc.php";

if ($current_user->isSuperAdmin() || $nodeObject->isOwner($current_user))
{
	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>Profile</legend>";
	$html .= "<table>";

	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Name")."</th>";
	$html .= "  <td>".$nodeObject->getName()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Node ID")."</th>";
	$html .= "  <td>".$nodeObject->getId()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Deployment Status")."</th>";
	$html .= "  <td>".$nodeObject->getDeploymentStatus()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Deployment date")."</th>";
	$html .= "  <td>".$nodeObject->getCreationDate()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Description")."</th>";
	$html .= "  <td>".$nodeObject->getDescription()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Network")."</th>";
	$html .= "  <td>".$nodeObject->getNetwork()->getName()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("GIS Location")."</th>";
	$html .= "  <td>";
	if ($nodeObject->getGisLocation()->getLatitude() && $nodeObject->getGisLocation()->getLongitude())
	{
		$html .= $nodeObject->getGisLocation()->getLatitude()." ".$nodeObject->getGisLocation()->getLongitude();
		$html .= " <input type='button' name='google_maps_geocode' value='"._("Map")."' onClick='window.open(\"hotspot_location_map.php?node_id={$node_id}\", \"hotspot_location\", \"toolbar=0,scrollbars=1,resizable=1,location=0,statusbar=0,menubar=0,width=600,height=600\");'>";
	}
	else
	{
		$html .= _("NOT SET");
	}
	$html .= "  </td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Homepage")."</th>";
	$html .= "  <td><a href='".$nodeObject->getHomePageURL()."'>".$nodeObject->getHomePageURL()."</a></td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Address")."</th>";
	$html .= "  <td>";
	$html .= trim($nodeObject->getCivicNumber()." ".$nodeObject->getStreetName())."<br>";
	$html .= trim($nodeObject->getCity()." ".$nodeObject->getProvince())."<br>";
	$html .= trim($nodeObject->getCountry()." ".$nodeObject->getPostalCode());
	$html .= "</td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Telephone")."</th>";
	$html .= "  <td>".$nodeObject->getTelephone()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Email")."</th>";
	$html .= "  <td><a href='mailto:".$nodeObject->getEmail()."'>".$nodeObject->getEmail()."</a></td>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Transit Info")."</th>";
	$html .= "  <td>".$nodeObject->getTransitInfo()."</td>";
	$html .= "</tr>";
	$html .= "</table>";
	$html .= "</fieldset>";

	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>Status</legend>";
	$html .= "<table>";

	$db->ExecSql("SELECT node_id, name, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes WHERE node_id = '{$node_id}'", $rows, false);

	$html .= ($rows[0]['is_up'] == 't') ? "<tr class='even'>" : "<tr class='red'>";
	$html .= "  <th>"._("WifiDog status")."</th>";
	$html .= "  <td>";
	$html .= ($rows[0]['is_up'] == 't') ? "UP" : "<span class='red'>DOWN</span>";
	$html .= "</td>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Last heartbeat")."</th>";
	$html .= "  <td>".seconds_in_words(time() - strtotime($nodeObject->getLastHeartbeatTimestamp()))." ago</td>";
	$html .= "</tr>";
	$html .= "</tr>";
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("WifiDog version")."</th>";
	$html .= "  <td>".$nodeObject->getLastHeartbeatUserAgent()."</td>";
	$html .= "</tr>";
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("IP Address")."</th>";
	$html .= "  <td>".$nodeObject->getLastHeartbeatIP()."</td>";
	$html .= "</tr>";
	$html .= "</table>";
	$html .= "</fieldset>";

	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>Statistics</legend>";
	$html .= "<table>";

	$db->ExecSql("SELECT round(CAST( (SELECT SUM(daily_connections) FROM (SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) FROM connections WHERE node_id='${node_id}' AND (incoming!=0 OR outgoing!=0) GROUP BY date_trunc('day', timestamp_in)) AS daily_connections_table) / (EXTRACT(EPOCH FROM (NOW()-(SELECT timestamp_in FROM connections WHERE node_id='${node_id}' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1)) )/(3600*24)) AS numeric),2) AS connections_per_day", $rows, false);
	$html .= "<tr class='even'>";
	$html .= "  <th>"._("Average visits per day").":</th>";
	$html .= "  <td>".$rows[0]['connections_per_day']."  </td>";
	$html .= "</tr>";

	$db->ExecSql("SELECT SUM(incoming) AS in, SUM(outgoing) AS out FROM connections WHERE node_id='{$node_id}' ${date_constraint}", $rows, false);
	$html .= "<tr class='odd'>";
	$html .= "  <th>"._("Traffic").":</th>";
	$html .= "  <td>";
	$html .= _("Incoming").": ".bytes_in_words($rows[0]['in']);
	$html .= "<br>";
	$html .= _("Outgoing").": ".bytes_in_words($rows[0]['out']);
	$html .= "<br>";
	$html .= "(for the selected period)";
	$html .= "</td>";

	$html .= "</table>";
	$html .= "</fieldset>";

	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>"._("Connections per hour of the day")."</legend>";
	if (Dependencies :: check("ImageGraph", $errmsg))
	{
		$html .= "<div><img src='graph_per_hour.php?node_id={$node_id}&date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}'></div>";
	}
	else
	{
		$html .= $errmsg;
	}
	$html .= "</fieldset>";

	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>"._("Connections per week day")."</legend>";
	if (Dependencies :: check("ImageGraph", $errmsg))
	{
		$html .= "<div><img src='graph_per_weekday.php?node_id={$node_id}&date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}'></div>";
	}
	else
	{
		$html .= $errmsg;
	}
	$html .= "</fieldset>";

	$html .= "<fieldset class='pretty_fieldset'>";
	$html .= "<legend>"._("Connections per month")."</legend>";
	if (Dependencies :: check("ImageGraph", $errmsg))
	{
		$html .= "<div><img src='graph_per_month.php?node_id={$node_id}&date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}'></div>";
	}
	else
	{
		$html .= $errmsg;
	}
	$html .= "</fieldset>";

	// Only Super admin
	if ($current_user->isSuperAdmin())
	{
		if (isset ($_REQUEST['group_connections']) && $_REQUEST['group_connections'] == "group_connections_by_mac")
		{
			$sql = "SELECT user_mac,COUNT(DISTINCT user_id) AS nb_users,COUNT(user_mac) AS nb_connections,MAX(timestamp_in) AS last_seen FROM connections WHERE node_id='{$node_id}' {$date_constraint} GROUP BY user_mac ORDER BY last_seen DESC";
			$db->ExecSql($sql, $rows, false);

			$number_of_macs = count($rows);

			$html .= "<fieldset class='pretty_fieldset'>";
			$html .= "<legend>Number of unique MACs: {$number_of_macs}</legend>";
			$html .= "<table>";
			$html .= "<thead>";
			$html .= "<tr>";
			$html .= "<th>MAC</th>";
			$html .= "<th>Users count</th>";
			$html .= "<th>Cx count</th>";
			$html .= "<th>Last seen</th>";
			$html .= "</tr>";
			$html .= "</thead>";

			foreach ($rows as $row)
			{
				$html .= "<tr>\n";
				$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_mac={$row['user_mac']}'>{$row['user_mac']}</a></td>\n";
				$html .= "  <td>".$row['nb_users']."</td>\n";
				$html .= "  <td>".$row['nb_connections']."</td>\n";
				$html .= "  <td>".strftime("%Y-%m-%d %H:%M:%S", strtotime($row['last_seen']))."</td>\n";
				$html .= "</tr>\n";
			}

			$html .= "</table>";
			$html .= "</fieldset>";

		}
		elseif (isset ($_REQUEST['group_connections']) && $_REQUEST['group_connections'] == "group_connections_by_user")
		{
			$sql = "select distinct(connections.user_id),count(distinct user_mac) as nb_mac,username,count(connections.user_id) as nb_cx,max(timestamp_in) as last_seen from connections,users where users.user_id=connections.user_id and node_id='{$node_id}' {$date_constraint} group by connections.user_id,username order by nb_cx desc,username";
			$db->ExecSql($sql, $rows, false);

			$number_of_usernames = count($rows);

			$html .= "<fieldset class='pretty_fieldset'>";
			$html .= "<legend>Number of unique Usernames: {$number_of_usernames}</legend>";
			$html .= "<table>";
			$html .= "<thead>";
			$html .= "<tr>";
			$html .= "<th>Username</th>";
			$html .= "<th>MAC Count</th>";
			$html .= "<th>Cx Count</th>";
			$html .= "<th>Last seen</th>";
			$html .= "</tr>";
			$html .= "</thead>";

			foreach ($rows as $row)
			{
				$html .= "<tr>\n";
				$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
				$html .= "  <td>".$row['nb_mac']."</td>\n";
				$html .= "  <td>".$row['nb_cx']."</td>\n";
				$html .= "  <td>".strftime("%Y-%m-%d %H:%M:%S", strtotime($row['last_seen']))."</td>\n";
				$html .= "</tr>\n";
			}

			$html .= "</table>";
			$html .= "</fieldset>";

		}
		else
		{
			$sql = "select *,users.username from connections,users where users.user_id=connections.user_id and node_id='{$node_id}' {$date_constraint} order by timestamp_in desc";
			$db->ExecSql($sql, $rows, false);

			$number_of_connections = count($rows);

			$html .= "<fieldset class='pretty_fieldset'>";
			$html .= "<legend>Number of non-unique connections: {$number_of_connections}</legend>";
			$html .= "<table>";
			$html .= "<thead>";
			$html .= "<tr>";
			$html .= "<th>Username</th>";
			$html .= "<th>MAC</th>";
			$html .= "<th>Date</th>";
			$html .= "<th>Time spent</th>";
			$html .= "</tr>";
			$html .= "</thead>";

			$even = 0;
			if ($rows)
			{
				foreach ($rows as $row)
				{
					if ($row['timestamp_in'])
						$timestamp_in = strtotime($row['timestamp_in']);
					else
						$timestamp_in = -1;

					if ($row['timestamp_out'])
						$timestamp_out = strtotime($row['timestamp_out']);
					else
						$timestamp_out = -1;

					if ($timestamp_in != -1 && $timestamp_out != -1)
					{
						$html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
						if ($even == 0)
							$even = 1;
						else
							$even = 0;
						$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
						$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_mac={$row['user_mac']}'>{$row['user_mac']}</a></td>\n";
						$html .= "  <td>".utf8_encode(strftime("%c", strtotime($row['timestamp_in'])))."</td>\n";
						$html .= "  <td>";
						if ($timestamp_in != -1 && $timestamp_out != -1)
						{
							$html .= seconds_in_words($timestamp_out - $timestamp_in);
						}
						$html .= "</td>\n";
						$html .= "</tr>\n";
					}
				}
			}

			$html .= "</table>\n";
			$html .= "</fieldset>\n";

			/* Users who signed up here */
			$sql = "select connections.user_id,users.username,users.reg_date FROM connections,nodes,users where timestamp_in IN (SELECT MIN(timestamp_in) as first_connection FROM connections GROUP BY user_id) ${date_constraint} AND users.user_id=connections.user_id AND connections.node_id='{$node_id}' AND nodes.node_id='{$node_id}' AND reg_date >= creation_date ORDER BY reg_date DESC";
			$db->ExecSql($sql, $rows, false);

			$html .= "<fieldset class='pretty_fieldset'>";
			$html .= "<legend>"._("Users who signed up here")."</legend>";
			$html .= "<table>";
			$html .= "<thead>";
			$html .= "<tr>";
			$html .= "<th>"._("Username")."</th>";
			$html .= "<th>"._("Registration date")."</th>";
			$html .= "</tr>";
			$html .= "</thead>";

			$even = 0;
			$total = 0;
			if ($rows)
			{
				foreach ($rows as $row)
				{
					$html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
					if ($even == 0)
						$even = 1;
					else
						$even = 0;

					$total ++;

					$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
					$html .= "  <td>".utf8_encode(strftime("%c", strtotime($row['reg_date'])))."</td>\n";
					$html .= "</tr>\n";
				}
			}

			$html .= "<tr>\n";
			$html .= "  <th>"._("Total").":</th>\n";
			$html .= "  <th>".$total."</th>\n";
			$html .= "</tr>\n";
			$html .= "</table>\n";
			$html .= "</fieldset>\n";
		}
	}
}
?>