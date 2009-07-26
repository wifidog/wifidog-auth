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
 * @subpackage Geocoders
 * @author     Robin Jones
 * @copyright  2009 Robin Jones, NetworkFusion
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required class
 */
require_once('classes/GenericObject.php');
require_once('classes/Server.php');

/**
 * @package    WiFiDogAuthServer
 * @subpackage Geocoders
 * @author     Robin Jones
 * @copyright  2009 Robin Jones, NetworkFusion
 */
class GeocoderGoogleGlobal extends AbstractGeocoder
{

    private $cached_latitude;
    private $cached_longitude;

    public function __construct()
    {
        //$this->setCountry("Earth");
        $this->setEndpointUrl("http://maps.google.com/maps/geo?");
        //API Key will be collected from the database in future
        if(!$vhost=VirtualHost::getCurrentVirtualHost()) {
            $this->setAPIKey("0");
        }
	    else
            $this->setAPIKey($vhost->getGoogleAPIKey());
    }


    /** Validate address, making sure we don't send an HTTP for nothing
     * @return boolean
     */
    public function validateAddress()
    {
        // Make sure a city or a postal code has been entered
        //if(($this->getCivicNumber() == "" || $this->getStreetName() == "" || $this->getCity() == "" || $this->getProvince() == "") && !$this->getPostalCode() == "")
            //return false;
        return true;
    }

    /** Constructs the address string that will be sent to the endpoint URL
     * @return string address string
     */
    private function buildAddress()
    {

        // Build correctly formated string depending on given parameters
        $address = Null;


        if ($this->getStreetName() != "")
            $address .= $this->getStreetName() . ', ';

        if ($this->getCity() != "")
            $address .= $this->getCity() . ', ';

        if ($this->getProvince() != "")
            $address .= $this->getProvince() . ', ';

            $address .= $this->getPostalCode();
        return $address;
	
    }

    /** Constructs the HTTP query string that will be sent to the endpoint URL
     * @return string HTTP GET query string
     */
    private function buildQuery()
    {

        // Build HTTP GET query string containing all parameters
        $http_params = array ("key" => $this->getAPIKey(), "q" => $this->buildAddress(), "output" => "xml", "oe"=> "utf8", "sensor" => "false");
        return $this->getEndpointUrl() . http_build_query($http_params);
	
    }


    /** Runs the HTTP GET query
     * @return boolean
     */
    private function executeQuery()
    {
        // Don't send multiple queries when the input has not changed
        if ($this->shouldExecuteQuery() == true)
        {

            // Load the XML document
            if (($xml = simplexml_load_file($this->buildQuery())) !== false)
            {



                //print $this->buildQuery() . '<br>';
                //print_r($xml->xpath["Points"]);
    
                $status = $xml->Response->Status->code;
                if (strcmp($status, "200") == 0) 
                {
                    // Successful geocode
                    $coordinates = $xml->Response->Placemark->Point->coordinates;
                    
                    $coordinatesSplit = split(",", $coordinates);
                    // Format: Longitude, Latitude, Altitude
                    $this->cached_latitude = $coordinatesSplit[1];
                    $this->cached_longitude = $coordinatesSplit[0];
    
                    // Prevent from sending multiple queries.
                    $this->keepResponse();
                }

            }
            else
                return false;
        }
        return true;
    }

    /** Get the latitude for enterred infos
     * @return string latitude ( decimal format 6-digits precision )
     */
    public function getLatitude()
    {
        if ($this->validateAddress())
            if ($this->executeQuery() == true)
                return $this->cached_latitude;
            else
                return null;
        else
            return null;
    }

    /** Get the longitude for enterred infos
     * @return string longitude ( decimal format 6-digits precision )
     */
    public function getLongitude()
    {
        if ($this->validateAddress())
            if ($this->executeQuery() == true)
                return $this->cached_longitude;
            else
                return null;
        else
            return null;
    }

    /** Get the altitude for enterred infos
     * @return string algitude
     */
    public function getAltitude()
    {
        // Not supported by google
        return null;
    }

    /** Get a GIS Point instance
     * @return GisPoint
     */
    public function getGisLocation()
    {
        $lat = $this->getLatitude();
        $long = $this->getLongitude();

        if($lat !== null && $long !== null)
            return new GisPoint($lat, $long, 0);
        return null;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


