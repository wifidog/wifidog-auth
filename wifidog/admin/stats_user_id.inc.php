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
/**@file stats_user_id.inc.php
 * @author Copyright (C) 2005 Philippe April
 */

$userinfo = $userObject->getInfoArray();
$userinfo['account_status_description'] = $account_status_to_text[$userinfo['account_status']];

$html .= "<fieldset class='pretty_fieldset'>\n";
$html .= "<legend>"._("Profile")."</legend>\n";

$html .= "<table>\n";

$html .= "<tr class='odd'>\n";
$html .= "  <th>"._("Username").":</th>\n";
$html .= "  <td>".$userinfo['username']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "  <th>"._("Real Name").":</th>\n";
$html .= "  <td>".$userinfo['real_name']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr class='odd'>\n";
$html .= "  <th>"._("Email").":</th>\n";
$html .= "  <td>".$userinfo['email']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "  <th>"._("Network").":</th>\n";
$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&network_id={$userinfo['account_origin']}'>{$userinfo['account_origin']}</a></td>\n";
$html .= "</tr>\n";

$html .= "<tr class='odd'>\n";
$html .= "  <th>"._("Unique ID").":</th>\n";
$html .= "  <td>".$userinfo['user_id']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "  <th>"._("Member since").":</th>\n";
$html .= "  <td>".strftime("%c", strtotime($userinfo['reg_date']))."</td>\n";
$html .= "</tr>\n";

$html .= "<tr class='odd'>\n";
$html .= "  <th>"._("Account Status").":</th>\n";
$html .= "  <td>".$userinfo['account_status_description']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr>\n";
$html .= "  <th>"._("Website").":</th>\n";
$html .= "  <td>".$userinfo['website']."</td>\n";
$html .= "</tr>\n";

$html .= "<tr class='odd'>\n";
$html .= "  <th>"._("Prefered Locale").":</th>\n";
$html .= "  <td>".$userinfo['prefered_locale']."</td>\n";
$html .= "</tr>\n";

$sql = "select count(distinct user_mac) as nb from connections where user_id = '{$user_id}' {$date_constraint}";
$db->ExecSql($sql, $rows, false);
$amount_of_mac_addresses = $rows[0]['nb'];

$html .= "<tr>\n";
$html .= "  <th>"._("MAC addresses").":</th>\n";
$html .= "  <td>".$amount_of_mac_addresses."</td>\n";
$html .= "</tr>\n";

$html .= "</table>\n";
$html .= "</fieldset>\n";

$html .= "<fieldset class='pretty_fieldset'>\n";
$html .= "<legend>"._("Connections")."</legend>\n";
$html .= "<table class='smaller'>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= "  <th>"._("Logged in")."</th>\n";
$html .= "  <th>"._("Time spent")."</th>\n";
$html .= "  <th>"._("Token status")."</th>\n";
$html .= "  <th>"._("Node")."</th>\n";
$html .= "  <th>"._("IP")."</th>\n";
$html .= "  <th>"._("D")."</th>\n";
$html .= "  <th>"._("U")."</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";

//$connections = $userObject->getConnections();
$sql = "select * from connections where user_id = '{$user_id}' {$date_constraint} ORDER BY timestamp_in DESC";
$db->ExecSql($sql, $connections, false);

// Variables init
$even = 0;
$total = array ();
$total['incoming'] = 0;
$total['outgoing'] = 0;
if ($connections)
{
	foreach ($connections as $connection)
	{
		$timestamp_in = !empty($connection['timestamp_out']) ? strtotime($connection['timestamp_in']) : null;
		$timestamp_out = !empty($connection['timestamp_out']) ? strtotime($connection['timestamp_out']) : null;

		$nodeObject = Node :: getObject($connection['node_id']);
		$total['incoming'] += $connection['incoming'];
		$total['outgoing'] += $connection['outgoing'];
			
		$connection['token_status_description'] = $token_to_text[$connection['token_status']];
		$html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
		if ($even == 0)
			$even = 1;
		else
			$even = 0;
		$html .= "  <td>".strftime("%c", $timestamp_in)."</td>\n";
		if (!empty($timestamp_in) && !empty($timestamp_out))
		{
			$html .= "<td>".seconds_in_words($timestamp_out - $timestamp_in)."</td>\n";
		}
		else
		{
			$html .= "<td>"._("N/A")."</td>\n";
		}
		$html .= "  <td>".$connection['token_status']."</td>\n";
		$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&node_id={$nodeObject->getId()}'>{$nodeObject->getName()}</a></td>\n";
		$html .= "  <td>".$connection['user_ip']."</td>\n";
		$html .= "  <td>".bytes_in_words($connection['incoming'])."</td>\n";
		$html .= "  <td>".bytes_in_words($connection['outgoing'])."</td>\n";
		$html .= "</tr>\n";
	}
}

$html .= "<tr>\n";
$html .= "  <td></td>\n";
$html .= "  <td></td>\n";
$html .= "  <td></td>\n";
$html .= "  <td></td>\n";
$html .= "  <th>"._("Total").":</th>\n";
$html .= "  <th>".bytes_in_words($total['incoming'])."</th>\n";
$html .= "  <th>".bytes_in_words($total['outgoing'])."</th>\n";
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</fieldset>\n";

$sql = "select distinct user_mac,count(user_mac) as nb from connections where user_id = '{$user_id}' {$date_constraint} group by user_mac order by nb desc";
$db->ExecSql($sql, $rows, false);

$html .= "<fieldset class='pretty_fieldset'>\n";
$html .= "<legend>"._("MAC addresses")."</legend>\n";
$html .= "<table>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= "  <th>"._("MAC")."</th>\n";
$html .= "  <th>"._("Count")."</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";

$even = 0;
if($rows)
	foreach ($rows as $row)
	{
		if ($row['user_mac'])
		{
			$html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
			if ($even == 0)
				$even = 1;
			else
				$even = 0;
			$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_mac={$row['user_mac']}'>{$row['user_mac']}</a></td>\n";
			$html .= "  <td>".$row['nb']."</td>\n";
			$html .= "</tr>\n";
		}
	}

$html .= "</table>\n";
$html .= "</fieldset>\n";
?>