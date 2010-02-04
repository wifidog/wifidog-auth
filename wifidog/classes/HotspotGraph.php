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
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */


/**
 * This class represent the hotspot graph with root being the network and leaf the nodes and having NodeGroups in between
 * It allows to group node various way and display contents for groups of node.
 *
 * @package    WiFiDogAuthServer
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
class HotspotGraph
{

    /**
     * Adds a relation to the hotspot graph
     * @param parentId the id of the parent
     * @param childId the id of the child
     */
    public static function addRelation($parentId, $childId) {
        $db = AbstractDb :: getObject();
        $parentIdStr = $db->escapeString($parentId);
        $childIdStr = $db->escapeString($childId);
        $sql = "INSERT INTO hotspot_graph (child_element_id, parent_element_id) VALUES ('{$childIdStr}', '{$parentIdStr}');";
        $db->execSqlUpdate($sql, false);
    }
    
    /**
     * Gets recursively all parents of the given object
     * 
     * @param HotspotGraphElement $object the object for which to get the parents
     */
    public static function getAllParents($object) {
        if (!$object instanceof HotspotGraphElement) {
            throw new Exception(_("HotspotGraph::getAllParents: argument of the wrong type. HotspotGraphElement expected."));
        }
        $db = AbstractDb :: getObject();
        $allparents = array();
        $hgeid = $object->getHgeId();
        $allparents[] = $hgeid;
        $newchildren = array();
        $newchildren[] = "{$db->escapeString($hgeid)}";      
        
        while (!empty($newchildren)) {     
            $notvisited = array();
            foreach ($newchildren as $child) {
                $next_level = HotspotGraph::getParents($child);
                if ($next_level) {
                    foreach ($next_level as $next_row) {
                        if (!in_array($next_row['next_element_id'], $allparents)) {
                            $allparents[] = $next_row['next_element_id'];
                            $notvisited[] =  $next_row['next_element_id'];
                        }
                    }
                }
            }
            $newchildren = $notvisited;
        }
        
        return $allparents;
    }
    
  /**
     * Get a flexible interface to view the children and parents of a given element
     * One can add parents and children to the hierarchy 
     *
     * @param string $user_prefix            A identifier provided by the
     *                                       programmer to recognise it's
     *                                       generated HTML form
     * @param string $graph_element          Hotspot_Graph_Element
     * @return string HTML markup

     */
    public static function getGraphAdminUI($user_prefix, $graph_element) {

        $db = AbstractDb :: getObject();

        // Init values
        $html = "";
        $object_id = $db->escapeString($graph_element->getHgeId());
        
        
        // Get the parents
        if (!$graph_element->isRoot()) {
            
            $_title = _("Parents :");
            $parent_rows = HotspotGraph::getParents($object_id);
            $listData = "";
            
            if($parent_rows) {
                foreach ($parent_rows as $parent_row) {
                    $classname = $parent_row['element_type'];
                    $element = HotspotGraphElement::getObject($parent_row['element_id'], $classname);
                    $parentStr = htmlspecialchars($parent_row['name']) . " (".htmlspecialchars($parent_row['element_type']) .")  ";
                    $name = $object_id . "_parent_" . $parent_row['next_element_id'] . "_remove";
                    $listDataContents = InterfaceElements::generateAdminSection("", $parentStr, InterfaceElements::generateInputSubmit($name, _("Remove from")));
                    $listData .= "<li class='admin_element_item_container node_owner_list'>".$listDataContents."</li>\n";
                }
            }
            
            $listData .= "<li class='admin_element_item_container'>";
            $listData .= HotspotGraphElement::getSelectGraphElementUI($object_id . "_parent_add_element", array('additionalWhere' => " AND element_type in ('Network', 'NodeGroup') AND hotspot_graph_element_id != '{$db->escapeString($graph_element->getHgeId())}'"));
            $listData .= InterfaceElements::generateInputSubmit($object_id . "_parent_add", _("Add"));
            $listData .= "<br class='clearbr' /></li>\n";

            $_data = "<ul id='node_owner_ul' class='admin_element_list'>\n".$listData."</ul>\n";
            
            $html .= InterfaceElements::generateAdminSectionContainer("element_parent", $_title, $_data);
            
        }
        
        
        // Get the children
        if (!$graph_element->isLeaf()) {
            
            $_title = _("Children :");
            $children_rows = HotspotGraph::getChildren($object_id);
            $listData = "";
            
            if($children_rows) {
                foreach ($children_rows as $child_row) {
                    $classname = $child_row['element_type'];
                    $element = HotspotGraphElement::getObject($child_row['element_id'], $classname);
                    $childStr = htmlspecialchars($child_row['name']) . " (".htmlspecialchars($child_row['element_type']) .")  ";;
                    $name = $object_id . "_child_" . $child_row['next_element_id'] . "_remove";
                    $listDataContents = InterfaceElements::generateAdminSection("", $childStr, InterfaceElements::generateInputSubmit($name, _("Remove from")));
                    $listData .= "<li class='admin_element_item_container node_owner_list'>".$listDataContents."</li>\n";
                }
            }
            
            $listData .= "<li class='admin_element_item_container'>";
            $listData .= HotspotGraphElement::getSelectGraphElementUI($object_id . "_child_add_element", array('additionalWhere' => " AND element_type in ('Node', 'NodeGroup') AND hotspot_graph_element_id != '{$db->escapeString($graph_element->getHgeId())}'"));
            $listData .= InterfaceElements::generateInputSubmit($object_id . "_child_add", _("Add"));
            $listData .= "<br class='clearbr' /></li>\n";

            $_data = "<ul id='node_owner_ul' class='admin_element_list'>\n".$listData."</ul>\n";
            
            $html .= InterfaceElements::generateAdminSectionContainer("element_children", $_title, $_data);
            
        }

     
        return $html;
    }

 		/**
     * Process the interface to assign new hierarchy links
     *
     * @return null
     *
     * @param $graph_element The graph element object to which to add parent or children
     */
    static public function processGraphAdminUI($graph_element, &$errMsg)
    {
        $db = AbstractDb::getObject();
        $object_id = $db->escapeString($graph_element->getHgeId());
        $allparents = array();
        
        // Process the parents
        // Process any remove element command
        $parent_rows = HotspotGraph::getParents($object_id);
        $allparents = array();
        
        if($parent_rows) {
            foreach ($parent_rows as $parent_row) {
                $allparents[$parent_row['next_element_id']] = $parent_row['name'];
                $name = $object_id . "_parent_" . $parent_row['next_element_id'] . "_remove";
                if(!empty($_REQUEST[$name])) {
                    $parentIdStr = $db->escapeString($parent_row['next_element_id']);
                    $sql = "DELETE FROM hotspot_graph WHERE parent_element_id='$parentIdStr' AND child_element_id='$object_id';";
                    $db->execSqlUpdate($sql, false);
                }
            }
        }
            
        // Did we add an element?
        $name = $object_id . "_parent_add";
        if (!empty($_REQUEST[$name])) {
            $element = HotspotGraphElement::processSelectGraphElementUI($object_id . "_parent_add_element", $errMsg);
            if ($element) {
                //The user and role exist
                if(isset($allparents[$element->getHgeId()])) {
                    $errMsg .= sprintf(_("Element %s is already a parent of this object"), $allparents[$element->getHgeId()]);
                } elseif (HotspotGraph::detectCycle($graph_element, $element->getHgeId(), false)) {
                    $errMsg .= sprintf(_("Cycle detected while adding element '%s' as a parent of this object: this element is already among the children of this object."), $element->__toString());
                }
                else {// the user doesn't already have that role
                    $sql = "INSERT INTO hotspot_graph (parent_element_id, child_element_id) VALUES ('{$element->getHgeId()}', '{$object_id}');";
                    $db->execSqlUpdate($sql, false);
    
                }
            }
        }
        
        // Process the children
        // Process any remove element command
        $children_rows = HotspotGraph::getChildren($object_id);
        
        $allchildren = array();
        
        if($children_rows) {
            foreach ($children_rows as $child_row) {
                $allchildren[$child_row['next_element_id']] = $child_row['name'];
                $name = $object_id . "_child_" . $child_row['next_element_id'] . "_remove";
                if(!empty($_REQUEST[$name])) {
                    $childIdStr = $db->escapeString($child_row['next_element_id']);
                    $sql = "DELETE FROM hotspot_graph WHERE parent_element_id='$object_id' AND child_element_id='$childIdStr';";
                    $db->execSqlUpdate($sql, false);
                }
            }
        }
            
        // Did we add an element?
        $name = $object_id . "_child_add";
        if (!empty($_REQUEST[$name])) {
            $element = HotspotGraphElement::processSelectGraphElementUI($object_id . "_child_add_element", $errMsg);
            if ($element) {
                //The user and role exist
                if(isset($allchildren[$element->getHgeId()])) {
                    $errMsg .= sprintf(_("Element '%s' is already a child of this object"), $allchildren[$element->getHgeId()]);
                }
                elseif (HotspotGraph::detectCycle($graph_element, $element->getHgeId(), true)) {
                    $errMsg .= sprintf(_("Cycle detected while adding element '%s' as a child of this object: this element is already among the parents of this object."), $element->__toString());
                }
                else {// the user doesn't already have that role
                    $sql = "INSERT INTO hotspot_graph (child_element_id, parent_element_id) VALUES ('{$element->getHgeId()}', '{$object_id}');";
                    $db->execSqlUpdate($sql, false);
    
                }
            }
        }
        return null;
    }
    
    /**
     * Returns an array of rows containing information on the children of this node
     * @param string $element the db-escaped hotspot_graph_element_id
     * @return array
     */
    public static function getChildren($object_id) {
        $db = AbstractDb::getObject();
        
        $children_sql = "SELECT hg.child_element_id as next_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.child_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.parent_element_id = '{$object_id}'"; 
            
        $children_rows = null;
        $db->execSql($children_sql, $children_rows, false);
        return $children_rows;
    }    
    
    /**
     * Returns an array of rows containing information on the parents of this node
     * @param string $element the db-escaped hotspot_graph_element_id
     * @return array
     */
    public static function getParents($object_id) {
        $db = AbstractDb::getObject();
        
        $parents_sql = "SELECT hg.parent_element_id as next_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.parent_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.child_element_id = '{$object_id}'"; 
            
        $parent_rows = null;
        $db->execSql($parents_sql, $parent_rows, false);
        return $parent_rows;
        
    }
    
    /**
     * Detect if the addition of element with id $object_id as either parent or child would cause the graph to have a cycle
     * @param HotspotGraphElement $start_element The element to start cycle detection with
     * @param string $object_id The object_id of the element to add to the graph from $start_elelemtn
     * @param boolean $child if the element to add to the graph is a child of the start_element or a parent
     * @return boolean (true if cycle detected)
     */
    protected static function detectCycle($start_element, $object_id, $child = true) {
        // If the element is a child, we check for its presence in the parents and vice versa
        if ($child) {
            $function = 'getParents';
        } else {
            $function = 'getChildren';
        }
        $db = AbstractDb::getObject();
        $start_object_id = $db->escapeString($start_element->getHgeId());
        return HotspotGraph::isCycle($start_object_id, $function, $object_id);
    }
    
    /**
     * 
     * @param string $start_object_id The id of the object to start the search from
     * @param string $function the function to get the next level of elements
     * @param string $object_id the object id to searhc for
     * @param array $visited the array of visited element
     * @return boolean true if cycle
     */
    protected static function isCycle($start_object_id, $function, $object_id, &$visited = array()) {
       
        if ($start_object_id == $object_id)
            return true;
        $visited[] = $start_object_id;
        $cycle = false;
        $next_level = HotspotGraph::$function($start_object_id);
        if ($next_level) {
            foreach ($next_level as $next_row) {
                if (!in_array($next_row['next_element_id'], $visited))
                    $cycle = $cycle || HotspotGraph::isCycle($next_row['next_element_id'], $function, $object_id, $visited);
                if ($cycle)
                    break;
            }
        }
        return $cycle;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */