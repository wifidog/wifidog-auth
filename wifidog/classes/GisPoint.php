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
/**@file GisPoint.php
 * @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>
 */
 
 class GisPoint
 {
 	// Domain-related attributes
 	private $latitude = .0;
 	private $longitude = .0;
 	private $altitude = .0;
 	
 	public function __construct($lat, $long, $alt)
 	{
 		$this->latitude = $lat;
 		$this->longitude = $long;
 		$this->altitude = $alt;
 	}
 	
 	public function getLatitude()
 	{
 		return $this->latitude;
 	}
 	
 	public function setLatitude($lat)
 	{
 		$this->latitude = $lat;
 	}
 	
 	public function getLongitude()
 	{
 		return $this->longitude;
 	}
 	
 	public function setLongitude($long)
 	{
 		$this->longitude = $long;
 	}
 	
 	public function getAltitude()
 	{
 		return $this->altitude;
 	}
 	
 	public function setAltitude($alt)
 	{
 		$this->altitude = $alt;
 	}

 }
 
 ?>