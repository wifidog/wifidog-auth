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
 * @subpackage Performance
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * PEAR::Cache_Lite implementation
 *
 * @package    WiFiDogAuthServer
 * @subpackage Performance
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 */
class Cache
{

    /**
     * Defines if caching is enabled or not
     *
     * @var bool
     * @access public
     */
    public $isCachingEnabled = false;

    /**
     * Lifetime of cache (value in seconds or null).
     *
     * @var mixed (int or null)
     * @access private
     */
    private $_lifeTime = null;

    /**
     * PEAR::Cache_Lite object.
     *
     * @var object
     * @access private
     */
    private $_cacheLite;

    /**
     * ID of PEAR::Cache_Lite object.
     *
     * @var mixed (string or null)
     * @access private
     */
    private $_cacheID = null;

    /**
     * Group of PEAR::Cache_Lite object.
     *
     * @var string
     * @access private
     */
    private $_cacheGroup = "default";

    /**
     * Constructor.
     *
     * @param string $id    ID of PEAR::Cache_Lite object.
     * @param string $group Group of PEAR::Cache_Lite object.
     *
     * @return void
     *
     * @access public
     */
    public function __construct($id, $group = "default")
    {
        // Check if need to load PEAR::Cache_Lite.
        if ($this->cachingEnabled()) {
            // Proceed if $id is set, only.
            if ($id != null && $id != "") {
                // Load PEAR::Cache_Lite.
                require_once('Cache/Lite.php');

                // Enable caching support.
                $this->isCachingEnabled = true;

                // Set cache properties.
                $_cacheOptions = array(
                    'cacheDir' => WIFIDOG_ABS_FILE_PATH . 'tmp/cache/',
                    'lifeTime' => $this->_lifeTime
                );

                // Create a PEAR::Cache_Lite object.
                $this->_cacheLite = new Cache_Lite($_cacheOptions);

                // Set ID of PEAR::Cache_Lite object.
                $this->_cacheID = $id;

                // Set group of PEAR::Cache_Lite object.
                $this->_cacheGroup = $group;
            }
        }
    }

    /**
     * Return if PEAR::Cache_Lite is available and caching has been enabled.
     *
     * @return bool Caching enabled or disabled.
     *
     * @access private
     */
    private function cachingEnabled()
    {
        // Init values.
        $_doCache = false;
        $_errmsg = "";

        // Check if PEAR::Cache_Lite is available.
        if (Dependencies :: check("Cache", $_errmsg)) {
            // Check if caching has been enabled in config.php or local.config.php.
            if (defined("USE_CACHE_LITE") && USE_CACHE_LITE == true) {
                $_doCache = true;
            }
        }

        return $_doCache;
    }

    /**
     * Return data from cache if it is available and caching has been enabled.
     *
     * @return string Data of cache.
     *
     * @access public
     */
    public function getCachedData()
    {
        // Init values.
        $_cacheData = null;

        // Check if caching has been enabled and if cache identifier has been set.
        if ($this->isCachingEnabled && $this->_cacheID != null && $this->_cacheGroup != null) {
            $_cacheData = $this->_cacheLite->get($this->_cacheID, $this->_cacheGroup);
        }

        return $_cacheData;
    }

    /**
     * Save data into cache if it is available and caching has been enabled.
     *
     * @param string $data Data to be saved into cache.
     *
     * @return bool Saving data into cache was successful or not.
     *
     * @access public
     */
    public function saveCachedData($data)
    {
        // Init values.
        $_cacheData = false;

        // Check if caching has been enabled and if cache identifier has been set.
        if ($this->isCachingEnabled && $this->_cacheID != null && $this->_cacheGroup != null) {
            // Proceed if $data is set, only.
            if ($data != null && $data != "") {
                $_cacheData = $this->_cacheLite->save($data, $this->_cacheID, $this->_cacheGroup);
            }
        }

        return $_cacheData;
    }

    /**
     * Erase specific data from cache if it is available and caching has been
     * enabled.
     *
     * @return bool Removing data from cache was successful or not.
     *
     * @access public
     */
    public function eraseCachedData()
    {
        // Init values.
        $_cacheData = null;

        // Check if caching has been enabled and if cache identifier has been set.
        if ($this->isCachingEnabled && $this->_cacheID != null && $this->_cacheGroup != null) {
            $_cacheData = $this->_cacheLite->remove($this->_cacheID, $this->_cacheGroup);
        }

        return $_cacheData;
    }

    /**
     * Erase a group of data from cache if it is available and caching has been
     * enabled.
     *
     * @return bool Removing data from cache was successful or not.
     *
     * @access public
     */
    public function eraseCachedGroupData()
    {
        // Init values.
        $_cacheData = null;

        // Check if caching has been enabled and if cache identifier has been set.
        if ($this->isCachingEnabled && $this->_cacheID != null && $this->_cacheGroup != null) {
            $_cacheData = $this->_cacheLite->clean($this->_cacheGroup);
        }

        return $_cacheData;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>