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

require_once('classes/StatisticReport.php');

/** Reports on the users who consume the most bandwidth, and how much */
class HighestBandwidthUsers extends StatisticReport
{
    const NUM_USERS_TO_DISPLAY = 10;
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return sprintf(_("%d highest bandwidth consumers"), self :: NUM_USERS_TO_DISPLAY);
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
        $distinguish_users_by = $this->stats->getDistinguishUsersBy();
        $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery(" connections.$distinguish_users_by, SUM(incoming+outgoing) AS total, SUM(incoming) AS total_incoming, SUM(outgoing) AS total_outgoing ", false);

        $sql = "$candidate_connections_sql GROUP BY connections.$distinguish_users_by ORDER BY total DESC LIMIT ".self :: NUM_USERS_TO_DISPLAY."";

        $db->ExecSql($sql, $frequent_users_stats, false);

        if ($frequent_users_stats)
        {
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            if ($distinguish_users_by == 'user_id')
                $caption = _("User (username)");
            else
                $caption = _("User (MAC address)");
            $html .= "  <th>$caption</th>";
            $html .= "  <th>"._("Incoming")."</th>";
            $html .= "  <th>"._("Outgoing")."</th>";
            $html .= "  <th>"._("Total")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            $even = 0;
            foreach ($frequent_users_stats as $row)
            {
                $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                if ($even == 0)
                    $even = 1;
                else
                    $even = 0;
                if (!empty ($row['user_id']))
                {
                    $user = User :: getObject($row['user_id']);
                    $display_id = $user->getUsername();
                }
                else
                { //We only have a MAC adress
                    $display_id = $row['user_mac'];
                }
                $html .= "  <td>{$display_id}</a></td>\n";

                $html .= "  <td>".Utils :: convertBytesToWords($row['total_incoming'])."</td>";
                $html .= "  <td>".Utils :: convertBytesToWords($row['total_outgoing'])."</td>";
                $html .= "  <td>".Utils :: convertBytesToWords($row['total'])."</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }
        else
        {
            $html .= _("No information found matching the report configuration");
        }
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
