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
 * @author     Vincent Vinet <vv@rlnx.com>
 * @copyright  2009 Revolution Linux, inc. www.revolutionlinux.com
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/../include/common.php');
require_once ('classes/SmartyWifidog.php');
require_once ('classes/Node.php');
require_once ('classes/Statistics.php');
require_once ('classes/StatisticGraph.php');


function rm_rf_dir( $path , $verbose=false){
    if ($verbose)
    print "rm_rf_dir( $path )\n";
    if(!is_dir($path))
    return false;
    $files = scandir($path);
    foreach ($files as $file){
        if(is_dir($file) and $file!="." and $file!="..")
        rm_rf_dir($file, $verbose);
    }
    rmdir($path);
    unset($files);
    unset($file);
}


class YearlyStats extends Statistics {

    public function __construct() {
        parent :: __construct();
        $curdate = date("Y-m-d");
        $term = 365;
        $mindate = date( "Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$term, date("y")) );
        //echo $curdate . "<br>" . $expdate;
        $this->report_date_min=$mindate;
    }

    public function setSelectedNodes($array) {
        $this->report_selected_nodes = $array;
    }

    public function setSelectedNetworks($array) {
        $this->report_selected_networks = $array;
    }
}// End class

$opt = getopt("v");
if (isset($opt['v']) or isset($opt['verbose']))
$verbose=true;
else
$verbose=false;


if(!is_dir(WIFIDOG_ABS_FILE_PATH . NODE_PUBLIC_STATS_DIR))
mkdir(WIFIDOG_ABS_FILE_PATH . NODE_PUBLIC_STATS_DIR, 0775);


$db = AbstractDb::getObject();
$sql = "SELECT node_id, network_id, allows_public_stats FROM nodes WHERE node_deployment_status != 'PERMANENTLY_CLOSED'";
$result = null;

$db->execSql($sql, $result, false);

$session = Session::getObject();

//print_r($result);
$statistics_object = new YearlyStats();
$session->set('current_statistics_object', $statistics_object);
foreach ($result as $row) {
    $node = Node::getObject($row['node_id']);
    $network = $node->getNetwork();

    if($node->getAllowsPublicStats()){

        $statistics_object->setSelectedNodes(array($node->getId() => $node));
        $statistics_object->setSelectedNetworks(array($network->getId() => $network));
        if($verbose)
        print "Processing node {$node->getId()} ... mem used: " . memory_get_usage() . "\n" ;
        //Make sure folder exists
        if(!is_dir($node->getPublicStatsDir()))
        mkdir($node->getPublicStatsDir(), 0775);

        //Write the index file header
        $index = fopen($node->getPublicStatsDir() . $node->getPublicStatsFile(),"w");
        fwrite($index, "".
            '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n".
            "<html>\n<head>\n".
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n".
            '<meta http-equiv="Pragma" content="no-cache">'."\n".
        //            "<meta http-equiv=\"Expires\" content="$expires">\n".

            "<title>Statistics for {$node->getName()}</title>\n".
            '<link rel="stylesheet" type="text/css"  href="/media/public_stats/stylesheet.css">'."\n".
            '<link rel="stylesheet" type="text/css" media="print"    href="/media/base_theme/printer.css">'."\n".
            "</head>\n<body>\n".
            "<h1>Statistics for {$node->getName()}</h1>\n");

        //Generate reports
        //        if ($verbose)
        //            print $statistics_object->getSqlCandidateConnectionsQuery("DEBUG Node {$node->getId()}", false);
        $graphclass = "VisitsPerMonth";
        $report = StatisticGraph::getObject($graphclass);
        $reportimg = "VisitsPerMonth.png" ;
        $report->showImageData('',array('filename' => ($node->getPublicStatsDir() . $reportimg)));

        unset($report);
        fwrite($index, "".
            "<div>\n".
            "<h2>"."Number of individual user visits per month"."</h2>\n".
            "<img src='$reportimg' alt='$graphclass' /><br />\n".
            "Note:  A visit is like counting connections, but only counting one connection per day for each user at a single node" .
            "</div>\n<br />\n"); 
         
        //Write the index file footer
        fwrite($index, "</body>\n");
        fclose($index);

        unset($index);

    }else{
        //Clear the folder
        rm_rf_dir($node->getPublicStatsDir());
    }

    Node::freeObject($node->getId());
    //    Network::freeObject($network->getId());
    unset($node);
    //    unset($network);
}
$session->set('current_statistics_object', null);
unset($statistics_object);

if ($verbose)
print "Generate stats done...\n";


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

