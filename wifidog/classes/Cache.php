<?php

/*********************************************************************\
 * This program is free software; you can redistribute it and/or     *
 * modify it under the terms of the GNU General Public License as    *
 * published by the Free Software Foundation; either version 2 of    *
 * the License, or (at your option) any later version.               *
 *                                                                   *
 * This program is distributed in the hope that it will be useful,   *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of    *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     *
 * GNU General Public License for more details.                      *
 *                                                                   *
 * You should have received a copy of the GNU General Public License *
 * along with this program; if not, contact:                         *
 *                                                                   *
 * Free Software Foundation           Voice:  +1-617-542-5942        *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                    *
 *                                                                   *
\*********************************************************************/

/**
 * @file Cache.php
 * @author Max Horváth, maxspot GmbH
 * @copyright Copyright (c) 2005, Max Horváth, maxspot GmbH
 */

/**
 * PEAR::Cache_Lite implementation
 */
class Cache {

    // Caching enabled?
    public $isCachingEnabled = false;

    // Lifetime of cache (value in seconds or null).
    private $_lifeTime = null;

    // PEAR::Cache_Lite object.
    private $_cacheLite;

    // ID of PEAR::Cache_Lite object.
    private $_cacheID = null;

    // Group of PEAR::Cache_Lite object.
    private $_cacheGroup = "default";

    /**
     * Constructor.
     * @param string $id ID of PEAR::Cache_Lite object.
     * @param string $group Group of PEAR::Cache_Lite object. (optional)
     * @return void
     */
    public function __construct($id, $group = "default") {
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
                    'cacheDir' => BASEPATH . 'tmp/cache/',
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
     * @return boolean Caching enabled or disabled.
     */
    private function cachingEnabled() {
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
     * @return string Data of cache.
     */
    public function getCachedData() {
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
     * @param string $data Data to be saved into cache.
     * @return boolean Saving data into cache was successful or not.
     */
    public function saveCachedData($data) {
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
     * @return boolean Removing data from cache was successful or not.
     */
    public function eraseCachedData() {
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
     * @return boolean Removing data from cache was successful or not.
     */
    public function eraseCachedGroupData() {
        // Init values.
        $_cacheData = null;

        // Check if caching has been enabled and if cache identifier has been set.
        if ($this->isCachingEnabled && $this->_cacheID != null && $this->_cacheGroup != null) {
            $_cacheData = $this->_cacheLite->clean($this->_cacheGroup);
        }

        return $_cacheData;
    }

}

?>