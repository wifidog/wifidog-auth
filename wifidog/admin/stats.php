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
 * Load common include file
 */
require_once('admin_common.php');

require_once('classes/MainUI.php');
require_once('classes/Utils.php');

$current_user = User :: getCurrentUser();

$statistics = new Statistics();
if (!empty ($_REQUEST['action']) && $_REQUEST['action'] == 'generate')
{
	$statistics->processAdminUI();
}
try
{
	if (!empty ($_REQUEST['node_id']))
	{
		$node_id = $db->escapeString($_REQUEST["node_id"]);
		$nodeObject = Node :: getObject($node_id);
		$stats_title = _("Connections at")." '".$nodeObject->getName()."'";
	}
	elseif (isset ($_REQUEST['user_id']))
	{
		$user_id = $db->escapeString($_REQUEST["user_id"]);
		$userObject = User :: getObject($user_id);
		$stats_title = _("User information for")." '".$userObject->getUsername()."'";
	}
	elseif (isset ($_REQUEST['user_mac']))
	{
		$user_mac = $db->escapeString($_REQUEST["user_mac"]);
		$stats_title = _("Connections from MAC")." '".$user_mac."'";
	}
	elseif (isset ($_REQUEST['network_id']))
	{
		$network_id = $db->escapeString($_REQUEST["network_id"]);
		$networkObject = Network :: getObject($network_id);
		$stats_title = _("Network information for")." '".$networkObject->getName()."'";
	}

	$html = '';

	if (isset ($stats_title))
	{
		$html .= "<h2>{$stats_title}</h2>";
	}
	$html .= "<form>";
	$html .= $statistics->getAdminUI();

	if (isset ($_REQUEST['node_id']))
	{
		$html .= "<input type='hidden' id='node_id' name='node_id' value='{$_REQUEST['node_id']}'>";
	}
	elseif (isset ($_REQUEST['user_id']))
	{
		$html .= "<input type='hidden' id='user_id' name='user_id' value='{$_REQUEST['user_id']}'>";
	}
	elseif (isset ($_REQUEST['user_mac']))
	{
		$html .= "<input type='hidden' id='user_mac' name='user_mac' value='{$_REQUEST['user_mac']}'>";
	}
	elseif (isset ($_REQUEST['network_id']))
	{
		$html .= "<input type='hidden' id='network_id' name='network_id' value='{$_REQUEST['network_id']}'>";
	}

	$html .= "<input type='hidden' name='action' value='generate'>";

	$html .= "<input type='submit' value='"._("Generate statistics")."'>";
	$html .= "</form>";
	$html .= "<hr>";
	$html .= $statistics->getReportUI();
}
catch (exception $e)
{
	$html = "<p class='error'>";
	$html .= $e->getMessage();
	$html .= "</p>";
}
$ui = new MainUI();
$ui->setToolSection('ADMIN');
$ui->setMainContent($html);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>