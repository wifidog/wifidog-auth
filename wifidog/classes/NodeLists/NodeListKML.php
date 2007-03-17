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
 * @author     Joe Bowser <bowserj@resist.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Joe Bowser
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: Content.php 974 2006-02-25 15:08:12Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Network.php');
require_once('classes/Node.php');

/**
 * Defines the KML type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Joe Bowser <bowserj@resist.ca>
 * @copyright  2006 Joe Bowser
 */
class NodeListKML {

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
        header("Cache-control: private, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
        header("Pragma: no-cache");
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/vnd.google-earth.kml+xml; charset=UTF-8");
        header("Content-Disposition: attachment; filename=" . $this->_network->getId() . "_hotspot_status.kml");
    }

    /**
     * Retreives the output of this object.
     *
     * @param bool $return_object If true this function only returns the DOM object
     *
     * @return string The XML output
     *
     * @author     Benoit Gregoire <bock@step.polymtl.ca>
     * @author     Francois Proulx <francois.proulx@gmail.com>
     * @author     Max Horváth <max.horvath@freenet.de>
	 * @author     Joe Bowser <bowserj@resist.ca>
     * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
     * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
     * @copyright  2006 Max Horváth, Horvath Web Consulting
	 * @copyright  2006 Joe Bowser
     */
    public function getOutput($return_object = false)
    {
		$_kml = $this->_xmldoc->createElement("kml");
		$_kml->setAttribute('xmlns', 'http://earth.google.com/kml/2.0');
		$this->_xmldoc->appendChild($_kml);

		// Document
		$_document = $this->_xmldoc->createElement("Document");
		$_kml->appendChild($_document);

		/*
		 * Style Elements (Up Nodes)
		 */
		$_style_up = $this->_xmldoc->createElement("Style");
		$_style_up->setAttribute('id', 'node_up');
		$_document->appendChild($_style_up);
		$_iconStyle = $this->_xmldoc->createElement("IconStyle");
		$_style_up->appendChild($_iconStyle);

		/* Since scale is the same, we only have to define it once */
		$_scale = $this->_xmldoc->createElement("scale");
		$_iconStyle->appendChild($_scale);
		$_textNode = $this->_xmldoc->createTextNode("0.5");
		$_scale->appendChild($_textNode);

		$_icon = $this->_xmldoc->createElement("Icon");
		$_iconStyle->appendChild($_icon);
		$_href = $this->_xmldoc->createElement("href");
		$_icon->appendChild($_href);
		$_textNode = $this->_xmldoc->createTextNode(BASE_URL_PATH . "images/HotspotStatusMap/up.png");
		$_href->appendChild($_textNode);

		/*
		 * Style Elements (Down Nodes)
		 */
		$_style_down = $this->_xmldoc->createElement("Style");
		$_style_down->setAttribute('id', 'node_down');
		$_document->appendChild($_style_down);
		$_iconStyle = $this->_xmldoc->createElement("IconStyle");
		$_style_down->appendChild($_iconStyle);

		$_scale = $this->_xmldoc->createElement("scale");
		$_iconStyle->appendChild($_scale);
		$_textNode = $this->_xmldoc->createTextNode("0.5");
		$_scale->appendChild($_textNode);

		$_iconStyle->appendChild($_scale);
		$_icon = $this->_xmldoc->createElement("Icon");
		$_iconStyle->appendChild($_icon);
		$_href = $this->_xmldoc->createElement("href");
		$_icon->appendChild($_href);
		$_textNode = $this->_xmldoc->createTextNode(BASE_URL_PATH . "images/HotspotStatusMap/down.png");
		$_href->appendChild($_textNode);

		/*
		 * Style Elements (Unknown Nodes)
		 */

		$_style_unknown = $this->_xmldoc->createElement("Style");
		$_style_unknown->setAttribute('id', 'node_unknown');
		$_document->appendChild($_style_unknown);
		$_iconStyle = $this->_xmldoc->createElement("IconStyle");
		$_style_unknown->appendChild($_iconStyle);

		$_scale = $this->_xmldoc->createElement("scale");
		$_iconStyle->appendChild($_scale);
		$_textNode = $this->_xmldoc->createTextNode("0.5");
		$_scale->appendChild($_textNode);

		$_icon = $this->_xmldoc->createElement("Icon");
		$_iconStyle->appendChild($_icon);
		$_href = $this->_xmldoc->createElement("href");
		$_icon->appendChild($_href);
		$_textNode = $this->_xmldoc->createTextNode(BASE_URL_PATH . "images/HotspotStatusMap/unknown.png");
		$_href->appendChild($_textNode);

		/*
		 * Creating the Folder
		 */
		$_folder = $this->_xmldoc->createElement("Folder");
		$_document->appendChild($_folder);
		$_name = $this->_xmldoc->createElement("name");
		$_folder->appendChild($_name);
		$_textNode = $this->_xmldoc->createTextNode($this->_network->getName());
		$_name->appendChild($_textNode);

		/*
		 * Creating the Placemarks (Nodes)
		 */
		if ($this->_nodes) {
			foreach ($this->_nodes as $_nodeData) {
				$_node = Node::getObject($_nodeData['node_id']);
                $this->_network = $_node->getNetwork();

				$_placemark = $this->_xmldoc->createElement("Placemark");
				$_folder->appendChild($_placemark);

				// Hotspot name
                $_hotspotName = $this->_xmldoc->createElement("name", htmlspecialchars($_node->getName(), ENT_QUOTES));
                $_placemark->appendChild($_hotspotName);
				$_html_data = "<b>" . _("Address") . ":</b><br />" . $_node->getCivicNumber() . " " . $_node->getStreetName() . "<br />" .
					$_node->getCity() . "," . $_node->getProvince() . "<br />" . $_node->getCountry() . "<br />" .
					$_node->getPostalCode() . "<br /><br /> <b>" . _("URL") . ":</b> <a href='" . $_node->getWebSiteURL() . "'>" .
					$_node->getWebSiteURL() . "</a> <br /> <b> " . _("Email") . ":</b> <a href='mailto:" . $_node->getEmail() . "'>" .
					$_node->getEmail() . "</a>";
				// Creating the description node with the data from it
				$_description = $this->_xmldoc->createElement("description");
				$_placemark->appendChild($_description);
				$_cdata = $this->_xmldoc->createCDATASection($_html_data);
				$_description->appendChild($_cdata);
				// Description data goes here
				$_point = $this->_xmldoc->createElement("Point");
				$_placemark->appendChild($_point);
				// Get GIS Location
				$_gis_loc = $_node->getGisLocation();
				$_gis_string = $_gis_loc->getLongitude() . ","
					. $_gis_loc->getLatitude() .","
					. $_gis_loc->getAltitude();
				$_coordinates = $this->_xmldoc->createElement("coordinates", $_gis_string);
				// Hotspot global status
                if ($_node->getDeploymentStatus() != 'NON_WIFIDOG_NODE') {
                    if ($_nodeData['is_up'] == 't') {
						$_styleURL = $this->_xmldoc->createElement("styleURL", "#node_up");
                    } else {
						$_styleURL = $this->_xmldoc->createElement("styleURL", "#node_down");
                    }
                }
				else {
					$_styleURL = $this->_xmldoc->createElement("styleURL", "#node_unknown");
				}

				$_point->appendChild($_coordinates);
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

?>
