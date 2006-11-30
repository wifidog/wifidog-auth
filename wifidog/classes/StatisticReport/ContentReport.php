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
 * @copyright  2005-2006 Philippe April
 * @version    Subversion $Id: UserReport.php 1114 2006-10-20 21:40:18Z prospere $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/StatisticReport.php');

/**
 * General report about a user
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire
 * @copyright  2006 Technologies Coeus inc.
 */
class ContentReport extends StatisticReport {
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName() {
        return _("Content display and clickthrough report");
    }

    /** Constructor
         * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct(Statistics $statistics_object) {
        parent :: __construct($statistics_object);
    }

    /** Get display and clikthrough report for a content
    * @param $content_id The Content object
    * @return SQL row
    */
    private function getDisplayClickThroughReport(Content $content) {
        $db = AbstractDb :: GetObject();
        $display_row = null;
        $clickthrough_row = null;

        $element_id = $db->escapeString($content->getId());
        $displayed_content_sql = "SELECT SUM(num_display) as display_count,  count(DISTINCT user_id) as display_unique_user_count, count(DISTINCT node_id) as display_unique_node_count, EXTRACT(EPOCH FROM min(first_display_timestamp)) as first_display_timestamp, EXTRACT(EPOCH FROM max(last_display_timestamp)) as last_display_timestamp FROM content_display_log WHERE content_id='$element_id'";
        $db->execSqlUniqueRes($displayed_content_sql, $display_row, false);

        $clickthrough_sql = "SELECT SUM(num_clickthrough) as clickthrough_count, count(DISTINCT user_id) as clickthrough_unique_user_count, count(DISTINCT node_id) as clickthrough_unique_node_count, EXTRACT(EPOCH FROM min(first_clickthrough_timestamp)) as first_clickthrough_timestamp, EXTRACT(EPOCH FROM max(last_clickthrough_timestamp)) as last_clickthrough_timestamp FROM content_clickthrough_log WHERE content_id='$element_id'";
        $db->execSqlUniqueRes($clickthrough_sql, $clickthrough_row, false);
        return array_merge($display_row, $clickthrough_row);
    }
    /** Get a report for a content element
    * @param $content The content
    * @return HTML markup
    */
    private function getContentReport(Content $content) {
        $html = null;
        $html .= "<table>";
        $html .= "<thead>";
        $title = $content->getTitle();
        $title ? $title_str = $title->__toString() : $title_str = _("Untitled Content");
        $html .= "<tr>";
        $html .= "  <th colspan=4>" . _("Content report for:") . " $title_str<" . ":</td>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "  <th></th>";
        $html .= "  <th>" . _("Clickthrough") . "</th>";
        $html .= "  <th>" . _("Prints") . "</th>";
        $html .= "  <th>" . _("Clickthrough/Prints") . "</th>";
        $html .= "</tr>";
        $html .= "</thead>";

        $report_row = $this->getDisplayClickThroughReport($content);
        //pretty_print_r($report_row);

        //$html .= _("Content ID:") . $content->getId()."<br/>";
        $html .= "<tr>\n";
        $html .= "  <th>" . _("Count") . "</th>";
        $html .= "  <td>" . $report_row['clickthrough_count'] . "</td>";
        $html .= "  <td>" . $report_row['display_count'] . "</td>";
        if ($report_row['display_count'] != 0) {
            $percentage = sprintf("%0.2f%%", ($report_row['clickthrough_count'] / $report_row['display_count']) * 100);
        } else {
            $percentage = null;
        }
        $html .= "  <td>$percentage</td>";
        $html .= "</tr>\n";
        $html .= "<tr>\n";
        $html .= "  <th>" . _("First") . "</th>";
        $report_row['first_clickthrough_timestamp']?$date = strftime("%x",$report_row['first_clickthrough_timestamp']):$date = null;
        $html .= "  <td>$date</td>";
        $report_row['first_display_timestamp']?$date = strftime("%x",$report_row['first_display_timestamp']):$date = null;
        $html .= "  <td>$date</td>";
        $html .= "</tr>\n";
        $html .= "<tr>\n";
        $html .= "  <th>" . _("Last") . "</th>";
        $report_row['last_clickthrough_timestamp']?$date = strftime("%x",$report_row['last_clickthrough_timestamp']):$date = null;
        $html .= "  <td>$date</td>";
        $report_row['last_display_timestamp']?$date = strftime("%x",$report_row['last_display_timestamp']):$date = null;
        $html .= "  <td>$date</td>";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "  <th>" . _("Rate") . "</th>";
                if ($report_row['last_clickthrough_timestamp']-$report_row['first_clickthrough_timestamp'] > 0) {
            $rate = sprintf(_("%0.2f per day"), $report_row['clickthrough_count'] / (($report_row['last_clickthrough_timestamp']-$report_row['first_clickthrough_timestamp'])/86400));
        } else {
            $rate = null;
        }
        $html .= "  <td>$rate</td>";
                        if ($report_row['last_display_timestamp']-$report_row['first_display_timestamp'] > 0) {
            $rate = sprintf(_("%0.2f per day"), $report_row['display_count'] / (($report_row['last_display_timestamp']-$report_row['first_display_timestamp'])/86400));
        } else {
            $rate = null;
        }
        $html .= "  <td>$rate</td>";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "  <th>" . _("Unique users") . "</th>";
        $html .= "  <td>" . $report_row['clickthrough_unique_user_count'] . "</td>";
        $html .= "  <td>" . $report_row['display_unique_user_count'] . "</td>";
        if ($report_row['display_unique_user_count'] != 0) {
            $percentage = sprintf("%0.2f%%", ($report_row['clickthrough_unique_user_count'] / $report_row['display_unique_user_count']) * 100);
        } else {
            $percentage = null;
        }
        $html .= "  <td>$percentage</td>";
        $html .= "</tr>\n";
        $html .= "<tr>\n";
        $html .= "  <th>" . _("Unique locations") . "</th>";
        $html .= "  <td>" . $report_row['clickthrough_unique_node_count'] . "</td>";
        $html .= "  <td>" . $report_row['display_unique_node_count'] . "</td>";
        if ($report_row['display_unique_node_count'] != 0) {
            $percentage = sprintf("%0.2f%%", ($report_row['clickthrough_unique_node_count'] / $report_row['display_unique_node_count']) * 100);
        } else {
            $percentage = null;
        }
        $html .= "  <td>$percentage</td>";
        $html .= "</tr>\n";

        $html .= "</table>";

        if ($content instanceof ContentGroup) {
        	$html .= "<br />";
            $html .= $this->getContentGroupReport($content, $report_row['display_count']);
        }
        return $html;

    }
    /** Get a report for a content group
    * @param $content The content group
    * @param $totalPrints Total number of prints for the entire group
    * @return HTML markup
    */
    private function getContentGroupReport(ContentGroup $content, $totalPrints) {
        global $db;
        $html = null;
        $group_id = $db->escapeString($content->getId());
        $displayed_element_sql = "SELECT count(*) as element_display_count, content_display_log.content_id, displayed_content_id  FROM content_display_log JOIN content_group_element ON (content_group_element_id=content_display_log.content_id) WHERE content_group_id='$group_id' GROUP BY content_id, displayed_content_id";
        $element_stats = null;
        $db->execSql($displayed_element_sql, $element_stats, false);
        $html .= "<table>";
        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "  <th colspan=2>" . _("Content group elements report") . "</th>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "  <th>" . _("Element # of prints") . "</th>";
        $html .= "  <th>" . _("Displayed content report") . "</th>";
        $html .= "</tr>";
        $html .= "</thead>";
        $total = 0;
        $even = 0;
        foreach ($element_stats as $element_row) {
            $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
            if ($even == 0)
                $even = 1;
            else
                $even = 0;

            $html .= "  <td>" . $element_row['element_display_count'];
            if($totalPrints)
        if ($totalPrints) {
            $percentage = sprintf("%0.2f%%", ($element_row['element_display_count'] / $totalPrints) * 100);
        $html .= " ($percentage)";
        }
            $html .= "</td>";

            $content_report = $this->getContentReport(Content :: getObject($element_row['displayed_content_id']));
            $html .= "  <td>$content_report</td>\n";
        }
        $html .= "<tfoot>";
        $html .= "<tr>";
        $html .= "  <th></th>";
        $html .= "  <th>" . _("Note:  The statistics above include all the prints and clickthroughs of the displayed element, not just those resulting from it's display in this group.") . ":</th>";
        $html .= "</tr>";
        $html .= "</tfoot>";
        $html .= "</table>";
        return $html;
    }
    /** Get the actual report.
     * Classes must override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI($child_html = null) {
        global $db;
        $html = '';
        $content_stats = null;
        //pretty_print_r($_REQUEST);
        empty ($_REQUEST['content_report_content_id']) ? $requested_group_id = '' : $requested_group_id = $_REQUEST['content_report_content_id'];
        $group_input_id = 'content_report_content_id';
        $html .= "<input type=hidden name='$group_input_id' id='$group_input_id' value='{$requested_group_id}'>";
        $name = 'content_report_content_id';
        $content = Content :: processSelectContentUI($name);
        $html .= Content :: getSelectExistingContentUI($name, "AND is_persistent=TRUE", null, "creation_timestamp", "select");
        /*            if (empty ($_REQUEST[$name])) {
        
              $distinguish_users_by = $this->stats->getDistinguishUsersBy();
                   $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("DISTINCT connections.$distinguish_users_by, connections.node_id, nodes.name, date_trunc('day', timestamp_in) as rounded_date");
                   //$db->execSql($candidate_connections_sql, $tmp, true);
                   $displayed_content_groups_sql = "SELECT count(*) as num_display, content_display_log.content_id FROM content_display_log JOIN content USING (content_id) WHERE content.content_type='ContentGroup' GROUP BY content_id";
                   $displayed_content_groups = null;
                   $db->execSql($displayed_content_groups_sql, $displayed_content_groups, false);
        
                   if ($displayed_content_groups) {
                       $html .= "<table>";
                       $html .= "<thead>";
                       $html .= "<tr>";
                       $html .= "  <th>" . _("Group title (click for detailed report)") . "</th>";
                       $html .= "  <th>" . _("Total number of prints") . "</th>";
                       $html .= "</tr>";
                       $html .= "</thead>";
        
                       $total = 0;
                       $even = 0;
        
                       foreach ($displayed_content_groups as $group_row) {
                           $group = Content :: GetObject($group_row['content_id']);
                           $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                           if ($even == 0)
                               $even = 1;
                           else
                               $even = 0;
                           $title = $group->getTitle();
                           $title ? $title_str = $title->__toString() : $title_str = _("Untitled Content");
                           $html .= "  <td><a name='group_select' onclick=\"document.getElementById('$group_input_id').value='{$group_row['content_id']}';document.stats_form.submit();\">{$title_str}</a></td>\n";
                           $html .= "  <td>" .$group_row['num_display'] . "</td>";
                           $html .= "</tr>";
                           $total += $group_row['num_display'];
                       }
                       $html .= "<tfoot>";
                       $html .= "<tr>";
                       $html .= "  <th>" . _("Total") . ":</th>";
                       $html .= "  <th>" . $total . "</th>";
                       $html .= "</tr>";
                       $html .= "<tr>";
                       $html .= "  <td colspan=2>" . _("Note: ") . ":</td>";
                       $html .= "</tr>";
                       $html .= "</tfoot>";
                       $html .= "</table>";
                   }
                   else {
                       $html .= _("No information found matching the report configuration");
                   }
                   */
        if ($content) {
            $html .= $this->getContentReport($content);
        }
        $html .= _("Important note:  Currently, Report configuration options are ignored for this report.");
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