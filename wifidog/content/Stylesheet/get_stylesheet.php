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
 * @author     Benoit Grégoire
 * @copyright  2006 Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/../../include/common.php');
require_once ('classes/Content.php');
if (!empty ($_REQUEST['content_id'])) {
    $db = AbstractDb :: getObject();
    $content = Content :: getObject($_REQUEST['content_id']);
    $content_str = $content->getString();
    if ($content instanceof Stylesheet) {
        // Check if the HTTP request is asking if the file has been modified since a certain date.
        $last_modified_date = isset ($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : 0;
        if ($last_modified_date && $_SERVER['REQUEST_METHOD'] == "GET" && strtotime($last_modified_date) >= strtotime($content->getLastUpdateTimestamp())) {
            header("HTTP/1.1 304 Not Modified");
        }
        else {
            //headers to send to the browser before beginning the binary download
            header("Pragma: public");
            // Send last update date to proxy / cache the binary
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", strtotime($content->getLastUpdateTimestamp())) . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header('Content-Type: text/css');
            header('Accept-Ranges: bytes');
            /* Compute correct length (parts of the code from egroupware): */
            $has_mbstring = extension_loaded('mbstring') || @ dl(PHP_SHLIB_PREFIX . 'mbstring.' . PHP_SHLIB_SUFFIX);
            $has_mb_shadow = (int) ini_get('mbstring.func_overload');

            if ($has_mbstring && ($has_mb_shadow & 2)) {
                $size = mb_strlen($content_str, 'latin1');
            }
            else {
                $size = strlen($content_str);
            }
            header('Content-Length: ' . $size); //this is the size of the zipped file
            // Do not send binary if this is only a HEAD request
            if ($_SERVER["REQUEST_METHOD"] != "HEAD")
                echo $content_str;
        }
    }
    else {
        header("HTTP/1.1 404 Not Found");
    }
}
else {
    header("HTTP/1.1 404 Not Found");
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>