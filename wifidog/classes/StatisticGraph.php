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
 * @subpackage Statistics
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Statistics.php');
require_once('classes/Session.php');
require_once('classes/Node.php');
/**
 * An abstract class.  All statistics must inherit from this class
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
abstract class StatisticGraph
{
    static protected $stats; /**< The Statistics object passed to the constructor */
    /** Get the Graph's name.  Must be overriden by the report class
     * @return a localised string */
    abstract public static function getGraphName();

    /** Get the report object.
     * @return a localised string */
    final public static function &getObject($classname)
    {
        require_once('classes/StatisticGraph/' . $classname . '.php');
        return new $classname ();
    }

    /** Is the graph available.  (Are all Dependency available,
     * are all preconditions in the statistics class available, etc.) Always
     * returns true unless overriden by the child class
     * @param &$errormsg Optionnal error message returned by the class
     * @return true or false */
    public function isAvailable(& $errormsg = null)
    {
        $retval = false;
        if (Dependency :: check("Image_Graph", $errormsg))
        {
            require_once ("Image/Graph.php");
            $retval = true;
        }

        return $retval;
    }

    /** Constructor, must be called by subclasses
     * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct()
    {
        $session = Session::getObject();
        self :: $stats = $session->get('current_statistics_object');
    }

    /** Get the actual report.
     * Classes  can (but don't have to) override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $statistics_object Mandatory to give the report it's context
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI(Statistics $statistics_object, $child_html = null)
    {
        $session = Session::getObject();
        $session->set('current_statistics_object', $statistics_object);
        self :: $stats = $statistics_object; /* Update it in case someone whants to use it right now */
        $html = '';
        $html .= "<fieldset>";
        $html .= "<legend>".$this->getGraphName()."</legend>";
        $errormsg = '';
        if ($this->isAvailable($errormsg))
        {
            $html .= "<div><img src='stats_show_graph.php?graph_class=".get_class($this)."'></div>";
        }
        else
        {
            $html .= $errormsg;
        }
        $html .= $child_html;
        $html .= "</fieldset>";
        return $html;
    }

    /** Return the actual Image data
     * Classes must override this.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    abstract public function showImageData();

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


