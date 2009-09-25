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
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/* session constants */
define('SESS_USERNAME_VAR', 'SESS_USERNAME');
define('SESS_USER_ID_VAR', 'SESS_USER_ID');
define('SESS_PASSWORD_HASH_VAR', 'SESS_PASSWORD_HASH');
define('SESS_ORIGINAL_URL_VAR', 'SESS_ORIGINAL_URL');
define('SESS_LANGUAGE_VAR', 'SESS_LANGUAGE');
define('SESS_GW_ADDRESS_VAR', 'SESS_GW_ADDRESS');
define('SESS_GW_PORT_VAR', 'SESS_GW_PORT');
define('SESS_GW_ID_VAR', 'SESS_GW_ID');
define('SESS_NODE_ID_VAR', 'SESS_NODE_ID');/**<Deprecated benoitg 22/11/2006*/
/* End session constants */

/**
 * Session class
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class Session
{
    private static $object;
    public static function &getObject() {
    	if (self::$object==null)
    	{
    		self::$object=new self();
    	}
    	return self::$object;
    }
    /**
     * Constructor
     *
     * @return void
     */
    private function __construct()
    {
        $session_id = session_id();

        if (empty($session_id)) {
            session_start();
        }
    }

    /**
     * Sets a session variable
     *
     * @param string $name Name of variable
     * @param mixed $value value of variable
     *
     * @return void
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Fetches a session variable
     *
     * @param string $name Name of variable
     *
     * @return mixed Value of session varaible or false if unable to get value
     */
    public function get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return false;
        }
    }

    /**
     * Deletes a session variable
     *
     * @param string $name Name of variable
     *
     * @return bool True if successful
     */
    public function remove($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the whole session
     *
     * @return void
     */
    public function destroy()
    {
        $_SESSION = array();
        session_destroy();
    }

    /**
     * Reinitializes the whole session
     *
     * @return void
     */
    public function restart()
    {
        $_SESSION = array();
        session_unset();
        session_destroy();

        session_start();
    }

    /**
     * Dump the whole session
     *
     * @param bool $print If true session will be printed
     *
     * @return mixed If $print is false the session data will be returned
     */
    public function dump($print = true)
    {
        if ($print) {
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        } else {
            return $_SESSION;
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

