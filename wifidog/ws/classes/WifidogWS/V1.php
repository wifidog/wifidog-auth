<?php

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
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Web service V1 class
 *
 * mandatory parameters:
 * action: get|list|auth
 *
 * Each action has its own set of parameters:
 * 
 * get: get some information concerning a given object, identified by its id
 * 		parameters: object_class  The class of the object to get
 *               object_id  The id of the object
 *               fields  The list of fields to fetch (absent: all the allowed fields)
 *               id_type (o)  Not used yet
 *               
 * list: get some informations concerning a list of objects
 *    parameters:  object_class The class of objects to list
 *               fields   The fields to list for each object
 *               parent_class (o)  The class of the parent object (for the nodes of a network, the class would be network)
 *               parent_id (o)  The id of the parent object
 *               
 * auth: verify the users credential. And in part authenticate the user
 *    parameters: username  The username to authenticate
 *               password   The password
 *               gw_id (o)  The gateway id if the request comes from a gateway
 *               gw_address (o)  The gateway address as sent in the original request from gateway
 *               gw_port (o)  The gateway port as sent from the original request from gateway
 *               from_ip (o)  The ip of the user, as can be got from the $_SERVER['REMOTE_ADDR'] variable
 *               mac (o)  The user mac as sent in the original request from gateway
 *     NOTE: This action DOES NOT authenticate the user on the gateway and hence, DOES NOT grant access to the internet.
 *           There is an authentication protocol that needs to be respected (http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1)
 *           However, this action will return the url that should be used as a next step of this protocol, so the calling system may do what it must
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
class WifidogWS_V1 extends WifidogWS
{
    /**
     * @var GET parameters of the function
     */
    protected $_action;
    protected $_objectClass;
    protected $_objectId;
    protected $_fields;
    
    /** @var list classes that are allowed to be fetched **/ 
    protected static $_allowedObjectClass = array('Network', 'User', 'Node');
    /** 
     * @var list allowed fields for each class
     * For each class, the array is such that the key is the field that will be requested in the GET request
     * the value is the name of the field in the app, such that class->getValue exists and returns 
     */
    protected static $_allowedFields = array(
        'Network' => array('Name' => 'Name',
                           'Userstotal' => 'NumUsers',
                           'Usersonline' => 'NumOnlineUsers',
                           'Hotspotstotal' => 'NumNodes',
                           'Hotspotsoperational' => 'NumOnlineNodes'),
        'Node' => array('Name' => 'Name', 
                        'Id' => 'Id',
                        'NumOnlineUsers' => 'NumOnlineUsers',
                        'CreationDate' => 'CreationDate',
                        'Status' => 'Status',
                        'OpeningDate' => 'CreationDate', 
        								'Connected_users' => 'OnlineUsers'),
        'User' => array('Username' => 'Username',
                        'AccountOrigin' => 'AccountOrigin',
                        'Email' => 'Email' )
    );
    
    /**
      * Constructor for the web service 
      * 
      * */
    protected function __construct()
    {
        parent::__construct();
    }
    
	  /**
     * Set the web service parameters
     * @param $params   the arrray of GET parameters
     */
    public function setParams($params = array()) {
        
        if (isset($params['action'])) {
            $this->_action = $params['action'];
            unset($params['action']);
        }
        if (isset($params['object_class'])) {
            $this->_objectClass = $params['object_class'];
            unset($params['object_class']);
        }
        if (isset($params['object_id'])) {
            $this->_objectId = $params['object_id'];
            unset($params['object_id']);
        }
        if (isset($params['fields'])) {
            $this->_fields = $params['fields'];
            unset($params['fields']);
        }
        
        parent::setParams($params);
    }
    
    protected function mapFields($objectClass, $infields = array()) {
        $fields = array()   ;    
        foreach($infields as $field) {
            if (isset(self::$_allowedFields[$objectClass][$field]))
                $fields[] = self::$_allowedFields[$objectClass][$field];
            else
                $fields[] = "$field.forbidden";
        }
        return $fields;
    }
    
    /**
     * This function executes the action requested by the web service
     * For the requested action, it verifies if the necessary parameters are there and then calls the appropriate function to really execute the function
     * @return unknown_type
     */
    protected function executeAction() {
        if (!isset($this->_action)) {
            throw new WSException("No action was specified.  Please use GET parameter 'action=list|get|auth' to specify an action", WSException::INVALID_PARAMETER); 
        }
        switch($this->_action) {
            case 'list':
                $object_class = (isset($this->_objectClass) ? ucfirst(strtolower($this->_objectClass)): null);
                $fields = (isset($this->_fields) ? explode(',',$this->_fields): array());
                $parentClass = (isset($this->_params['parent_class']) ? $this->_params['parent_class']:null);
                $parentId = (isset($this->_params['parent_id']) ? $this->_params['parent_id']:null);
                $this->executeList($object_class, $fields, $parentClass, $parentId);
                break;
            case 'get':
                $object_class = (isset($this->_objectClass) ? ucfirst(strtolower($this->_objectClass)): null);
                $object_id = (isset($this->_objectId) ? $this->_objectId: null);
                $fields = (isset($this->_fields) ? explode(',',$this->_fields): array());
                $idType = (isset($this->_params['id_type']) ? $this->_params['id_type']:null);
                $this->executeGet($object_class, $object_id, $fields, $idType);
                break;
            case 'auth':
                $gw_id = (isset($this->_params['gw_id']) ? $this->_params['gw_id']:null);
                $gw_address = (isset($this->_params['gw_address']) ? $this->_params['gw_address']:null);
                $gw_port = (isset($this->_params['gw_port']) ? $this->_params['gw_port']:null);
                $mac = (isset($this->_params['mac']) ? $this->_params['mac']:null);
                $from = (isset($this->_params['from_ip']) ? $this->_params['from_ip']:null);
                $username = (isset($this->_params['username']) ? $this->_params['username']:'');
                $password = (isset($this->_params['password']) ? $this->_params['password']:'');
                $this->executeAuth($username, $password, $gw_id, $gw_address, $mac, $gw_port, $from);
                break;
            default:
                throw new WSException("Action {$this->_action} is not defined.  Please use GET parameter 'action=list|get|auth' to specify an action", WSException::INVALID_PARAMETER);
                break;
        }
        
    }

    /**
     * Verify the given user credentials against the wifidog database 
     * @param $username   The username to authenticate
     * @param $pwdhash    The password hash
     * @param $gw_id      The gateway id
     * @param $gw_ip   	  The gateway's ip addresss
     * @return unknown_type
     */
    protected function executeAuth($username = null, $password = null, $gw_id = null, $gw_ip = null, $mac = null, $gw_port = null, $from = null) {
        $this->_outputArr['auth'] = 0;
        
        require_once('classes/Node.php');
        require_once('classes/User.php');
        require_once('classes/Network.php');
        require_once('classes/Authenticator.php');
        
        if (!is_null($gw_id)) {
            if (is_null($gw_ip) || is_null($gw_port) || is_null($from)) {
                throw new WSException("Missing information on the gateway.  You must specify parameter 'gw_address' AND 'gw_port' AND 'from_ip' if the parameter 'gw_id' is specified.", WSException::INVALID_PARAMETER);
            }
            $node = Node::getObjectByGatewayId($gw_id);
            if ($node) {
                $network = $node->getNetwork();
            } else {
                throw new WSException("Node identified by $gw_id cannot be found", WSException::PROCESS_ERROR);
            }
        } else {
            // Gateway ID is not set ... virtual login
            $network = Network::getCurrentNetwork();
            $node = null;
        }
        
        /*
         * If this is a splash-only node, then the user is automatically authenticated
         */
        $token = null;
        if (!empty($node) && $node->isSplashOnly()) {
            $this->_outputArr['auth'] = 1;
            $user = $network->getSplashOnlyUser();
            $token = $user->generateConnectionTokenNoSession($node, $from, $mac);
            if (!$token) throw new WSException("User authenticated but cannot generate connection token.", WSException::PROCESS_ERROR);
        } else {
            // Authenticate the user on the requested network
            $user = $network->getAuthenticator()->login($username, $password, $errMsg);
            if (!$user) {
                $this->_outputArr['auth'] = 0;
                $this->_outputArr['explanation'] = $errMsg;
            } else {
                $this->_outputArr['auth'] = 1;
                if (!is_null($node)) {
                    $token = $user->generateConnectionTokenNoSession($node, $from, $mac);
                   
                    if (!$token) throw new WSException("User authenticated but cannot generate connection token.", WSException::PROCESS_ERROR);
                }
            }
        }
        if ($this->_outputArr['auth'] == 1 && !is_null($token)) {
            $this->_outputArr['forwardTo'] = "http://" . $gw_ip . ":" . $gw_port . "/wifidog/auth?token=" . $token;
        }
    }
    
