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
require_once('classes/MainUI.php');
require_once('classes/User.php');

/**
 * Defines the HTML type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class NodeListHTML {

    /**
     * Smarty object
     *
     * @var object
     *
     * @access private
     */
    private $_smarty;

    /**
     * Network to generate the list from
     *
     * @var object
     *
     * @access private
     */
    private $_network;

    /**
     * Nodes to generate the list from
     *
     * @var array
     *
     * @access private
     */
    private $_nodes;

    /**
     * Object of current user
     *
     * @var object
     *
     * @access private
     */
    private $_currentUser;

    /**
     * Object of MainUI class
     *
     * @var object
     *
     * @access private
     */
    private $_mainUI;

    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     */
    public function __construct(&$network)
    {
        // Define globals
        global $db;
        global $smarty;

        // Init Smarty
        $this->_smarty = &$smarty;

        // Init network
        $this->_network = $network;

        // Init user
        $this->_currentUser = User::getCurrentUser();

        // Init MainUI class
        $this->_mainUI = new MainUI();

        // Query the database, sorting by node name
        $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '" . $db->escapeString($this->_network->getId()) . "' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY name", $this->_nodes, false);
    }

    /**
     * Sets header of output
     *
     * @return void
     *
     * @access public
     */
    public function setHeader()
    {
        header("Cache-control: private, no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
        header("Pragma: no-cache");
    }

    /**
     * Retreives the output of this object.
     *
     * @param bool $return_object This parameter doesn't have any effect in
     *                            the class
     *
     * @return void
     *
     * @author     Benoit Gregoire <bock@step.polymtl.ca>
     * @author     Francois Proulx <francois.proulx@gmail.com>
     * @author     Max Horvath <max.horvath@maxspot.de>
     * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
     * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
     * @copyright  2006 Max Horvath, maxspot GmbH
     *
     * @access public
     */
    public function getOutput($return_object = false)
    {
        // Init ALL smarty SWITCH values
        $this->_smarty->assign('sectionTOOLCONTENT', false);
        $this->_smarty->assign('sectionMAINCONTENT', false);

        // Init ALL smarty values
        $this->_smarty->assign('isSuperAdmin', false);
        $this->_smarty->assign('isOwner', false);
        $this->_smarty->assign('GMapsEnabled', false);
        $this->_smarty->assign('nodes', array());
        $this->_smarty->assign('num_deployed_nodes', 0);

        /*
         * Tool content
         */

        /**
         * Define user security levels for the template
         *
         * These values are used in the default template of WiFoDog but could be used
         * in a customized template to restrict certain links to specific user
         * access levels.
         */
        $this->_smarty->assign('isSuperAdmin', $this->_currentUser && $this->_currentUser->isSuperAdmin());
        $this->_smarty->assign('isOwner', $this->_currentUser && $this->_currentUser->isOwner());

        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $this->_smarty->assign('GMapsEnabled', true);
        }
        // Set section of Smarty template
        $this->_smarty->assign('sectionTOOLCONTENT', true);

        // Compile HTML code
        $_html = $this->_smarty->fetch("templates/sites/hotspot_status.tpl");

        /*
         * Main content
         */

        // Reset ALL smarty SWITCH values
        $this->_smarty->assign('sectionTOOLCONTENT', false);
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
        $this->_mainUI->SetHtmlHeader('<link rel="alternate" type="application/rss+xml" title="' . $this->_network->getName() . ": " . _("Newest Hotspots") . '" href="' . BASE_SSL_PATH . 'hotspot_status.php?format=RSS">');
        $this->_mainUI->setToolContent($_html);
        $this->_mainUI->setMainContent($_html_body);

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

?>