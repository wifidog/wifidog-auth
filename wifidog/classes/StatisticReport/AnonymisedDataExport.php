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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: ConnectionLog.php 1127 2006-11-14 20:11:42 +0000 (Tue, 14 Nov 2006) benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/StatisticReport.php');

/**
 * Report on user connections
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class AnonymisedDataExport extends StatisticReport
{
    private $_nonRepeatableHashTable = array();
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Anonymised SQL data export (for academic research)");
    }

    /** Constructor
     * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct(Statistics $statistics_object)
    {
        parent :: __construct($statistics_object);
    }

    function getNonRepeatableHash($string) {
        if(empty($this->_nonRepeatableHashTable[$string])){
            $hash = md5($string.mt_rand(0, mt_getrandmax()));
            $this->_nonRepeatableHashTable[$string]=$hash;
        }
        return $this->_nonRepeatableHashTable[$string];
    }
    /** Get the actual report.
     * Classes must override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI($child_html = null)
    {
        $db = AbstractDb::getObject();
        $html = '';

        /* User visits */
        // Only Super admin
        if (!User :: getCurrentUser()->DEPRECATEDisSuperAdmin())
        {
            $html .= "<p class='error'>"._("Access denied")."</p>";
        }
        else
        {
            /** Starting   sql file with geolocation data */
            $tmpdir = sys_get_temp_dir();
            $nodefile = tempnam($tmpdir, 'wd');
            $nfilehndl = fopen($nodefile, 'w');
            $datafile = tempnam($tmpdir, 'wd');
            $datahndl = fopen($datafile, 'w');
            
            if (!$nfilehndl || !$datahndl) {
                $html .= "<p class='error'>"._("Could not create files for anonymised data")."</p>";
                
            } else {
                /* header('Content-Type: application/octet-stream');
                header('Content-Disposition: inline; filename="anonymised_nodes.sql"');
                header("Content-Transfer-Encoding: binary"); */
                
                $text  = <<<EOT
                CREATE TABLE nodes_anonymised
                (
                node_id text NOT NULL,
                latitude  NUMERIC(16, 6),
                longitude  NUMERIC(16, 6)
                );
EOT;
                $text .= "\n";
    
                fwrite($nfilehndl, $text);
                
                $node_constraint = $this->stats->getSqlNodeConstraint('nodes.node_id');
                $network_constraint = $this->stats->getSqlNetworkConstraint('nodes.network_id');
                $sql = "SELECT node_id, latitude, longitude \n";
                $sql .= "FROM nodes \n";
                $sql .= "WHERE 1=1 {$node_constraint} {$network_constraint}";
                
                $db->execSql($sql, $nodes);
                
                if ($nodes) {
                    foreach($nodes as $row) {
                        $keys = null;
                        $values = null;
                        $first = true;
                        foreach ($row as $key=>$value)
                        {
                            if($key == 'user_id' || $key == 'node_id' || $key == 'conn_id' || $key == 'user_mac' ) {
                                $value = "'".$this->getNonRepeatableHash($value)."'";
                            }
                            else if ($key == 'latitude' && empty ($value)) {
                                $value = 'NULL';
                            }
                            else if ($key == 'longitude' && empty ($value)) {
                                $value = 'NULL';
                            }
                            else {
                                $value = "'$value'";
                            }
                            if(!$first) {
                                $keys .= ', ';
                                $values .= ', ';
                            }
                            else {
                                $first = false;
                            }
                            $keys .= $key;
                            $values .= $value;
                        }
                        //fwrite($temp, "INSERT INTO connections_anonymised ($keys) VALUES ($values);\n");
                        fwrite($nfilehndl, "INSERT INTO nodes_anonymised ($keys) VALUES ($values);\n");
                    }
                }
                
                
                /** End sql file with node data */
                
                /** Get the sql file with anonymised connection data */
              /*  header('Content-Type: application/octet-stream');
                header('Content-Disposition: inline; filename="anonymised_data.sql"');
                header("Content-Transfer-Encoding: binary");*/
    
                $text = <<<EOT
                CREATE TABLE connections_anonymised
                (
                conn_id text NOT NULL,
                timestamp_in timestamp,
                node_id text,
                timestamp_out timestamp,
                user_id text NOT NULL DEFAULT '',
                user_mac text,
                incoming int8,
                outgoing int8
                );
EOT;
                $text .= "\n";
            
                fwrite($datahndl,  $text);
                $distinguish_users_by = $this->stats->getDistinguishUsersBy();
    
                $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("conn_id, users.user_id, nodes.node_id, connections.user_id, user_mac, timestamp_in, timestamp_out, incoming, outgoing ", true);
    
                $sql = "$candidate_connections_sql ORDER BY timestamp_in DESC";
                $db->execSqlRaw($sql, $resultHandle, false);
                if($resultHandle) {
                    while($row=pg_fetch_array($resultHandle,null,PGSQL_ASSOC))
                    {
    
                        $keys = null;
                        $values = null;
                        $first = true;
                        foreach ($row as $key=>$value)
                        {
                            if($key == 'user_id' || $key == 'node_id' || $key == 'conn_id' || $key == 'user_mac' ) {
                                $value = "'".$this->getNonRepeatableHash($value)."'";
                            }
                            else if ($key == 'timestamp_out' && empty ($value)) {
                                $value = 'NULL';
                            }
                            else {
                                $value = "'$value'";
                            }
                            if(!$first) {
                                $keys .= ', ';
                                $values .= ', ';
                            }
                            else {
                                $first = false;
                            }
                            $keys .= $key;
                            $values .= $value;
                        }
                        //fwrite($temp, "INSERT INTO connections_anonymised ($keys) VALUES ($values);\n");
                        fwrite($datahndl, "INSERT INTO connections_anonymised ($keys) VALUES ($values);\n");
                    }
                }
                fclose($datahndl);
                fclose($nfilehndl);
                
                $html .= <<<EOS
                <script type="text/javascript">
                		window.open('/admin/stats.php?file=$nodefile&type=node', 'Node File');
                		window.open('/admin/stats.php?file=$datafile&type=data', 'Data file');
								</script>
EOS;
                
                
            }
        }
        return $html;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


