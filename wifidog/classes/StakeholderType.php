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
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/GenericDataObject.php');

/**
 * This class represent the different stakeholder types for permissions.
 * The stakeholder id is actually the table name storing the object to which the role applies
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class StakeholderType extends GenericDataObject
{
    const Node = 'Node';
    const Network = 'Network';
    const Server = 'Server';
    const Content = 'Content';
    const NodeGroup = 'NodeGroup';
    
    /**
     * Get an instance of the object
     *
     * @param string $id The object id
     *
     * @return mixed The Content object, or null if there was an error
     *               (an exception is also thrown)
     *
     * @see GenericObject
     */
    public static function &getObject($id)
    {
        $retval = new self($id);
        return  $retval;
    }
    
    private function __construct($id) {
        $this->_id=$id;
    }
    /**
     * Get an interface to pick a ContentTypeFilter
     *
     * @param string $user_prefix         A identifier provided by the
     *                                    programmer to recognise it's generated
     *                                    html form
     *  @param string $userData=null Array of contextual data optionally sent to the method.
     *  The function must still function if none of it is present.
     *
     * This method understands:
     *  $userData['preSelectedObject'] An optional ProfileTemplate object id.
     *
     * @return string HTML markup

     */
    public static function getSelectUI($user_prefix, $userData = null)
    {
        $db = AbstractDb::getObject();

        // Init values
        $html = "";
        $_content_type_filter_rows = null;
        !empty($userData['preSelectedObject'])?$selectedId=$userData['preSelectedObject']->getId():$selectedId=null;
        $sql = "SELECT * FROM stakeholder_types ORDER BY stakeholder_type_id ASC";
        $db->execSql($sql, $rows, false);
        $name = $user_prefix;
        $i = 0;
        foreach ($rows as $row) {
            
            $tab[$i][0] = $row['stakeholder_type_id'];
            $tab[$i][1] = $row['stakeholder_type_id'];
            $i ++;
        }
        $html .= FormSelectGenerator::generateFromArray($tab, $selectedId, $name, null, false);
        return $html;
    }
    /**
     * Get the selected object.
     *
     * @param string $user_prefix A identifier provided by the programmer to
     *                            recognise it's generated form
     *
     * @return mixed The selected object or null

     */
    public static function processSelectUI($user_prefix)
    {
        $name = "{$user_prefix}";

        if (!empty ($_REQUEST[$name])) {
            return self::getObject($_REQUEST[$name]);
        } else {
            return null;
        }
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */