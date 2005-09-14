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
  /**@file stats.php
   * @author Copyright (C) 2005 Philippe April
   */
   /*
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
*/

define('BASEPATH','../');
require_once BASEPATH.'admin/admin_common.php';
require_once BASEPATH.'classes/MainUI.php';

function bytes_in_words($bytes) {
    if ($bytes > 1024*1024*1024)
        return round($bytes/(1024*1024*1024),1) . "G";
    if ($bytes > 1024*1024)
        return round($bytes/(1024*1024),1) . "M";
    if ($bytes > 1024)
        return round($bytes/(1024),1) . "K";
}

function seconds_in_words($seconds) {
    $r = '';
    if ($seconds >  60*60*24*365.25) {
        $amount = floor($seconds/(60*60*24*365.25));
        if ($amount != 0)
            $r .= " {$amount}y";
        $seconds -= ($amount*60*60*24*365.25);
    }
    if ($seconds > 60*60*24) {
        $amount = floor($seconds/(60*60*24));
        if ($amount != 0)
            $r .= " {$amount}d";
        $seconds -= ($amount*60*60*24);
    }
    if ($seconds > 60*60) {
        $amount = floor($seconds/(60*60));
        if ($amount != 0)
            $r .= " {$amount}h";
        $seconds -= ($amount*60*60);
    }
    if ($seconds > 60) {
        $amount = floor($seconds/60);
        if ($amount != 0)
            $r .= " {$amount}m";
        $seconds -= ($amount*60);
    }
    if ($seconds != 0) {
        $r .= " {$seconds}s";
    }
    trim($r);
    return $r;
}

$current_user = User::getCurrentUser();

