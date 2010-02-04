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
 * @author     Geneviève Bastien <gbastien@versatic.net>
 * @version    Subversion $Id: Network.php 1428 2009-10-30 18:21:05Z gbastien $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/GenericDataObject.php');
require_once('classes/Content.php');
require_once('classes/User.php');
require_once('classes/HotspotGraph.php');

/**
 * Abstract a hotspot graph element
 *
 * The hotspot graph is a graph of different elements (Networks, Nodes and NodeGroup) used to logically group
 *   nodes and hotspots so that content can be shared for a group of hotspots
 *
 * @package    WiFiDogAuthServer
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
abstract class HotspotGraphElement extends GenericDataObject
{
    /** Object cache for the object factory (getObject())*/
    protected static $instanceArray = array('Network' => array(), 'Node' => array(), 'NodeGroup' => array());
    protected $_hgeid;
    protected $_hgerow;

    /**
     * Get an instance of the object
     *
     * 
     * @param string $id The object id
     * @param string $type The type of graph element: Node, Network or NodeGroup
     *
     * @return mixed The Content object, or null if there was an error
     *               (an exception is also thrown)
     *
     * @see GenericObject
     * @static
     * @access public
     */
    public static function &getObject($id, $type = 'Node')
    {
        if (!(class_exists($type) && (get_parent_class($type) == 'HotspotGraphElement')))
            throw new Exception(_("HotspotGraphElement::getObject, parameter type has a wrong value.  Should be the name of a child class."));
        if(!isset(self::$instanceArray[$type][$id]))
        {
            self::$instanceArray[$type][$id] = new $type($id);
        }
        return self::$instanceArray[$type][$id];
    }

    /** Free an instanciated object
     * @param $id The id to free
     * Thanks and so long for all the ram.
     */
    public static function freeObject($id, $type)
    {
        if (!(class_exists($type) && (get_parent_class($type) == 'HotspotGraphElement')))
            throw new Exception(_("HotspotGraphElement::freeObject, parameter type has a wrong value.  Should be the name of a child class."));
        if(isset(self::$instanceArray[$type][$id]))
        {
            unset(self::$instanceArray[$type][$id]);
        }
    }

    /**
     * Create a new GraphElement object in the database
     *
     * @param string $element_id The element id of the new element.  Must be specified
     * @param string $element_type The element type of this element.  Must be specified
     *
     * @return mixed The newly created object, or null if there was an error
     *
     * @see GenericObject
     * @static
     * @access public
     */
    public static function createNewObject($element_id, $element_type, $parent_element = null)
    {
        $db = AbstractDb::getObject();
        $graph_element_id = get_guid();
        
         if (!(class_exists($element_type) && (get_parent_class($element_type) == 'HotspotGraphElement')))
            throw new Exception(_('Cannot add element to hotspot graph. Wrong type specified: ').$element_type);
       
        $sql = "INSERT INTO hotspot_graph_elements (hotspot_graph_element_id, element_id, element_type) VALUES ('$graph_element_id', '$element_id', '$element_type')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new element in the database!'));
        }
        $object = self::getObject($element_id, $element_type);
        
        if (!is_null($parent_element)) {
            if (method_exists($parent_element, 'getHgeId')) {
                $parentid = $parent_element->getHgeId();
                $childid = $object->getHgeId();
                HotspotGraph::addRelation($parentid, $childid);
            }
        }
        
        return $object;
    }

    /**
     * Get an interface to pick an object of this class
     *
     * If there is only one server available, no interface is actually shown
     *
     * @param string $user_prefix         A identifier provided by the
     *                                    programmer to recognise it's generated
     *                                    html form
     *  @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     *
     * This method understands:
     *  $userData['preSelectedObject'] An optional Object of this class to be selected.
     *	$userData['additionalWhere'] Additional SQL conditions for the
     *                                    objects to select
     *  $userData['allowEmpty'] boolean Allow not selecting any object
     * @return string HTML markup

     */
    /**
     * Get an interface to pick a graph element
     *
     *
     * @param string $user_prefix          A identifier provided by the
     *                                     programmer to recognise it's
     *                                     generated html form
     *
     * @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     *
     * This method understands:
     *  $userData['preSelectedObject'] An optional object to pre-select.
     *	$userData['additionalWhere'] Additional SQL conditions for the
     *                                    objects to select
     *	$userData['allowEmpty'] boolean Allow not selecting any object
     *  $userData['onlyNetwoksAllowingSignup'] boolean Only list networks allowing user self-signup
     * @return string HTML markup

     */
    public static function getSelectUI($user_prefix, $userData=null)
    {
    /*    $userData=$userData===null?array():$userData;
        $html = '';
        $name = $user_prefix;
        //pretty_print_r($userData);
        array_key_exists('preSelectedObject',$userData)?(empty($userData['preSelectedObject'])?$selected_id=null:$selected_id=$userData['preSelectedObject']->getId()):$selected_id=self::getDefaultNetwork()->getId();
        !empty($userData['additionalWhere'])?$additional_where=$userData['additionalWhere']:$additional_where=null;
        !empty($userData['allowEmpty'])?$allow_empty=$userData['allowEmpty']:$allow_empty=false;
        !empty($userData['nullCaptionString'])?$nullCaptionString=$userData['nullCaptionString']:$nullCaptionString=null;
        !empty($userData['onChange'])?$onChangeString=$userData['onChange']:$onChangeString="";
        !empty($userData['onlyNetwoksAllowingSignup'])?$onlyNetwoksAllowingSignup=$userData['onlyNetwoksAllowingSignup']:$onlyNetwoksAllowingSignup=false;

        $db = AbstractDb::getObject();
        $sql = "SELECT network_id, name FROM networks WHERE 1=1 $additional_where";
        $network_rows = null;
        $db->execSql($sql, $network_rows, false);
        if ($network_rows == null) {
            throw new Exception(_("Network::getAllNetworks:  Fatal error: No networks in the database!"));
        }

        $number_of_networks = count($network_rows);
        if ($number_of_networks > 1) {
            $i = 0;
            foreach ($network_rows as $network_row) {
                
                if($onlyNetwoksAllowingSignup==false || (self::getObject($network_row['network_id'])->getAuthenticator()->isRegistrationPermitted())==true){
                    $tab[$i][0] = $network_row['network_id'];
                    $tab[$i][1] = $network_row['name'];
                    $i ++;
                }
            }
            $html .= _("Network:")." \n";
            $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, $allow_empty, $nullCaptionString, "onchange='$onChangeString'");

        } else {
            foreach ($network_rows as $network_row) //iterates only once...
            {
                $html .= _("Network:")." \n";
                $html .= " $network_row[name] ";
                $html .= "<input type='hidden' name='$name' value='".htmlspecialchars($network_row['network_id'], ENT_QUOTES, 'UTF-8')."'>";
            }
        }

        return $html;*/
    }

    /**
     * Get the selected Graph element object.
     *
     * @param string $user_prefix A identifier provided by the programmer to
     *                            recognise it's generated form
     *
     * @return mixed The network object or null

     */
    public static function processSelectUI($user_prefix)
    {
      /*  $name = "{$user_prefix}";
        if (!empty ($_REQUEST[$name])) {
            return self::getObject($_REQUEST[$name]);
        } else {
            return null;
        }*/
    }

    /**
     * Get an interface to create a new graph element.
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     * @todo 	For now, there is no need for interface as the children of this class already have their own interfaces
     */
    public static function getCreateNewObjectUI()
    {
        // Init values
        $html = '';

        /*$html .= _("Create a new network with ID")." \n";
        $name = "new_network_id";
        $html .= "<input type='text' size='10' name='{$name}'>\n";*/
        return $html;
    }

    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return mixed The Graph element object or null if no new element was created.
     *
     * @static
     * @access public
     */
    public static function processCreateNewObjectUI()
    {
        // Init values
      /*  $retval = null;
        $name = "new_network_id";

        if (!empty($_REQUEST[$name])) {
            $network_id = $_REQUEST[$name];

            if (!preg_match('/^[0-9a-zA-Z_-]+$/', $network_id)) {
            throw new Exception(_("The Network ID entered was not valid. It must only contain Alphanumerical Characters, Hyphens and Underscores e.g. My_Network-6"));
            return;
            }

            if ($network_id) {
                Security::requirePermission(Permission::P('SERVER_PERM_ADD_NEW_NETWORK'), Server::getServer());
                $retval = self::createNewObject($network_id);
            }
        }

        return $retval;*/
    }

    /**
     * Constructor
     *
     * @param string $type the type of element (Node, Network or NodeGroup)
     * @param string $id the id of the element
     *
     * @return void
     *
     * @access private
     */
    protected function __construct($id, $type)
    {
        $db = AbstractDb::getObject();
        $element_id_str = $db->escapeString($id);

        $sql = "SELECT * FROM hotspot_graph_elements WHERE element_id='$element_id_str' AND element_type='$type'";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            throw new Exception("The element of type $type with id $element_id_str could not be found in the database");
        }
        $this->_hgerow = $row;
        $this->_hgeid = $row['hotspot_graph_element_id'];
    }

    public function getHgeId() {
        return $this->_hgeid;
    }
    
    public abstract function __toString();

    /**
     * Get the type of graph element (read-only for now)
     * 
     * @return string
     */
    protected abstract function getType();
  
    /**
     * Return whether this element is a root or has parent (Network is root)
     * @return boolean
     */
    public abstract function isRoot() ;
    
		/**
     * Return whether this element is a leaf or has children (Node is leaf)
     * @return boolean
     */
    public abstract function isLeaf();

    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getContentAdminUI()
    {
        $html = '';
        /** Until phase 2 of node group and hotspot graph, when this is called, it means the permissions have already been verified on the child element */
        require_once('classes/InterfaceElements.php');
        require_once('classes/Stakeholder.php');
        
        $hge_id = $this->getHgeId();

        $_html_content = array();
        $_title = _("Node content");
        $_data = Content::getLinkedContentUI("hge_" . $hge_id . "_content", "hotspot_graph_element_has_content", "hotspot_graph_element_id", $hge_id, "portal");
        $html .= InterfaceElements::generateAdminSectionContainer("node_content", $_title, $_data);
        
        
        return $html;
    }

    /**
     * Process admin interface of this object.
     *
     * @return void
     *
     * @access public
     */
    public function processContentAdminUI()
    {
        $hge_id = $this->getHgeId();
        
        $name = "hge_{$hge_id}_content";
        Content::processLinkedContentUI($name, 'hotspot_graph_element_has_content', 'hotspot_graph_element_id', $hge_id);
        
    }
    
    /**
     * Retreives the admin interface of this object
     * 
     * @param Network $network the network of this element
     *
     * @return string The HTML fragment for this interface
     */
    public function getGraphAdminUI($network = null)
    {
        $html = '';
        /** Until phase 2 of node group and hotspot graph, when this is called, it means the permissions have already been verified on the child element */
        require_once('classes/InterfaceElements.php');
        require_once('classes/Stakeholder.php');
        
        // Group section
        if (is_null($network) || Security::hasPermission(Permission::P('NETWORK_PERM_ALLOW_GROUP_NODE'), $network)) {
            $hge_id = $this->getHgeId();
        
            $_html_content = array();
            $_title = _("Hierarchy");
            $_data = HotspotGraph::getGraphAdminUI("hge_" . $hge_id . "_graph", $this);
            $html .= InterfaceElements::generateAdminSectionContainer("hge_graph", $_title, $_data);
        }
  
        return $html;
    }

    /**
     * Process admin interface of this object.
     *
     * @return void
     *
     * @access public
     */
    public function processGraphAdminUI(&$errMsg, $network = null)
    {
        $hge_id = $this->getHgeId();
        
        if (is_null($network) || Security::hasPermission(Permission::P('NETWORK_PERM_ALLOW_GROUP_NODE'), $network)) {
            $name = "hge_{$hge_id}_graph";
            HotspotGraph::processGraphAdminUI($this, $errMsg);
        }
        
    }
    
    /**
     * Get an interface to select a graph element
     *
     * @param string $user_prefix      A identifier provided by the programmer
     *                                 to recognise it's generated HTML form
     * @param string $user_data
     * This method understands:
     *  $userData['preSelectedObject'] An optional object to pre-select.
     *	$userData['additionalWhere'] Additional SQL conditions for the
     *                                    objects to select
     *	$userData['allowEmpty'] boolean Allow not selecting any object
     *
     * @return string HTML markup

     */
    public static function getSelectGraphElementUI($user_prefix, $userData = null) {
        $userData=$userData===null?array():$userData;
        $html = '';
        $name = $user_prefix;
        //pretty_print_r($userData);
        array_key_exists('preSelectedObject',$userData)?(empty($userData['preSelectedObject'])?$selected_id=null:$selected_id=$userData['preSelectedObject']->getId()):$selected_id = 0;
        !empty($userData['additionalWhere'])?$additional_where=$userData['additionalWhere']:$additional_where=null;
        !empty($userData['allowEmpty'])?$allow_empty=$userData['allowEmpty']:$allow_empty=false;
        !empty($userData['nullCaptionString'])?$nullCaptionString=$userData['nullCaptionString']:$nullCaptionString=null;
        !empty($userData['onChange'])?$onChangeString=$userData['onChange']:$onChangeString="";

        $db = AbstractDb::getObject();
        $sql = "SELECT hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph_elements hge
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                WHERE 1=1 $additional_where";
        $element_rows = null;
        $db->execSql($sql, $element_rows, false);

        $number_of_networks = count($element_rows);
        if ($number_of_networks > 1) {
            $i = 0;
            foreach ($element_rows as $element_row) {   
                $tab[$i][0] = "{$element_row['element_id']},{$element_row['element_type']}";
                $tab[$i][1] = $element_row['name'] . " ({$element_row['element_type']})";
                $i ++;
               
            }
            $html .= _("Element:")." \n";
            $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, $allow_empty, $nullCaptionString, "onchange='$onChangeString'");

        } else {
            foreach ($element_rows as $element_row) //iterates only once...
            {
                $html .= _("Element:")." \n";
                $html .= " {$element_row['name']} ({$element_row['element_type']})"; 
                $html .= "<input type='hidden' name='$name' value='".htmlspecialchars($element_row['element_id'], ENT_QUOTES, 'UTF-8')."'>";
            }
        }

        return $html;
    }

    /** Get the selected graph element, IF one was selected and is valid
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the HotspotGraphElement object, or null if the user is invalid or none was selected
     */
    static function processSelectGraphElementUI($user_prefix, &$errMsg) {
        
        $name = "{$user_prefix}";
        if (!empty ($_REQUEST[$name])) {
            $elid = explode(',', $_REQUEST[$name]);
            return self::getObject($elid[0], $elid[1]);
        } else {
            return null;
        }
    }

    /**
     * Add network-wide content to this network
     *
     * @param object Content object
     *
     * @return void
     *
     * @access public
     */
    public function addContent(Content $content)
    {
        $db = AbstractDb::getObject();

        $content_id = $db->escapeString($content->getHgeId());
        $sql = "INSERT INTO hotspot_graph_element_has_content (hotspot_graph_element_id, content_id) VALUES ('$this->_hgeid','$content_id')";
        $db->execSqlUpdate($sql, false);
    }

    /**
     * Remove network-wide content from this network
     *
     * @param object Content object
     *
     * @return void
     *
     * @access public
     */
    public function removeContent(Content $content)
    {
        $db = AbstractDb::getObject();

        $content_id = $db->escapeString($content->getHgeId());
        $sql = "DELETE FROM hotspot_graph_element_has_content WHERE hotspot_graph_element_id='$this->_hgeid' AND content_id='$content_id'";
        $db->execSqlUpdate($sql, false);
    }
    
    
    /** Delete this Object form it's storage mechanism
     * @param &$errmsg Appends an explanation of the error on failure
     * @return true on success, false on failure or access denied */
    public function delete(& $errmsg) {
        $errmsg .= sprintf(_("Delete not supported on class %s"),get_class($this));
        return false;
    }

    /**
     * Function called by the children of this class to delete the parent element form the graph
     * @param unknown_type $errmsg
     * @return unknown_type
     */
    protected function _delete(& $errmsg)
    {
        // Init values
        $retval = false;
        
        $db = AbstractDb::getObject();
        $id = $db->escapeString($this->getHgeId());
        if (!$db->execSqlUpdate("DELETE FROM hotspot_graph_elements WHERE hotspot_graph_element_id='{$id}'", false)) {
            $errmsg = _('Could not delete graph element!');
        } else {
            $retval = true;
        }

        return $retval;
    }
    /**
     * Reloads the object from the database.
     *
     * Should normally be called after a set operation
     *
     * @return void
     *
     * @access protected
     */
    protected function refresh()
    {
        //$this->__construct($this->_id);
    }
    


    /** Menu hook function */
    static public function hookMenu() {
     /*   $items = array();
        if($networks = Security::getObjectsWithPermission(Permission::P('NETWORK_PERM_EDIT_NETWORK_CONFIG'))) {
            foreach ($networks as $networkId => $network) {
                $items[] = array('path' => 'network/network_'.$networkId.'edit',
                'title' => sprintf(_("Edit %s"), $network->getName()),
                'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=Network&action=edit&object_id=$networkId")
                );
            }
        }
        if(Security::hasPermission(Permission::P('SERVER_PERM_ADD_NEW_NETWORK'), Server::getServer())){
            $items[] = array('path' => 'network/network_add_new',
                'title' => sprintf(_("Add a new network on this server")),
                'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=Network&action=new_ui")
            );
        }
        $items[] = array('path' => 'network',
                'title' => _('Network administration'),
                'type' => MENU_ITEM_GROUPING);
        return $items;*/
    }

    /**
     * Assigns values about network to be processed by the Smarty engine.
     *
     * @param object $smarty Smarty object
     * @param object $net    Network object
     *
     * @return void
     */
    public static function assignSmartyValues($smarty, $net = null)
    {
     /*   if (!$net) {
            $net = Network::getCurrentNetwork();
        }

        // Set network details
        $smarty->assign('networkName', $net ? $net->getName() : '');
        $smarty->assign('networkWebSiteURL', $net ? $net->getWebSiteURL() : '');
        // Set networks usage information
        $smarty->assign('networkNumOnlineUsers', $net ? $net->getNumOnlineUsers() : 0);

        // Set networks node information
        $smarty->assign('networkNumDeployedNodes', $net ? $net->getNumDeployedNodes() : 0);
        $smarty->assign('networkNumOnlineNodes', $net ? $net->getNumOnlineNodes() : 0);
        $smarty->assign('networkNumNonMonitoredNodes', $net ? $net->getNumOnlineNodes(true) : 0);*/
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
