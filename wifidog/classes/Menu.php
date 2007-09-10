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

/** This file contains the code for the MainUI class, as well as GUI exception handling.
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: MainUI.php 1246 2007-07-03 16:35:09Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */

require_once('classes/SmartyWifidog.php');

define('MENU_ITEM_GROUPING','MENU_ITEM_GROUPING');
/**
 * Singleton class for managing menu.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class Menu {
    /** holder for the singleton */
    private static $object;

    /**
     * Object for Smarty class
     */
    private $smarty;
    /** The array from which the menu is generated */
    private $menuArray;
    /**
     * Get the MainUI object
     * @return object The MainUI object
     */
    public static function &getObject() {
        if (self :: $object == null) {
            self :: $object = new self();
        }
        return self :: $object;
    }
    /**
     * Contructor
     *
     * @return void
     *
     * @access public
     */
    private function __construct() {
        //$db = AbstractDb :: getObject();
        // Init Smarty
        $this->smarty = SmartyWifidog :: getObject();

        // Init the menu array
        $this->_menuArray = array('childrens'=>array());
    }

    /** Builds an HTML menu, appends it to $userData*/
    static private function buildHtmlMenuItemCallback($menuItemArray, $menuItemIndex, &$userData) {
        //echo "buildHtmlMenuItemCallback for: ";pretty_print_r($menuItemArray);
        $html = '';
        //echo "previous_level: {$userData['previous_level']} current_level: {$menuItemArray['level']}<br/>";
        if(isset($userData['previous_level']) && $userData['previous_level']>$menuItemArray['level']) {
            $html .= "</ul>\n</li>\n";
        }
        !empty($menuItemArray['title'])?$title=$menuItemArray['title']:$title=$menuItemArray['path'];
        if(!empty($menuItemArray['url'])) {
            $html .= "<li><a href='{$menuItemArray['url']}'>{$menuItemArray['title']}</a>\n";
        }
        else if(!empty($menuItemArray['childrens'])){
            $html .= "<li><a href='#'>{$menuItemArray['title']}</a>\n";
        }

        if(!empty($menuItemArray['childrens'])) {
            $html .= "<ul>\n";
        } else {
            $html .= "\n</li>\n";
        }
        $userData['previous_level'] = $menuItemArray['level'];
        isset($userData['html'])?$userData['html'].=$html:$userData['html']=$html;
    }
    /** Takes an array_walk_recursive compatible callback.  Will be called for each menu item */
    private static function menuArrayWalkRecursiveReal($menuArray, $menuItemIdx, $funcname, &$userdata = null) {
        //echo "menuArrayWalkRecursive called with menuArray:"; pretty_print_r($menuArray);
        $retval = true;
        if(isset($menuArray['path'])){
            //Only call if we are in a real menu item (and, among other, not at the root)
            //echo "menuArrayWalkRecursive(): Calling callback.<br/>";
            $retval = call_user_func($funcname, $menuArray, $menuItemIdx, &$userdata);
        }
        foreach ($menuArray['childrens'] as $menuItemIdx => $menuItem) {
            //pretty_print_r($menuItem);
            //echo "Recusively calling for $menuItemIdx<br/>";
            $retval = $retval & self::menuArrayWalkRecursiveReal($menuItem, $menuItemIdx, $funcname, &$userdata);
        }
        return $retval;
    }

    /** Takes an array_walk_recursive compatible callback.  Will be called for each menu item */
    public function menuArrayWalkRecursive($funcname, &$userdata = null) {
        return self::menuArrayWalkRecursiveReal($this->_menuArray, 0, $funcname, &$userdata);
    }
    /** Compare menu items according to it's title output passed to strcoll().  The toString
     * output is converted to ISO-8859-1 before sorting to allow strcoll() to be used
     *  for locale-specific sorting.
     */
    public static function titlestrcoll($object1, $object2)
    {
        //echo "CMP: ".$object1['title']." vs ". $object2['title']."<br/>\n";
        return strcoll ( utf8_decode($object1['title']), utf8_decode($object2['title']) );
    }
    
    /** Sort the menu using a user defined sort function */
    private static function menuArraySort(&$menuArray, $funcname, &$userdata = null) {
        //echo "menuArraySort called with menuArray:"; pretty_print_r($menuArray);
        $retval = true;
        //Sort childrens
        $retval = uasort($menuArray['childrens'], $funcname);
        foreach ($menuArray['childrens'] as $menuItemIdx => &$menuItem) {
            //Recursive call
            //pretty_print_r($menuItem);
            //echo "Recusively calling for $menuItemIdx<br/>";
            $retval = $retval & self::menuArraySort($menuItem, $funcname, &$userdata);
        }
        return $retval;
    }

    public function processHookMenu($classname) {
        require_once("classes/$classname.php");
        $hookArray = call_user_func(array($classname, 'hookMenu'));
        //pretty_print_r($hookArray);
        foreach($hookArray as $hookArrayItem) {

            $parts = explode('/', $hookArrayItem['path']);
            $menuItem=&$this->_menuArray;//This is just for initialisation, the foreach will set it to a leaf of menuitem.
            foreach ($parts as $k => $part) {
                //echo "$k => $part<br/>";
                if (!isset($menuItem['childrens'][$part])){
                    //echo "$part does not exist, setting it<br/>";
                    $menuItem['childrens'][$part]=array("childrens"=>array(), "level"=>$k);
                }
                $menuItem=&$menuItem['childrens'][$part];
            }
            $menuItem = array_merge($menuItem, $hookArrayItem);
            //echo "Setting menuItem to hookArrayItem of path {$hookArrayItem['path']}, new _menuArray:<br/>";
            // pretty_print_r($this->_menuArray);
        }
    }
    public function initMenu() {
        $this->processHookMenu('Server');
        $this->processHookMenu('Node');
        $this->processHookMenu('Network');
        $this->processHookMenu('User');
        $this->processHookMenu('Content');
        $this->processHookMenu('NodeList');
        $this->processHookMenu('Role');
        $this->processHookMenu('VirtualHost');
        $this->processHookMenu('ContentTypeFilter');
        $this->processHookMenu('ProfileTemplate');
                $this->processHookMenu('DependenciesList');
        self::menuArraySort($this->_menuArray, array('Menu','titlestrcoll'));
        //pretty_print_r($this->_menuArray);
    }

    /**
     * IE's braindeadness requires a javascript workaround to allow suckerfish menus to work.
     *
     * @return HTML markup
     */
    static public function getIEWorkaroundJS() {
                $html = <<<EOT
        <script type="text/javascript"><!--//--><![CDATA[//><!--

sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
		}
		sfEls[i].onmouseout=function() {
			//alert(this.className);
			this.className=this.className.replace(new RegExp(" sfhover"), "");
			//alert(this.className);
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);

//--><!]]></script>
EOT;
return $html;
    }
    public function getUserUI()
    {
        $this->initMenu();
        $html = '';
        //Deal with internet explorer's baindeadness.  From http://www.htmldog.com/articles/suckerfish/dropdowns/example/vertical.html

$html .= "<ul id='nav'>\n";
        $userData=null;
        self::menuArrayWalkRecursive(array('Menu','buildHtmlMenuItemCallback'), $userData);
        $html .= $userData['html'];
        if(isset($userData['previous_level']) && $userData['previous_level']>0) {
            $html .= "</ul>\n";
        }
        $html .= "</ul>\n";
                $html .= "<br/ class='clearbr'>\n";
        //echo htmlspecialchars($userData['html']);
        return $html;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */