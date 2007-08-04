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
 * @copyright  2005-2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/User.php');
require_once('classes/AbstractDb.php');
/**
 * Security class
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
abstract class Security
{
    /* Check if the current user has the requested permission
     * Sorry about the weird SQL, it's there to avoid checking any more permissions than strictly necessary
     * @param AND or OR
     */
    private static function hasPermissionsHelper(Array $permissionsCheckArray, $operator, $user=null)
    {
        $retval = false;
        $first = true;
        $sql = '';
        if(!$user) {
            $user = User::getCurrentUser();
        }
        if($user) {
            $db = AbstractDb::getObject();
            foreach ($permissionsCheckArray as $permissionsCheck) {
                $permission = $permissionsCheck[0];
                $targetObject = $permissionsCheck[1];
                $objectClass = $permission->getTargetObjectClass();
                if($targetObject) {
                    if($objectClass!=get_class($targetObject)) {
                        throw new Exception(sprintf("Tried to check if an object of class %s has a permission of type %s",$objectClass, get_class($targetObject)));
                    }
                    $objectId = $db->escapeString($targetObject->getId());
                    $objectSqlAnd = "\n AND object_id = '$objectId' \n";
                }
                else {
                    $objectSqlAnd = '';
                }
                $table = strtolower($objectClass).'_stakeholders';
                $permissionIdStr = $db->escapeString($permission->getId());
                $sqlSelect = "SELECT DISTINCT permission_id FROM $table JOIN role_has_permissions USING (role_id) WHERE user_id='{$user->getId()}' $objectSqlAnd AND permission_id = '$permissionIdStr'";
                if($operator == 'OR') {
                    $first?$sql .= " ($sqlSelect)\n":$sql .= ", ($sqlSelect)\n";
                }
                else  if($operator == 'AND') {
                    $first?$sql .= "":$sql .= ", ";
                    $sql .= "CASE WHEN EXISTS ($sqlSelect)\n";
                    $sql .= "THEN NULL\n";
                    $sql .= "ELSE '$permissionIdStr'\n";
                    $sql .= "END\n";
                }
                else {
                    throw new Exception("Operator $operator is unknown!");
                }


                $first = false;
            }
            $sql = "SELECT COALESCE(\n$sql\n)\n";
            $row = null;
            $db->execSqlUniqueRes($sql, $row, false);
            if($operator == 'OR') {                //If any of the permission checks succeded returns the first permission id that succeded
                $row['coalesce']?$retval=true:$retval=false;
            }
            else  if($operator == 'AND') {
                //Return false if any of the permission checks failed returns the first permission id that didn't match the query),
                $row['coalesce']?$retval=false:$retval=true;
            }
            else {
                throw new Exception("Operator $operator is unknown!");
            }
        }
        return $retval;
    }

    /* Check if the current user has the requested permission
     * @param permission The permission to check
     * @param $targetObject The Object on which the permssion applies (Network, Server, etc.)  If null, the user must have this permission on at least one object
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function hasPermission(Permission $permission, $targetObject=null, $user=null)
    {
        return self::hasPermissionsHelper(array(array($permission, $targetObject)), 'AND', $user);
    }

    /* Check if the current user has ANY of the requested permission
     * @param $permissionsArray An two dimensionnal array of permissions to check
     * permissionsArray[]=array($permission, $targetObject);
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function hasAnyPermission(Array $permissionsArray, $user=null)
    {
        return self::hasPermissionsHelper($permissionsArray, 'OR', $user);
    }

    /* Check if the current user has ALL of the requested permissions
     * @param $permissionsArray An two dimensionnal array of permissions to check
     * permissionsArray[]=array($permission, $targetObject);
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function hasAllPermissions(Array $permissionsArray, $user=null)
    {
        return self::hasPermissionsHelper($permissionsArray, 'AND', $user);
    }

    /* require that the user has the current permission, otherwise, throw up an interface to deal with the proplem
     * @param permission The permission to check
     * @param $targetObject The Object on which the permission applies (Network, Server, etc.).  If null, the user must have this permission on at least one object
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function requirePermission(Permission $permission, $targetObject=null)
    {
        $hasPermission = self::hasPermission($permission, $targetObject, User::getCurrentUser());
        if(!$hasPermission) {
            self::handleMissingPermissions(array(array($permission, $targetObject)));
        }

        return true;
    }

    /* Require that the user has ANY of the requested permissions, otherwise, throw up an interface to deal with the proplem
     * @param $permissionsArray A two dimensionnal array of permissions to check
     * permissionsArray[]=array($permission, $targetObject);
     */
    public static function requireAnyPermission(Array $permissionsArray)
    {
        $hasPermission = self::hasAnyPermission($permissionsArray, User::getCurrentUser());
        if(!$hasPermission) {
            self::handleMissingPermissions($permissionsArray);
        }
        return true;
    }

    /* Require that the user has ALL of the requested permissions
     * @param $permissionsArray A two dimensionnal array of permissions to check
     * permissionsArray[]=array($permission, $targetObject);
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function requireAllPermissions(Array $permissionsArray, $user=null)
    {
        $hasPermission = self::hasAllPermission($permissionsArray, User::getCurrentUser());
        if(!$hasPermission) {
            self::handleMissingPermissions($permissionsArray);
        }
        return true;
    }

    private static function handleMissingPermissions(Array $permissionsArray)
    {
        $missingPerms = null;
        foreach($permissionsArray as $permissionsCheck) {
            $permission = $permissionsCheck[0];
            $targetObject = $permissionsCheck[1];
            if(!self::hasPermission($permission, $targetObject)) {
                $missingPerms .= sprintf(_("%s (%s) on  %s: %s")."<br/>\n", $permission->getId(), $permission->getDescription(), $permission->getTargetObjectClass(), $targetObject?(string)$targetObject:_('Any'));
            }
        }
        $msg =  _("Some (possibly all) of the following permission(s) you don't have are required to perform the operation your requested:")."<br/>\n$missingPerms";
        throw new SecurityException($msg);
    }

    /* Returns an array of objects for which the user has the specified permission
     * @param permission The permission to check
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     * @return array (possibly empty) of objects of the type matching the permission type.  The array index is the object id.
     *      */
    public static function getObjectsWithPermission(Permission $permission, $user=null)
    {
        $retval=array();
        if(!$user) {
            $user = User::getCurrentUser();
        }
        if($user) {
            $db = AbstractDb::getObject();
            $object_class = $permission->getTargetObjectClass();
            $table = strtolower($object_class).'_stakeholders';
            $permissionIdStr = $db->escapeString($permission->getId());
            $sql = "SELECT DISTINCT object_id FROM $table JOIN role_has_permissions USING (role_id) WHERE user_id='{$user->getId()}' AND permission_id = '$permissionIdStr'";
            $db->execSql($sql, $rows, false);
            if($rows) {
                foreach ($rows as $row){
                    $retval[$row['object_id']] = call_user_func(array($object_class,'getObject'),$row['object_id']);
                }
            }
        }
        return $retval;
    }

    /* Check if the current user has the requested role on the target object
     * @param role The role object to check
     * @param $targetObject The Object on which the permssion applies (Network, Server, etc.)
     * @param user User object, optional, if unspecified, the current user is used.  Note that there may be no current user (annonymous)
     */
    public static function hasRole(Role $role, $targetObject, $user=null)
    {
        $retval = false;
        $db = AbstractDb::getObject();
        $object_class = get_class($targetObject);
        $table = strtolower($object_class).'_stakeholders';

        $object_id = $db->escapeString($targetObject->getId());
        $roleIdStr = $db->escapeString($role->getId());
        if(!$user) {
            $user = User::getCurrentUser();
        }
        if($user) {
            $sql = "SELECT * FROM $table WHERE object_id = '$object_id' AND user_id='{$user->getId()}' AND role_id = '$roleIdStr';";
            $rows = null;
            $db->execSql($sql, $rows, false);
            if($rows) {
                $retval = true;
            }
        }
        return $retval;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

