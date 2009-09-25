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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2004-2007 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Dependency.php');
require_once('classes/NodeList.php');
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/MainUI.php');
require_once('classes/User.php');

/**
 * Defines the HTML type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class NodeListHTML extends NodeList implements AcceptsNullNetwork {

    /**
     * Smarty object
     *
     * @var object

     */
    private $_smarty;

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
     * Object of current user
     *
     * @var object

     */
    private $_currentUser;

    /**
     * Object of MainUI class
     *
     * @var object

     */
    private $_mainUI;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(&$network)
    {
        
        $db = AbstractDb::getObject();
        $smarty = SmartyWifidog::getObject();

        // Init Smarty
        $this->_smarty = &$smarty;

        // Init network
        $this->_network = $network;
        
        // Init user
        $this->_currentUser = User::getCurrentUser();

        // Init MainUI class
        $this->_mainUI = MainUI::getObject();

        $network_where_sql = $network === null?"":"network_id = '" . $db->escapeString($this->_network->getId()) . "' AND ";

        // Query the database, sorting by node name
        $db->execSql("SELECT *, (CURRENT_TIMESTAMP-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE $network_where_sql (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY lower(name)", $this->_nodes, false);
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
    }

    /**
     * Displays the output of this node list.
	 *
     * @return void
     *
     * @author     Benoit Grégoire <benoitg@coeus.ca>
     * @author     Francois Proulx <francois.proulx@gmail.com>
     * @author     Max Horváth <max.horvath@freenet.de>
     * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
     * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
     * @copyright  2006 Max Horváth, Horvath Web Consulting
     */
    public function getOutput()
    {

        // Init ALL smarty values
        $this->_smarty->assign('DEPRECATEDisSuperAdmin', false);
        $this->_smarty->assign('GMapsEnabled', false);
        $this->_smarty->assign('nodes', array());
        $this->_smarty->assign('num_deployed_nodes', 0);
        $this->_smarty->assign('PdfSupported', false);

        $userData['preSelectedObject'] = $this->_network;
        $userData['allowEmpty'] = true;
        $userData['nullCaptionString'] = _("All");
        $userData['onChange'] = "submit.click();";
        $this->_smarty->assign('selectNetworkUI', Network::getSelectUI('network_id', $userData) . (count(Network::getAllNetworks()) > 1 ? '<input class="submit" type="submit" name="submit" value="' . _("Change network") . '">' : ""));
        $this->_smarty->assign('selectedNetworkName', $this->_network === null?_("All networks"):$this->_network->getName());


        /**
         * Define user security levels for the template
         *
         * These values are used in the default template of WiFoDog but could be used
         * in a customized template to restrict certain links to specific user
         * access levels.
         */
        $this->_smarty->assign('DEPRECATEDisSuperAdmin', $this->_currentUser && $this->_currentUser->DEPRECATEDisSuperAdmin());

        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $this->_smarty->assign('GMapsEnabled', true);
        }
        
        $_html=null;
        /*
         * Main content
         */

        // Reset ALL smarty SWITCH values
        $this->_smarty->assign('sectionMAINCONTENT', false);

        // Set section of Smarty template
        $this->_smarty->assign('sectionMAINCONTENT', true);

        // Node details
        if ($this->_nodes) {
            foreach ($this->_nodes as $_nodeData) {
                $_node = Node::getObject($_nodeData['node_id']);
                $_nodeData['num_online_users'] = $_node->getNumOnlineUsers();
                $this->_smarty->append("nodes", $_nodeData);
            }
        }

        $this->_smarty->assign("num_deployed_nodes", count($this->_nodes));

        // Compile HTML code
        $_html_body = $this->_smarty->fetch("templates/sites/hotspot_status.tpl");

        /*
         * Compile HTML output
         */
        $this->_mainUI->setTitle(_("Hotspot list"));
        $this->_mainUI->appendHtmlHeadContent('<link rel="alternate" type="application/rss+xml" title="' . ($this->_network === null?_("All networks"):$this->_network->getName()) . ": " . _("Newest Hotspots") . '" href="' . BASE_SSL_PATH . 'hotspot_status.php?format=RSS">');
        $this->_mainUI->addContent('left_area_middle', $_html);
        $this->_mainUI->addContent('main_area_middle', $_html_body);

        $this->_mainUI->display();
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

