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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: GenericObject.php 1042 2006-05-20 20:28:27Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/GenericObject.php');
/**
 * Any object that implement this interface can be administered in a generic way.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
abstract class GenericDataObject implements GenericObject
{
    /** The object Id, a string */
    protected $_id;
    /** Get an instance of the object
     * @see GenericObject
     * @param $id The object id
     * @return the Content object, or null if there was an error (an exception is also thrown)
     */
    //static public function &getObject($id);
    /*
    * Example implementation:
    private static $instanceArray = array();
    static function &getObject($class, $id) {
    if(!isset(self::$instanceArray[$id]))
    {
    self::$instanceArray[$id] = new $class($id);
    }
    return self::$instanceArray[$id];
    }
    */

    /** Create a new object in the database
     * @see GenericObject
     * @return the newly created object, or null if there was an error (or if method is unsupported)
     */
    //static function createNewObject()

    /** Get an interface to create a new object.
     * @return html markup, or null.  If it returns null, this object does not support new
     * object creation
     */
    public static function getCreateNewObjectUI() {
        return null;
    }

    /** Process the new object interface.
     *  Will return the new object if the user has the credentials
     * necessary (Else an exception is thrown) and and the form was fully
     * filled (Else the object returns null).
     * @return the object or null if no new object was created.
     */
    public static function processCreateNewObjectUI() {
        return null;
    }

    /** Retreives the id of the object
     * @return The id, a string */
    public function getId()
    {
        return $this->_id;
    }
    /** Retreives the admin interface of this object.
     * @return The HTML fragment for this interface, or null.
     * If it returns null, this object does not support new object creation */
    public function getAdminUI() {
        return null;
    }

    /** Process admin interface of this object.
     */
    public function processAdminUI() {
        return null;
    }

    /** Delete this Object form it's storage mechanism
     * @param &$errmsg Appends an explanation of the error on failure
     * @return true on success, false on failure or access denied */
    public function delete(& $errmsg) {
        $errmsg .= sprintf(_("Delete not supported on class %s"),get_class($this));
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