    /**
     * Gets the requested fields from an object
     * @param $objectClass   The class of the object
     * @param $objectId      The id of the object to fetch
     * @param $fields        The list of fields to get
     * @return unknown_type
     */
    protected function executeGet($objectClass, $objectId, $fields = array(), $idtype = null) {
        if (is_null($objectClass)) {
            throw new WSException("Missing parameter 'object_class' in the request.", WSException::INVALID_PARAMETER);
        }
        if (is_null($objectId)) {
            throw new WSException("Missing parameter 'object_id' in the request.", WSException::INVALID_PARAMETER);
        }
        if (!in_array($objectClass,self::$_allowedObjectClass)) {
            throw new WSException("Wrong object class '{$objectClass}' requested.  Possible values are " . implode(', ', self::$_allowedObjectClass), WSException::INVALID_PARAMETER);
        }
        
        include_once('classes/'.$objectClass.'.php');
        
        // Impossible to use this syntax because of php bug http://bugs.php.net/bug.php?id=31318.  But is valid though with php > 5.3, so using the ugly syntax below instead
        //$object = $objectClass::getObject($objectId);
        try {
            $object = call_user_func($objectClass.'::getObject', $objectId);
        } catch (Exception $e) {
            $object = null;
        }
        
        // Maybe there is a default object for this class, let's try to get it
        if (is_null($object)) {
            $object = call_user_func($objectClass.'::getDefault'.$objectClass);
        }
        // IF the object still is not found, then return an error
        if (is_null($object)) {
            throw new WSException("Object of class {$objectClass} with id {$objectId} not found", WSException::PROCESS_ERROR);
        }
  
        $fields = $this->mapFields($objectClass, $fields);
        if (empty($fields)) {
            $fields = array_keys(self::$_allowedFields[$objectClass]);
        } 
        $allowedFields = self::$_allowedFields[$objectClass];
        
        $this->_outputArr = self::filterRet($object, $fields);
        /*
        foreach($fields as $field) {
            if (isset($allowedFields[ucfirst(strtolower($field))])) {
                $methodName = 'get'.$allowedFields[ucfirst(strtolower($field))];
                if (method_exists($object, $methodName)) {
                    $this->_outputArr[$field] = self::filterRet($object->$methodName());
                } else {
                    $this->_outputArr[$field] = 'unknown';
                }
                
            } else {
                $this->_outputArr[$field] = 'Not allowed';
            }
        }
        */
        
    }
    
