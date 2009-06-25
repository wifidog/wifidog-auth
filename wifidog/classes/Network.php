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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/GenericDataObject.php');
require_once('classes/Content.php');
require_once('classes/User.php');
require_once('classes/Node.php');
require_once('classes/GisPoint.php');
require_once('classes/Cache.php');
require_once('classes/ThemePack.php');
require_once('classes/Security.php');

/**
 * Abstract a Network.
 *
 * A network is an administrative entity with it's own users, nodes and authenticator.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class Network extends GenericDataObject
{
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();

    /**
     * Get an instance of the object
     *
     * @param string $id The object id
     *
     * @return mixed The Content object, or null if there was an error
     *               (an exception is also thrown)
     *
     * @see GenericObject
     * @static
     * @access public
     */
    public static function &getObject($id)
    {
        if(!isset(self::$instanceArray[$id]))
        {
            self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
    }

    /** Free an instanciated object
     * @param $id The id to free
     * Thanks and so long for all the ram.
     */
    public static function freeObject($id)
    {
        if(isset(self::$instanceArray[$id]))
        {
            unset(self::$instanceArray[$id]);
        }
    }

    /**
     * Get all the Networks configured on this server
     *
     * @return array An array of Network objects.  The default network is
     *               returned first
     *
     * @static
     * @access public
     */
    public static function getAllNetworks()
    {
        $retval = array ();
        $db = AbstractDb::getObject();
        $sql = "SELECT network_id FROM networks";
        $network_rows = null;
        $db->execSql($sql, $network_rows, false);
        if ($network_rows == null) {
            throw new Exception(_("Network::getAllNetworks:  Fatal error: No networks in the database!"));
        }
        foreach ($network_rows as $network_row) {
            $retval[] = self::getObject($network_row['network_id']);
        }
        return $retval;
    }

    /**
     * Get the default network
     *
     * @return object A Network object, NEVER returns null.
     *
     * @static
     * @access public
     */
    public static function getDefaultNetwork()
    {
        $retval = null;
        $vhost = VirtualHost :: getCurrentVirtualHost();
        if ($vhost == null) {
            $vhost = VirtualHost :: getDefaultVirtualHost();
        }
        return $vhost->getDefaultNetwork();
    }

    /**
     * Get the current network for which the portal is displayed or to which a
     * user is physically connected.
     *
     * @param bool $real_network_only NOT IMPLEMENTED YET true or false.  If
     *                                true, the real physical network where the
     *                                user is connected is returned, and
     *                                the node set by setCurrentNode is ignored.
     *
     * @return objetc A Network object, NEVER returns null.
     *
     * @static
     * @access public
     */
    public static function getCurrentNetwork($real_network_only = false)
    {
        $retval = null;
        $current_node = Node :: getCurrentNode();

        if ($current_node != null) {
            $retval = $current_node->getNetwork();
        } else {
            $retval = Network :: getDefaultNetwork();
        }

        return $retval;
    }

    /**
     * Create a new Network object in the database
     *
     * @param string $network_id The network id of the new network.  If absent,
     *                           will be assigned a guid.
     *
     * @return mixed The newly created object, or null if there was an error
     *
     * @see GenericObject
     * @static
     * @access public
     */
    public static function createNewObject($network_id = null)
    {
        $db = AbstractDb::getObject();
        if (empty ($network_id)) {
            $network_id = get_guid();
        }
        $network_id = $db->escapeString($network_id);

        $sql = "INSERT INTO networks (network_id, network_authenticator_class) VALUES ('$network_id', 'AuthenticatorLocalUser')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new network in the database!'));
        }
        $object = self::getObject($network_id);
        require_once('classes/Stakeholder.php');
        Stakeholder::add(null, Role::getObject('NETWORK_OWNER'), $object);
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
     * Get an interface to pick a network
     *
     * If there is only one network available, no interface is actually shown
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
     * @return string HTML markup

     */
    public static function getSelectUI($user_prefix, $userData=null)
    {
        $userData=$userData===null?array():$userData;
        $html = '';
        $name = $user_prefix;
        //pretty_print_r($userData);
        array_key_exists('preSelectedObject',$userData)?(empty($userData['preSelectedObject'])?$selected_id=null:$selected_id=$userData['preSelectedObject']->getId()):$selected_id=self::getDefaultNetwork()->getId();
        !empty($userData['additionalWhere'])?$additional_where=$userData['additionalWhere']:$additional_where=null;
        !empty($userData['allowEmpty'])?$allow_empty=$userData['allowEmpty']:$allow_empty=false;
        !empty($userData['nullCaptionString'])?$nullCaptionString=$userData['nullCaptionString']:$nullCaptionString=null;
        !empty($userData['onChange'])?$onChangeString=$userData['onChange']:$onChangeString="";

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
                $tab[$i][0] = $network_row['network_id'];
                $tab[$i][1] = $network_row['name'];
                $i ++;
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

        return $html;
    }

    /**
     * Get the selected Network object.
     *
     * @param string $user_prefix A identifier provided by the programmer to
     *                            recognise it's generated form
     *
     * @return mixed The network object or null

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

    /**
     * Get an interface to create a new network.
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getCreateNewObjectUI()
    {
        // Init values
        $html = '';

        $html .= _("Create a new network with ID")." \n";
        $name = "new_network_id";
        $html .= "<input type='text' size='10' name='{$name}'>\n";

        return $html;
    }

    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return mixed The Network object or null if no new Network was created.
     *
     * @static
     * @access public
     */
    public static function processCreateNewObjectUI()
    {
        // Init values
        $retval = null;
        $name = "new_network_id";

        if (!empty($_REQUEST[$name])) {
            $network_id = $_REQUEST[$name];

            if ($network_id) {
                Security::requirePermission(Permission::P('SERVER_PERM_ADD_NEW_NETWORK'), Server::getServer());
                $retval = self::createNewObject($network_id);
            }
        }

        return $retval;
    }

    /**
     * Constructor
     *
     * @param string $p_network_id
     *
     * @return void
     *
     * @access private
     */
    private function __construct($p_network_id)
    {
        $db = AbstractDb::getObject();
        $network_id_str = $db->escapeString($p_network_id);
        if ($network_id_str == "")
            $network_id_str = $db->escapeString(self::getDefaultNetwork()->getId());

        $sql = "SELECT *, EXTRACT(EPOCH FROM validation_grace_time) as validation_grace_time_seconds FROM networks WHERE network_id='$network_id_str'";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            throw new Exception("The network with id $network_id_str could not be found in the database");
        }
        $this->_row = $row;
        $this->_id = $p_network_id;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Retreives the network name
     *
     * @return string The id
     *
     * @access public
     */
    public function getTechSupportEmail()
    {
        return $this->_row['tech_support_email'];
    }

    /**
     * Set the network's tech support and information email address
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setTechSupportEmail($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getName()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET tech_support_email = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retrieves the network name
     *
     * @return string A string
     *
     * @access public
     */
    public function getName()
    {
        return $this->_row['name'];
    }

    /**
     * Set the network's name
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setName($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getName()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET name = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retrieves the network's theme pack
     *
     * @return mixed ThemePack or null
     *
     * @access public
     */
    public function getThemePack()
    {
        if (!empty ($this->_row['theme_pack'])) {
            return ThemePack::getObject($this->_row['theme_pack']);
        } else {
            return null;
        }
    }

    /**
     * Set the network's name
     *
     * @param string $value The new ThemePack, or null
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setThemePack($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getThemePack()) {
            $db = AbstractDb::getObject();
            empty($value)?$value="NULL":$value="'".$db->escapeString($value->getId())."'";
            $retval = $db->execSqlUpdate("UPDATE networks SET theme_pack = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retrieves the network's creation date
     *
     * @return string Network's creation date
     *
     * @access public
     */
    public function getCreationDate()
    {
        return $this->_row['creation_date'];
    }

    /**
     * Set the network's creation date
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     */
    public function setCreationDate($value)
    {

        $db = AbstractDb::getObject();

        // Init values
        $retVal = true;

        if ($value != $this->getCreationDate()) {
            $value = $db->escapeString($value);
            $retVal = $db->execSqlUpdate("UPDATE networks SET creation_date = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retVal;
    }

    /**
     * Retreives the network's homepage url
     *
     * @return string The id
     *
     * @access public
     */
    public function getWebSiteURL()
    {
        return $this->_row['homepage_url'];
    }

    /**
     * Set the network's homepage url
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setWebSiteUrl($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getName()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET homepage_url = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retreives the network's authenticator's class name.
     *
     * @return string Name of authenticator's class
     */
    public function getAuthenticatorClassName()
    {
        return $this->_row['network_authenticator_class'];
    }

    /**
     * Set the network's authenticator's class.
     *
     * The subclass of Authenticator to be used for user authentication
     * (ex: AuthenticatorRadius)
     *
     * @param string $value The class name of a  subclass of Authenticator
     *
     * @return bool True on success, false on failure
     */
    public function setAuthenticatorClassName($value)
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = true;

        if ($value != $this->getAuthenticatorClassName()) {
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET network_authenticator_class = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retreives the authenticator's parameters
     *
     * @return string Authenticator's parameters
     */
    public function getAuthenticatorConstructorParams()
    {
        return $this->_row['network_authenticator_params'];
    }

    /**
     * The explicit parameters to be passed to the authenticator's constructor
     * (ex: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key',
     * 'CHAP_MD5')
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     */
    public function setAuthenticatorConstructorParams($value)
    {

        $db = AbstractDb::getObject();

        // init values
        $retval = true;

        if ($value != $this->getAuthenticatorConstructorParams()) {
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET network_authenticator_params = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Get the Authenticator object for this network
     *
     * @return object A subclass of Authenticator
     */
    public function getAuthenticator()
    {
        require_once ('classes/Authenticator.php');

        // Include only the authenticator we are about to use
        require_once ("classes/Authenticators/".$this->_row['network_authenticator_class'].".php");

        if (strpos($this->_row['network_authenticator_params'], ';') != false) {
            throw new Exception("Network::getAuthenticator(): Security error: The parameters passed to the constructor of the authenticator are potentially unsafe");
        }

        $string=$this->_row['network_authenticator_params'];
         
        $params = explode(',', $string);
        for ($i = 0; $i < count($params); $i++) {
            $nquotes = substr_count($params[$i], '\'');
            if ($nquotes %2 == 1) {
                for ($j = $i+1; $j < count($params); $j++) {
                    if (substr_count($params[$j], '\'') > 0) {
                        array_splice($params, $i, $j-$i+1,
                        implode(',', array_slice($params, $i, $j-$i+1)));
                        break;
                    }
                }
            }
            if ($nquotes > 0) {
                $qstr =& $params[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, '\''), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, '\''), 1);
            }
        }
        return call_user_func_array(array (new ReflectionClass($this->_row['network_authenticator_class']), 'newInstance'), $params);

    }

    /**
     * Get the list of available Authenticators on the system
     *
     * @return array An array of class names

     */
    public static function getAvailableAuthenticators()
    {
        // Init values
        $authenticators = array ();
        $useCache = false;
        $cachedData = null;

        // Create new cache object with a lifetime of one week
        $cache = new Cache("AuthenticatorClasses", "ClassFileCaches", 604800);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData("mixed");

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $authenticators = $cachedData;
            }
        }

        if (!$useCache) {
            $dir = WIFIDOG_ABS_FILE_PATH."classes/Authenticators";
            $dirHandle = @ opendir($dir);

            if ($dirHandle) {
                // Loop over the directory
                while (false !== ($filename = readdir($dirHandle))) {
                    // Loop through sub-directories of Content
                    if ($filename != '.' && $filename != '..') {
                        $matches = null;

                        if (preg_match("/^(.*)\.php$/", $filename, $matches) > 0) {
                            // Only add files containing a corresponding Authenticator class
                            if (is_file("{$dir}/{$matches[0]}")) {
                                $authenticators[] = $matches[1];
                            }
                        }
                    }
                }

                closedir($dirHandle);
            }
            else {
                throw new Exception(_('Unable to open directory ').$dir);
            }

            // Sort the result array
            sort($authenticators);

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save results into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($authenticators, "mixed");
            }
        }

        return $authenticators;
    }

    /**
     * Get an interface to pick an Authenticator
     *
     * @param string $user_prefix                A identifier provided by the
     *                                           programmer to recognise it's
     *                                           generated html form
     * @param string $pre_selected_authenticator The Authenticator to be
     *                                           pre-selected in the form object
     *
     * @return string HTML markup

     */
    public static function getSelectAuthenticator($user_prefix, $pre_selected_authenticator = null)
    {

        $db = AbstractDb::getObject();

        // Init values
        $authenticators = array ();

        foreach (self :: getAvailableAuthenticators() as $authenticator) {
            $authenticators[] = array ($authenticator, $authenticator);
        }

        $name = $user_prefix;

        if ($pre_selected_authenticator) {
            $selectedID = $pre_selected_authenticator;
        }
        else {
            $selectedID = null;
        }

        $html = FormSelectGenerator :: generateFromArray($authenticators, $selectedID, $name, null, false);

        return $html;
    }

    /**
     * Is the network the default network?
     *
     * @return bool True or false
     *
     * @access public
     */
    public function isDefaultNetwork()
    {
        (self::getDefaultNetwork()->getId() == $this->getId()) ? $retval = true : $retval = false;
        return $retval;
    }

    /**
     * Retreives the network's validation grace period
     *
     * @return int Network's validation grace period in seconds
     *
     * @access public
     */
    public function getValidationGraceTime()
    {
        return $this->_row['validation_grace_time_seconds'];
    }

    /**
     * Set the network's validation grace period in seconds.
     *
     * A new user is granted Internet access for this period check his email
     * and validate his account.
     *
     * @param int $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setValidationGraceTime($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getValidationGraceTime()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET validation_grace_time = '{$value} seconds' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retreives the FROM address of the validation email
     *
     * @return string A string
     *
     * @access public
     */
    public function getValidationEmailFromAddress()
    {
        return $this->_row['validation_email_from_address'];
    }

    /**
     * Set the FROM address of the validation email
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setValidationEmailFromAddress($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getValidationEmailFromAddress()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET validation_email_from_address = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Can an account be connected more than once at the same time?
     *
     * @return bool True or false
     *
     * @access public
     */
    public function getMultipleLoginAllowed()
    {
        return ($this->_row['allow_multiple_login'] == 't') ? true : false;
    }

    /**
     * Set if a account be connected more than once at the same time
     *
     * @param bool $value The new value, true or false
     *
     * @return bool true on success, false on failure
     *
     * @access public
     */
    public function setMultipleLoginAllowed($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getMultipleLoginAllowed()) {
            $db = AbstractDb::getObject();
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_multiple_login = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Are nodes allowed to be set as splash-only (no login)?
     *
     * @return bool True or false
     *
     * @access public
     */
    public function getSplashOnlyNodesAllowed()
    {
        return (($this->_row['allow_splash_only_nodes'] == 't') ? true : false);
    }

    /**
     * Set if nodes are allowed to be set as splash-only (no login)
     *
     * @param bool $value The new value, true or false
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setSplashOnlyNodesAllowed($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getSplashOnlyNodesAllowed()) {
            $db = AbstractDb::getObject();
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_splash_only_nodes = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Get a GisPoint object
     *
     * @return object GisPoint object
     */
    public function getGisLocation()
    {
        return new GisPoint($this->_row['gmaps_initial_latitude'], $this->_row['gmaps_initial_longitude'], $this->_row['gmaps_initial_zoom_level']);
    }

    /**
     * Set the network's GisPoint object
     *
     * @param $value The new GisPoint object
     *
     * @return bool True on success, false on failure
     */
    public function setGisLocation($pt)
    {
        $retval=false;
        $db = AbstractDb::getObject();

        if (!empty ($pt)) {
            $lat = $db->escapeString($pt->getLatitude());
            $long = $db->escapeString($pt->getLongitude());
            $alt = $db->escapeString($pt->getAltitude());

            if (!empty ($lat) && !empty ($long) && !empty ($alt)) {
                $db->execSqlUpdate("UPDATE networks SET gmaps_initial_latitude = $lat, gmaps_initial_longitude = $long, gmaps_initial_zoom_level = $alt WHERE network_id = '{$this->getId()}'");
                $retval=true;
            }
            else {
                $db->execSqlUpdate("UPDATE networks SET gmaps_initial_latitude = NULL, gmaps_initial_longitude = NULL, gmaps_initial_zoom_level = NULL WHERE network_id = '{$this->getId()}'");
            }
            $this->refresh();
        }
        return $retval;
    }

    /**
     * Retreives the default Google maps type
     *
     * @return string Default Google maps type
     */
    public function getGisMapType()
    {
        return $this->_row['gmaps_map_type'];
    }

    /**
     * Set the network's default Google maps type
     *
     * @param $value The new default Google maps type
     *
     * @return bool True on success, false on failure
     */
    public function setGisMapType($value)
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = true;

        if ($value != $this->getGisMapType()) {
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET gmaps_map_type = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /**
     * Get an interface to pick a Google maps type
     *
     * @param string $user_prefix           A identifier provided by the
     *                                      programmer to recognise it's
     *                                      generated html form
     * @param string $pre_selected_map_type The Google map type to be
     *                                      pre-selected in the form object
     *
     * @return string HTML markup

     */
    public static function getSelectGisMapType($user_prefix, $pre_selected_map_type = "G_NORMAL_MAP")
    {

        $db = AbstractDb::getObject();

        // Init values
        $map_types = array (array ("G_NORMAL_MAP", _("Map")), array ("G_SATELLITE_MAP", _("Satellite")), array ("G_HYBRID_MAP", _("Hybrid")));

        $name = $user_prefix;

        if ($pre_selected_map_type) {
            $selectedID = $pre_selected_map_type;
        }
        else {
            $selectedID = null;
        }

        $html = FormSelectGenerator :: generateFromArray($map_types, $selectedID, $name, null, false);

        return $html;
    }

    /**
     * Get's the splash-only user.
     *
     * This is the user that people logged-in at a splash-only hotspot will
     * show up as.  This user always has multiple-login capabilities.
     *
     * @param string $username The username of the user
     * @param string $account_origin The account origin
     *
     * @return object A User object
     *
     * @access public
     */
    public function getSplashOnlyUser()
    {
        $username = 'SPLASH_ONLY_USER';
        if(!empty($this->splashOnlyUser)) {
            $user = $this->splashOnlyUser;
        }
        else
        {
            $user = User :: getUserByUsernameAndOrigin($username, $this);
            if (!$user) {
                $user = User :: createUser(get_guid(), $username, $this, '', '');
                $user->setAccountStatus(ACCOUNT_STATUS_ALLOWED);
            }
            $this->splashOnlyUser = $user;
        }
        return $user;
    }

    /**
     * Find out the total number of users in this networks's database
     *
     * @return int Number of users
     */
    public function getNumUsers()
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $cache = new Cache('network_'.$this->_id.'_num_users', $this->_id, 60);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of users
            $network_id = $db->escapeString($this->_id);
            $db->execSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_origin='$network_id'", $row, false);

            // String has been found
            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Find out how many users are valid in this networks's database
     *
     * @return int Number of valid users
     */
    public function getNumValidUsers()
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $cache = new Cache('network_'.$this->_id.'_num_valid_users', $this->_id, 60);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of valid users
            $network_id = $db->escapeString($this->_id);
            $db->execSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_status = ".ACCOUNT_STATUS_ALLOWED." AND account_origin='$network_id'", $row, false);
            // String has been found
            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Find out how many users are connected on the entire network
     * Counts every user account connected (once for every account), except the splash-only user + every mac adresses connecting as the splash-only user
     * @return int Number of online users
     */
    public function getNumOnlineUsers()
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $cache = new Cache('network_'.$this->_id.'_num_online_users', $this->_id, 60);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of online users
            $network_id = $db->escapeString($this->_id);
            $splashOnlyUserId = $this->getSplashOnlyUser()->getId();
            $sql = "SELECT ((SELECT COUNT(DISTINCT users.user_id) as count FROM users,connections JOIN tokens USING (token_id) NATURAL JOIN nodes JOIN networks ON (nodes.network_id=networks.network_id AND networks.network_id='$network_id') WHERE tokens.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND users.user_id!='{$splashOnlyUserId}') + (SELECT COUNT(DISTINCT connections.user_mac) as count FROM users,connections JOIN tokens USING (token_id) NATURAL JOIN nodes JOIN networks ON (nodes.network_id=networks.network_id AND networks.network_id='$network_id') WHERE tokens.token_status='".TOKEN_INUSE."' AND users.user_id=connections.user_id AND users.user_id='{$splashOnlyUserId}')) AS count";
            $db->execSqlUniqueRes($sql, $row, false);

            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Find out how many nodes are registered in this networks's database
     *
     * @return int Number of nodes
     */
    public function getNumNodes()
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        $cache = new Cache('network_'.$this->_id.'_num_nodes', $this->_id, 300);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of nodes
            $network_id = $db->escapeString($this->_id);
            $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$network_id'", $row, false);

            // String has been found
            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Find out how many nodes are deployed in this networks's database
     *
     * @return int Number of deployed nodes
     */
    public function getNumDeployedNodes()
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        $cache = new Cache('network_'.$this->_id.'_num_deployed_nodes', $this->_id, 300);

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of deployed nodes
            $network_id = $db->escapeString($this->_id);
            $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$network_id' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE')", $row, false);

            // String has been found
            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Find out how many deployed nodes are online in this networks's database
     *
     * @param bool $nonMonitoredOnly Return number of non-monitored nodes only
     *
     * @return int Number of deployed nodes which are online
     */
    public function getNumOnlineNodes($nonMonitoredOnly = false)
    {

        $db = AbstractDb::getObject();

        // Init values
        $retval = 0;
        $row = null;
        $useCache = false;
        $cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        if ($nonMonitoredOnly) {
            $cache = new Cache('network_'.$this->_id.'_num_online_nodes_non_monitored', $this->_id, 300);
        } else {
            $cache = new Cache('network_'.$this->_id.'_num_online_nodes', $this->_id, 300);
        }

        // Check if caching has been enabled.
        if ($cache->isCachingEnabled) {
            $cachedData = $cache->getCachedData();

            if ($cachedData) {
                // Return cached data.
                $useCache = true;
                $retval = $cachedData;
            }
        }

        if (!$useCache) {
            // Get number of online nodes
            $network_id = $db->escapeString($this->_id);

            if ($nonMonitoredOnly) {
                $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$network_id' AND node_deployment_status = 'NON_WIFIDOG_NODE' AND ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) >= interval '5 minutes')", $row, false);
            } else {
                $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$network_id' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') AND ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) < interval '5 minutes')", $row, false);
            }

            // String has been found
            $retval = $row['count'];

            // Check if caching has been enabled.
            if ($cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $cache->saveCachedData($retval);
            }
        }

        return $retval;
    }

    /**
     * Are nodes allowed to redirect users to an arbitrary web page instead of
     * the portal?
     *
     * @return bool True or false
     *
     * @access public
     */
    public function getCustomPortalRedirectAllowed()
    {
        return (($this->_row['allow_custom_portal_redirect'] == 't') ? true : false);
    }

    /**
     * Set if nodes are allowed to redirect users to an arbitrary web page
     * instead of the portal?
     *
     * @param bool $value The new value, true or false
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setCustomPortalRedirectAllowed($value)
    {
        // Init values
        $retval = true;

        if ($value != $this->getCustomPortalRedirectAllowed()) {
            $db = AbstractDb::getObject();
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_custom_portal_redirect = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

      /** 
      * Are nodes allowed to redirect users to the original requested web page instead of 
      * the portal? 
      * 
      * @return bool True or false 
      * 
      * @access public 
      */ 
     public function getPortalOriginalUrlAllowed() 
     { 
         return (($this->_row['allow_original_url_redirect'] == 't') ? true : false); 
     } 
  
     /** 
      * Set if nodes are allowed to redirect users to the original requested web page 
      * instead of the portal? 
      * 
      * @param bool $value The new value, true or false 
      * 
      * @return bool True on success, false on failure 
      * 
      * @access public 
      */ 
     public function setPortalOriginalUrlAllowed($value) 
     { 
         // Init values 
         $retval = true; 
  
         if ($value != $this->getPortalOriginalUrlAllowed()) { 
             $db = AbstractDb::getObject(); 
             $value ? $value = 'TRUE' : $value = 'FALSE'; 
             $retval = $db->execSqlUpdate("UPDATE networks SET allow_original_url_redirect = {$value} WHERE network_id = '{$this->getId()}'", false); 
             $this->refresh(); 
         } 
  
         return $retval; 
     } 


    /** The length of the window during which the user must not have exceeded the limits below.
     *
     * @return string Interval as returned by postgresql
     */
    public function getConnectionLimitWindow()
    {
        return $this->_row['connection_limit_window'];
    }

    /**
     * Set the network's creation date
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     */
    public function setConnectionLimitWindow($value)
    {
        $db = AbstractDb::getObject();
        // Init values
        $retVal = true;

        if ($value != $this->getConnectionLimitWindow()) {
            $value?$value_sql="'".$db->escapeString($value)."'":$value_sql="NULL";
            $retVal = $db->execSqlUpdate("UPDATE networks SET connection_limit_window = $value_sql WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }

    /** Maximum data transfer during the abuse control window, in bytes
     *
     * @return integer Number of bytes
     */
    public function getConnectionLimitNetworkMaxTotalBytes()
    {
        return $this->_row['connection_limit_network_max_total_bytes'];
    }

    /**
     * Maximum data transfer during the abuse control window, in bytes
     *
     * @param $value integer Number of bytes
     *
     * @return bool True on success, false on failure
     */
    public function setConnectionLimitNetworkMaxTotalBytes($value)
    {
        $db = AbstractDb::getObject();
        // Init values
        $retVal = true;

        if ($value != $this->getConnectionLimitNetworkMaxTotalBytes()) {
            $value?$value_sql="'".$db->escapeString($value)."'":$value_sql="NULL";
            $retVal = $db->execSqlUpdate("UPDATE networks SET connection_limit_network_max_total_bytes = $value_sql WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }

    /** Maximum connection duration during the abuse control window
     *
     * @return string Interval as returned by postgresql
     */
    public function getConnectionLimitNetworkMaxDuration()
    {
        return $this->_row['connection_limit_network_max_usage_duration'];
    }

    /** Maximum connection duration during the abuse control window
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     */
    public function setConnectionLimitNetworkMaxDuration($value)
    {
        $db = AbstractDb::getObject();
        // Init values
        $retVal = true;

        if ($value != $this->getConnectionLimitNetworkMaxDuration()) {
            $value?$value_sql="'".$db->escapeString($value)."'":$value_sql="NULL";
            $retVal = $db->execSqlUpdate("UPDATE networks SET connection_limit_network_max_usage_duration = $value_sql WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }

    /** Maximum data transfer during the abuse control window, in bytes
     *
     * @return integer Number of bytes
     */
    public function getConnectionLimitNodeMaxTotalBytes()
    {
        return $this->_row['connection_limit_node_max_total_bytes'];
    }

    /**
     * Maximum data transfer during the abuse control window, in bytes
     *
     * @param $value integer Number of bytes
     *
     * @return bool True on success, false on failure
     */
    public function setConnectionLimitNodeMaxTotalBytes($value)
    {
        $db = AbstractDb::getObject();
        // Init values
        $retVal = true;

        if ($value != $this->getConnectionLimitNodeMaxTotalBytes()) {
            $value?$value_sql="'".$db->escapeString($value)."'":$value_sql="NULL";
            $retVal = $db->execSqlUpdate("UPDATE networks SET connection_limit_node_max_total_bytes = $value_sql WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }

    /** Maximum connection duration during the abuse control window
     *
     * @return string Interval as returned by postgresql
     */
    public function getConnectionLimitNodeMaxDuration()
    {
        return $this->_row['connection_limit_node_max_usage_duration'];
    }

    /** Maximum connection duration during the abuse control window
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     */
    public function setConnectionLimitNodeMaxDuration($value)
    {
        $db = AbstractDb::getObject();
        // Init values
        $retVal = true;

        if ($value != $this->getConnectionLimitNodeMaxDuration()) {
            $value?$value_sql="'".$db->escapeString($value)."'":$value_sql="NULL";
            $retVal = $db->execSqlUpdate("UPDATE networks SET connection_limit_node_max_usage_duration = $value_sql WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retVal;
    }


    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
    public function getAdminUI()
    {
        Security::requirePermission(Permission::P('NETWORK_PERM_EDIT_NETWORK_CONFIG'), $this);
        require_once('classes/InterfaceElements.php');
        // Init values
        $html = '';

        /*
         * Begin with admin interface
         */
        $html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
        $html .= "<legend>"._("Network management")."</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";

        /*
         * Content management
         */
        $title = _("Network content");
        $name = "network_".$this->_id."_content";
        $data = Content::getLinkedContentUI($name, "network_has_content", "network_id", $this->_id, $display_page = "portal");
        $html .= InterfaceElements::generateAdminSectionContainer("network_content", $title, $data);

        /*
         * Network information
         */
        $html_network_information = array();

        // network_id
        $title = _("Network ID");
        $data = htmlspecialchars($this->getId(), ENT_QUOTES);
        $html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_id", $title, $data);

        // name
        $title = _("Network name");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_name", $this->getName(), "network_name_input");
        $html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_name", $title, $data);

        // creation_date
        $title = _("Network creation date");
        $data = DateTimeWD::getSelectDateTimeUI(new DateTimeWD($this->getCreationDate()), "network_" . $this->getId() . "_creation_date", DateTimeWD::INTERFACE_DATETIME_FIELD, "network_creation_date_input");
        $html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_creation_date", $title, $data);

        // homepage_url
        $title = _("Network's web site");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_homepage_url", $this->getWebSiteURL(), "network_homepage_url_input");
        $html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_homepage_url", $title, $data);

        // tech_support_email
        $title = _("Technical support email");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_tech_support_email", $this->getTechSupportEmail(), "network_tech_support_email_input");
        $html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_tech_support_email", $title, $data);

        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_information", _("Information about the network"), implode(null, $html_network_information));

        /*
         * Network authentication
         */
        $html_network_authentication = array();

        //  network_authenticator_class
        $title = _("Network authenticator class");
        $help = _("The subclass of Authenticator to be used for user authentication. Example: AuthenticatorRadius");
        $name = "network_" . $this->getId() . "_network_authenticator_class";
        $value = htmlspecialchars($this->getAuthenticatorClassName(), ENT_QUOTES);
        $data = $this->getSelectAuthenticator($name, $value);
        $html_network_authentication[] = InterfaceElements::generateAdminSectionContainer("network_network_authenticator_class", $title, $data, $help);

        //  network_authenticator_params
        $title = _("Authenticator parameters");
        $help = _("The explicit parameters to be passed to the authenticator. You MUST read the constructor documentation of your desired authenticator class (in wifidog/classes/Authenticators/) BEFORE you start playing with this.  Example: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key', 'CHAP_MD5'");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_network_authenticator_params", $this->getAuthenticatorConstructorParams(), "network_network_authenticator_params_input");
        $html_network_authentication[] = InterfaceElements::generateAdminSectionContainer("network_network_authenticator_params", $title, $data, $help);

        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_authentication", _("Network Authentication"), implode(null, $html_network_authentication));

        /*
         * Network properties
         */
        $html_network_properties = array();

        //  theme_pack
        $title = _("Selected theme pack for this network");
        $data = ThemePack::getSelectUI("network_" . $this->getId() . "_theme_pack", $this->getThemePack());
        $html_network_properties[] = InterfaceElements::generateAdminSectionContainer("network_theme_pack", $title, $data);

        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_properties", _("Network properties"), implode(null, $html_network_properties));

        /*
         * Network's node properties
         */
        $html_network_node_properties = array();

        //  allow_splash_only_nodes
        $title = _("Splash-only nodes");
        $help = _("Are nodes allowed to be set as splash-only (no login)?");
        $data = InterfaceElements::generateInputCheckbox("network_" . $this->getId() . "_allow_splash_only_nodes", "", _("Yes"), $this->getSplashOnlyNodesAllowed(), "network_allow_splash_only_nodes_radio");
        $html_network_node_properties[] = InterfaceElements::generateAdminSectionContainer("network_allow_splash_only_nodes", $title, $data, $help);

        //  allow_custom_portal_redirect
        $title = _("Portal page redirection");
        $help = _("Are nodes allowed to redirect users to an arbitrary web page instead of the portal?");
        $data = InterfaceElements::generateInputCheckbox("network_" . $this->getId() . "_allow_custom_portal_redirect", "", _("Yes"), $this->getCustomPortalRedirectAllowed(), "network_allow_custom_portal_redirect_radio");
        $html_network_node_properties[] = InterfaceElements::generateAdminSectionContainer("network_allow_custom_portal_redirect", $title, $data, $help);

        //  allow_original_URL_redirect
        $title = _("Original URL redirection");
        $help = _("Are nodes allowed to redirect users to the web page they originally requested instead of the portal?");
        $data = InterfaceElements::generateInputCheckbox("network_" . $this->getId() . "_allow_original_URL_redirect", "", _("Yes"), $this->getPortalOriginalUrlAllowed(), "network_allow_original_URL_redirect_radio");
        $html_network_node_properties[] = InterfaceElements::generateAdminSectionContainer("network_allow_original_URL_redirect", $title, $data, $help);

        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_node_properties", _("Network's node properties"), implode(null, $html_network_node_properties));

        /*
         * Network's user verification
         */
        $html_network_user_verification = array();

        //  validation_grace_time
        $title = _("Validation grace period");
        $help = _("The length of the validation grace period in seconds.  A new user is granted Internet access for this period check his email and validate his account.");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_validation_grace_time", $this->getValidationGraceTime(), "network_validation_grace_time_input");
        $html_network_user_verification[] = InterfaceElements::generateAdminSectionContainer("network_validation_grace_time", $title, $data, $help);

        //  validation_email_from_address
        $title = _("This will be the from address of the validation email");
        $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_validation_email_from_address", $this->getValidationEmailFromAddress(), "network_validation_email_from_address_input");
        $html_network_user_verification[] = InterfaceElements::generateAdminSectionContainer("network_validation_email_from_address", $title, $data);

        //  allow_multiple_login
        $title = _("Multiple connections");
        $help = _("Can an account be connected more than once at the same time?");
        $data = InterfaceElements::generateInputCheckbox("network_" . $this->getId() . "_allow_multiple_login", "", _("Yes"), $this->getMultipleLoginAllowed(), "network_allow_multiple_login_radio");
        $html_network_user_verification[] = InterfaceElements::generateAdminSectionContainer("network_allow_multiple_login", $title, $data, $help);

        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_user_verification", _("Network's user verification"), implode(null, $html_network_user_verification));

        /*
         * Dynamic abuse control
         */
        $html_dynamic_abuse_control = array();
        $permArray=null;
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_DYNAMIC_ABUSE_CONTROL'), $this);
        if (Security::hasAnyPermission($permArray)) {
            //  connection_limit_window
            $title = _("Abuse control window");
            $help = _("The length of the window during which the user must not have exceeded the limits below.  Any valid postgresql interval expression is acceptable, typically '1 month' '1 week'.  A user who exceeds the limits will be denied access until his usage falls below the limits.");
            $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_connection_limit_window", $this->getConnectionLimitWindow(), "network_connection_limit_window_input");
            $html_dynamic_abuse_control[] = InterfaceElements::generateAdminSectionContainer("network_connection_limit_window", $title, $data, $help);

            //  connection_limit_network_max_total_bytes
            $title = _("Network max total bytes transfered");
            $help = _("Maximum data transfer during the abuse control window");
            $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_connection_limit_network_max_total_bytes", $this->getConnectionLimitNetworkMaxTotalBytes(), "network_connection_limit_network_max_total_bytes");
            $html_dynamic_abuse_control[] = InterfaceElements::generateAdminSectionContainer("network_connection_limit_network_max_total_bytes", $title, $data, $help);

            //  connection_limit_network_max_usage_duration
            $title = _("Network max connection duration");
            $help = _("Maximum connection duration during the abuse control window.  Any valid postgresql interval expression is acceptable, such as hh:mm:ss");
            $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_connection_limit_network_max_usage_duration", $this->getConnectionLimitNetworkMaxDuration(), "network_connection_limit_network_max_usage_duration");
            $html_dynamic_abuse_control[] = InterfaceElements::generateAdminSectionContainer("network_connection_limit_network_max_usage_duration", $title, $data, $help);

            //  connection_limit_node_max_total_bytes
            $title = _("Node max total bytes transfered");
            $help = _("Maximum data transfer during the abuse control window");
            $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_connection_limit_node_max_total_bytes", $this->getConnectionLimitNodeMaxTotalBytes(), "network_connection_limit_node_max_total_bytes");
            $html_dynamic_abuse_control[] = InterfaceElements::generateAdminSectionContainer("network_connection_limit_node_max_total_bytes", $title, $data, $help);

            //  connection_limit_node_max_usage_duration
            $title = _("Node max connection duration");
            $help = _("Maximum connection duration during the abuse control window.  Any valid postgresql interval expression is acceptable, such as hh:mm:ss");
            $data = InterfaceElements::generateInputText("network_" . $this->getId() . "_connection_limit_node_max_usage_duration", $this->getConnectionLimitNodeMaxDuration(), "network_connection_limit_node_max_usage_duration");
            $html_dynamic_abuse_control[] = InterfaceElements::generateAdminSectionContainer("network_connection_limit_node_max_usage_duration", $title, $data, $help);
        }
        else{
            $html_dynamic_abuse_control[] = _("You do not have access to edit these options");
        }
        // Build section
        $html .= InterfaceElements::generateAdminSectionContainer("network_user_verification", _("Dynamic abuse control"), implode(null, $html_dynamic_abuse_control));

        /*
         * Access management
         */
        $html_access_rights = array();

        /*
         * Access rights
         */
        if (true) {
            require_once('classes/Stakeholder.php');
            $html_access_rights = Stakeholder::getAssignStakeholdersUI($this);
            $html .= InterfaceElements::generateAdminSectionContainer("access_rights", _("Access rights"), $html_access_rights);
        }
        /*
         * Network GIS data
         */
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $html_network_gis_data = array();

            $gis_point = $this->getGisLocation();
            $gis_lat_name = "network_" . $this->getId() . "_gis_latitude";
            $gis_lat_value = htmlspecialchars($gis_point->getLatitude(), ENT_QUOTES);
            $gis_long_name = "network_" . $this->getId() . "_gis_longitude";
            $gis_long_value = htmlspecialchars($gis_point->getLongitude(), ENT_QUOTES);
            $gis_alt_name = "network_" . $this->getId() . "_gis_altitude";
            $gis_alt_value = htmlspecialchars($gis_point->getAltitude(), ENT_QUOTES);

            $html_network_gis_data[] = '<p>'._("Note that to be valid, all 3 values must be present.")."</p>\n";
            $title = _("Latitude");
            $help = _("Center latitude for the area covered by your wireless network");
            $data = InterfaceElements::generateInputText($gis_lat_name, $gis_lat_value, "network_gis_latitude_input");
            $html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_latitude", $title, $data, $help);

            $title = _("Longitude");
            $help = _("Center longitude for the area covered by your wireless network");
            $data = InterfaceElements::generateInputText($gis_long_name, $gis_long_value, "network_gis_longitude_input");
            $html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_longitude", $title, $data, $help);

            $title = _("Zoomlevel");
            $help = _("Zoomlevel of the Google Map.  12 is a typical value.");
            $data = InterfaceElements::generateInputText($gis_alt_name, $gis_alt_value, "network_gis_altitude_input");
            $html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_altitude", $title, $data, $help);

            $title = _("Map type");
            $help = _("Default Google Map type for your the area of your wireless network");
            $data = $this->getSelectGisMapType("network_" . $this->getId() . "_gmaps_map_type", $this->getGisMapType());
            $html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gmaps_map_type", $title, $data, $help);

            // Build section
            $html .= InterfaceElements::generateAdminSectionContainer("network_gis_data", _("GIS data"), implode(null, $html_network_gis_data));
        }

        // Profile templates
        $title = _("Network profile templates");
        $name = "network_".$this->_id."_profile_templates";
        $data = ProfileTemplate::getLinkedProfileTemplateUI($name, "network_has_profile_templates", "network_id", $this->_id);
        $html .= InterfaceElements::generateAdminSectionContainer("network_profile_templates", $title, $data);

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
        Security::requirePermission(Permission::P('NETWORK_PERM_EDIT_NETWORK_CONFIG'), $this);

        // Content management
        $name = "network_".$this->_id."_content";
        Content :: processLinkedContentUI($name, 'network_has_content', 'network_id', $this->_id);

        // name
        $name = "network_".$this->getId()."_name";
        $this->setName($_REQUEST[$name]);

        // creation_date
        $name = "network_".$this->getId()."_creation_date";
        $this->setCreationDate($_REQUEST[$name]);

        // homepage_url
        $name = "network_".$this->getId()."_homepage_url";
        $this->setWebSiteUrl($_REQUEST[$name]);

        // tech_support_email
        $name = "network_".$this->getId()."_tech_support_email";
        $this->setTechSupportEmail($_REQUEST[$name]);

        //  network_authenticator_class
        $name = "network_".$this->getId()."_network_authenticator_class";
        $this->setAuthenticatorClassName($_REQUEST[$name]);

        //  network_authenticator_params
        $name = "network_".$this->getId()."_network_authenticator_params";
        $this->setAuthenticatorConstructorParams($_REQUEST[$name]);

        //  is_default_network
        $name = "network_".$this->getId()."_is_default_network";
        if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == 'on')
        $this->setAsDefaultNetwork();

        //  validation_grace_time
        $name = "network_".$this->getId()."_validation_grace_time";
        $this->setValidationGraceTime($_REQUEST[$name]);

        //  validation_email_from_address
        $name = "network_".$this->getId()."_validation_email_from_address";
        $this->setValidationEmailFromAddress($_REQUEST[$name]);

        //  theme_pack
        $name = "network_".$this->getId()."_theme_pack";
        if (!empty ($_REQUEST[$name])) {
            $theme_pack = ThemePack::getObject($_REQUEST[$name]);
        }
        else {
            $theme_pack = null;
        }
        $this->setThemePack($theme_pack);

        //  allow_multiple_login
        $name = "network_".$this->getId()."_allow_multiple_login";
        $this->setMultipleLoginAllowed(empty ($_REQUEST[$name]) ? false : true);

        //  allow_splash_only_nodes
        $name = "network_".$this->getId()."_allow_splash_only_nodes";
        $this->setSplashOnlyNodesAllowed(empty ($_REQUEST[$name]) ? false : true);

        //  allow_custom_portal_redirect
        $name = "network_".$this->getId()."_allow_custom_portal_redirect";
        $this->setCustomPortalRedirectAllowed(empty ($_REQUEST[$name]) ? false : true);

        //  'allow_original_URL_redirect
        $name = "network_".$this->getId()."_allow_original_URL_redirect";
        $this->setPortalOriginalUrlAllowed(empty ($_REQUEST[$name]) ? false : true);

        /*
         * Dynamic abuse control
         */
        $html_dynamic_abuse_control = array();
        $permArray=null;
        $permArray[]=array(Permission::P('NETWORK_PERM_EDIT_DYNAMIC_ABUSE_CONTROL'), $this);
        if (Security::hasAnyPermission($permArray)) {
            //  connection_limit_window
            $name = "network_" . $this->getId() . "_connection_limit_window";
            $this->setConnectionLimitWindow($_REQUEST[$name]);

            //  connection_limit_network_max_total_bytes
            $name = "network_" . $this->getId() . "_connection_limit_network_max_total_bytes";
            $this->setConnectionLimitNetworkMaxTotalBytes($_REQUEST[$name]);

            //  connection_limit_network_max_usage_duration
            $name = "network_" . $this->getId() . "_connection_limit_network_max_usage_duration";
            $this->setConnectionLimitNetworkMaxDuration($_REQUEST[$name]);

            //  connection_limit_node_max_total_bytes
            $name = "network_" . $this->getId() . "_connection_limit_node_max_total_bytes";
            $this->setConnectionLimitNodeMaxTotalBytes($_REQUEST[$name]);

            //  connection_limit_node_max_usage_duration
            $name = "network_" . $this->getId() . "_connection_limit_node_max_usage_duration";
            $this->setConnectionLimitNodeMaxDuration($_REQUEST[$name]);
        }
         
        // Access rights
        require_once('classes/Stakeholder.php');
        Stakeholder::processAssignStakeholdersUI($this, $errMsg);
        if(!empty($errMsg)) {
            echo $errMsg;
        }

        // GIS data
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
            $gis_lat_name = "network_".$this->getId()."_gis_latitude";
            $gis_long_name = "network_".$this->getId()."_gis_longitude";
            $gis_alt_name = "network_".$this->getId()."_gis_altitude";
            $this->setGisLocation(new GisPoint($_REQUEST[$gis_lat_name], $_REQUEST[$gis_long_name], $_REQUEST[$gis_alt_name]));

            $name = "network_".$this->getId()."_gmaps_map_type";
            $this->setGisMapType($_REQUEST[$name]);
        }

        // Profile templates
        $name = "network_".$this->_id."_profile_templates";
        ProfileTemplate :: processLinkedProfileTemplateUI($name, 'network_has_profile_templates', 'network_id', $this->_id);

        // Node creation
        $new_node = Node :: processCreateNewObjectUI();
        if ($new_node) {
            $url = GENERIC_OBJECT_ADMIN_ABS_HREF."?".http_build_query(array ("object_class" => "Node", "action" => "edit", "object_id" => $new_node->getId()));
            header("Location: {$url}");
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

        $content_id = $db->escapeString($content->getId());
        $sql = "INSERT INTO network_has_content (network_id, content_id) VALUES ('$this->_id','$content_id')";
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

        $content_id = $db->escapeString($content->getId());
        $sql = "DELETE FROM network_has_content WHERE network_id='$this->_id' AND content_id='$content_id'";
        $db->execSqlUpdate($sql, false);
    }


    /**
     * Add a profile template to this network
     *
     * @param object ProfileTemplate object
     *
     * @return void
     *
     * @access public
     */
    public function addProfileTemplate(ProfileTemplate $profile_template)
    {
        $db = AbstractDb::getObject();

        $profile_template_id = $db->escapeString($profile_template->getId());
        $sql = "INSERT INTO network_has_profile_templates (network_id, profile_template_id) VALUES ('$this->_id','$profile_template_id')";
        $db->execSqlUpdate($sql, false);
    }

    /**
     * Remove a profile template to this network
     *
     * @param object ProfileTemplate object
     *
     * @return void
     *
     * @access public
     */
    public function removeProfileTemplate(ProfileTemplate $profile_template)
    {
        $db = AbstractDb::getObject();

        $profile_template_id = $db->escapeString($profile_template->getId());
        $sql = "DELETE FROM network_has_profile_templates WHERE network_id='$this->_id' AND profile_template_id='$profile_template_id'";
        $db->execSqlUpdate($sql, false);
    }

    /**Get an array of all ProfileTemplates linked to this network
     * @return an array of ProfileTemplates or an empty arrray */
    function getAllProfileTemplates() {
        $db = AbstractDb::getObject();
        $retval = array ();
        $profile_template_rows = null;
        $sql = "SELECT profile_template_id FROM network_has_profile_templates WHERE network_id='$this->_id'";
        $db->execSql($sql, $profile_template_rows, false);
        if ($profile_template_rows != null) {
            foreach ($profile_template_rows as $profile_template_row) {
                $retval[] = ProfileTemplate :: getObject($profile_template_row['profile_template_id']);
            }
        }
        return $retval;
    }

    /**
     * Delete this Object form the it's storage mechanism
     *
     * @param string &$errmsg Returns an explanation of the error on failure
     *
     * @return bool true on success, false on failure or access denied
     *
     * @access public
     */
    public function delete(& $errmsg)
    {
        // Init values
        $retval = false;
        Security::requirePermission(Permission::P('NETWORK_PERM_DELETE_NETWORK'), $this);
        if ($this->isDefaultNetwork() === true) {
            $errmsg = _('Cannot delete default network, create another one and select it before you remove this one.');
        } else {
            $db = AbstractDb::getObject();
            $id = $db->escapeString($this->getId());
            if (!$db->execSqlUpdate("DELETE FROM networks WHERE network_id='{$id}'", false)) {
                $errmsg = _('Could not delete network!');
            } else {
                $retval = true;
            }
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
        $this->__construct($this->_id);
    }

    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
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
        return $items;
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
        if (!$net) {
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
        $smarty->assign('networkNumNonMonitoredNodes', $net ? $net->getNumOnlineNodes(true) : 0);
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
