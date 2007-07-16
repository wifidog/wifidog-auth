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
require_once('classes/StatisticGraph.php');

/**
 * An abstract class.  All statistics must inherit from this class
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
abstract class StatisticReport
{
    protected $stats; /**< The Statistics object passed to the constructor */
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    abstract public static function getReportName();

    /** Get the report object.
     * @param $statistics_object Mandatory to give the report it's context
     * @return a localised string */
    final public static function &getObject($classname, Statistics $statistics_object)
    {
        $object = new $classname ($statistics_object);
        return $object;
    }

    /** Is the report available.  (Are all Dependency available,
     * are all preconditions in the statistics calss available, etc.)
     * Always returns true unless overriden by the child class
     * @param $statistics_object Mandatory to give the report it's context
     * @param &$errormsg Optionnal error message returned by the class
     * @return true or false */
    public static function isAvailable(Statistics $statistics_object, & $errormsg = null)
    {
        return true;
    }

    /** Constructor, must be called by subclasses
         * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct(Statistics $statistics_object)
    {
        $this->stats = $statistics_object;
    }

    /** Get the actual report.
     * Classes must override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI($child_html)
    {
        $html = '';
        $html .= "<fieldset>";
        $html .= "<legend>".$this->getReportName()."</legend>";
        $html .= $child_html;
        $html .= "</fieldset>";
        return $html;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


