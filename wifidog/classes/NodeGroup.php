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
 * @version    Subversion $Id: Node.php 1419 2009-09-18 21:51:29Z gbastien $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/User.php');
require_once('classes/Utils.php');
require_once('classes/DateTimeWD.php');
require_once('classes/HotspotGraphElement.php');

/**
 * Abstract a NodeGroup.  A Node group is a virtual entity that corresond to no physical machine or hotspot
 *  A NodeGroup can have its own content and can be the parent element of other Nodes and/or NodeGroups
 *
 *
 * @package    WiFiDogAuthServer
 * @author     Geneviève Bastien <gbastien@versatic.net>
 * @copyright  2005 Benoit Grégoire, Technologies Coeus inc.
 */
class NodeGroup extends HotspotGraphElement
{
    /** Object cache for the object factory (getObject())*/
    protected $_row;
    protected $mdB; /**< An AbstractDb instance */
    protected $id;
    
    /**
     * Defines a warning message
     *
     * @var string
     *
     * @access private
     */
    protected $_warningMessage;

    /** Instantiate a nodeGroup object
     * @param $id The id of the requested node group
     * @return a NodeGroup object, or null if there was an error
     */
    public static function &getObject($id)
    {
        return HotspotGraphElement::getObject($id, 'NodeGroup');
    }

    /** Free an instanciated object
     * @param $id The id to free
     * Thanks and so long for all the ram.
     */
    public static function freeObject($id)
    {
        HotspotGraphElement::freeObject($id, 'NodeGroup');
    }
    

    /** Instantiate a node group object using its name
     * @param $name The id of the requested node
     * @return a Node Group object, or null if there was an error
     */
    static function getObjectByName($name)
    {
        $object = null;
        $object = new self($name, 'NAME');
        return $object;
    }


    public function delete(& $errmsg)
    {
        $retval = false;
        Security::requirePermission(Permission::P('NODEGROUP_PERM_DELETE_NODEGROUP'), $this);
       
        $db = AbstractDb::getObject();
        $id = $db->escapeString($this->getId());
        if (!$db->execSqlUpdate("DELETE FROM node_groups WHERE node_group_id='{$id}'", false)) {
            $errmsg = _('Could not delete node group!');
        } else {
            $retval = true;
        }
        

        return $retval;
    }

    /**
     * Create a new Node group in the database
     *
     * @param string $ng_name The name of the new node group to create.  If empty a dummy value will be set
     * @param object $network Network object.  The node's network.  If not
     *                        present, the current Network will be assigned
     *
     * @return mixed The newly created Node Group object, or null if there was
     *               an error
     *
     * @static
     * @access public
     */
    public static function createNewObject($ng_name = null, $network = null)
    {
        $db = AbstractDb::getObject();
        if (empty ($ng_name)) {
            $ng_name = $db->escapeString(_('New node group name'));
        }
        else
        {
            $ng_name = $db->escapeString($ng_name);
        }
        $node_group_id = get_guid();
        
        if (empty ($network)) {
            $network = Network::getCurrentNetwork();
        }

        $network_id = $db->escapeString($network->getId());

        $duplicate = null;
        try{
            $duplicate = NodeGroup::getObjectByName($ng_name);
        }
        catch (Exception $e)
        {
        }
        if ($duplicate) {
            throw new Exception(sprintf(_('Sorry, a node group with the name %s already exists.'),$ng_name));
        }

        $sql = "INSERT INTO node_groups (node_group_id, name) VALUES ('$node_group_id', '$ng_name')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert new node group into database!'));
        }
        
        HotspotGraphElement::createNewObject($node_group_id, 'NodeGroup', $network);

        $object = self::getObject($node_group_id);

