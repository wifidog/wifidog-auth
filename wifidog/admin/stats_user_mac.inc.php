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
/**@file stats_user_mac.inc.php
 * @author Copyright (C) 2005 Philippe April
 */

$sql = "select *,nodes.name,users.username from connections,nodes where nodes.node_id=connections.node_id and users.user_id=connections.user_id and user_mac = '{$_REQUEST['user_mac']}' {$date_constraint} order by timestamp_in desc";
$db->ExecSql($sql, $rows, false);

$html .= "<fieldset class='pretty_fieldset'>\n";
$html .= "<legend>"._("Connections")."</legend>\n";
$html .= "<table>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= "  <th>"._("Username")."</th>\n";
$html .= "  <th>"._("Date")."</th>\n";
$html .= "  <th>"._("Node")."</th>\n";
$html .= "  <th>"._("Time spent")."</th>\n";
$html .= "  <th>"._("D")."</th>\n";
$html .= "  <th>"._("U")."</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";

// Vars init
$even = 0;
$total = array ();
$total['incoming'] = 0;
$total['outgoing'] = 0;
$total['time_spent'] = 0;
foreach ($rows as $row)
{
	$timestamp_in = strtotime($row['timestamp_in']);
	$timestamp_out = strtotime($row['timestamp_out']);

	$total['incoming'] += $row['incoming'];
	$total['outgoing'] += $row['outgoing'];

	$html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
	if ($even == 0)
		$even = 1;
	else
		$even = 0;
	$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
	$html .= "  <td>".utf8_encode(strftime("%c", strtotime($row['timestamp_in'])))."</td>";
	$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&node_id={$row['node_id']}'>{$row['name']}</a></td>";
	if ($timestamp_in != -1 && $timestamp_out != -1)
	{
		$total['time_spent'] += ($timestamp_out - $timestamp_in);
		$html .= "  <td>".seconds_in_words($timestamp_out - $timestamp_in)."</td>";
	}
	else
	{
		$html .= "  <td></td>";
	}
	if ($row['incoming'])
		$html .= "<td>".bytes_in_words($row['incoming'])."</td>\n";
	else
		$html .= "<td></td>\n";

	if ($row['outgoing'])
		$html .= "<td>".bytes_in_words($row['outgoing'])."</td>\n";
	else
		$html .= "<td></td>\n";

	$html .= "</tr>";
}

$html .= "<tr>\n";
$html .= "  <td></td>\n";
$html .= "  <td></td>\n";
$html .= "  <th>"._("Total").":</th>\n";
$html .= "  <th>".seconds_in_words($total['time_spent'])."</th>\n";
$html .= "  <th>".bytes_in_words($total['incoming'])."</th>\n";
$html .= "  <th>".bytes_in_words($total['outgoing'])."</th>\n";
$html .= "</tr>\n";

$html .= "</table>\n";
$html .= "</fieldset>\n";
?>