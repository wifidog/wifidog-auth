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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: validate.php 1031 2006-05-10 18:56:02Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/include/common.php');

global $db; 
if (!empty ($_REQUEST['destination_url'])) {
       
    if (!empty ($_REQUEST['content_id'])) {
        $destination_url = $db->escapeString($_REQUEST['destination_url']);
        $content_id = $db->escapeString($_REQUEST['content_id']);
        empty ($_REQUEST['node_id']) ? $node_id = 'NULL' : $node_id = "'" . $db->escapeString($_REQUEST['node_id']) . "'";
        empty ($_REQUEST['user_id']) ? $user_id = 'NULL' : $user_id = "'" . $db->escapeString($_REQUEST['user_id']) . "'";
        $sql = "INSERT INTO content_clickthrough_log (user_id, content_id, node_id, destination_url) VALUES($user_id, '$content_id', $node_id, '$destination_url')\n";
        $db->execSqlUpdate($sql, false);
    } 
header("Location: " . $_REQUEST['destination_url']);
} 
else
{
    echo "<h1>Redirect destination missing</h1>";
}
/*
        * Local variables:
        * tab-width: 4
        * c-basic-offset: 4
        * c-hanging-comment-ender-p: nil
        * End:
        */