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
require_once BASEPATH.'classes/Statistics.php';

//$style = new Style();
$stats=new Statistics();

//echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' hotspot status');
// echo "<div id='head'><h1>". HOTSPOT_NETWORK_NAME ." node list</h1></div>\n";    
  
//echo "<div id='content'>\n";

$db->ExecSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat,
 CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ORDER BY creation_date",$node_results, false);
echo "<table class='spreadsheet'>\n";
echo "<thead><tr class='spreadsheet'><th class='spreadsheet' colspan=6>"._('Status of the ').count($node_results) .' '._("open").' '.HOTSPOT_NETWORK_NAME.' '._("HotSpots")."</th></tr>\n";
echo "<tr class='spreadsheet'><th class='spreadsheet'>"._('HotSpot / Status')."</th>\n";
echo "<th class='spreadsheet'>"._('Description')."</th>\n";
echo "<th class='spreadsheet'>"._('Location')."</th>\n";	
echo "</tr></thead>\n";
foreach($node_results as $node_row)
{
  echo "<tr class='spreadsheet'>\n";
  echo "<td class='spreadsheet'>\n";
  if($node_row['node_deployment_status']=='NON_WIFIDOG_NODE')
    {
      echo "? ";
    }
  else
    {
      if($node_row['is_up']=='t')
	{
	  echo "<img src='".BASE_URL_PATH . "images/hotspot_status_up.png'> ";
	}
      else
	{
	  echo "<img src='".BASE_URL_PATH . "images/hotspot_status_down.png'> ";
	}
    }

  if(empty($node_row['home_page_url']))
    {
      echo "$node_row[name]\n";
    }
  else
    {
      echo "<a href='$node_row[home_page_url]' target='_new'>$node_row[name]</a>\n";
    }
      if($node_row['is_up']!='t')
	{
	  $duration = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
	  echo '<br />' . $duration['days'].'days '.$duration['hours'].'h '.$duration['minutes'].'min<br />';
	}

  echo "</td>\n";
  echo "<td class='spreadsheet'>\n";
  if(!empty($node_row['description']))
    {
      echo "$node_row[description]\n";
    }
  echo '<br />';
  echo _("Opened on ");
  echo "$node_row[creation_date]\n";
  $num_online_users = $stats->getNumOnlineUsers($node_row['node_id']);
  if($num_online_users!=0)
    {
      echo ", $num_online_users ";
      echo _("user(s) online");
    }


  echo "</td>\n";
  echo "<td class='spreadsheet'>\n";
  if(!empty($node_row['street_address']))
    {
      echo "<br />$node_row[street_address]\n";
    }
  if(!empty($node_row['map_url']))
    {
        echo " - <a href='$node_row[map_url]' target='_new'>Map</a>\n";
    }
  if(!empty($node_row['mass_transit_info']))
    {
      echo "<br />$node_row[mass_transit_info]\n";
    }
  if(!empty($node_row['public_phone_number']))
    {
      echo "<br />$node_row[public_phone_number]\n";
    }
  if(!empty($node_row['public_email']))
    {
      echo "<br />$node_row[public_email]\n";
    } 

  
  echo "</td>\n";
}
echo "</table>\n";

//    echo "</div>\n";	

//echo $style->GetFooter();
?>
