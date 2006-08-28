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
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: Content.php 1074 2006-06-18 09:53:56Z fproulx $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/User.php');

/**
 * Abstraction and utilities to handle HyperLinks
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 */
class HyperLink {
    /**
     * Find http, https and ftp hyperlinks in a string
     *
     * @param string $string The string to parse to find hyperlinks in A
     * HREF constructs
     * @return array of URLs
    
     */
    public static function findHyperLinks(&$string) {
        $pattern = '/<a\s.*?HREF=[\'"]?((?:http|https|ftp).*?)[\'"\s].*?>/mi';
        //pretty_print_r($pattern);
        $matches = null;
        $num_matches = preg_match_all($pattern, $string, $matches);
        //pretty_print_r($matches);
        return $matches[1];
    }

/** Get the  clickthrough-logged equivalent of a sincle URL (http, https or ftp) */
    public static function getClickThroughLink($hyperlink, Content &$content, $node, $user) {
        $node?$node_id=urlencode($node->getId()):$node_id=null;
        $user?$user_id=urlencode($user->getId()):$user_id=null;
        return BASE_URL_PATH . "clickthrough.php?destination_url=" . urlencode($hyperlink) . "&content_id=".urlencode($content->getId())."&node_id={$node_id}&user_id={$user_id}";
    }

/** Replace all hyperlinks in the source string with their clickthrough-logged equivalents */
    public static function replaceHyperLinks(&$string, Content &$content) {
        $links = self :: findHyperLinks($string);
        if(!empty($links))
        {
            $node = Node::getCurrentNode();
        $user = User::getCurrentUser();
        foreach ($links as $link) {
            $replacements[] = self :: getClickThroughLink($link, $content, $node, $user);
        }
        //pretty_print_r($replacements);
        return str_replace($links, $replacements, $string);
        }
        else
        {
            return $string;
        }
    }
} // End class

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */