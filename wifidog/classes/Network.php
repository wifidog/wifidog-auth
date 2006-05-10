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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/GenericObject.php');
require_once ('classes/Content.php');
require_once ('classes/User.php');
require_once ('classes/Node.php');
require_once ('classes/GisPoint.php');
require_once ('classes/Cache.php');
require_once ('classes/ThemePack.php');

/**
 * Abstract a Network.
 *
 * A network is an administrative entity with it's own users, nodes and authenticator.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class Network implements GenericObject {
    private $id; /**< The network id */
    private $mRow;

    /** Get an instance of the object
    * @see GenericObject
    * @param $id The object id
    * @return the Content object, or null if there was an error (an exception is also thrown)
    */
    static public function getObject($id) {
        return new self($id);
    }

    /** Get all the Networks configured on this server
     * @return an array of Network objects.  The default network is returned
     * first
     */
    static function getAllNetworks() {
        $retval = array ();
        global $db;
        $sql = "SELECT network_id FROM networks ORDER BY is_default_network DESC";
        $network_rows = null;
        $db->execSql($sql, $network_rows, false);
        if ($network_rows == null) {
            throw new Exception(_("Network::getAllNetworks:  Fatal error: No networks in the database!"));
        }
        foreach ($network_rows as $network_row) {
            $retval[] = new self($network_row['network_id']);
        }
        return $retval;
    }

    /** Get the default network
     * @return a Network object, NEVER returns null.
     */
    static function getDefaultNetwork($real_network_only = false) {
        $retval = null;
        global $db;
        $sql = "SELECT network_id FROM networks WHERE is_default_network=TRUE ORDER BY creation_date LIMIT 1";
        $network_row = null;
        $db->execSqlUniqueRes($sql, $network_row, false);
        if ($network_row == null) {
            throw new Exception(_("Network::getDefaultNetwork:  Fatal error: Unable to find the default network!"));
        }
        $retval = new self($network_row['network_id']);
        return $retval;
    }

    /** Get the current network for which the portal is displayed or to which a user is physically connected.
     * @param $real_network_only NOT IMPLEMENTED YET true or false.  If true,
     * the real physical network where the user is connected is returned, and
     * the node set by setCurrentNode is ignored.
     * @return a Network object, NEVER returns null.
     */
    static function getCurrentNetwork($real_network_only = false) {
        $retval = null;
        $current_node = Node :: getCurrentNode();
        if ($current_node != null) {
            $retval = $current_node->getNetwork();
        }
        else {
            $retval = Network :: getDefaultNetwork();
        }
        return $retval;
    }

    /** Create a new Content object in the database
     * @see GenericObject
     * @param $network_id The network id of the new network.  If absent, will be
     * assigned a guid.
     * @return the newly created object, or null if there was an error
     */
    static function createNewObject($network_id = null) {
        global $db;
        if (empty ($network_id)) {
            $network_id = get_guid();
        }
        $network_id = $db->escapeString($network_id);

        $sql = "INSERT INTO networks (network_id, network_authenticator_class) VALUES ('$network_id', 'AuthenticatorLocalUser')";

        if (!$db->execSqlUpdate($sql, false)) {
            throw new Exception(_('Unable to insert the new network in the database!'));
        }
        $object = new self($network_id);
        return $object;

    }

    /**
     * Get an interface to pick a network
     *
     * If there is only one network available, no interface is actually shown
     *
     * @param string $user_prefix          A identifier provided by the
     *                                     programmer to recognise it's
     *                                     generated html form
     * @param object $pre_selected_network Network object: The network to be
     *                                     pre-selected in the form object
     * @param string $additional_where     Additional SQL conditions for the
     *                                     networks to select
     *
     * @return string HTML markup
     *
     * @static
     * @access public
     */
    public static function getSelectNetworkUI($user_prefix, $pre_selected_network = null, $additional_where = null) {
        $html = '';
        $name = $user_prefix;
        $html .= _("Network:")." \n";

        if ($pre_selected_network) {
            $selected_id = $pre_selected_network->getId();
        }
        else {
            $selected_id = null;
        }
        global $db;
        $additional_where = $db->escapeString($additional_where);
        $sql = "SELECT network_id, name FROM networks WHERE 1=1 $additional_where ORDER BY is_default_network DESC";
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
            $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, false);

        }
        else {
            foreach ($network_rows as $network_row) //iterates only once...
                {
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
     * @return mixed The network object or an exception
     *
     * @static
     * @access public
     */
    public static function processSelectNetworkUI($user_prefix) {
        $name = "{$user_prefix}";
        if (!empty ($_REQUEST[$name]))
            return new self($_REQUEST[$name]);
        else
            throw new exception(sprintf(_("Unable to retrieve the selected network, the %s REQUEST parameter does not exist"), $name));
    }

    /** Get an interface to create a new network.
    * @return html markup
    */
    public static function getCreateNewObjectUI() {
        $html = '';
        $html .= _("Create a new network with ID")." \n";
        $name = "new_network_id";
        $html .= "<input type='text' size='10' name='{$name}'>\n";
        return $html;

    }

    /** Process the new object interface.
     *  Will return the new object if the user has the credentials and the form was fully filled.
     * @return the Network object or null if no new Network was created.
     */
    static function processCreateNewObjectUI() {
        $retval = null;
        $name = "new_network_id";
        if (!empty ($_REQUEST[$name])) {
            $network_id = $_REQUEST[$name];
            if ($network_id) {
                if (!User :: getCurrentUser()->isSuperAdmin()) {
                    throw new Exception(_("Access denied"));
                }
                $retval = self :: createNewObject($network_id);
            }
        }
        return $retval;
    }

    private function __construct($p_network_id) {
        global $db;

        $network_id_str = $db->escapeString($p_network_id);
        $sql = "SELECT *, EXTRACT(EPOCH FROM validation_grace_time) as validation_grace_time_seconds FROM networks WHERE network_id='$network_id_str'";
        $row = null;
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            throw new Exception("The network with id $network_id_str could not be found in the database");
        }
        $this->mRow = $row;
        $this->id = $db->escapeString($row['network_id']);
    }

    /** Retreives the id of the object
     * @return The id */
    public function getId() {
        return $this->id;
    }

    /** Retreives the network name
     * @return The id */
    public function getTechSupportEmail() {
        return $this->mRow['tech_support_email'];
    }

    /** Set the network's tech support and information email address
     * @param $value The new value
     * @return true on success, false on failure */
    function setTechSupportEmail($value) {
        $retval = true;
        if ($value != $this->getName()) {
            global $db;
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET tech_support_email = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /**
     * Retrieves the network name
     * @return A string
     */
    public function getName() {
        return $this->mRow['name'];
    }

    /** Set the network's name
     * @param $value The new value
     * @return true on success, false on failure
     */
    function setName($value) {
        $retval = true;
        if ($value != $this->getName()) {
            global $db;
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET name = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /**
     * Retrieves the network's theme pack
     * @return ThemePack or null
     */
    public function getThemePack() {
        if (!empty ($this->mRow['theme_pack'])) {
            return ThemePack::getObject($this->mRow['theme_pack']);
        }
        else {
            return null;
        }
    }

    /** Set the network's name
     * @param $value The new ThemePack, or null
     * @return true on success, false on failure
     */
    function setThemePack($value) {
        $retval = true;
        if ($value != $this->getThemePack()) {
            global $db;
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
     */
    public function getCreationDate() {
        return $this->mRow['creation_date'];
    }

    /**
     * Set the network's creation date
     *
     * @param string $value The new creation date
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setCreationDate($value) {
        // Define globals
        global $db;

        // Init values
        $_retVal = true;

        if ($value != $this->getCreationDate()) {
            $value = $db->escapeString($value);
            $_retVal = $db->execSqlUpdate("UPDATE networks SET creation_date = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $_retVal;
    }

    /** Retreives the network's homepage url
     * @return The id */
    public function getHomepageURL() {
        return $this->mRow['homepage_url'];
    }

    /** Set the network's homepage url
     * @param $value The new value
     * @return true on success, false on failure */
    function setHomepageURL($value) {
        $retval = true;
        if ($value != $this->getName()) {
            global $db;
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
     *
     * @access public
     */
    public function getAuthenticatorClassName() {
        return $this->mRow['network_authenticator_class'];
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
     *
     * @access public
     */
    public function setAuthenticatorClassName($value) {
        // Define globals
        global $db;

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
     *
     * @access public
     */
    public function getAuthenticatorConstructorParams() {
        return $this->mRow['network_authenticator_params'];
    }

    /**
     * The explicit parameters to be passed to the authenticator's constructor
     * (ex: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key',
     * 'CHAP_MD5')
     *
     * @param string $value The new value
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setAuthenticatorConstructorParams($value) {
        // Define globals
        global $db;

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
     *
     * @access public
     */
    public function getAuthenticator() {
        require_once ('classes/Authenticator.php');

        // Include only the authenticator we are about to use
        require_once ("classes/Authenticators/".$this->mRow['network_authenticator_class'].".php");

        if (strpos($this->mRow['network_authenticator_params'], ';') != false) {
            throw new Exception("Network::getAuthenticator(): Security error: The parameters passed to the constructor of the authenticator are potentially unsafe");
        }

        return call_user_func_array(array (new ReflectionClass($this->mRow['network_authenticator_class']), 'newInstance'), explode(",", str_replace(array ("'", '"'), "", str_replace(", ", ",", $this->mRow['network_authenticator_params']))));
    }

    /**
     * Get the list of available Authenticators on the system
     *
     * @return array An array of class names
     *
     * @static
     * @access public
     */
    public static function getAvailableAuthenticators() {
        // Init values
        $_authenticators = array ();
        $_useCache = false;
        $_cachedData = null;

        // Create new cache object with a lifetime of one week
        $_cache = new Cache("AuthenticatorClasses", "ClassFileCaches", 604800);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData("mixed");

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_authenticators = $_cachedData;
            }
        }

        if (!$_useCache) {
            $_dir = WIFIDOG_ABS_FILE_PATH."classes/Authenticators";
            $_dirHandle = @ opendir($_dir);

            if ($_dirHandle) {
                // Loop over the directory
                while (false !== ($_filename = readdir($_dirHandle))) {
                    // Loop through sub-directories of Content
                    if ($_filename != '.' && $_filename != '..') {
                        $_matches = null;

                        if (preg_match("/^(.*)\.php$/", $_filename, $_matches) > 0) {
                            // Only add files containing a corresponding Authenticator class
                            if (is_file("{$_dir}/{$_matches[0]}")) {
                                $_authenticators[] = $_matches[1];
                            }
                        }
                    }
                }

                closedir($_dirHandle);
            }
            else {
                throw new Exception(_('Unable to open directory ').$_dir);
            }

            // Sort the result array
            sort($_authenticators);

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save results into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_authenticators, "mixed");
            }
        }

        return $_authenticators;
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
     *
     * @static
     * @access public
     */
    public static function getSelectAuthenticator($user_prefix, $pre_selected_authenticator = null) {
            // Define globals
    global $db;

        // Init values
        $_authenticators = array ();

        foreach (self :: getAvailableAuthenticators() as $_authenticator) {
            $_authenticators[] = array ($_authenticator, $_authenticator);
        }

        $_name = $user_prefix;

        if ($pre_selected_authenticator) {
            $_selectedID = $pre_selected_authenticator;
        }
        else {
            $_selectedID = null;
        }

        $_html = FormSelectGenerator :: generateFromArray($_authenticators, $_selectedID, $_name, null, false);

        return $_html;
    }

    /**
     * Is the network the default network?
     *
     * @return true or false
     */
    public function isDefaultNetwork() {
        ($this->mRow['is_default_network'] == 't') ? $retval = true : $retval = false;
        return $retval;
    }

    /** Set as the default network.  The can only be one default network, so this method will unset is_default_network for all other network
     * @return true on success, false on failure */
    function setAsDefaultNetwork() {
        $retval = true;
        if (!$this->isDefaultNetwork()) {
            global $db;
            $sql = "UPDATE networks SET is_default_network = FALSE;\n";
            $sql .= "UPDATE networks SET is_default_network = TRUE WHERE network_id = '{$this->getId()}';\n";
            $retval = $db->execSqlUpdate($sql, false);
            $this->refresh();
        }
        return $retval;
    }

    /** Retreives the network's validation grace period
     * @return An integer (seconds) */
    public function getValidationGraceTime() {
        return $this->mRow['validation_grace_time_seconds'];
    }

    /** Set the network's validation grace period in seconds.  A new user is granted Internet access for this period check his email and validate his account.
     * @param $value The new value
     * @return true on success, false on failure */
    function setValidationGraceTime($value) {
        $retval = true;
        if ($value != $this->getValidationGraceTime()) {
            global $db;
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET validation_grace_time = '{$value} seconds' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /** Retreives the FROM adress of the validation email
     * @return A string */
    public function getValidationEmailFromAddress() {
        return $this->mRow['validation_email_from_address'];
    }

    /** Set the FROM adress of the validation email
     * @param $value The new value
     * @return true on success, false on failure */
    function setValidationEmailFromAddress($value) {
        $retval = true;
        if ($value != $this->getValidationEmailFromAddress()) {
            global $db;
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET validation_email_from_address = '{$value}' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /** Can an account be connected more than once at the same time?
     * @return true or false */
    public function getMultipleLoginAllowed() {
        return ($this->mRow['allow_multiple_login'] == 't') ? true : false;
    }

    /** Set if a account be connected more than once at the same time?
     * @param $value The new value, true or false
     * @return true on success, false on failure */
    function setMultipleLoginAllowed($value) {
        $retval = true;
        if ($value != $this->getMultipleLoginAllowed()) {
            global $db;
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_multiple_login = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /** Are nodes allowed to be set as splash-only (no login)?
     * @return true or false */
    public function getSplashOnlyNodesAllowed() {
        return (($this->mRow['allow_splash_only_nodes'] == 't') ? true : false);
    }

    /** Set if nodes are allowed to be set as splash-only (no login)
     * @param $value The new value, true or false
     * @return true on success, false on failure */
    function setSplashOnlyNodesAllowed($value) {
        $retval = true;
        if ($value != $this->getSplashOnlyNodesAllowed()) {
            global $db;
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
     *
     * @access public
     */
    public function getGisLocation() {
        return new GisPoint($this->mRow['gmaps_initial_latitude'], $this->mRow['gmaps_initial_longitude'], $this->mRow['gmaps_initial_zoom_level']);
    }

    /**
     * Set the network's GisPoint object
     *
     * @param $value The new GisPoint object
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setGisLocation($pt) {
        // Define globals
        global $db;

        if (!empty ($pt)) {
            $lat = $db->escapeString($pt->getLatitude());
            $long = $db->escapeString($pt->getLongitude());
            $alt = $db->escapeString($pt->getAltitude());

            if (!empty ($lat) && !empty ($long) && !empty ($alt)) {
                $db->execSqlUpdate("UPDATE networks SET gmaps_initial_latitude = $lat, gmaps_initial_longitude = $long, gmaps_initial_zoom_level = $alt WHERE network_id = '{$this->getId()}'");
            }
            else {
                $db->execSqlUpdate("UPDATE networks SET gmaps_initial_latitude = NULL, gmaps_initial_longitude = NULL, gmaps_initial_zoom_level = NULL WHERE network_id = '{$this->getId()}'");
            }

            $this->refresh();
        }
    }

    /**
     * Retreives the default Google maps type
     *
     * @return string Default Google maps type
     *
     * @access public
     */
    public function getGisMapType() {
        return $this->mRow['gmaps_map_type'];
    }

    /**
     * Set the network's default Google maps type
     *
     * @param $value The new default Google maps type
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setGisMapType($value) {
        // Define globals
        global $db;

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
     *
     * @static
     * @access public
     */
    public static function getSelectGisMapType($user_prefix, $pre_selected_map_type = "G_NORMAL_MAP") {
            // Define globals
    global $db;

        // Init values
        $_map_types = array (array ("G_NORMAL_MAP", _("Map")), array ("G_SATELLITE_MAP", _("Satellite")), array ("G_HYBRID_MAP", _("Hybrid")));

        $_name = $user_prefix;

        if ($pre_selected_map_type) {
            $_selectedID = $pre_selected_map_type;
        }
        else {
            $_selectedID = null;
        }

        $_html = FormSelectGenerator :: generateFromArray($_map_types, $_selectedID, $_name, null, false);

        return $_html;
    }

    /** Get's the splash-only user.  This is the user that people logged-in at a splash-only hotspot will show up as.  This user always has multiple-login capabilities.
    * @param $username The username of the user
    * @param $account_origin The account origin
    * @return a User object
    */
    public function getSplashOnlyUser() {
        $username = 'SPLASH_ONLY_USER';

        $user = User :: getUserByUsernameAndOrigin($username, $this);
        if (!$user) {
            $user = User :: createUser(get_guid(), $username, $this, '', '');
            $user->setAccountStatus(ACCOUNT_STATUS_ALLOWED);
        }
        return $user;
    }

    /**
     * Find out the total number of users in this networks's database
     *
     * @return int Number of users
     *
     * @access public
     */
    public function getNumUsers() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $_cache = new Cache('network_'.$this->id.'_num_users', $this->id, 60);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of users
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_origin='$_network_id'", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /**
     * Find out how many users are valid in this networks's database
     *
     * @return int Number of valid users
     *
     * @access public
     */
    public function getNumValidUsers() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $_cache = new Cache('network_'.$this->id.'_num_valid_users', $this->id, 60);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of valid users
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(user_id) FROM users WHERE account_status = ".ACCOUNT_STATUS_ALLOWED." AND account_origin='$_network_id'", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /**
     * Find out how many users are online on the entire network or at a
     * specific Hotspot on the network
     *
     * @return int Number of online users
     *
     * @access public
     */
    public function getNumOnlineUsers() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 1 minute)
        $_cache = new Cache('network_'.$this->id.'_num_online_users', $this->id, 60);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of online users
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(DISTINCT users.user_id) FROM users,connections NATURAL JOIN nodes JOIN networks ON (nodes.network_id=networks.network_id AND networks.network_id='$_network_id') "."WHERE connections.token_status='".TOKEN_INUSE."' "."AND users.user_id=connections.user_id ", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /**
     * Find out how many nodes are registered in this networks's database
     *
     * @return int Number of nodes
     *
     * @access public
     */
    public function getNumNodes() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        $_cache = new Cache('network_'.$this->id.'_num_nodes', $this->id, 300);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of nodes
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$_network_id'", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /**
     * Find out how many nodes are deployed in this networks's database
     *
     * @return int Number of deployed nodes
     *
     * @access public
     */
    public function getNumDeployedNodes() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        $_cache = new Cache('network_'.$this->id.'_num_deployed_nodes', $this->id, 300);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of deployed nodes
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$_network_id' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE')", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /**
     * Find out how many deployed nodes are online in this networks's database
     *
     * @return int Number of deployed nodes which are online
     *
     * @access public
     */
    public function getNumOnlineNodes() {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        $_cache = new Cache('network_'.$this->id.'_num_online_nodes', $this->id, 300);

        // Check if caching has been enabled.
        if ($_cache->isCachingEnabled) {
            $_cachedData = $_cache->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            }
        }

        if (!$_useCache) {
            // Get number of online nodes
            $_network_id = $db->escapeString($this->id);
            $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$_network_id' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') AND ((NOW()-last_heartbeat_timestamp) < interval '5 minutes')", $_row, false);

            // String has been found
            $_retval = $_row['count'];

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Save data into cache, because it wasn't saved into cache before.
                $_cache->saveCachedData($_retval);
            }
        }

        return $_retval;
    }

    /** Are nodes allowed to redirect users to an arbitrary web page instead of the portal?
     * @return true or false */
    public function getCustomPortalRedirectAllowed() {
        return (($this->mRow['allow_custom_portal_redirect'] == 't') ? true : false);
    }

    /** Set if nodes are allowed to redirect users to an arbitrary web page instead of the portal?
     * @param $value The new value, true or false
     * @return true on success, false on failure */
    function setCustomPortalRedirectAllowed($value) {
        $retval = true;
        if ($value != $this->getCustomPortalRedirectAllowed()) {
            global $db;
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_custom_portal_redirect = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /**
     * Does the user have admin access to this network?
     * @return boolean true our false
     */
    function hasAdminAccess(User $user) {
        // Define globals
        global $db;

        // Init values
        $row = null;
        $retval = false;

        if ($user != null) {
            $user_id = $user->getId();
            $db->execSqlUniqueRes("SELECT * FROM network_stakeholders WHERE is_admin = true AND network_id='{$this->id}' AND user_id='{$user_id}'", $row, false);

            if ($row != null) {
                $retval = true;
            }
            else {
                if ($user->isSuperAdmin()) {
                    $retval = true;
                }
            }
        }

        return $retval;
    }

    /**
     * Get an array of all Content linked to the network
     *
     * @param bool   $exclude_subscribed_content Exclude subscribed content?
     * @param object $subscriber                 The User object used to
     *                                           discriminate the content
     *
     * @return array An array of Content or an empty array
     *
     * @access public
     */
    /*public function getAllContent($exclude_subscribed_content = false, $subscriber = null)
    {
        // Define globals
    	global $db;
    
        // Init values
        $content_rows = null;
    	$retval = array ();
    
    	// Get all network, but exclude user subscribed content if asked
    	if ($exclude_subscribed_content == true && $subscriber) {
    		$sql = "SELECT content_id FROM network_has_content WHERE network_id='$this->id' AND content_id NOT IN (SELECT content_id FROM user_has_content WHERE user_id = '{$subscriber->getId()}') ORDER BY subscribe_timestamp DESC";
    	} else {
    		$sql = "SELECT content_id FROM network_has_content WHERE network_id='$this->id' ORDER BY subscribe_timestamp DESC";
    	}
    
    	$db->execSql($sql, $content_rows, false);
    
    	if ($content_rows != null) {
    		foreach ($content_rows as $content_row) {
    			$retval[] = Content :: getObject($content_row['content_id']);
    		}
    	}
    
    	return $retval;
    }
    */
    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getAdminUI() {
        $html = '';
        $html .= "<h3>"._("Network management")."</h3>\n";
        $html .= "<div class='admin_container'>\n";
        $html .= "<div class='admin_class'>Network (".get_class($this)." instance)</div>\n";

        // network_id
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network ID")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $value = htmlspecialchars($this->getId(), ENT_QUOTES);
        $html .= $value;
        $html .= "</div>\n";
        $html .= "</div>\n";

        // creation_date
        $name = "network_".$this->getId()."_creation_date";
        $value = htmlspecialchars($this->getCreationDate(), ENT_QUOTES);

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network creation date").":</div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        // name
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network name")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_name";
        $value = htmlspecialchars($this->getName(), ENT_QUOTES);
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        // homepage_url
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network's web site")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_homepage_url";
        $value = htmlspecialchars($this->getHomepageURL(), ENT_QUOTES);
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        // tech_support_email
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Technical support email")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_tech_support_email";
        $value = htmlspecialchars($this->getTechSupportEmail(), ENT_QUOTES);
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  network_authenticator_class
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network authenticator class.  The subclass of Authenticator to be used for user authentication (ex: AuthenticatorRadius)")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_network_authenticator_class";
        $value = htmlspecialchars($this->getAuthenticatorClassName(), ENT_QUOTES);
        $html .= $this->getSelectAuthenticator($name, $value);
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  network_authenticator_params
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("The explicit parameters to be passed to the authenticator (ex: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key', 'CHAP_MD5')")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_network_authenticator_params";
        $value = htmlspecialchars($this->getAuthenticatorConstructorParams(), ENT_QUOTES);
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  is_default_network
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Is this network the default network?")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_is_default_network";
        $this->isDefaultNetwork() ? $checked = 'CHECKED' : $checked = '';
        $html .= "<input type='checkbox' name='$name' $checked>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  validation_grace_time
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("The length of the validation grace period in seconds.  A new user is granted Internet access for this period check his email and validate his account.")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_validation_grace_time";
        $value = htmlspecialchars($this->getValidationGraceTime(), ENT_QUOTES);
        $html .= "<input type='text' size ='5' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  validation_email_from_address
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("This will be the from adress of the validation email")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_validation_email_from_address";
        $value = htmlspecialchars($this->getValidationEmailFromAddress(), ENT_QUOTES);
        $html .= "<input type='text' size ='50' value='$value' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  theme_pack
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Selected theme pack for this network")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_theme_pack";
        $html .= ThemePack :: getSelectUI($name, $this->getThemePack());
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  allow_multiple_login
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Can an account be connected more than once at the same time?")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_allow_multiple_login";
        $this->getMultipleLoginAllowed() ? $checked = 'CHECKED' : $checked = '';
        $html .= "<input type='checkbox' name='$name' $checked>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  allow_splash_only_nodes
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Are nodes allowed to be set as splash-only (no login)?")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_allow_splash_only_nodes";
        $this->getSplashOnlyNodesAllowed() ? $checked = 'CHECKED' : $checked = '';
        $html .= "<input type='checkbox' name='$name' $checked>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //  allow_custom_portal_redirect
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Are nodes allowed to redirect users to an arbitrary web page instead of the portal?")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $name = "network_".$this->getId()."_allow_custom_portal_redirect";
        $this->getCustomPortalRedirectAllowed() ? $checked = 'CHECKED' : $checked = '';
        $html .= "<input type='checkbox' name='$name' $checked>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";

        //	network_stakeholders
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Network stakeholders")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        //$name = "network_".$this->getId()."_allow_custom_portal_redirect";
        //$this->getCustomPortalRedirectAllowed()? $checked='CHECKED': $checked='';
        //$html .= "<input type='checkbox' name='$name' $checked>\n";
        $html .= "WRITEME!";
        $html .= "</div>\n";
        $html .= "</div>\n";

        // Build HTML form fields names & values
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED === true) {
            $gis_point = $this->getGisLocation();
            $gis_lat_name = "network_".$this->getId()."_gis_latitude";
            $gis_lat_value = htmlspecialchars($gis_point->getLatitude(), ENT_QUOTES);
            $gis_long_name = "network_".$this->getId()."_gis_longitude";
            $gis_long_value = htmlspecialchars($gis_point->getLongitude(), ENT_QUOTES);
            $gis_alt_name = "network_".$this->getId()."_gis_altitude";
            $gis_alt_value = htmlspecialchars($gis_point->getAltitude(), ENT_QUOTES);

            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Center latitude for your the area of your wireless network")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= "<input type='text' size ='15' value='$gis_lat_value' id='$gis_lat_name' name='$gis_lat_name'>\n";
            $html .= "</div>\n";
            $html .= "</div>\n";

            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Center longitude for your the area of your wireless network")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= "<input type='text' size ='15' value='$gis_long_value' id='$gis_long_name' name='$gis_long_name'>\n";
            $html .= "</div>\n";
            $html .= "</div>\n";

            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Zoomlevel of the Google Map for your the area of your wireless network")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= "<input type='text' size ='15' value='$gis_alt_value' id='$gis_alt_name' name='$gis_alt_name'>\n";
            $html .= "</div>\n";
            $html .= "</div>\n";

            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Default Google Map type for your the area of your wireless network")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= $this->getSelectGisMapType("network_".$this->getId()."_gmaps_map_type", $this->getGisMapType());
            $html .= "</div>\n";
            $html .= "</div>\n";
        }

        // Create new nodes
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("New node ID")." : </div>\n";

        $html .= "<div class='admin_section_data'>\n";

        $html .= Node :: getCreateNewObjectUI($this);

        $html .= "<div class='admin_section_tools'>\n";
        $name = "network_{$this->getId()}_create_node";
        $html .= "<input type='submit' name='{$name}' value='"._("Create a new node")."'>\n";
        $html .= "</div>\n";

        $html .= "</div>\n";
        $html .= "</div>\n";

        // Content management
        $title = _("Network content");
        $name = "network_".$this->id."_content";
        $data = Content :: getLinkedContentUI($name, "network_has_content", "network_id", $this->id, $display_page = "portal");
        $html .= InterfaceElements :: generateAdminSectionContainer("network_content", $title, $data);

        return $html;
    }

    /** Process admin interface of this object.
    */
    public function processAdminUI() {
        //pretty_print_r($_REQUEST);
        $user = User :: getCurrentUser();
        if (!$this->hasAdminAccess($user)) {
            throw new Exception(_('Access denied!'));
        }

        // creation_date
        $name = "network_".$this->getId()."_creation_date";
        $this->setCreationDate($_REQUEST[$name]);

        // name
        $name = "network_".$this->getId()."_name";
        $this->setName($_REQUEST[$name]);

        // homepage_url
        $name = "network_".$this->getId()."_homepage_url";
        $this->setHomepageURL($_REQUEST[$name]);

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

        // GIS data
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED === true) {
            $gis_lat_name = "network_".$this->getId()."_gis_latitude";
            $gis_long_name = "network_".$this->getId()."_gis_longitude";
            $gis_alt_name = "network_".$this->getId()."_gis_altitude";
            $this->setGisLocation(new GisPoint($_REQUEST[$gis_lat_name], $_REQUEST[$gis_long_name], $_REQUEST[$gis_alt_name]));

            $name = "network_".$this->getId()."_gmaps_map_type";
            $this->setGisMapType($_REQUEST[$name]);
        }

        // Node creation
        $new_node = Node :: processCreateNewObjectUI();
        if ($new_node) {
            $url = GENERIC_OBJECT_ADMIN_ABS_HREF."?".http_build_query(array ("object_class" => "Node", "action" => "edit", "object_id" => $new_node->getId()));
            header("Location: {$url}");
        }
        // Content management
        $name = "network_".$this->id."_content";
        Content :: processLinkedContentUI($name, 'network_has_content', 'network_id', $this->id);
    }

    /** Add network-wide content to this network */
    public function addContent(Content $content) {
        global $db;
        $content_id = $db->escapeString($content->getId());
        $sql = "INSERT INTO network_has_content (network_id, content_id) VALUES ('$this->id','$content_id')";
        $db->execSqlUpdate($sql, false);
    }

    /** Remove network-wide content from this network */
    public function removeContent(Content $content) {
        global $db;
        $content_id = $db->escapeString($content->getId());
        $sql = "DELETE FROM network_has_content WHERE network_id='$this->id' AND content_id='$content_id'";
        $db->execSqlUpdate($sql, false);
    }

    /** Delete this Object form the it's storage mechanism
     * @param &$errmsg Returns an explanation of the error on failure
     * @return true on success, false on failure or access denied */
    public function delete(& $errmsg) {
        $retval = false;
        $user = User :: getCurrentUser();
        if (!$user->isSuperAdmin()) {
            $errmsg = _('Access denied (must have super admin access)');
        }
        else {
            if ($this->isDefaultNetwork() === true)
                $errmsg = _('Cannot delete default network, create another one and select it before remove this one.');
            else {
                global $db;
                $id = $db->escapeString($this->getId());
                if (!$db->execSqlUpdate("DELETE FROM networks WHERE network_id='{$id}'", false)) {
                    $errmsg = _('Could not delete network!');
                }
                else {
                    $retval = true;
                }
            }
        }
        return $retval;
    }
    /** Reloads the object from the database.  Should normally be called after a set operation */
    protected function refresh() {
        $this->__construct($this->id);
    }

    public static function assignSmartyValues($smarty, $net = null) {
        if (!$net)
            $net = Network :: getCurrentNetwork();

        $smarty->assign('networkName', $net ? $net->getName() : '');
        $smarty->assign('networkHomepageURL', $net ? $net->getHomepageURL() : '');
        // Set networks usage information
        $smarty->assign('networkNumValidUsers', $net ? $net->getNumValidUsers() : 0);
        $smarty->assign('networkNumOnlineUsers', $net ? $net->getNumOnlineUsers() : 0);

        // Set networks node information
        $smarty->assign('networkNumDeployedNodes', $net ? $net->getNumDeployedNodes() : 0);
        $smarty->assign('networkNumOnlineNodes', $net ? $net->getNumOnlineNodes() : 0);
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
