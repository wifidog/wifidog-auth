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
require_once('classes/GenericObject.php');
require_once('classes/Content.php');
require_once('classes/User.php');
require_once('classes/Node.php');
require_once('classes/GisPoint.php');
require_once('classes/Cache.php');
require_once('classes/ThemePack.php');


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
class Network implements GenericObject
{
    /**
     * The network Id
     *
     * @var string
     *
     * @access private
     */
    private $id;

    private $mRow;

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
    public static function getObject($id)
    {
        return new self($id);
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

    /**
     * Get the default network
     *
     * @param bool $real_network_only Return a real network only?
     *
     * @return object A Network object, NEVER returns null.
     *
     * @static
     * @access public
     */
    public static function getDefaultNetwork($real_network_only = false)
    {
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
     * Create a new Content object in the database
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

     */
    public static function getSelectNetworkUI($user_prefix, $pre_selected_network = null, $additional_where = null)
    {
        $html = '';
        $name = $user_prefix;

        if ($pre_selected_network) {
            $selected_id = $pre_selected_network->getId();
        } else {
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
            $html .= _("Network:")." \n";
            $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, false);

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
     * @return mixed The network object or an exception

     */
    public static function processSelectNetworkUI($user_prefix)
    {
        $name = "{$user_prefix}";

        if (!empty ($_REQUEST[$name])) {
            return new self($_REQUEST[$name]);
        } else {
            throw new exception(sprintf(_("Unable to retrieve the selected network, the %s REQUEST parameter does not exist"), $name));
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
                try {
                    if (!User::getCurrentUser()->isSuperAdmin()) {
                        throw new Exception(_("Access denied"));
                    }
                } catch (Exception $e) {
                    $ui = new MainUI();
                    $ui->setToolSection('ADMIN');
                    $ui->displayError($e->getMessage(), false);
                    exit;
                }

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
        // Define globals
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

    /**
     * Retreives the id of the object
     *
     * @return string The id
     *
     * @access public
     */
    public function getId()
    {
        return $this->id;
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
        return $this->mRow['tech_support_email'];
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
            global $db;
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
        return $this->mRow['name'];
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
            global $db;
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
        if (!empty ($this->mRow['theme_pack'])) {
            return ThemePack::getObject($this->mRow['theme_pack']);
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
     *
     * @access public
     */
    public function getCreationDate()
    {
        return $this->mRow['creation_date'];
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

    /**
     * Retreives the network's homepage url
     *
     * @return string The id
     *
     * @access public
     */
    public function getHomepageURL()
    {
        return $this->mRow['homepage_url'];
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
    public function setHomepageURL($value)
    {
        // Init values
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
     */
    public function getAuthenticatorClassName()
    {
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
     */
    public function setAuthenticatorClassName($value)
    {
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
     */
    public function getAuthenticatorConstructorParams()
    {
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
     */
    public function setAuthenticatorConstructorParams($value)
    {
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
     */
    public function getAuthenticator()
    {
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

     */
    public static function getAvailableAuthenticators()
    {
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

     */
    public static function getSelectAuthenticator($user_prefix, $pre_selected_authenticator = null)
    {
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
     * @return bool True or false
     *
     * @access public
     */
    public function isDefaultNetwork()
    {
        ($this->mRow['is_default_network'] == 't') ? $retval = true : $retval = false;
        return $retval;
    }

    /**
     * Set as the default network.
     *
     * The can only be one default network, so this method will unset
     * is_default_network for all other network
     *
     * @return bool True on success, false on failure
     *
     * @access public
     */
    public function setAsDefaultNetwork()
    {
        // Init values
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

    /**
     * Retreives the network's validation grace period
     *
     * @return int Network's validation grace period in seconds
     *
     * @access public
     */
    public function getValidationGraceTime()
    {
        return $this->mRow['validation_grace_time_seconds'];
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
            global $db;
            $value = $db->escapeString($value);
            $retval = $db->execSqlUpdate("UPDATE networks SET validation_grace_time = '{$value} seconds' WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Retreives the FROM adress of the validation email
     *
     * @return string A string
     *
     * @access public
     */
    public function getValidationEmailFromAddress()
    {
        return $this->mRow['validation_email_from_address'];
    }

    /**
     * Set the FROM adress of the validation email
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
            global $db;
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
        return ($this->mRow['allow_multiple_login'] == 't') ? true : false;
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
            global $db;
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
        return (($this->mRow['allow_splash_only_nodes'] == 't') ? true : false);
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
     */
    public function getGisLocation()
    {
        return new GisPoint($this->mRow['gmaps_initial_latitude'], $this->mRow['gmaps_initial_longitude'], $this->mRow['gmaps_initial_zoom_level']);
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
     */
    public function getGisMapType()
    {
        return $this->mRow['gmaps_map_type'];
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

     */
    public static function getSelectGisMapType($user_prefix, $pre_selected_map_type = "G_NORMAL_MAP")
    {
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
     */
    public function getNumUsers()
    {
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
     */
    public function getNumValidUsers()
    {
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
     */
    public function getNumOnlineUsers()
    {
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
     */
    public function getNumNodes()
    {
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
     */
    public function getNumDeployedNodes()
    {
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
     * @param bool $nonMonitoredOnly Return number of non-monitored nodes only
     *
     * @return int Number of deployed nodes which are online
     */
    public function getNumOnlineNodes($nonMonitoredOnly = false)
    {
        // Define globals
        global $db;

        // Init values
        $_retval = 0;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects (valid for 5 minutes)
        if ($nonMonitoredOnly) {
            $_cache = new Cache('network_'.$this->id.'_num_online_nodes_non_monitored', $this->id, 300);
        } else {
            $_cache = new Cache('network_'.$this->id.'_num_online_nodes', $this->id, 300);
        }

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

            if ($nonMonitoredOnly) {
                $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$_network_id' AND node_deployment_status = 'NON_WIFIDOG_NODE' AND ((NOW()-last_heartbeat_timestamp) >= interval '5 minutes')", $_row, false);
            } else {
                $db->execSqlUniqueRes("SELECT COUNT(node_id) FROM nodes WHERE network_id = '$_network_id' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') AND ((NOW()-last_heartbeat_timestamp) < interval '5 minutes')", $_row, false);
            }

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
     * Are nodes allowed to redirect users to an arbitrary web page instead of
     * the portal?
     *
     * @return bool True or false
     *
     * @access public
     */
    public function getCustomPortalRedirectAllowed()
    {
        return (($this->mRow['allow_custom_portal_redirect'] == 't') ? true : false);
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
            global $db;
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE networks SET allow_custom_portal_redirect = {$value} WHERE network_id = '{$this->getId()}'", false);
            $this->refresh();
        }

        return $retval;
    }

    /**
     * Does the user have admin access to this network?
     *
     * @return bool true our false
     *
     * @access public
     */
    public function hasAdminAccess(User $user)
    {
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
     */
    public function getAdminUI()
    {
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
        $name = "network_".$this->id."_content";
        $data = Content::getLinkedContentUI($name, "network_has_content", "network_id", $this->id, $display_page = "portal");
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
        $data = DateTime::getSelectDateTimeUI(new DateTime($this->getCreationDate()), "network_" . $this->getId() . "_creation_date", DateTime::INTERFACE_DATETIME_FIELD, "network_creation_date_input");
		$html_network_information[] = InterfaceElements::generateAdminSectionContainer("network_creation_date", $title, $data);

        // homepage_url
		$title = _("Network's web site");
		$data = InterfaceElements::generateInputText("network_" . $this->getId() . "_homepage_url", $this->getHomepageURL(), "network_homepage_url_input");
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
        $help = _("The explicit parameters to be passed to the authenticator. Example: 'my_network_id', '192.168.0.11', 1812, 1813, 'secret_key', 'CHAP_MD5'");
		$data = InterfaceElements::generateInputText("network_" . $this->getId() . "_network_authenticator_params", $this->getAuthenticatorConstructorParams(), "network_network_authenticator_params_input");
		$html_network_authentication[] = InterfaceElements::generateAdminSectionContainer("network_network_authenticator_params", $title, $data, $help);

		// Build section
		$html .= InterfaceElements::generateAdminSectionContainer("network_authentication", _("Network Authentication"), implode(null, $html_network_authentication));

        /*
         * Network properties
         */
		$html_network_properties = array();

        //  is_default_network
		$title = _("Is this network the default network?");
		$data = InterfaceElements::generateInputCheckbox("network_" . $this->getId() . "_is_default_network", "", _("Yes"), $this->isDefaultNetwork(), "network_is_default_network_radio");
		$html_network_properties[] = InterfaceElements::generateAdminSectionContainer("network_is_default_network", $title, $data);

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
		$title = _("This will be the from adress of the validation email");
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
		 * Access management
		 */
		$html_access_rights = array();

        //	network_stakeholders
		$title = _("Network stakeholders");
        $data = "WRITEME!";
		$html_access_rights[] = InterfaceElements::generateAdminSectionContainer("network_stakeholders", $title, $data);

		// Build section
		$html .= InterfaceElements::generateAdminSectionContainer("network_access_rights", _("Access rights"), implode(null, $html_access_rights));

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

    		$title = _("Latitude");
    		$help = _("Center latitude for your the area of your wireless network");
    		$data = InterfaceElements::generateInputText($gis_lat_name, $gis_lat_value, "network_gis_latitude_input");
    		$html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_latitude", $title, $data, $help);

    		$title = _("Longitude");
    		$help = _("Center longitude for your the area of your wireless network");
    		$data = InterfaceElements::generateInputText($gis_long_name, $gis_long_value, "network_gis_longitude_input");
    		$html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_longitude", $title, $data, $help);

    		$title = _("Zoomlevel");
    		$help = _("Zoomlevel of the Google Map for your the area of your wireless network");
    		$data = InterfaceElements::generateInputText($gis_alt_name, $gis_alt_value, "network_gis_altitude_input");
    		$html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gis_altitude", $title, $data, $help);

    		$title = _("Map type");
    		$help = _("Default Google Map type for your the area of your wireless network");
    		$data = $this->getSelectGisMapType("network_" . $this->getId() . "_gmaps_map_type", $this->getGisMapType());
    		$html_network_gis_data[] = InterfaceElements::generateAdminSectionContainer("network_gmaps_map_type", $title, $data, $help);

    		// Build section
    		$html .= InterfaceElements::generateAdminSectionContainer("network_gis_data", _("GIS data"), implode(null, $html_network_gis_data));
        }

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
        $user = User::getCurrentUser();

            if (!$this->hasAdminAccess($user)) {
                throw new Exception(_('Access denied!'));
            }
            
        // Content management
        $name = "network_".$this->id."_content";
        Content :: processLinkedContentUI($name, 'network_has_content', 'network_id', $this->id);

        // name
        $name = "network_".$this->getId()."_name";
        $this->setName($_REQUEST[$name]);

        // creation_date
        $name = "network_".$this->getId()."_creation_date";
        $this->setCreationDate($_REQUEST[$name]);

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
        if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
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
        // Define globals
        global $db;

        $content_id = $db->escapeString($content->getId());
        $sql = "INSERT INTO network_has_content (network_id, content_id) VALUES ('$this->id','$content_id')";
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
        // Define globals
        global $db;

        $content_id = $db->escapeString($content->getId());
        $sql = "DELETE FROM network_has_content WHERE network_id='$this->id' AND content_id='$content_id'";
        $db->execSqlUpdate($sql, false);
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

        $user = User :: getCurrentUser();
        if (!$user->isSuperAdmin()) {
            $errmsg = _('Access denied (must have super admin access)');
        } else {
            if ($this->isDefaultNetwork() === true) {
                $errmsg = _('Cannot delete default network, create another one and select it before remove this one.');
            } else {
                global $db;
                $id = $db->escapeString($this->getId());
                if (!$db->execSqlUpdate("DELETE FROM networks WHERE network_id='{$id}'", false)) {
                    $errmsg = _('Could not delete network!');
                } else {
                    $retval = true;
                }
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
        $this->__construct($this->id);
    }

    /**
     * Assigns values about network to be processed by the Smarty engine.
     *
     * @param object $smarty Smarty object
     * @param object $net    Network object
     *
     * @return void
     *
     * @static
     * @access public
     */
    public static function assignSmartyValues($smarty, $net = null)
    {
        if (!$net) {
            $net = Network::getCurrentNetwork();
        }

        // Set network details
        $smarty->assign('networkName', $net ? $net->getName() : '');
        $smarty->assign('networkHomepageURL', $net ? $net->getHomepageURL() : '');

        // Set networks usage information
        $smarty->assign('networkNumValidUsers', $net ? $net->getNumValidUsers() : 0);
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
