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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: validate.php 1031 2006-05-10 18:56:02Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/../../include/common.php');
require_once('classes/User.php');
$db = AbstractDb::getObject();
$user=User::getCurrentUser();

//$node=Node::getObject('6ddf7add76aa0408691106cbb83a7ac2');//For testing, change to your test node's id
//For production.  If someone is going to spam, at least force him to physically be at a hotspot.
$node=Node::getCurrentRealNode();
//pretty_print_r($node);
if($user) {
	if (!empty ($_REQUEST['shoutbox_id']) && !empty ($_REQUEST['shout_text']) && $node) {
		$shoutbox_id = $db->escapeString($_REQUEST['shoutbox_id']);
		$message_content = Content::createNewObject($content_type = "TrivialLangstring");
		$message_content->addString($_REQUEST['shout_text']);
		$message_content_id = $db->escapeString($message_content->getId());
		$node_id = $db->escapeString($node->getId());
		$user_id = $db->escapeString($user->getId());
		$sql = "INSERT INTO content_shoutbox_messages (author_user_id, shoutbox_id, origin_node_id, message_content_id) VALUES('$user_id', '$shoutbox_id', '$node_id', '$message_content_id')\n";
		$db->execSqlUpdate($sql, false);
		//pretty_print_r($_SERVER['HTTP_REFERER']);
		header("Location: " . $_SERVER['HTTP_REFERER']);
	}
	else {
		echo "<h1>"._("Sorry, some parameters are missing")."</h1>";
	}
}
else
{
	echo "<h1>You must be logged-in</h1>";
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */