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
 * @author     Philippe April
 * @copyright  2005-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

 /**
  * Load required files
  */

  require_once(dirname(__FILE__) . '/../include/common.php');

  require_once('classes/Network.php');
  require_once('classes/Node.php');

  function page_if_down_since($nodeObject, $minutes) {
      $db = AbstractDb::getObject();
      $last_heartbeat = strtotime($nodeObject->getLastHeartbeatTimestamp());
$time = time();

      if ($time - $last_heartbeat > 60*$minutes) {
          $lastPaged = strtotime($nodeObject->getLastPaged());
          //echo sprintf("Node down for %f minutes, Last paged: %f minutes ago, difference between last page and last heartbeat: %s, difference must be less than %d minutes to page again <br/>\n", ($time-$last_heartbeat)/60, ($time-$lastPaged)/60, ($lastPaged - $last_heartbeat)/60, $minutes);
          if (!$nodeObject->getLastPaged() || !$lastPaged) {
              $nodeObject->setLastPaged($time);
          }
          else if (($lastPaged >= $last_heartbeat) && ($lastPaged - $last_heartbeat <= 60*$minutes)) {//If we haven't paged within this downtime period AND we haven't paged after this downtime reached the threshold
              $network = $nodeObject->getNetwork();

              $nodeObject->setLastPaged(time());
              $usersToPage = $nodeObject->DEPRECATEDgetTechnicalOfficers();
              $usersMsg = null;
              foreach ($usersToPage as $officer) {
                  # Doesn't work if called from cron
                  #Locale :: setCurrentLocale(Locale::getObject($officer->getPreferedLocale()));
                  $mail = new Mail();
                  $mail->setSenderName(_("Monitoring system"));
                  $mail->setSenderEmail($network->getTechSupportEmail());
                  $mail->setRecipientEmail($officer->getEmail());
                  $mail->setMessageSubject($minutes . " - " . $network->getName()." "._("node")." ".$nodeObject->getName());
                  $mail->setMessageBody(sprintf(_("Node %s (%s) has been down for %d minutes (since %s)"), $nodeObject->getName(), $nodeObject->getId(), $minutes, date("r", $last_heartbeat)));
                  $mailRetval = $mail->send();
                  $usersMsg .= sprintf("%s: %s", $officer->getUsername(), $mailRetval?_("Success"):_("Failed sending mail"))."\n";

              }
              $msg = sprintf("Node %s has been DOWN for %d minutes, we mailed the following %d user(s):\n%s", $nodeObject->getName(), $minutes, count($usersToPage), $usersMsg) ;
              throw new exception($msg);
          }
          throw new exception(sprintf("Node %s DOWN for more than %d minutes, but we already notified everyone"."\n",$nodeObject->getName(),  $minutes));
      }
  }

  try {
      $sql = "SELECT node_id FROM nodes WHERE node_deployment_status = 'DEPLOYED'";
      $nodes_results = null;
      $db = AbstractDb::getObject();
      $db->execSql($sql, $nodes_results, false);

      if ($nodes_results == null)
      throw new Exception(_("No deployed nodes could not be found in the database"));

      foreach ($nodes_results as $node_row)
      {
          $nodeObject = Node :: getObject($node_row['node_id']);
          #echo $nodeObject->getName();
          #echo " - ";
          #echo $nodeObject->getLastHeartbeatTimestamp();
          #echo " - ";
          try {
              page_if_down_since($nodeObject, 43200);//A month
              page_if_down_since($nodeObject, 10080);//A week
              page_if_down_since($nodeObject, 1440);//A day
              page_if_down_since($nodeObject, 120);//Two hours
              page_if_down_since($nodeObject, 30);//30 min
              page_if_down_since($nodeObject, 5);//5 min
          } catch (Exception $e) {
              # Do nothing, we cronned this
              echo $e->getMessage();
          }
          #echo "<br>";
          #echo "<hr>";
          #echo "\n";
      }
  } catch (Exception $e) {
      echo $e;
  }

  /*
   * Local variables:
   * tab-width: 4
   * c-basic-offset: 4
   * c-hanging-comment-ender-p: nil
   * End:
   */

   ?>