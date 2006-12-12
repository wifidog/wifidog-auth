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
 * @author     Philippe April
 * @copyright  2004-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * This class regroups a bunch of utility functions
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @copyright  2004-2006 Philippe April
 */
class Utils
{
	/**
	 * Converts the bytes integer value in human-readable format
	 */
    public static function convertBytesToWords($bytes)
    {
        if ($bytes > 1024 * 1024 * 1024)
            return round($bytes / (1024 * 1024 * 1024), 1)."G";
        if ($bytes > 1024 * 1024)
            return round($bytes / (1024 * 1024), 1)."M";
        if ($bytes > 1024)
            return round($bytes / (1024), 1)."K";
    }

	/**
	 * Converts seconds integer value in human-readable format
	 */
    public static function convertSecondsToWords($seconds)
    {
        $r = '';
        if ($seconds > 60 * 60 * 24 * 365.25)
        {
            $amount = floor($seconds / (60 * 60 * 24 * 365.25));
            if ($amount != 0)
                $r .= " {$amount}y";
            $seconds -= ($amount * 60 * 60 * 24 * 365.25);
        }
        if ($seconds > 60 * 60 * 24)
        {
            $amount = floor($seconds / (60 * 60 * 24));
            if ($amount != 0)
                $r .= " {$amount}d";
            $seconds -= ($amount * 60 * 60 * 24);
        }
        if ($seconds > 60 * 60)
        {
            $amount = floor($seconds / (60 * 60));
            if ($amount != 0)
                $r .= " {$amount}h";
            $seconds -= ($amount * 60 * 60);
        }
        if ($seconds > 60)
        {
            $amount = floor($seconds / 60);
            if ($amount != 0)
                $r .= " {$amount}m";
            $seconds -= ($amount * 60);
        }
        if ($seconds != 0)
        {
            $r .= " {$seconds}s";
        }
        trim($r);
        return $r;
    }

    /**
     * Naturally sorts (collates) a 2-dimensionnal array.
     * Ie. it will sort in human order : numbers first, letters ...
     *
     * From PHP.net forums : Thanks to mroach at mroach dot com
     */
    public static function natsort2d(& $arrIn, $index = null)
    {
        $arrTemp = array ();
        $arrOut = array ();
        foreach ($arrIn as $key => $value)
        {
            reset($value);
            $arrTemp[$key] = is_null($index) ? current($value) : $value[$index];
        }
        natcasesort($arrTemp);
        foreach ($arrTemp as $key => $value)
            $arrOut[] = $arrIn[$key];
        $arrIn = $arrOut;
    }

/** Use PHP internal functions to execute a command 
 @return: Return value of the command*/
        public static function execCommand($command, & $output, $debug = false) {
            if($debug)
            print "$command";
            exec($command.'  2>&1', & $output, & $retval);
            if($debug){
                if ($retval != 0)
            print "<p style='color:red'><em>Error:</em>  Command did not complete successfully  (returned $retval): <br/>\n";
            else
            print "<p style='color:green'>Command completed successfully  (returned $retval): <br/>\n";
            }
            if ($debug && $output) {
                foreach ($output as $output_line)
                print " $output_line <br/>\n";
            }
            if($debug)
                print "</p>\n";
            return $retval;
        }
}//End class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