        return $object;
    }

    /** Get an interface to pick a node group.
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
     *
     * @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     * This method understands:
     *  $userData['preSelectedObject'] An optional object to pre-select.
     *	$userData['additionalWhere'] Additional SQL conditions for the
     *                                    objects to select
     *  $userData['additionalJoin'] Additional SQL JOIN conditions for the
     *                                    objects to select
     *	$userData['preSelectedObjects'] An optional object or array of objects to pre-select. (not
     * supported by type_interface=table)
     *  $userData['typeInterface'] select, select_multiple or table.  Default is "select"
     *
     * * @return html markup
     */
    public static function getSelectUI($user_prefix, $userData=null)
    {
        $userData=$userData===null?array():$userData;
        $html = '';
        $name = $user_prefix;
        //pretty_print_r($userData);
        !empty($userData['additionalWhere'])?$additional_where=$userData['additionalWhere']:$additional_where=null;
        !empty($userData['allowEmpty'])?$allow_empty=$userData['allowEmpty']:$allow_empty=false;
        !empty($userData['nullCaptionString'])?$nullCaptionString=$userData['nullCaptionString']:$nullCaptionString=null;
        !empty($userData['onChange'])?$onChangeString=$userData['onChange']:$onChangeString="";
        !empty($userData['onlyNetwoksAllowingSignup'])?$onlyNetwoksAllowingSignup=$userData['onlyNetwoksAllowingSignup']:$onlyNetwoksAllowingSignup=false;

        $db = AbstractDb::getObject();
        $sql = "SELECT node_group_id, name FROM node_groups WHERE 1=1 $additional_where";
        $ng_rows = array();
        $db->execSql($sql, $ng_rows, false);
        if ($ng_rows == null) {
            $ng_rows = array();
        }

        $number_of_nodegroups = count($ng_rows);

        $i = 0;
        $tab = array();
        foreach ($ng_rows as $ng_row) {
            
            $tab[$i][0] = $ng_row['node_group_id'];
            $tab[$i][1] = $ng_row['name'];
            $i ++;
            
        }
        $html .= _("Node Group:")." \n";
        $html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, $allow_empty, $nullCaptionString, "onchange='$onChangeString'");



        return $html;
    }


    /** Get the selected Node Group object.
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the node object
     */
    static function processSelectUI($user_prefix)
    {
        $object = null;
        $name = "{$user_prefix}";
        return self::getObject($_REQUEST[$name]);
    }

    /** Get an interface to create a new node group.
     * @param $network Optional:  The network to which the new node will belong,
     * if absent, the user will be prompted.
     * @return html markup
     */
    public static function getCreateNewObjectUI($network = null)
    {
        $html = '';
        $html .= _("Add a new node group with name ")." \n";
        $name = "new_node_group_name";
        $html .= "<input type='text' size='40' name='{$name}'>\n";
        if ($network)
        {
            $name = "new_node_group_network_id";
            $html .= "<input type='hidden' name='{$name}' value='{$network->getId()}'>\n";
        }
        else
        {
            $html .= " "._("in ")." \n";
            $html .= Network :: getSelectUI('new_node_group');
        }
        return $html;

    }

    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form was fully filled.
     * @return the node object or null if no new node was created.
     */
    public static function processCreateNewObjectUI()
    {
        // Init values
        $retval = null;
        $name = "new_node_group_name";

        if (!empty ($_REQUEST[$name])) {
            $ng_name = $_REQUEST[$name];
        }
        else
        {
            $ng_name = null;
        }
        $name = "new_node_group_network_id";

        if (!empty ($_REQUEST[$name])) {
            $network = Network::getObject($_REQUEST[$name]);
        } else {
            $network = Network::processSelectUI('new_node_group');
        }

        if ($network) {
            Security::requirePermission(Permission::P('NETWORK_PERM_ADD_NODEGROUP'), $network);
            $retval = self::createNewObject($ng_name, $network);
        }

        return $retval;
    }

    /** @param $id The id of the node group
     * @param $idType 'GROUP_ID' or 'NAME'*/
    protected function __construct($id, $idType='GROUP_ID')
    {
        $db = AbstractDb::getObject();
        $this->mDb = & $db;

        $id_str = $db->escapeString($id);
        switch ($idType) {
            case 'GROUP_ID': $sqlWhere = "node_group_id='$id_str'";
            break;
            case 'NAME': $sqlWhere = "name='$id_str'";
            break;
            default:
                throw new exception('Unknown idType parameter');
        }
        $sqlWhere =
        $sql = "SELECT * FROM node_groups WHERE $sqlWhere";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null)
        {
            throw new Exception(sprintf(_("The node group with %s: %s could not be found in the database!"), $idType, $id_str));
        }

        $this->_row = $row;
        $this->id = $row['node_group_id'];
        
        parent::__construct($this->id, 'NodeGroup');
    }

    function __toString() {
        return $this->getName();
    }

    function getId()
    {
        return $this->id;
    }

    /** Return the name of the node
     */
    function getName()
    {
        return $this->_row['name'];
    }

    function setName($name)
    {
        $name = $this->mDb->escapeString($name);
        $this->mDb->execSqlUpdate("UPDATE node_groups SET name = '{$name}' WHERE node_group_id = '{$this->getId()}'");
        $this->refresh();
    }

    function getCreationDate()
    {
        return $this->_row['group_creation_date'];
    }

    function setCreationDate($creation_date)
    {
        $creation_date = $this->mDb->escapeString($creation_date);
        $this->mDb->execSqlUpdate("UPDATE node_groups SET group_creation_date = '{$creation_date}' WHERE node_group_id = '{$this->getId()}'");
        $this->refresh();
    }

    function getDescription()
    {
        return $this->_row['description'];
    }

    function setDescription($description)
    {
        $description = $this->mDb->escapeString($description);
        $this->mDb->execSqlUpdate("UPDATE node_groups SET description = '{$description}' WHERE node_group_id = '{$this->getId()}'");
        $this->refresh();
    }

    /**
     * Retrieves the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     *
     * @todo Most of this code will be moved to Hotspot class when the
     *       abtraction will be completed
     */
    public function getAdminUI()
    {
        $permArray=null;
        /** @todo this should not be the default network here */
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_ANY_NODEGROUP_CONFIG'), Network::getDefaultNetwork());
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_ANY_NODEGROUP_CONFIG'), $this);
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_NODEGROUP_CONFIG'), $this);
        Security::requireAnyPermission($permArray);
        require_once('classes/InterfaceElements.php');
        require_once('classes/Stakeholder.php');
        // Init values
        $html = '';

        $ng_id = $this->getId();

        /*
         * Check for a warning message
         */
        if ($this->_warningMessage != "") {
            $html .= "<div class='errormsg'>".$this->_warningMessage."</div>\n";
        }

        /*
         * Begin with admin interface
         */
        $html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
        $html .= "<legend>"._("Edit a node group")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";

        /*
         * Information about the node group
         */
        $_html_node_information = array();

      
        // Name
        $permArray = null;
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_ANY_NODEGROUP_CONFIG'), Network::getDefaultNetwork());
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_NODEGROUP_CONFIG'), $this);
        if (Security::hasAnyPermission($permArray)) {
            $_title = _("Name");
            $_data = InterfaceElements::generateInputText("node_group_" . $ng_id . "_name", $this->getName(), "node_group_name_input");
            $_html_node_information[] = InterfaceElements::generateAdminSectionContainer("node_group_name", $_title, $_data);
        }
        else {
            $_title = _("Name");
            $_data = $this->getName();
            $_html_node_information[] = InterfaceElements::generateAdminSectionContainer("node_group_name", $_title, $_data);
        }
        
        // Description
        $_title = _("Description");
        $name = "node_" . $ng_id . "_description";
        $_data = "<textarea name='$name' cols=80 rows=5 id='node_description_textarea'>\n".$this->getDescription()."\n</textarea>\n";
        $_html_node_information[] = InterfaceElements::generateAdminSectionContainer("node_description", $_title, $_data);
        
        // Creation date
        $_title = _("Creation date");
        $_data = DateTimeWD::getSelectDateTimeUI(new DateTimeWD($this->getCreationDate()), "node_group_" . $ng_id . "_creation_date", DateTimeWD::INTERFACE_DATETIME_FIELD, "node_group_creation_date_input");
        $_html_node_information[] = InterfaceElements::generateAdminSectionContainer("node_creation_date", $_title, $_data);

        //Node content
        $html .= parent::getContentAdminUI();
            
        //Node hierarchy
        $html .= parent::getGraphAdminUI();
            
        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("node_group_information", _("Information about the node group"), implode(null, $_html_node_information));

        /*
         * Access rights
         */
        if (User::getCurrentUser()->DEPRECATEDisSuperAdmin()) {
            require_once('classes/Stakeholder.php');
            $html_access_rights = Stakeholder::getAssignStakeholdersUI($this);
            $html .= InterfaceElements::generateAdminSectionContainer("access_rights", _("Access rights"), $html_access_rights);
        }

        $html .= "</ul>\n";
        $html .= "</fieldset>";

        return $html;
    }

    /**
     * Process admin interface of this object.
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        require_once('classes/Stakeholder.php');
        $user = User::getCurrentUser();
        // Get information about the network
        $network = Network::getDefaultNetwork();
        //pretty_print_r($_REQUEST);
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_ANY_NODEGROUP_CONFIG'), Network::getDefaultNetwork());
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_ANY_NODEGROUP_CONFIG'), $this);
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_NODEGROUP_CONFIG'), $this);
        Security::requireAnyPermission($permArray);
        // Check if user is a admin
        $_userIsAdmin = User::getCurrentUser()->DEPRECATEDisSuperAdmin();

        // Information about the node

        $ng_id = $this->getId();

        // Content processing
        parent::processContentAdminUI();

        // Name
        $permArray = null;
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_ANY_NODEGROUP_CONFIG'), Network::getDefaultNetwork());
        $permArray[]=array(Permission::P('NODEGROUP_PERM_EDIT_NODEGROUP_CONFIG'), $this);
        if (Security::hasAnyPermission($permArray)) {
            $name = "node_group_" . $ng_id . "_name";
            $this->setName($_REQUEST[$name]);
        }

        // Creation date
        $name = "node_group_".$ng_id."_creation_date";
        $this->setCreationDate(DateTimeWD::processSelectDateTimeUI($name, DateTimeWD :: INTERFACE_DATETIME_FIELD)->getIso8601FormattedString());
      
        // Description
        $name = "node_".$ng_id."_description";
        $this->setDescription($_REQUEST[$name]);

        parent::processGraphAdminUI($errMsg);
        if(!empty($errMsg)) {
            echo $errMsg;
            $errMsg = null;
        }
      
        // End Node group configuration section

        // Access rights
        Stakeholder::processAssignStakeholdersUI($this, $errMsg);
        if(!empty($errMsg)) {
            echo $errMsg;
        }
    }

    /** Reloads the object from the database.  Should normally be called after a set operation */
    protected function refresh()
    {
        $this->__construct($this->id);
    }
    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
       
        if(Security::getObjectsWithPermission(Permission::P('NETWORK_PERM_EDIT_ANY_NODEGROUP_CONFIG')))
        {
            $items[] = array('path' => 'node/node_group_edit',
            'title' => _("Edit node groups"),
            'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=NodeGroup&action=list")
            );
        }
        else if($nodes = Security::getObjectsWithPermission(Permission::P('NODEGROUP_PERM_EDIT_NODEGROUP_CONFIG'))) {
             
            foreach ($nodes as $nodeId => $node) {
                $items[] = array('path' => 'node/node_'.$nodeId.'edit',
                'title' => sprintf(_("Edit %s"), $node->getName()),
                'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=NodeGroup&action=edit&object_id=$nodeId")
                );
            }
        }
        if(Security::hasPermission(Permission::P('NETWORK_PERM_ADD_NODEGROUP'))){
            $items[] = array('path' => 'node/node_group_add_new',
                'title' => sprintf(_("Add a new node group")),
                'url' => BASE_URL_PATH.htmlspecialchars("admin/generic_object_admin.php?object_class=NodeGroup&action=new_ui")
            );
        }
        $items[] = array('path' => 'node',
        'title' => _('Node administration'),
        'type' => MENU_ITEM_GROUPING);
        
        return $items;
    }
    /**
     * Assigns values about node to be processed by the Smarty engine.
     *
     * @param object $smarty Smarty object
     * @param object $node    Node object, if unset, the current node will be used
     *
     * @return void
     */
    public static function assignSmartyValues($smarty, $node = null)
    {
        if (!$node) {
            $node = self::getCurrentNode();
        }

        // Set node details
        $smarty->assign('nodeId', $node ? $node->getId() : '');
        $smarty->assign('nodeName', $node ? $node->getName() : '');
        $smarty->assign('nodeLastHeartbeatIP', $node ? $node->getLastHeartbeatIP() : '');
        $smarty->assign('nodeNumOnlineUsers', $node ? $node->getNumOnlineUsers() : '');
        $smarty->assign('nodeWebSiteURL', $node ? $node->getWebSiteURL() : '');
        $node = self::getCurrentRealNode();
        // Set node details
        $smarty->assign('realNodeId', $node ? $node->getId() : '');
        $smarty->assign('realNodeName', $node ? $node->getName() : '');
        $smarty->assign('realNodeLastHeartbeatIP', $node ? $node->getLastHeartbeatIP() : '');
    }
    
	/**
     * Get the type of graph element (read-only for now)
     * 
     * @return string
     */
    protected function getType() {
        return 'NodeGroup';
    }
  
    /**
     * Return whether this element is a root or has parent (Network is root)
     * @return boolean
     */
    public function isRoot(){
        return false;
    }
    
		/**
     * Return whether this element is a leaf or has children (Node is leaf)
     * @return boolean
     */
    public function isLeaf() {
        return false;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
