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
	echo "</table></p>\n";

    echo "</div>\n";	

echo $style->GetFooter();
?>
