<?php
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file GeocoderUsa.php
 * @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>
 */
 
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/GenericObject.php';
require_once BASEPATH.'classes/AbstractGeocoder.php';

class GeocoderUsa extends AbstractGeocoder
{
	public function __construct()
	{
		$this->setCountry("USA");
		$this->setEndpointUrl("http://rpc.geocoder.us/service/rest?");
	}
	
	/** Run regexp to verify the postal code
	 * @return boolean 
	 */
	private function validatePostalCode()
	{
		// Match USA zipcode (ex. 33044 or ZIP+4 90210-3378)
		return preg_match("/^\d{5}(-\d{4})?$/", $this->getPostalCode());
	}
	
	/** Validate address, making sure we don't send an HTTP for nothing
	 * @return boolean 
	 */
	public function validateAddress()
	{
		// Make sure a city or a postal code has been entered
		if(($this->getCivicNumber() == "" || $this->getStreetName() == "" || $this->getCity() == "" || $this->getProvince() == "") && !$this->validatePostalCode())
			return false;
		return true;
	}

	/** Constructs the HTTP query string that will be sent to the endpoint URL
	 * @return string HTTP GET query string
	 */
	private function buildQuery()
	{
		if($this->getCity() == "" && $this->getPostalCode() != "")
			$address = "{$this->getCivicNumber()} {$this->getStreetName()}, {$this->getPostalCode()}";
		else
			$address = "{$this->getCivicNumber()} {$this->getStreetName()}, {$this->getCity()}, {$this->getProvince()}";
			
		// Build HTTP GET query string containing all parameters
		$http_params = array ("address" => $address);
		return $this->getEndpointUrl().http_build_query($http_params);
	}

	/** Runs the HTTP GET query
	 * @return boolean 
	 */
	private function executeQuery()
	{
		// Don't send multiple queries when the input has not changed
		if($this->shouldExecuteQuery() == true)
		{
			// Load the XML document ( Tell the function to shut up, since geocoder.us sends malfromed XML on bad queries )
			if (($dom = @DOMDocument :: load($this->buildQuery())) !== false)
			{
				$xpath = new DOMXpath($dom);
				$xpath->registerNamespace("geo", "http://www.w3.org/2003/01/geo/wgs84_pos#");
				$xpath->registerNamespace("rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
			
				// Run XPath quries to extract data		
				$this->cached_latitude = $xpath->query("//rdf:RDF/geo:Point/geo:lat")->item(0)->nodeValue;
				$this->cached_longitude = $xpath->query("//rdf:RDF/geo:Point/geo:long")->item(0)->nodeValue;
				
				// Prevent from sending multiple queries.
				$this->keepResponse();
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
		// Not supported by geocoder.us
		return null;
	}	
} // End class

?>