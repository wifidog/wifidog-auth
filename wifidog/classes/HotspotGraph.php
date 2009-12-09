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
        $allparents[] = $object->getId();
        $newchildren = array();
        $newchildren[] = "'{$db->escapeString($object->getId())}'";      
        
        while (!empty($newchildren)) {
            $parentswhere = implode(', ', $newchildren);
            $parents_sql = "SELECT hg.parent_element_id FROM hotspot_graph hg
                where hg.child_element_id in ({$parentswhere})"; 
            
            $parent_rows = null;
            $db->execSql($parents_sql, $parent_rows, false);
            $newchildren = array();
            
            if ($parent_rows) {
                foreach ($parent_rows as $parent_row) {
                    if (!in_array($parent_row['parent_element_id'], $allparents)) {
                        $allparents[] = $parent_row['parent_element_id'];
                        $newchildren[] = "'{$db->escapeString($parent_row['parent_element_id'])}'";
                    }
                }
            }
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
        $object_id = $graph_element->getId();
        
        
        // Get the parents
        if (!$graph_element->isRoot()) {
            
            $_title = _("Parents :");
            $parents_sql = "SELECT hg.parent_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.parent_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.child_element_id = '{$db->escapeString($graph_element->getId())}'"; 
            
            $parent_rows = null;
            $db->execSql($parents_sql, $parent_rows, false);
            $listData = "";
            
            if($parent_rows) {
                foreach ($parent_rows as $parent_row) {
                    $classname = $parent_row['element_type'];
                    $element = HotspotGraphElement::getChildObject($classname, $parent_row['element_id']);
                    $parentStr = htmlspecialchars($parent_row['name']) . " (".htmlspecialchars($parent_row['element_type']) .")  ";
                    $name = $object_id . "_parent_" . $parent_row['parent_element_id'] . "_remove";
                    $listDataContents = InterfaceElements::generateAdminSection("", $parentStr, InterfaceElements::generateInputSubmit($name, _("Remove from")));
                    $listData .= "<li class='admin_element_item_container node_owner_list'>".$listDataContents."</li>\n";
                }
            }
            
            $listData .= "<li class='admin_element_item_container'>";
            $listData .= HotspotGraphElement::getSelectGraphElementUI($object_id . "_parent_add_element", array('additionalWhere' => " AND element_type in ('Network', 'NodeGroup') AND hotspot_graph_element_id != '{$db->escapeString($graph_element->getId())}'"));
            $listData .= InterfaceElements::generateInputSubmit($object_id . "_parent_add", _("Add"));
            $listData .= "<br class='clearbr' /></li>\n";

            $_data = "<ul id='node_owner_ul' class='admin_element_list'>\n".$listData."</ul>\n";
            
            $html .= InterfaceElements::generateAdminSectionContainer("element_parent", $_title, $_data);
            
        }
        
        
        // Get the children
        if (!$graph_element->isLeaf()) {
            
            $_title = _("Children :");
            $children_sql = "SELECT hg.child_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.child_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.parent_element_id = '{$db->escapeString($graph_element->getId())}'"; 
            
            $children_rows = null;
            $db->execSql($children_sql, $children_rows, false);
            $listData = "";
            
            if($children_rows) {
                foreach ($children_rows as $child_row) {
                    $classname = $child_row['element_type'];
                    $element = HotspotGraphElement::getChildObject($classname, $child_row['element_id']);
                    $childStr = htmlspecialchars($child_row['name']) . " (".htmlspecialchars($child_row['element_type']) .")  ";;
                    $name = $object_id . "_child_" . $child_row['child_element_id'] . "_remove";
                    $listDataContents = InterfaceElements::generateAdminSection("", $childStr, InterfaceElements::generateInputSubmit($name, _("Remove from")));
                    $listData .= "<li class='admin_element_item_container node_owner_list'>".$listDataContents."</li>\n";
                }
            }
            
            $listData .= "<li class='admin_element_item_container'>";
            $listData .= HotspotGraphElement::getSelectGraphElementUI($object_id . "_child_add_element", array('additionalWhere' => " AND element_type in ('Node', 'NodeGroup') AND hotspot_graph_element_id != '{$db->escapeString($graph_element->getId())}'"));
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
        $object_id = $db->escapeString($graph_element->getId());
        $allparents = array();
        
        // Process the parents
        // Process any remove element command
        $parents_sql = "SELECT hg.parent_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.parent_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.child_element_id = '{$object_id}'"; 
            
        $parent_rows = null;
        $db->execSql($parents_sql, $parent_rows, false);
        $allparents = array();
        
        if($parent_rows) {
            foreach ($parent_rows as $parent_row) {
                $allparents[$parent_row['parent_element_id']] = $parent_row['name'];
                $name = $object_id . "_parent_" . $parent_row['parent_element_id'] . "_remove";
                if(!empty($_REQUEST[$name])) {
                    $parentIdStr = $db->escapeString($parent_row['parent_element_id']);
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
                if(isset($allparents[$element->getId()])) {
                    $errMsg .= sprintf(_("Element %s is already a parent of this object"), $allparents[$element->getId()]);
                }
                else {// the user doesn't already have that role
                    $sql = "INSERT INTO hotspot_graph (parent_element_id, child_element_id) VALUES ('{$element->getId()}', '{$object_id}');";
                    $db->execSqlUpdate($sql, false);
    
                }
            }
        }
        
        // Process the children
        // Process any remove element command
        $children_sql = "SELECT hg.child_element_id, hge.element_id, hge.element_type, coalesce(n.name, ng.name, no.name) as name FROM hotspot_graph hg
                inner join hotspot_graph_elements hge on hg.child_element_id = hge.hotspot_graph_element_id
                left join networks n on hge.element_id = n.network_id AND hge.element_type='Network' 
                left join node_groups ng on hge.element_id = ng.node_group_id AND hge.element_type = 'NodeGroup'
                left join nodes no on hge.element_id = no.node_id AND hge.element_type = 'Node'
                where hg.parent_element_id = '{$object_id}'"; 
            
        $children_rows = null;
        $db->execSql($children_sql, $children_rows, false);
        $allchildren = array();
        
        if($children_rows) {
            foreach ($children_rows as $child_row) {
                $allchildren[$child_row['child_element_id']] = $child_row['name'];
                $name = $object_id . "_child_" . $child_row['child_element_id'] . "_remove";
                if(!empty($_REQUEST[$name])) {
                    $childIdStr = $db->escapeString($child_row['child_element_id']);
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
                if(isset($allchildren[$element->getId()])) {
                    $errMsg .= sprintf(_("Element '%s' is already a child of this object"), $allchildren[$element->getId()]);
                }
                elseif (isset($allparents[$element->getId()])) {
                    $errMsg .= sprintf(_("Element '%s' is a parent of this object and therefore cannot be a child as well"), $allparents[$element->getId()]);
                }
                else {// the user doesn't already have that role
                    $sql = "INSERT INTO hotspot_graph (child_element_id, parent_element_id) VALUES ('{$element->getId()}', '{$object_id}');";
                    $db->execSqlUpdate($sql, false);
    
                }
            }
        }
        return null;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */