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
/**@file AbstractGeocoder.php
 * @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>
 */

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/FormSelectGenerator.php';

// Implementation classes loading is done after abstract class definition

abstract class AbstractGeocoder
{
	// Domain-related attributes
	private $civic_number = "";
	private $street_name = "";
	private $city = "";
	private $province = "";
	private $country = "";
	private $postal_code = "";

	// Implementation attributes
	private $endpoint_url;
	// This value is only used to prevent from running to same query twice
	private $execute_query = true;

	// Factory method hash map
	private static $implementations_map = array ("Canada" => "GeocoderCanada", "USA" => "GeocoderUsa");

	/** Returns a list of countries for which we provide a geocoder implementation 
	 * @return array Array of string keys
	 */
	public static function getAvailableCountriesList()
	{
		return array_keys(self :: $implementations_map);
	}

	/** Return a string containing HTML definition of a forms select box containing countries
	 * @return string 
	 */
	public static function getAvailableCountriesFormSelect()
	{
		$array_tmp = array();
		foreach(self::$implementations_map as $key => $value)
			$array_tmp[] = array($key, $key);
		return FormSelectGenerator :: generateFromArray($array_tmp, null, "country", null, false);
	}

	/** Returns a list of available geocoders implementations
	 * @return array Array of string keys
	 */
	public static function getAvailableGeocoderImplementations()
	{
		return array_values(self :: $implementations_map);
	}

	/** Instanciates a new Geocoder implementation
	 * @param String $country_key The key corresponding to an entry given by getAvailableCountriesList
	 * @return AbstractGeocoder implementation
	 */
	public static function getGeocoder($country_key)
	{
		// Return the mapped implementation
		if (!isset (self :: $implementations_map[$country_key]))
			return null;
		$class_name = self :: $implementations_map[$country_key];
		return new $class_name;
	}

	public function getCivicNumber()
	{
		return $this->civic_number;
	}

	public function setCivicNumber($civic_number)
	{
		$this->trashResponse();
		$this->civic_number = $civic_number;
	}

	public function getStreetName()
	{
		return $this->street_name;
	}

	public function setStreetName($street_name)
	{
		$this->trashResponse();
		$this->street_name = $street_name;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function setCity($city)
	{
		$this->trashResponse();
		$this->city = $city;
	}

	public function getProvince()
	{
		return $this->province;
	}

	public function setProvince($province)
	{
		$this->trashResponse();
		$this->province = $province;
	}

	public function getCountry()
	{
		return $this->country;
	}

	protected function setCountry($country)
	{
		$this->trashResponse();
		$this->country = $country;
	}

	public function getPostalCode()
	{
		return $this->postal_code;
	}

	public function setPostalCode($postal_code)
	{
		$this->trashResponse();
		$this->postal_code = $postal_code;
	}

	public function getEndpointUrl()
	{
		return $this->endpoint_url;
	}

	protected function setEndpointUrl($url)
	{
		$this->trashResponse();
		$this->endpoint_url = $url;
	}

	protected function shouldExecuteQuery()
	{
		return $this->execute_query;
	}

	protected function trashResponse()
	{
		$this->execute_query = true;
	}

	protected function keepResponse()
	{
		$this->execute_query = false;
	}

	abstract public function validateAddress();
	abstract public function getLatitude();
	abstract public function getLongitude();
	abstract public function getAltitude();
	abstract public function getGisLocation();

} // End class

$class_names = AbstractGeocoder :: getAvailableGeocoderImplementations();
foreach ($class_names as $class_name)
	require_once BASEPATH.'classes/Geocoders/'.$class_name.'.php';
?>