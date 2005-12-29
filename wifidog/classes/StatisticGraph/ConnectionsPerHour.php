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
 * @author     Philippe April
 * @copyright  2005 Philippe April
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once('classes/StatisticGraph.php');

/* An abstract class.  All statistics must inherit from this class */
class ConnectionsPerHour extends StatisticGraph
{
    /** Get the Graph's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getGraphName()
    {
        return _("Number of new connections opening per hour of the day");
    }

    /** Constructor, must be called by subclasses */
    protected function __construct()
    {
        parent :: __construct();
    }

    /** Return the actual Image data
     * Classes must override this.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function showImageData()
    {
        require_once ("Image/Graph.php");
        global $db;

$Graph =& Image_Graph::factory("Image_Graph", array(600, 200));
$Plotarea =& $Graph->add(Image_Graph::factory("Image_Graph_Plotarea"));
$Dataset =& Image_Graph::factory("Image_Graph_Dataset_Trivial");
$Bar =& Image_Graph::factory("Image_Graph_Plot_Bar", $Dataset);
$Bar->setFillColor("#9db8d2");
$Plot =& $Plotarea->add($Bar);
        $candidate_connections_sql = self :: $stats->getSqlCandidateConnectionsQuery("COUNT(distinct user_mac) AS connections, extract('hour' from timestamp_in) AS hour");

        $db->execSql("$candidate_connections_sql GROUP BY hour ORDER BY hour", $results, false);

        if ($results)
        {
            foreach ($results as $row)
            {
$Dataset->addPoint($row['hour'] . "h", $row['connections']);
            }
        }
        $Graph->done();
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
