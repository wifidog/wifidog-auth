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
 * @subpackage NodeLists
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: Content.php 974 2006-02-25 15:08:12Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/User.php');

/**
 * Defines the RSS type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class NodeListRSS {

    /**
     * XML DOM Document that will contain all the data concerning the nodes
     *
     * @var object

     */
    private $_xmldoc;

    /**
     * Network to generate the list from
     *
     * @var object

     */
    private $_network;

    /**
     * Nodes to generate the list from
     *
     * @var array

     */
    private $_nodes;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(&$network)
    {
        
        $db = AbstractDb::getObject();

        // Init XML Document
        $this->_xmldoc = new DOMDocument("1.0", "UTF-8");
        $this->_xmldoc->formatOutput = true;

        // Init network
        $this->_network = $network;

        // Query the database, sorting by node name
        $db->execSql("SELECT *, (CURRENT_TIMESTAMP-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '" . $db->escapeString($this->_network->getId()) . "' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY lower(name)", $this->_nodes, false);
    }

    /**
     * Sets header of output
     *
     * @return void
     */
    public function setHeader()
    {
        header("Cache-control: private, no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
        header("Pragma: no-cache");
        header("Content-Type: text/xml; charset=UTF-8");
    }

    /**
     * Retreives the output of this object.
     *
     * @param bool $return_object This parameter doesn't have any effect in
     *                            the class
     *
     * @return void
     *
     * @author     Benoit Grégoire <bock@step.polymtl.ca>
     * @author     Francois Proulx <francois.proulx@gmail.com>
     * @author     Max Horváth <max.horvath@freenet.de>
     * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
     * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
     * @copyright  2006 Max Horváth, Horvath Web Consulting
     */
    public function getOutput($return_object = false)
    {
        
        $db = AbstractDb::getObject();

        // Root node
        $_rss = $this->_xmldoc->createElement("rss");
        $this->_xmldoc->appendChild($_rss);

        $_rss->setAttribute('version', '2.0');

        // channel
        $_channel = $this->_xmldoc->createElement("channel");
        $_rss->appendChild($_channel);

        /*
         * Required channel elements
         */

        // title
        $_title = $this->_xmldoc->createElement("title");
        $_title = $_channel->appendChild($_title);

        $_textNode = $this->_xmldoc->createTextNode($this->_network->getName() . ": " . _("Newest Hotspots"));
        $_title->appendChild($_textNode);

        // link
        $_link = $this->_xmldoc->createElement("link");
        $_channel->appendChild($_link);
        $_textNode = $this->_xmldoc->createTextNode($this->_network->getHomepageURL());
        $_link->appendChild($_textNode);

        // description
        $_description = $this->_xmldoc->createElement("description");
        $_channel->appendChild($_description);
        $_textNode = $this->_xmldoc->createTextNode(_("List of the most recent Hotspots opened by the network: ") . $this->_network->getName());
        $_description->appendChild($_textNode);

        /*
         * Optional channel elements
         */

        // language
        $_language = $this->_xmldoc->createElement("language");
        $_channel->appendChild($_language);

        if (User::getCurrentUser() != null) {
            $_textNode = $this->_xmldoc->createTextNode(substr(User::getCurrentUser()->getPreferedLocale(), 0, 5));
        } else {
            $_textNode = $this->_xmldoc->createTextNode("en-US");
        }
        $_language->appendChild($_textNode);

        // copyright
        $_copyright = $this->_xmldoc->createElement("copyright");
        $_channel->appendChild($_copyright);
        $_textNode = $this->_xmldoc->createTextNode(_("Copyright ") . $this->_network->getName());
        $_copyright->appendChild($_textNode);

        // webMaster
        if ($this->_network->getTechSupportEmail() != "") {
            $_webMaster = $this->_xmldoc->createElement("webMaster");
            $_channel->appendChild($_webMaster);
            $_textNode = $this->_xmldoc->createTextNode($this->_network->getTechSupportEmail());
            $_webMaster->appendChild($_textNode);
        }

        // pubDate
        $_pubDate = $this->_xmldoc->createElement("pubDate");
        $_channel->appendChild($_pubDate);
        $_textNode = $this->_xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", time()));
        $_pubDate->appendChild($_textNode);

        /**
         * lastBuildDate
         *
         * <lastBuildDate> -- The date-time the last time the content of the
         * channel changed.
         *
         * Make a request through the database for the latest modification date
         * of an object.
         *
         * @todo The latest modification date of an object should be an
         *       object property
         */
        $db->execSqlUniqueRes("SELECT EXTRACT(epoch FROM MAX(creation_date)) as date_last_hotspot_opened FROM nodes WHERE network_id = '" . $db->escapeString($this->_network->getId()) . "' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE')", $_lastHotspotRow, false);

        $_lastBuildDate = $this->_xmldoc->createElement("lastBuildDate");
        $_channel->appendChild($_lastBuildDate);
        $_textNode = $this->_xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", $_lastHotspotRow['date_last_hotspot_opened']));
        $_lastBuildDate->appendChild($_textNode);

        // generator
        $_generator = $this->_xmldoc->createElement("generator");
        $_channel->appendChild($_generator);
        $_textNode = $this->_xmldoc->createTextNode(WIFIDOG_NAME . " " . WIFIDOG_VERSION);
        $_generator->appendChild($_textNode);

        // docs
        $_docs = $this->_xmldoc->createElement("docs");
        $_channel->appendChild($_docs);
        $_textNode = $this->_xmldoc->createTextNode("http://blogs.law.harvard.edu/tech/rss");
        $_docs->appendChild($_textNode);

        // image
        /*if (defined('NETWORK_LOGO_NAME') && file_exists(WIFIDOG_ABS_FILE_PATH . "local_content/common/" . constant('NETWORK_LOGO_NAME'))) {
            $_image = $this->_xmldoc->createElement("image");
            $_channel->appendChild($_image);

            // title
            $_title = $this->_xmldoc->createElement("title");
            $_image->appendChild($_title);
            $_textNode = $this->_xmldoc->createTextNode($this->_network->getName() . ": " . _("Newest Hotspots"));
            $_title->appendChild($_textNode);

            // url
            $_url = $this->_xmldoc->createElement("url");
            $_image->appendChild($_url);
            $_textNode = $this->_xmldoc->createTextNode(COMMON_CONTENT_URL . NETWORK_LOGO_NAME);
            $_url->appendChild($_textNode);

            // link
            $_link = $this->_xmldoc->createElement("link");
            $_image->appendChild($_link);
            $_textNode = $this->_xmldoc->createTextNode($this->_network->getHomepageURL());
            $_link->appendChild($_textNode);

            $_imageSize = @getimagesize(WIFIDOG_ABS_FILE_PATH . "local_content/common/" . NETWORK_LOGO_NAME);

            if ($_imageSize) {
                // width
                $_width = $this->_xmldoc->createElement("width");
                $_image->appendChild($_width);
                $_textNode = $this->_xmldoc->createTextNode($_imageSize[0]);
                $_width->appendChild($_textNode);

                // height
                $_height = $this->_xmldoc->createElement("height");
                $_image->appendChild($_height);
                $_textNode = $this->_xmldoc->createTextNode($_imageSize[1]);
                $_height->appendChild($_textNode);
            }

            // description
            $_description = $this->_xmldoc->createElement("description");
            $_image->appendChild($_description);
            $_textNode = $this->_xmldoc->createTextNode(_("List of the most recent Hotspots opened by the network: ") . $this->_network->getName());
            $_description->appendChild($_textNode);
        }*/

        // Node details
        if ($this->_nodes) {
            foreach ($this->_nodes as $_nodeData) {
                $_node = Node::getObject($_nodeData['node_id']);
                $this->_network = $_node->getNetwork();

                $_hotspot = $this->_xmldoc->createElement("item");
                $_hotspot = $_channel->appendChild($_hotspot);

                // Hotspot name
                $_hotspotName = $this->_xmldoc->createElement("title", htmlspecialchars($_node->getName(), ENT_QUOTES));
                $_hotspot->appendChild($_hotspotName);

                // Hotspot Website URL
                if ($_node->getHomePageURL() != "") {
                    $_hotspotUrl = $this->_xmldoc->createElement("link", htmlspecialchars($_node->getHomePageURL(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotUrl);
                }

                // Hotspot name
                $_hotspotDesc = $this->_xmldoc->createElement("description");
                $_hotspot->appendChild($_hotspotDesc);

                $_descriptionText = '<p>';

                // Hotspot global status
                if ($_node->getDeploymentStatus() != 'NON_WIFIDOG_NODE') {
                    if ($_nodeData['is_up'] == 't') {
                        $_descriptionText .= "<img src='" . COMMON_IMAGES_URL . "HotspotStatus/up.gif' alt='up' />";
                    } else {
                        $_descriptionText .= "<img src='" . COMMON_IMAGES_URL . "HotspotStatus/down.gif' alt='down' />";
                    }
                }

                // Description
                if ($_node->getDescription() != "") {
                    $_descriptionText .= htmlspecialchars($_node->getDescription(), ENT_QUOTES);
                }
                $_descriptionText .= '</p>';
                $_descriptionText .= '<p>';
                $_descriptionText .= _("Address") . ": ";

                // Civic number
                if ($_node->getCivicNumber() != "") {
                    $_descriptionText .= $_node->getCivicNumber() . ", ";
                }

                // Street address
                if ($_node->getStreetName() != "") {
                    $_descriptionText .= htmlspecialchars($_node->getStreetName(), ENT_QUOTES) . ", ";
                }

                // City
                if ($_node->getCity() != "") {
                    $_descriptionText .= htmlspecialchars($_node->getCity(), ENT_QUOTES) . ", ";
                }

                // Province
                if ($_node->getProvince() != "") {
                    $_descriptionText .= htmlspecialchars($_node->getProvince(), ENT_QUOTES) . ", ";
                }

                // Postal code
                if ($_node->getPostalCode() != "") {
                    $_descriptionText .= $_node->getPostalCode() . ", ";
                }

                // Country
                if ($_node->getCountry() != "") {
                    $_descriptionText .= htmlspecialchars($_node->getCountry(), ENT_QUOTES);
                }

                // Map Url
                if ($_node->getMapURL() != "") {
                    $_descriptionText .= " <a href='" . $_node->getMapURL() . "'>" . _("See Map") . "</a>";
                }

                // Mass transit info
                if ($_node->getTransitInfo() != "") {
                    $_descriptionText .= "<br />";
                    $_descriptionText .= htmlspecialchars($_node->getTransitInfo(), ENT_QUOTES);
                }

                $_descriptionText .= "</p>";

                if (($_node->getEmail() != "") || ($_node->getTelephone() != "")) {
                    $_descriptionText .= "<p>";
                    $_descriptionText .= _("Contact") . ": ";

                    // Contact e-mail
                    if ($_node->getEmail() != "") {
                        $_descriptionText .= "<br /><a href='mailto:" . $_node->getEmail() . "'>" . $_node->getEmail() . "</a>";
                    }

                    // Contact phone
                    if ($_node->getTelephone() != "") {
                        $_descriptionText .= "<br />" . $_node->getTelephone();
                    }

                    $_descriptionText .= "</p>";
                }

                $_hotspotDesc->appendChild($this->_xmldoc->createTextNode($_descriptionText));

                // guid
                if ($_node->getHomePageURL() != "") {
                    $_guid = $this->_xmldoc->createElement("guid");
                    $_guid->setAttribute('isPermaLink', 'false');
                    $_hotspot->appendChild($_guid);
                    $_textNode = $this->_xmldoc->createTextNode(htmlspecialchars($_node->getHomePageURL(), ENT_QUOTES));
                    $_guid->appendChild($_textNode);
                }

                // pubDate
                $_hotspotOpeningDate = $this->_xmldoc->createElement("pubDate", gmdate("D, d M Y H:i:s \G\M\T", $_nodeData['creation_date_epoch']));
                $_hotspot->appendChild($_hotspotOpeningDate);
            }
        }

        echo $this->_xmldoc->saveXML();
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

