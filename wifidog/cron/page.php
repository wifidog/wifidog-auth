<?php
// $Id: 
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
/**@file page.php
 * @author Copyright (C) 2005 Philippe April
 */
define('BASEPATH', '../');

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Network.php';
require_once BASEPATH.'classes/Node.php';

function page_if_down_since($nodeObject, $minutes) {
    $last_heartbeat = strtotime($nodeObject->getLastHeartbeatTimestamp());

    if (time() - $last_heartbeat > 60*$minutes) {
        $lastPaged = strtotime($nodeObject->getLastPaged());
        if (!$nodeObject->getLastPaged() || !$lastPaged) {
            $nodeObject->setLastPaged(time());
        } else if ($lastPaged - $last_heartbeat < 60*$minutes) {
            $network = $nodeObject->getNetwork();

            $nodeObject->setLastPaged(time());

            foreach ($nodeObject->getTechnicalOfficers() as $officer) {
                # Doesn't work if called from cron
                #Locale :: setCurrentLocale(Locale::getObject($officer->getPreferedLocale()));
                $mail = new Mail();
                $mail->setSenderName(_("Monitoring system"));
                $mail->setSenderEmail($network->getTechSupportEmail());
                $mail->setRecipientEmail($officer->getEmail());
                $mail->setMessageSubject($minutes . " - " . $network->getName()." "._("node")." ".$nodeObject->getName());
                $mail->setMessageBody(sprintf(_("Node %s (%s) has been down for %d minutes (since %s)"), $nodeObject->getName(), $nodeObject->getId(), $minutes, date("r", $last_heartbeat)));
                $mail->send();
            }

            throw new exception("Node has been DOWN for $minutes, we paged");
        }
        throw new exception("DOWN since $minutes");
    }
}

try {
	#$sql = "SELECT node_id FROM nodes WHERE node_deployment_status = 'DEPLOYED'";
	$sql = "SELECT node_id FROM nodes WHERE node_id='philippe'";
    $nodes_results = null;
    $db->ExecSql($sql, $nodes_results, false);

    if ($nodes_results == null)
    	throw new Exception(_("No nodes could not be found in the database"));

    foreach ($nodes_results as $node_row)
    {
    	$nodeObject = Node :: getObject($node_row['node_id']);
        #echo $nodeObject->getName();
        #echo " - ";
        #echo $nodeObject->getLastHeartbeatTimestamp();
        #echo " - ";
        try {
            page_if_down_since($nodeObject, 60);
            page_if_down_since($nodeObject, 120);
            page_if_down_since($nodeObject, 30);
            page_if_down_since($nodeObject, 20);
            page_if_down_since($nodeObject, 15);
            page_if_down_since($nodeObject, 10);
            page_if_down_since($nodeObject, 9);
            page_if_down_since($nodeObject, 8);
            page_if_down_since($nodeObject, 7);
            page_if_down_since($nodeObject, 5);
            page_if_down_since($nodeObject, 2);
        } catch (Exception $e) {
            # Do nothing, we cronned this
            #echo $e->getMessage() . "<br>";
        }
        #echo "<br>";
        #echo "<hr>";
        #echo "\n";
    }
} catch (Exception $e) {
    echo $e;
}
?>
