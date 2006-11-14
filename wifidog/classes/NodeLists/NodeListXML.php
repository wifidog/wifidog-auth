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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: Content.php 974 2006-02-25 15:08:12Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Network.php');
require_once('classes/Node.php');

/**
 * Defines the XML type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class NodeListXML {

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
     * @param bool $return_object If true this function only returns the DOM object
     *
     * @return string The XML output
     *
     * @author     Benoit Grégoire <bock@step.polymtl.ca>
     * @author     Francois Proulx <francois.proulx@gmail.com>
     * @author     Max Horvath <max.horvath@maxspot.de>
     * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
     * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
     * @copyright  2006 Max Horvath, maxspot GmbH
     */
    public function getOutput($return_object = false)
    {
        // Root node
        $_hotspotStatusRootNode = $this->_xmldoc->createElement("wifidogHotspotsStatus");
        $_hotspotStatusRootNode->setAttribute('version', '1.0');
        $this->_xmldoc->appendChild($_hotspotStatusRootNode);

        // Document metadata
        $_documentGendateNode = $this->_xmldoc->createElement("generationDateTime", gmdate("Y-m-d\Th:m:s\Z"));
        $_hotspotStatusRootNode->appendChild($_documentGendateNode);

        // Network metadata
        $_networkMetadataNode = $this->_xmldoc->createElement("networkMetadata");
        $_networkMetadataNode = $_hotspotStatusRootNode->appendChild($_networkMetadataNode);

        $_networkUriNode = $this->_xmldoc->createElement("networkUri", htmlspecialchars($this->_network->getHomepageURL(), ENT_QUOTES));
        $_networkMetadataNode->appendChild($_networkUriNode);

        $_networkNameNode = $this->_xmldoc->createElement("name", htmlspecialchars($this->_network->getName(), ENT_QUOTES));
        $_networkMetadataNode->appendChild($_networkNameNode);

        $_networkUrlNode = $this->_xmldoc->createElement("websiteUrl", htmlspecialchars($this->_network->getHomepageURL(), ENT_QUOTES));
        $_networkMetadataNode->appendChild($_networkUrlNode);

        $_email = $this->_network->getTechSupportEmail();
        if (!empty($email)) {
            $_networkEmailNode = $this->_xmldoc->createElement("techSupportEmail", $_email);
            $_networkMetadataNode->appendChild($_networkEmailNode);
        }

        $_nodesCountNode = $this->_xmldoc->createElement("hotspotsCount", count($this->_nodes));
        $_networkMetadataNode->appendChild($_nodesCountNode);

        $_networkValidUsersNode = $this->_xmldoc->createElement("validSubscribedUsersCount", $this->_network->getNumValidUsers());
        $_networkMetadataNode->appendChild($_networkValidUsersNode);

        // Get number of online users
        $_networkOnlineUsersNode = $this->_xmldoc->createElement("onlineUsersCount", $this->_network->getNumOnlineUsers());
        $_networkMetadataNode->appendChild($_networkOnlineUsersNode);

        // Node details
        if ($this->_nodes) {
            // Hotspots metadata
            $_hotspotsMetadataNode = $this->_xmldoc->createElement("hotspots");
            $_hotspotsMetadataNode = $_hotspotStatusRootNode->appendChild($_hotspotsMetadataNode);

            foreach ($this->_nodes as $_nodeData) {
                $_node = Node::getObject($_nodeData['node_id']);
                $this->_network = $_node->getNetwork();

                $_hotspot = $this->_xmldoc->createElement("hotspot");
                $_hotspot = $_hotspotsMetadataNode->appendChild($_hotspot);

                // Hotspot ID
                $_hotspotId = $this->_xmldoc->createElement("hotspotId", $_node->getId());
                $_hotspot->appendChild($_hotspotId);

                // Hotspot name
                $_hotspotName = $this->_xmldoc->createElement("name", htmlspecialchars($_node->getName(), ENT_QUOTES));
                $_hotspot->appendChild($_hotspotName);

                /**
                 * (1..n) A Hotspot has many node
                 *
                 * WARNING For now, we are simply duplicating the hotspot data in node
                 * Until wifidog implements full abstractiong hotspot vs nodes.
                 */
                $_nodes = $this->_xmldoc->createElement("nodes");
                $_hotspot->appendChild($_nodes);

                $_nodeMetadataNode = $this->_xmldoc->createElement("node");
                $_nodes->appendChild($_nodeMetadataNode);

                // Node ID
                $_nodeId = $this->_xmldoc->createElement("nodeId", $_node->getId());
                $_nodeMetadataNode->appendChild($_nodeId);

                $_nodeCreationDate = $this->_xmldoc->createElement("creationDate", $_node->getCreationDate());
                $_nodeMetadataNode->appendChild($_nodeCreationDate);

                if ($_node->getDeploymentStatus() != 'NON_WIFIDOG_NODE') {
                    if ($_nodeData['is_up'] == 't') {
                        $_nodeStatus = $this->_xmldoc->createElement("status", "up");
                    } else {
                        $_nodeStatus = $this->_xmldoc->createElement("status", "down");
                    }

                    $_nodeMetadataNode->appendChild($_nodeStatus);
                }

				if (($_gisData = $_node->getGisLocation()) !== null) {
                    $_nodeGis = $this->_xmldoc->createElement("gisLatLong");
                    $_nodeGis->setAttribute("lat", $_gisData->getLatitude());
                    $_nodeGis->setAttribute("long", $_gisData->getLongitude());
                    $_nodeMetadataNode->appendChild($_nodeGis);
                }

                // Hotspot opening date ( for now it's called creation_date )
                $_hotspotOpeningDate = $this->_xmldoc->createElement("openingDate", $_node->getCreationDate());
                $_hotspot->appendChild($_hotspotOpeningDate);

                // Hotspot Website URL
                if ($_node->getHomePageURL() != "") {
                    $_hotspotUrl = $this->_xmldoc->createElement("webSiteUrl", htmlspecialchars($_node->getHomePageURL(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotUrl);
                }

                // Hotspot global status
                if ($_node->getDeploymentStatus() != 'NON_WIFIDOG_NODE') {
                    if ($_nodeData['is_up'] == 't') {
                        $_hotspotStatus = $this->_xmldoc->createElement("globalStatus", "100");
                    } else {
                        $_hotspotStatus = $this->_xmldoc->createElement("globalStatus", "0");
                    }

                    $_hotspot->appendChild($_hotspotStatus);
                }

                // Description
                if ($_node->getDescription() != "") {
                    $_hotspotDesc = $this->_xmldoc->createElement("description", htmlspecialchars($_node->getDescription(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotDesc);
                }

                // Map Url
                if ($_node->getMapURL() != "") {
                    $_hotspotMapUrl = $this->_xmldoc->createElement("mapUrl", htmlspecialchars($_node->getMapURL(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotMapUrl);
                }

                // Mass transit info
                if ($_node->getTransitInfo() != "") {
                    $_hotspotTransit = $this->_xmldoc->createElement("massTransitInfo", htmlspecialchars($_node->getTransitInfo(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotTransit);
                }

                // Contact e-mail
                if ($_node->getEmail() != "") {
                    $_hotspotContactEmail = $this->_xmldoc->createElement("contactEmail", $_node->getEmail());
                    $_hotspot->appendChild($_hotspotContactEmail);
                }

                // Contact phone
                if ($_node->getTelephone() != "") {
                    $_hotspotContactPhone = $this->_xmldoc->createElement("contactPhoneNumber", $_node->getTelephone());
                    $_hotspot->appendChild($_hotspotContactPhone);
                }

                // Civic number
                if ($_node->getCivicNumber() != "") {
                    $_hotspotCivicNr = $this->_xmldoc->createElement("civicNumber", $_node->getCivicNumber());
                    $_hotspot->appendChild($_hotspotCivicNr);
                }

                // Street address
                if ($_node->getStreetName() != "") {
                    $_hotspotStreet = $this->_xmldoc->createElement("streetAddress", htmlspecialchars($_node->getStreetName(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotStreet);
                }

                // City
                if ($_node->getCity() != "") {
                    $_hotspotCity = $this->_xmldoc->createElement("city", htmlspecialchars($_node->getCity(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotCity);
                }

                // Province
                if ($_node->getProvince() != "") {
                    $_hotspotProvince = $this->_xmldoc->createElement("province", htmlspecialchars($_node->getProvince(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotProvince);
                }

                // Postal code
                if ($_node->getPostalCode() != "") {
                    $_hotspotPostalCode = $this->_xmldoc->createElement("postalCode", $_node->getPostalCode());
                    $_hotspot->appendChild($_hotspotPostalCode);
                }

                // Country
                if ($_node->getCountry() != "") {
                    $_hotspotCountry = $this->_xmldoc->createElement("country", htmlspecialchars($_node->getCountry(), ENT_QUOTES));
                    $_hotspot->appendChild($_hotspotCountry);
                }

                // Long / Lat
				if (($_gisData = $_node->getGisLocation()) !== null) {
                    $_hotspotGis = $this->_xmldoc->createElement("gisCenterLatLong");
                    $_hotspotGis->setAttribute("lat", $_gisData->getLatitude());
                    $_hotspotGis->setAttribute("long", $_gisData->getLongitude());
                    $_hotspot->appendChild($_hotspotGis);
                }
            }
        }

        if ($return_object) {
            return $this->_xmldoc;
        } else {
            echo $this->_xmldoc->saveXML();
        }
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

