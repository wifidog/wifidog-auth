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
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: Content.php 1086 2006-09-01 11:00:58 +0000 (Fri, 01 Sep 2006) benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/Content.php');
/**
 * Defines any type of content
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class ContentTypeFilter {
    public $criteria_array;

    /**
     * Constructor
     *
     * @param string $content_id Id of content
     *
     * @return void
     */
    private function __construct() {
    }

    /**
     * Get the content object, specific to it's content type
     *
     * @param string $criteria_array an array of functions call on the objects.  For each one, the method must exist and return true.
     * Format is array(array(callback_funct, array(callback_funct_parameters))
     *
     * @return object The Content object, or null if there was an error
     *                (an exception is also thrown)
     */
    public static function getObject($criteria_array) {
        $object = new self();
        $object->criteria_array = $criteria_array;
                //pretty_print_r($object->criteria_array);
        return $object;
    }
    /** Is this class name an acceptable content type?  Will call all functions in the criteria array, but ADDING THE CANDIDATE CLASSNAME as the LAST parameter.
     * @param string $classname The classname to check
     * @return true or false.  Will also silently return false if the class does not exist */
    public function isAcceptableContentClass($classname) {
        $retval = true;
                /*pretty_print_r($this->criteria_array);
                $reflector = new ReflectionClass($classname);
                $methods = $reflector->getMethods();
                pretty_print_r($methods);*/
        if(is_array($this->criteria_array))
        {
            foreach ($this->criteria_array as $criteria) {
                //echo "call_user_func_array called with: ";
                //pretty_print_r($criteria);

                if(is_callable(array($classname,$criteria[0])) === false)
                {
                    throw new exception (sprintf("Class %s does not implement method %s", $classname, $criteria[0]));
                }

                $criteria[1][]=$classname;
                if(!call_user_func_array(array($classname,$criteria[0]), $criteria[1])) {
                    //The content type does not meet the criteria
                    $retval=false;
                    break;
                }
                //$retval ? $result ="TRUE" : $result="FALSE";
                //echo "call_user_func_array result: $result<br>";
            }
        }
        return $retval;
    }
} //end class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */