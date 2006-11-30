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
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/InterfaceElements.php');

/**
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx, Technologies Coeus inc.
 */
class DateTimeWD
{
    const INTERFACE_DATE_SELECTOR = 1;
    const INTERFACE_DATETIME_SELECTOR = 2;
    const INTERFACE_DATETIME_FIELD = 3;

    /**
     * @todo Complete this
     */
    public static function getSelectDateTimeUI(DateTimeWD $datetime, $user_prefix, $interface_type, $id = "")
    {
        $html = "";
        switch($interface_type)
        {
        case self::INTERFACE_DATE_SELECTOR:
            $html = "";
            break;

        case self::INTERFACE_DATETIME_SELECTOR:
            $html = "";
            break;

        case self::INTERFACE_DATETIME_FIELD:
            $str = $datetime->getIso8601FormattedString();
            $html = InterfaceElements::generateInputText($user_prefix, $str, $id);
            break;
        }

        return $html;
    }

    static function processSelectDateTimeUI($user_prefix, $interface_type)
    {
        $object = null;

        switch($interface_type)
        {
        case self::INTERFACE_DATE_SELECTOR:
            $object = new self();
            break;

        case self::INTERFACE_DATETIME_SELECTOR:
            $object = new self();
            break;

        case self::INTERFACE_DATETIME_FIELD:
            $object = new self($_REQUEST[$user_prefix]);
            break;

        }

        return $object;
    }
    
    /** PHP timestamp of the datetime */
	private $timestamp;
    // Domain-related attributes
    private $day = 0;
    private $month = 0;
    private $year = 0;
    private $hours = 0;
    private $minutes = 0;
    private $seconds = 0;
    private $is_empty_date = true;
    /**
    * @param string $datetime_str formatted date string (ideally ISO-8601-2000)
    */
    public function __construct($datetime_str)
    {
        if ($datetime_str != null) {
        	$this->timestamp = strtotime($datetime_str);
            $date_attributes = getdate($this->timestamp);
            $this->day = $date_attributes["mday"];
            $this->month = $date_attributes["mon"];
            $this->year = $date_attributes["year"];
            $this->hours = $date_attributes["hours"];
            $this->minutes = $date_attributes["minutes"];
            $this->seconds = $date_attributes["seconds"];
            $this->is_empty_date = false;
        }
        else
        {
            $this->is_empty_date = true;
        }
    }

    public function isEmpty()
    {
            return $this->is_empty_date;
    }
    
    /** Get the PHP timestamp (seconds since UNIX epoch) */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getIso8601FormattedString()
    {
        if($this->is_empty_date)
        {
            return null;
        }
        else{
        return sprintf("%04d-%02d-%02d", $this->getYear(), $this->getMonth(), $this->getDayOfMonth());
        }
    }

    public function getDayOfMonth()
    {
        return $this->day;
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function getHours()
    {
        return $this->hours;
    }

    public function getMinutes()
    {
        return $this->minutes;
    }

    public function getSeconds()
    {
        return $this->seconds;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

