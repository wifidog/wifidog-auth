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
  /**@file stats_network.inc.php
   * @author Copyright (C) 2005 Philippe April
   */

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Network Profile") . "</legend>";
$html .= "<table>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Name") . "</th>";
$html .= "  <td>" . $networkObject->getName() . "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "  <th>" . _("Creation date") . "</th>";
$html .= "  <td>" . $networkObject->getCreationDate() . "</td>";
$html .= "</tr>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Homepage") . "</th>";
$html .= "  <td>" . $networkObject->getHomepageURL() . "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "  <th>" . _("Tech support email") . "</th>";
$html .= "  <td>" . $networkObject->getTechSupportEmail() . "</td>";
$html .= "</tr>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Validation grace time") . "</th>";
$html .= "  <td>" . seconds_in_words($networkObject->getValidationGraceTime()) . "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "  <th>" . _("Validation email") . "</th>";
$html .= "  <td>" . $networkObject->getValidationEmailFromAddress() . "</td>";
$html .= "</tr>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Allows multiple login") . "?</th>";
$html .= "  <td>" . ($networkObject->getMultipleLoginAllowed() ? 'yes' : 'no') . "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "  <th>" . _("Splash only nodes allowed") . "?</th>";
$html .= "  <td>" . ($networkObject->getSplashOnlyNodesAllowed() ? 'yes' : 'no') . "</td>";
$html .= "</tr>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Custom portal redirect nodes allowed") . "?</th>";
$html .= "  <td>" . ($networkObject->getCustomPortalRedirectAllowed() ? 'yes' : 'no') . "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "  <th>" . _("Number of users") . ":</th>";
$html .= "  <td>" . $stats->getNumUsers() . "</td>";
$html .= "</tr>";

$html .= "<tr class='odd'>";
$html .= "  <th>" . _("Number of validated users") . ":</th>";
$html .= "  <td>" . $stats->getNumValidUsers() . "</td>";
$html .= "</tr>";

$html .= "</table>";
$html .= "</fieldset>";

/**
 * Graph for connections per hours
 */
$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Registrations per month") . "</legend>";
$html .= "<div><img src='graph_registrations.php?network_id={$network_id}&date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}'></div>";
$html .= "</fieldset>";

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Most registrations per node") . "</legend>";
$html .= "<div><img src='graph_registrations_cumulative.php?network_id={$network_id}&date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}'></div>";
$html .= "</fieldset>";

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Most connections per node") . "</legend>";
$node_usage_stats = Statistics::getNodesUsage($_REQUEST['date_from'], $_REQUEST['date_to']);
if ($node_usage_stats) {
    $html .= "<table>";
    $html .= "<thead>";
    $html .= "<tr>";
    $html .= "  <th>" . _("Node") . "</th>";
    $html .= "  <th>" . _("Connections") . "</th>";
    $html .= "</tr>";
    $html .= "</thead>";

    $total = 0;
    $even = 0;

    foreach ($node_usage_stats as $row) {
        $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
        if ($even == 0)
            $even = 1;
        else
            $even = 0;
        $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&node_id={$row['node_id']}'>{$row['name']}</a></td>\n";
        $html .= "  <td>" . $row['connections'] . "</td>";
        $html .= "</tr>";
        $total += $row['connections'];
    }
    $html .= "<tr>";
    $html .= "  <th>" . _("Total") . ":</th>";
    $html .= "  <th>" . $total . "</th>";
    $html .= "</tr>";
    $html .= "</table>";
} else {
    $html .= _("No information for specified time frame");
}
$html .= "</fieldset>";

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Ten most mobile users") . "</legend>";
$mobile_users_stats = Statistics::getMostMobileUsers(10, $_REQUEST['date_from'], $_REQUEST['date_to']);
if ($mobile_users_stats) {
    $html .= "<table>";
    $html .= "<thead>";
    $html .= "<tr>";
    $html .= "  <th>" . _("User") . "</th>";
    $html .= "  <th>" . _("Nodes visited") . "</th>";
    $html .= "</tr>";
    $html .= "</thead>";

    $even = 0;
    foreach ($mobile_users_stats as $row) {
        $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
        if ($even == 0)
            $even = 1;
        else
            $even = 0;
        $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
        $html .= "  <td>" . $row['num_hotspots_visited'] . "</td>";
        $html .= "</tr>";
    }
    $html .= "</table>";
} else {
    $html .= _("No information for specified time frame");
}
$html .= "</fieldset>";

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Ten most frequent users") . "</legend>";

$frequent_users_stats = Statistics::getMostFrequentUsers(10, $_REQUEST['date_from'], $_REQUEST['date_to']);
if ($frequent_users_stats) {
    $html .= "<table>";
    $html .= "<thead>";
    $html .= "<tr>";
    $html .= "  <th>" . _("User") . "</th>";
    $html .= "  <th>" . _("Different days connected") . "</th>";
    $html .= "</tr>";
    $html .= "</thead>";

    $even = 0;
    foreach ($frequent_users_stats as $row) {
        $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
        if ($even == 0)
            $even = 1;
        else
            $even = 0;
        $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
        $html .= "  <td>" . $row['active_days'] . "</td>";
        $html .= "</tr>";
    }
    $html .= "</table>";
} else {
    $html .= _("No information for specified time frame");
}
$html .= "</fieldset>";

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("Ten most greedy users") . "</legend>";
$frequent_users_stats = Statistics::getMostGreedyUsers(10, $_REQUEST['date_from'], $_REQUEST['date_to']);
if ($frequent_users_stats) {
    $html .= "<table>";
    $html .= "<thead>";
    $html .= "<tr>";
    $html .= "  <th>" . _("User") . "</th>";
    $html .= "  <th>" . _("Incoming") . "</th>";
    $html .= "  <th>" . _("Outgoing") . "</th>";
    $html .= "  <th>" . _("Total") . "</th>";
    $html .= "</tr>";
    $html .= "</thead>";

    $even = 0;
    foreach ($frequent_users_stats as $row) {
        $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
        if ($even == 0)
            $even = 1;
        else
            $even = 0;
        $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_id={$row['user_id']}'>{$row['username']}</a></td>\n";
        $html .= "  <td>" . bytes_in_words($row['total_incoming']) . "</td>";
        $html .= "  <td>" . bytes_in_words($row['total_outgoing']) . "</td>";
        $html .= "  <td>" . bytes_in_words($row['total']) . "</td>";
        $html .= "</tr>";
    }
    $html .= "</table>";
} else {
    $html .= _("No information for specified time frame");
}
$html .= "</fieldset>";
?>
