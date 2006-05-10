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
 * Network status page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/Network.php');
require_once('classes/NodeList.php');

if (!empty ($_REQUEST['format'])) {
    $format = $db->escapeString($_REQUEST['format']);
} else {
    $format = "HTML";
}

if (!empty ($_REQUEST['network_id'])) {
    $network = Network::getObject($db->escapeString($_REQUEST['network_id']));
} else {
    $network = Network::getDefaultNetwork(true);
}

if ($network) {
    // Init node list type
    $nodeList = new NodeList($format, $network);

    // If a XSL transform stylesheet has been specified, try to us it.
    if ($format == "XML" && defined('XSLT_SUPPORT') && XSLT_SUPPORT && !empty($_REQUEST['xslt']) && ($xslt_dom = @DomDocument::load($_REQUEST['xslt'])) !== false) {
        // Load the XSLT
        $xslt_proc = new XsltProcessor();
        $xslt_proc->importStyleSheet($xslt_dom);

        // Prepare HTML
        header("Content-Type: text/html; charset=UTF-8");
        echo $xslt_proc->transformToXML($nodeList->nodeList->getOutput(true));
    } else {
        // Deliver node list
        $nodeList->nodeList->getOutput();
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>