    /**
     * Get the list of all objectClass, for the given parent if specified or globally otherwise
     * @param $objectClass    The class whose object must be listed
     * @param $fields         The fields to list for each object
     * @param $parentClass    The parent class if necessary (for nodes for instance)
     * @param $parentId       The identifier of the parent object
     * @return unknown_type
     */
    protected function executeList($objectClass, $fields = array(), $parentClass = null, $parentId = null) {
        if (is_null($objectClass)) {
            throw new WSException("Missing parameter 'object_class' in the request.", WSException::INVALID_PARAMETER);
        }
        if (!in_array($objectClass,self::$_allowedObjectClass)) {
            throw new WSException("Wrong object class '{$objectClass}' requested.  Possible values are " . implode(', ', self::$_allowedObjectClass), WSException::INVALID_PARAMETER);
        }
        
        include_once('classes/'.$objectClass.'.php');
        
        $parentObject = null;
        if (!is_null($parentClass)) {
            if (!is_null($parentId)) {
                if (!in_array($parentClass,self::$_allowedObjectClass)) {
                    throw new WSException("Wrong parent class '{$parentClass}' specified.  Possible values are " . implode(', ', self::$_allowedObjectClass), WSException::INVALID_PARAMETER);
                }
                include_once('classes/'.$parentClass.'.php');
                $parentObject = call_user_func($parentClass.'::getObject', $parentId);
            } else {
                throw new WSException("If parent class is specified, must specify 'parent_id'", WSException::INVALID_PARAMETER);
            }
        }
        
        if (is_null($parentObject)) {
            if (method_exists($objectClass, 'getAll'.$objectClass.'s')) {
                $objectList = call_user_func($objectClass.'::getAll'.$objectClass.'s');
            }
        }
        $fields = $this->mapFields($objectClass, $fields);
        if (empty($fields)) {
            $fields = self::$_allowedFields[$objectClass];
        } 

        $this->_outputArr = self::filterRet($objectList, $fields);
    }
    
    /**
     * Filters the returned value to return only allowed fields if the returned value is an object
     * @param $retVals         array of mixed, array of objects or other arrays to filter
     * @param $fields          List of fields to filter (if none specified, for objects the allowed fields for the class is taken, otherwise, all is taken)   
     * @return array | mixed
     */
    protected static function filterRet($retVals = array(), $fields = array()) {
        if (!is_array($retVals)) {
            $retVals = array($retVals);
        }
        $filtered = array();

        foreach($retVals as $key => $value) {
            // If the return is one object we filter, return only the allowed fields
            if (is_object($value)) {
                // Object class must be one of the allowed classes or else return nothing
                $object_class = get_class($value);
                if (in_array($object_class, self::$_allowedObjectClass)) {
                    
                    // Get each allowed field
                    if (empty($fields)) {
                        $fields = self::$_allowedFields[$object_class];
                    }
                    $retFields = array();
                    foreach ($fields as $field) {
                        $forbiddenfield = explode(".", $field);
                        if (! (count($forbiddenfield) == 2)) {
                            $methodName = 'get'.$field;
                            if (method_exists($value, $methodName)) {
                                
                                $retFields[$field] = self::filterRet($value->$methodName());
                            } else {
                                $retFields[$field] = 'unknown';
                            }
                        } else
                            $retFields[$forbiddenfield[0]] = 'Not allowed';
                    }
                    $filtered[] = $retFields;
                }
                else {
                    $filtered[] = array();
                }
            } else if (is_array($value) && !empty($fields)) {
                $allowed_array = array();
                foreach ($fields as $field) {
                    if (isset($value[$field])) {
                        $allowed_array[$field] = $value[$field];
                    } else {
                        // In an array, the actual field name may be a _-separated string where the word separation is the uppercase
                        $fieldname = preg_replace("/([A-Z])/e", "'_'.strtolower('\\1')", $field);
                        // This preg_replace also put a _ before the first ucletter, which we don't want.
                        if ($fieldname[0] == '_')  $fieldname = substr($fieldname, 1);
                        if (isset($value[$fieldname])) {
                            $allowed_array[$field] = $value[$fieldname];
                        }
                        else {
                            $allowed_array[$field] = 'unknown';
                        }
                    }
                }
                $filtered[] = $allowed_array;
            }
            else {
                $filtered[] = $value;
            }
        }
        return (count($filtered) == 1? $filtered[0]: $filtered);
    }
    
}