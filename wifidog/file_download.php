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
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/include/common.php');

if (!empty ($_REQUEST['file_id']))
{
	$db = AbstractDb::getObject();
	$file_id = $db->escapeString($_REQUEST['file_id']);
	$sql = "SELECT * FROM content_file JOIN content ON (content_id=files_id) WHERE files_id = '$file_id'";
	$db->execSqlUniqueRes($sql, $file_row, false);

	if ($file_row && $file_row['data_blob'])
	{
		// Check if the HTTP request is asking if the file has been modified since a certain date.
		$last_modified_date = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : 0;
		if($last_modified_date && $_SERVER['REQUEST_METHOD'] == "GET" && strtotime($last_modified_date) >= strtotime($file_row['last_update_timestamp']))
		   header("HTTP/1.1 304 Not Modified");
		else
		{
			//headers to send to the browser before beginning the binary download
			header("Pragma: public");
			// Send last update date to proxy / cache the binary
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", strtotime($file_row['last_update_timestamp'])) . " GMT");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header('Content-Type: ' . $file_row['mime_type']);
			header("Content-Transfer-Encoding: binary");
			header('Accept-Ranges: bytes');
			if (strpos($file_row['mime_type'], "image") === false)
				header('Content-Length: ' .$file_row['local_binary_size']); //this is the size of the zipped file
			header('Keep-Alive: timeout=15, max=100');
			header('Content-Disposition: inline; filename="' . $file_row['filename'] . '"');

			// Do not send binary if this is only a HEAD request
			if ($_SERVER["REQUEST_METHOD"] != "HEAD")
				$db->readFlushLargeObject($file_row['data_blob']);
		}
	}
	else
		header("HTTP/1.1 404 Not Found");
}
else
	header("HTTP/1.1 404 Not Found");

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>