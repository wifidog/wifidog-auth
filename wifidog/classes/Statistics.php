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
/**@file Statistics.php
 * @author Copyright (C) 2004 Technologies Coeus inc.
 */

require_once BASEPATH.'include/common.php';

/* Gives various statistics about the status of the network or of a specific node */
class Statistics
{

	function Statistics()
	{
	}

	/**
	 * Find out how many users are online at a HotSpot or on the network
	 * @param $node_id Optionnal.  The id of the node used for which you want the number of users.  Leave null to get the number of users online on the entire network
	 * @return Number of online users
	 */
	function getNumOnlineUsers($node_id = null)
	{
		global $db;
		if ($node_id != null)
		{
			$node_id = $db->EscapeString($node_id);
			$sql_node = " AND connections.node_id='$node_id'";
		}
		else
		{
			$sql_node = "";
		}

		$db->ExecSqlUniqueRes("SELECT COUNT(DISTINCT users.user_id) FROM users,connections "."WHERE connections.token_status='".TOKEN_INUSE."' "."AND users.user_id=connections.user_id $sql_node ", $row, false);
		return $row['count'];
	}

	/**
	 * Find out how many users are valid in the database
	 * @return Number of valid users
	 */
	function getNumValidUsers()
	{
		global $db;

		$db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_status = ".ACCOUNT_STATUS_ALLOWED, $row, false);
		return $row['count'];
	}

	/**
	 * Find out the total number of users in the database
	 * @return Number of users
	 */
	function getNumUsers()
	{
		global $db;
		$db->ExecSqlUniqueRes("SELECT COUNT(user_id) FROM users", $row, false);
		return $row['count'];
	}

	/**
	 * Find out the date of the most recent successfull (meaning with data transferred) connection to a HotSpot.
	 * @param $node_id Optionnal.  The id of the node used for which you want the last successfull connection date
	 * @return Textual date
	 */
	function getLastConnDate($node_id = null)
	{
		global $db;

		if ($node_id != null)
		{
			$node_id = $db->EscapeString($node_id);
			$sql_node = " AND connections.node_id='$node_id'";
		}
		else
		{
			$sql_node = "";
		}

		$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE incoming!=0 $sql_node ORDER BY timestamp_in DESC LIMIT 1", $row, false);
		return $row['timestamp_in'];
	}

	public static function getRegistrationsPerMonth($from = '', $to = '', $order = "DESC")
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND reg_date >= '$from' AND reg_date <= '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT COUNT(users) AS num_users, date_trunc('month', reg_date) AS month FROM users  WHERE account_status = ".ACCOUNT_STATUS_ALLOWED." ${date_constraint} GROUP BY date_trunc('month', reg_date) ORDER BY month $order", $results, false);
		return $results;
	}

	public static function getRegistrationsPerNode($from = '', $to = '')
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND timestamp_in BETWEEN '$from' AND '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT nodes.name,connections.node_id,COUNT(user_id) as registrations FROM connections,nodes WHERE timestamp_in IN (SELECT MIN(timestamp_in) as first_connection FROM connections GROUP BY user_id) ${date_constraint} AND nodes.node_id=connections.node_id GROUP BY connections.node_id,nodes.name ORDER BY registrations DESC", $results, false);
		return $results;
	}

	public static function getNodesUsage($from = '', $to = '')
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND timestamp_in BETWEEN '$from' AND '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT nodes.name,connections.node_id,COUNT(connections.node_id) AS connections FROM connections,nodes WHERE nodes.node_id=connections.node_id ${date_constraint} GROUP BY connections.node_id,nodes.name ORDER BY connections DESC;", $results, false);
		return $results;
	}

	public static function getMostMobileUsers($limit, $from = '', $to = '')
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND timestamp_in >= '$from' AND timestamp_out <= '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT COUNT(DISTINCT node_id) AS num_hotspots_visited, user_id, username, account_origin FROM users NATURAL JOIN connections WHERE (incoming!=0 OR outgoing!=0) ${date_constraint} GROUP BY account_origin, username,user_id ORDER BY num_hotspots_visited DESC LIMIT $limit", $results, false);
		return $results;
	}

	public static function getMostFrequentUsers($limit, $from = '', $to = '')
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND timestamp_in >= '$from' AND timestamp_out <= '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT COUNT(user_active_days.user_id) AS active_days, user_active_days.user_id, username, account_origin FROM (SELECT DISTINCT user_id, date_trunc('day', timestamp_in) AS date FROM connections WHERE (incoming!=0 OR outgoing!=0)  ${date_constraint} GROUP BY date,user_id) user_active_days JOIN users ON (users.user_id = user_active_days.user_id) GROUP BY account_origin, username, user_active_days.user_id ORDER BY active_days DESC LIMIT $limit", $results, false);
		return $results;
	}

	public static function getMostGreedyUsers($limit, $from = '', $to = '')
	{
		global $db;

		if ($from != '' && $to != '')
			$date_constraint = "AND timestamp_in >= '$from' AND timestamp_out <= '$to'";
		else
			$date_constraint = '';

		$db->ExecSql("SELECT DISTINCT connections.user_id, SUM(incoming+outgoing) AS total, SUM(incoming) AS total_incoming, SUM(outgoing) AS total_outgoing, username, account_origin FROM connections JOIN users ON (users.user_id = connections.user_id) WHERE incoming IS NOT NULL AND outgoing IS NOT NULL  ${date_constraint} GROUP BY account_origin, username,connections.user_id ORDER BY total DESC limit $limit", $results, false);
		return $results;
	}

} //End class
?>