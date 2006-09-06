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
 * @author     François Proulx <francois.proulx@gmail.com>
 * @copyright  (c)2006 François Proulx
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Network.php');
require_once('classes/Node.php');

/**
 * Defines the JiWire CSV type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     François Proulx <francois.proulx@gmail.com>
 * @copyright  (c) 2006 François Proulx
 */
class NodeListJiWireCSV {

	/**
	 *
	 * The JiWire formatted CSV document
	 */
	private $_csv_document;

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
        // Define globals
        global $db;

        // Init network
        $this->_network = $network;

        $this->_csv_document = "";

        // Query the database, sorting by node name
        $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '" . $db->escapeString($this->_network->getId()) . "' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY lower(name)", $this->_nodes, false);
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
        header("Content-Type: text/csv; charset=UTF-8");
    }

    private function quoteForCsv($str)
    {
    		return "\"".str_replace("\"", "\"\"", $str)."\"";
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
    		$this->_csv_document = "jiwire_ref, status, provider_id, provider_name, address, address2, city, state, country, postal_code, website_url, email, phone, fax, location_name, location_type_id, field17, node_type, ssid, fee_comments, equipement, standard_802_11, MAC_address, network_drop, latitude, longitude\r\n";

    		if ($this->_nodes) {
    			foreach ($this->_nodes as $_nodeData) {
    				$_node = Node::getObject($_nodeData['node_id']);

    				// No JiWire ref. number, status NEW
    				$this->_csv_document .= "###,NEW,";

				// Provider Name = Wifidog node ID
    				$this->_csv_document .= $this->quoteForCsv($_node->getId()).",";

    				// Provider Id = Wifidog Network name
    				$this->_csv_document .= $this->quoteForCsv($this->_network->getName()).",";

    				// Address
    				$this->_csv_document .= $this->quoteForCsv($_node->getCivicNumber().", ".$_node->getStreetName()).",";

    				// Address 2 (skipped)
    				$this->_csv_document .= ",";

    				// City
    				$this->_csv_document .= $this->quoteForCsv($_node->getCity()).",";

    				// State
    				$this->_csv_document .= $this->quoteForCsv($_node->getProvince()).",";

    				// Country
    				$this->_csv_document .= $this->quoteForCsv($_node->getCountry()).",";

				// Postal code
    				$this->_csv_document .= $this->quoteForCsv($_node->getPostalCode()).",";

    				// Web Site URL
    				$this->_csv_document .= $this->quoteForCsv($_node->getHomePageURL()).",";

    				// Email
    				$this->_csv_document .= $this->quoteForCsv($_node->getEmail()).",";

    				// Phone number
    				$this->_csv_document .= $this->quoteForCsv($_node->getTelephone()).",";

    				// Fax (skipped)
    				$this->_csv_document .= ",";

    				// Node name
    				$this->_csv_document .= $this->quoteForCsv($_node->getName()).",";

    				// Location type (JiWite Appendix A --> 5 = Café), field17 (skipped), node_type (2 = free), SSID (skipped),  fee comment (skipped), 802.11 type (b or g, skipped),
    				$this->_csv_document .= "5,, 2,,,,,,,";

    				// Latitude + longitude
    				$this->_csv_document .= $_node->getGisLocation()->getLatitude().",".$_node->getGisLocation()->getLongitude();

    				$this->_csv_document .= "\r\n";
    			}
    		}

        echo $this->_csv_document;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

