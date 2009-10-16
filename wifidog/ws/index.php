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
 * Web service main page
 *
 * The wifidog web service allow external applications to access internal
 * wifidog information in a compact format.
 * 
 * The web service uses a simple GET method and returns the requested content in the given format
 *
 * {PROTOCOL}://{HOSTNAME}:{PORT}{PATH}ws?action=A&object_class=OC[&object_id=OID][&fields=F1%2CF2][&f=json][&v=1]
 *
 * PROTOCOL, HOSTNAME, PORT and PATH refer to the authserver's settings
 * PROTOCOL http or https if SSLAvailable is yes for the hotspot's current authserver
 * HOSTNAME Hostname of the current authserver
 * PORT is HTTPPort or SSLPort of the current authserver
 * PATH is the Path of the current authserver.  PATH starts and ends with a /
 *
 * action (get,list)         The action to perform  (MANDATORY)
 * object_class (user, node, network) The type of object whose data is to be fetched  (MANDATORY)  
 * object_id       The object id of the object involved in the query  (MANDATORY IF action is not list)
 * fields     the url-encoded comma-separated list of fields to return   (OPTIONAL)
 * f  (json)   The format of the response (OPTIONAL, default json)
 * v  (1)     The version of the web service protocol  (OPTIONAL, default 1)
 *
 * http://auth.ilesansfil.org:80/ws?action=get&object_class=node&object_id=215&fields=connected_users%2Cname
 * 
 * The result is returned according to the requested format, such that top-level has result=0|1 (0=error, 1=success) and 
 * values = ... (if error, value is array('type' => exception class, 'message' => 'exception message')
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 * @copyright  2009 Geneviève Bastien, VersaTIC Technologies inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../include/common.php');
require_once('ws/classes/WifidogWS.php');
require_once('ws/classes/WSOutput.php');
include_once('ws/classes/Exceptions/WSException.php');

/**
 * Process the input parameter
 **/
if (isset($_GET['v'])) {
    $version = $_GET['v'];
    unset($_GET['v']);
} else {
    $version = 1;
}

$service = WifidogWS::webServiceFactory($version);

/**
 * This custom exception handler returns the exception
 */
function wifidog_exception_handler($e) {
    global $service;
    $output = $service->getOutput();
    $exceptionClass = get_class($e);
    if (!is_null($output)) {
        echo $output->outputError(array('type' => $exceptionClass, 
                                    'message' => sprintf(_("Detailed error was:  Uncaught %s %s (%s) thrown in file %s, line %d"),get_class($e), $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine())));
    } else {
        echo sprintf(_("Detailed error was:  Uncaught %s %s (%s) thrown in file %s, line %d"),get_class($e), $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
    }

}

set_exception_handler('wifidog_exception_handler');

throw (new WSException(_("The Wifidog API module is not fit for production yet.  The source code has been released to share ideas and help development, but it has not been thoroughly tested yet and may represent a security issue for now.  If you'd like to test the module, you can do so by commenting this line in the auth server's source code.  But it is highly not advised to do so in a production environment for now.  Please stay tuned for more development")));

$service->setParams($_GET);

$service->execute();

echo $service->output();