try {
    if (isset($_REQUEST['node_id']) && $_REQUEST['node_id']) {
	    $node_id = $db->EscapeString($_REQUEST["node_id"]);
        $nodeObject = Node::getObject($node_id);
        $stats_title = _("Connections at") . " '" . $nodeObject->getName() . "'";
    } elseif (isset($_REQUEST['user_id'])) {
        $user_id = $db->EscapeString($_REQUEST["user_id"]);
        $userObject = User::getObject($user_id);
        $stats_title = _("User information for") . " '" . $userObject->getUsername() . "'";
    } elseif (isset($_REQUEST['user_mac'])) {
        $user_mac = $db->EscapeString($_REQUEST["user_mac"]);
        $stats_title = _("Connections from MAC") . " '" . $user_mac . "'";
    } elseif (isset($_REQUEST['network_id'])) {
	    $network_id = $db->EscapeString($_REQUEST["network_id"]);
        $networkObject = Network::getObject($network_id);
        $stats_title = _("Network information for") . " '" . $networkObject->getName() . "'";
    }

    if (!isset($_REQUEST["date_from"]))
        $_REQUEST["date_from"] = strftime("%Y-%m-%d 00:00");
    if (!isset($_REQUEST["date_to"]))
        $_REQUEST["date_to"] = strftime("%Y-%m-%d 11:59");

    $date_constraint = "AND timestamp_in >= '{$_REQUEST['date_from']}' AND timestamp_in <= '{$_REQUEST['date_to']}'";

    $html = '';
    $html .= <<<EOF
<script language="javascript">
    function change_value(value, from, to) {
        if (value != "") {
            var values_array = value.split(",");
            from.value = values_array[0];
            if (values_array[1]) {
                to.value = values_array[1];
            }
        }
    }
</script>
EOF;

    $from_presets = array(
            "Select from..." => "",
            "yesterday"     => strftime("%Y-%m-%d 00:00", strtotime("-1 day")),
            "today"         => strftime("%Y-%m-%d 00:00", time()),
            "2 days ago"    => strftime("%Y-%m-%d 00:00", strtotime("-2 days")),
            "3 days ago"    => strftime("%Y-%m-%d 00:00", strtotime("-3 days")),
            "1 week ago"    => strftime("%Y-%m-%d 00:00", strtotime("-1 week")),
            "2 weeks ago"   => strftime("%Y-%m-%d 00:00", strtotime("-2 weeks")),
            "3 weeks ago"   => strftime("%Y-%m-%d 00:00", strtotime("-3 weeks")),
            "1 month ago"   => strftime("%Y-%m-%d 00:00", strtotime("-1 month")),
            "2 months ago"  => strftime("%Y-%m-%d 00:00", strtotime("-2 months")),
            "6 months ago"  => strftime("%Y-%m-%d 00:00", strtotime("-6 months")),
            "1 year ago"    => strftime("%Y-%m-%d 00:00", strtotime("-1 year")),
            "-"             => "",
            "Select from and to..." => "",
            "yesterday (whole day)"     => strftime("%Y-%m-%d 00:00", strtotime("-1 day")).",".strftime("%Y-%m-%d 11:59", strtotime("-1 day")),
            "today (whole day)"         => strftime("%Y-%m-%d 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()),
            "this month"    => strftime("%Y-%m-01 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()),
            "last month"    => strftime("%Y-%m-01 00:00", strtotime("-1 month")).",".strftime("%Y-%m-01 00:00", time()),
            "this year"     => strftime("%Y-01-01 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()),
            "forever"       => "1970-01-01 00:00," . strftime("%Y-%m-%d %H:%M", time())
    );

    $to_presets = array(
            "Select to..." => "",
            "yesterday"     => strftime("%Y-%m-%d 11:59", strtotime("-1 day")),
            "today"         => strftime("%Y-%m-%d 11:59", time()),
            "2 days ago"    => strftime("%Y-%m-%d 11:59", strtotime("-2 days")),
            "3 days ago"    => strftime("%Y-%m-%d 11:59", strtotime("-3 days")),
            "1 week ago"    => strftime("%Y-%m-%d 11:59", strtotime("-1 week")),
            "2 weeks ago"   => strftime("%Y-%m-%d 11:59", strtotime("-2 weeks")),
            "3 weeks ago"   => strftime("%Y-%m-%d 11:59", strtotime("-3 weeks")),
            "1 month ago"   => strftime("%Y-%m-%d 11:59", strtotime("-1 month")),
            "2 months ago"  => strftime("%Y-%m-%d 11:59", strtotime("-2 months")),
            "6 months ago"  => strftime("%Y-%m-%d 11:59", strtotime("-6 months")),
            "1 year ago"    => strftime("%Y-%m-%d 11:59", strtotime("-1 year")),
            "-"             => "",
            "Select from and to..." => "",
            "yesterday (whole day)"     => strftime("%Y-%m-%d 11:59", strtotime("-1 day")).",".strftime("%Y-%m-%d 00:00", strtotime("-1 day")),
            "today (whole day)"         => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-%m-%d 00:00", time()),
            "this month"    => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-%m-01 00:00", time()),
            "last month"    => strftime("%Y-%m-01 00:00", time()).",".strftime("%Y-%m-01 00:00", strtotime("-1 month")),
            "this year"     => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-01-01 00:00", time()),
            "forever"       => strftime("%Y-%m-%d %H:%M", time()) . ",1970-01-01 00:00"
    );

    if (isset($stats_title)) {
        $html .= "<h2>{$stats_title}</h2>";
        $html .= "<form>";

        if (isset($_REQUEST['node_id'])) {
            $html .= "<input type='hidden' id='node_id' name='node_id' value='{$_REQUEST['node_id']}'>";
        } elseif (isset($_REQUEST['user_id'])) {
            $html .= "<input type='hidden' id='user_id' name='user_id' value='{$_REQUEST['user_id']}'>";
        } elseif (isset($_REQUEST['user_mac'])) {
            $html .= "<input type='hidden' id='user_mac' name='user_mac' value='{$_REQUEST['user_mac']}'>";
        } elseif (isset($_REQUEST['network_id'])) {
            $html .= "<input type='hidden' id='network_id' name='network_id' value='{$_REQUEST['network_id']}'>";
        }
        
        $html .= "<b>"._("Select the time range for which statistics will be computed.")."</b>";
        $html .= "<table>";
        $html .= "<tr>";
        $html .= "    <th>" . _("From") . ":</th>";
        $html .= "    <td><input type='text' name='date_from' value='{$_REQUEST['date_from']}'></td>";
        $html .= "    <td>";
        $html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_from,this.form.date_to);\">";

        foreach ($from_presets as $label => $value) {
            $html .= "<option value=\"{$value}\">{$label}";
        }

        $html .= "    </select>\n";
        $html .= "    </td>\n";
        $html .= "</tr>\n";
        $html .= "<tr>\n";
        $html .= "    <th>" . _("To") . ":</th>\n";
        $html .= "    <td><input type=\"text\" name=\"date_to\" value=\"{$_REQUEST['date_to']}\"></td>\n";
        $html .= "    <td>\n";
        $html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_to,this.form.date_from);\">\n";

        foreach ($to_presets as $label => $value) {
            $html .= "<option value=\"{$value}\">{$label}";
        }

        $html .= "    </select>\n";
        $html .= "    </td>\n";
        $html .= "</tr>\n";
        $html .= "</table>\n";

        if (isset($_REQUEST['node_id']) && $current_user->isSuperAdmin()) {
            $html .= "<em>" . _("Group connections") . "?</em><br>";

            $html .= "<input type=\"radio\" name=\"group_connections\" value=\"\"";
            $html .= empty($_REQUEST['group_connections']) ? 'CHECKED' : '';
            $html .= ">No<br>";

            $html .= "<input type=\"radio\" name=\"group_connections\" value=\"group_connections_by_mac\"";
            $html .= isset($_REQUEST['group_connections']) && $_REQUEST['group_connections'] == "group_connections_by_mac" ? 'CHECKED' : '';
            $html .= ">By unique MACs<br>";

            $html .= "<input type=\"radio\" name=\"group_connections\" value=\"group_connections_by_user\"";
            $html .= isset($_REQUEST['group_connections']) && $_REQUEST['group_connections'] == "group_connections_by_user" ? 'CHECKED' : '';
            $html .= ">By unique usernames<br>";
        }

        $html .= "<input type='submit' value='" . _("Generate statistics") . "'>";
        $html .= "</form>";
        $html .= "<hr>";
    }

    //$sql = "select user_mac,count(user_mac) as nb,max(timestamp_in) as last_seen,substract(timestamp_in, timestamp_out) as time_spend from connections where node_id='{$node_id}' group by user_mac order by nb desc";

    if (isset($node_id) && ($current_user->isSuperAdmin() || $current_user->isOwner())) {
        include "stats_node.inc.php";
    } elseif (isset($user_id) && $current_user->isSuperAdmin()) {
        include "stats_user_id.inc.php";
    } elseif (isset($user_mac) && $current_user->isSuperAdmin()) {
        include "stats_user_mac.inc.php";
    } elseif (isset($network_id) && $current_user->isSuperAdmin()) {
        include "stats_network.inc.php";
    } else if($current_user->isSuperAdmin()) {
        include "stats_all_networks.inc.php";
    }


} catch (exception $e) {
    $html = "<p class='error'>";
    $html .= $e->getMessage();
    $html .= "</p>";
}
$ui=new MainUI();
$ui->setToolSection('ADMIN');
$ui->setMainContent($html);
$ui->display();
?>