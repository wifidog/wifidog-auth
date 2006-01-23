<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/StatisticReport.php');

/**
 * General report about a node
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Gregoire
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 */
class NodeStatus extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Node status information");
    }

    /** Constructor
         * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct(Statistics $statistics_object)
    {
        parent :: __construct($statistics_object);
    }

    /** Get the actual report.
     * Classes must override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI($child_html = null)
    {
        global $db;
        $html = '';
        $selected_nodes = $this->stats->getSelectedNodes();
        if (count($selected_nodes) == 0)
        {
            $html .= _("Sorry, this report requires you to select individual nodes");
        }
        else
        {
            //pretty_print_r($this->stats->getSelectedNodes ());
            foreach ($selected_nodes as $node_id => $nodeObject)
            {
                $html .= "<fieldset class='pretty_fieldset'>";
                $html .= "<legend>".$nodeObject->getName()."</legend>";

                /* Status */
                $html .= "<fieldset class='pretty_fieldset'>";
                $html .= "<legend>"._("Status")."</legend>";
                $html .= "<table>";

                $db->execSql("SELECT node_id, name, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes WHERE node_id = '{$node_id}'", $rows, false);

                $html .= ($rows[0]['is_up'] == 't') ? "<tr class='even'>" : "<tr class='red'>";
                $html .= "  <th>"._("WifiDog status")."</th>";
                $html .= "  <td>";
                $html .= ($rows[0]['is_up'] == 't') ? "UP" : "<span class='red'>DOWN</span>";
                $html .= "</td>";
                $html .= "<tr class='odd'>";
                $html .= "  <th>"._("Last heartbeat")."</th>";
                $html .= "  <td>".sprintf(_("%s ago"), Utils :: convertSecondsToWords(time() - strtotime($nodeObject->getLastHeartbeatTimestamp())))."</td>";
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
                $html .= "<tr class='even'>";
                $html .= "  <th>"._("Number of users online")."</th>";
                $html .= "  <td>".$nodeObject->getNumOnlineUsers()."</td>";
                $html .= "</tr>";
                $html .= "</table>";
                $html .= "</fieldset>";
                /* End Status */

                /* Profile */
                $html .= "<fieldset class='pretty_fieldset'>";
                $html .= "<legend>"._("Profile")."</legend>";
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

                /* End Profile */

                /* Statistics */
                $html .= "<fieldset class='pretty_fieldset'>";
                $html .= "<legend>"._("Statistics")."</legend>";
                $html .= "<table>";
                $date_constraint = $this->stats->getSqlDateConstraint();
                $db->execSql("SELECT round(CAST( (SELECT SUM(daily_connections) FROM (SELECT COUNT(DISTINCT user_id) AS daily_connections, date_trunc('day', timestamp_in) FROM connections WHERE node_id='${node_id}' AND (incoming!=0 OR outgoing!=0) {$date_constraint} GROUP BY date_trunc('day', timestamp_in)) AS daily_connections_table) / (EXTRACT(EPOCH FROM (NOW()-(SELECT timestamp_in FROM connections WHERE node_id='${node_id}' AND (incoming!=0 OR outgoing!=0) ORDER BY timestamp_in LIMIT 1)) )/(3600*24)) AS numeric),2) AS connections_per_day", $rows, false);
                $html .= "<tr class='even'>";
                $html .= "  <th>"._("Average visits per day").":</th>";
                $html .= "  <td>".$rows[0]['connections_per_day']." "._("(for the selected period)")." </td>";
                $html .= "</tr>";

                $db->execSql("SELECT SUM(incoming) AS in, SUM(outgoing) AS out FROM connections WHERE node_id='{$node_id}' ${date_constraint}", $rows, false);
                $html .= "<tr class='odd'>";
                $html .= "  <th>"._("Traffic").":</th>";
                $html .= "  <td>";
                $html .= _("Incoming").": ".Utils :: convertBytesToWords($rows[0]['in']);
                $html .= "<br>";
                $html .= _("Outgoing").": ".Utils :: convertBytesToWords($rows[0]['out']);
                $html .= "<br>";
                $html .= _("(for the selected period)");
                $html .= "</td>";

                $html .= "</table>";
                $html .= "</fieldset>";
                $html .= "</fieldset>";
                /* End Statistics */

            } //End foreach
        } //End else
        return parent :: getReportUI($html);
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
