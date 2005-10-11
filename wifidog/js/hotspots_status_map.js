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
/**@file hotspots_status_map.js
 * JavaScript class for displaying a hotspot status map using Google Maps
 * @author Copyright (C) 2005 Francois Proulx <francois.proulx@gmail.com>
 */
    
// Constructor
function HotspotsMap(viewport, external_object_name)
{
	if(GBrowserIsCompatible())
	{
		// Create the map attribute
		this.map = new GMap(document.getElementById(viewport));
		this.map.addControl(new GLargeMapControl());
		this.map.addControl(new GMapTypeControl());
		// Create the array that will contain refs to markers
		this.markers = Array();
		// Init source url
		this.xml_source = null;
		// This is quite stupide, but it's needed since we need to build onclick urls
		this.external_object_name = external_object_name;
	}
	else
		alert("Sorry, your browser does not support Google Maps.");
}

HotspotsMap.prototype.getGPointFromPostalCode = function(postal_code)
{
	// Fool the async callback
	var geocoded_point = null;
	var request = GXmlHttp.create();
	var self = this;
	// Download asynchronously the hotspots data
	request.open("GET", "geocoder.php?postal_code=" + postal_code, true);
	// Once completed, start parsing
	request.onreadystatechange = function()
	{
		if (request.readyState == 4)
		{
			if(request.responseXML != undefined)
			{
				var xml_doc = request.responseXML;
				var lng = xml_doc.documentElement.getElementsByTagName("long");
				var lat = xml_doc.documentElement.getElementsByTagName("lat");
				self.findClosestHotspotByGPoint(new GPoint(lng[0].firstChild.nodeValue, lat[0].firstChild.nodeValue));
			}
		}
	}
	request.send(null);
}

HotspotsMap.prototype.findClosestHotspotByPostalCode = function(postal_code)
{
	if(postal_code != undefined && this.markers.length > 0)
		this.getGPointFromPostalCode(postal_code);
}

HotspotsMap.prototype.findClosestHotspotByGPoint = function(pt)
{
	if(pt != null && this.markers.length > 0)
	{
		var dist = null;
		var hotspot_id = null;
		// For each registered markers
		for(i in this.markers)
		{
			// Compute the distance in meters between the two points
			tmp = this.computeDistance(pt, this.markers[i].point);
			if(dist == null || tmp < dist)
			{
				dist = tmp
				hotspot_id = i;
			}
		}
		// If a hotspot has been found, pop the blowup balloon
		if(hotspot_id != null)
			this.openInfoBubble(hotspot_id);
	}
}

// Computes the distance between two GPoint in meters
HotspotsMap.prototype.computeDistance = function(pt1, pt2)
{
	x1 = this.convertDegreesToRadian(pt1.y); 
	y1 = this.convertDegreesToRadian(pt1.x); 
	x2 = this.convertDegreesToRadian(pt2.y); 
	y2 = this.convertDegreesToRadian(pt2.x); 
	return 6378800 * (Math.acos(Math.sin(x1) * Math.sin(x2) + Math.cos(x1) * Math.cos(x2) * Math.cos(y2 - y1))); 
}

HotspotsMap.prototype.convertDegreesToRadian = function(deg)
{
	return deg / (180/Math.PI);
}

HotspotsMap.prototype.buildHtmlFromHotspot = function(hotspot_element, icon)
{
	var html = "<table><tr><td><img src='" + icon.image + "'></td><td>";
	
	var name = hotspot_element.getElementsByTagName("name");
	if(name.length == 1)
		html += "<b>" + name[0].firstChild.nodeValue + "</b>";
		
	html += "<br/>";
	
	var civicNumber = hotspot_element.getElementsByTagName("civicNumber");
	if(civicNumber.length == 1)
		html += "<i>" + civicNumber[0].firstChild.nodeValue + ",&nbsp;</i>";
			
	var streetAddress = hotspot_element.getElementsByTagName("streetAddress");
	if(streetAddress.length == 1)
		html += "<i>" + streetAddress[0].firstChild.nodeValue + ",&nbsp;</i>";
	
	html += "<br/>";
		
	var city = hotspot_element.getElementsByTagName("city");
	if(city.length == 1)
		html += "<i>" + city[0].firstChild.nodeValue + ",&nbsp;</i>";
	
	var province = hotspot_element.getElementsByTagName("province");
	if(province.length == 1)
		html += "<i>" + province[0].firstChild.nodeValue + ",&nbsp;</i>";
		
	html += "<br/>";
		
	var postalCode = hotspot_element.getElementsByTagName("postalCode");
	if(postalCode.length == 1)
		html += "<i>" + postalCode[0].firstChild.nodeValue + ",&nbsp;</i>";
	
	var country = hotspot_element.getElementsByTagName("country");
	if(country.length == 1)
		html += "<i>" + country[0].firstChild.nodeValue + "</i>";
		
	html += "<br/>";
		
	var phone = hotspot_element.getElementsByTagName("contactPhoneNumber");
	if(phone.length == 1)
		html += "<i>" + phone[0].firstChild.nodeValue + "</i><br/>";
		
	var transit = hotspot_element.getElementsByTagName("massTransitInfo");
	if(transit.length == 1)
		html += "<b>" + transit[0].firstChild.nodeValue + "</b><br/>";
		
	var websiteUrl = hotspot_element.getElementsByTagName("webSiteUrl");
	if(websiteUrl.length == 1)
		html += "<a href='" + websiteUrl[0].firstChild.nodeValue + "'>URL (WWW)</a>";
	html += "</td></tr></table>";
	
	return html;
}

// GIcon factory method
HotspotsMap.prototype.createIcon = function (imageUrl, iSize, shadowUrl, sSize, iconAnchor, bubbleAnchor)
{
	var icon = new GIcon();
	icon.image = imageUrl;
	icon.iconSize = iSize;
	icon.shadow = shadowUrl;
	icon.shadowSize = sSize;
	icon.iconAnchor = iconAnchor;
	icon.infoWindowAnchor = bubbleAnchor;
	return icon;
}

HotspotsMap.prototype.createInfoBubble = function(point, icon, html) 
{
	var marker = new GMarker(point, icon);
	GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(html);
	});
	return marker;
}

HotspotsMap.prototype.openInfoBubble = function(bubbleId)
{
	// Trigger click ( NB. markers is a global var )
	GEvent.trigger(this.markers[bubbleId], "click");
}

HotspotsMap.prototype.loadHotspotsStatus = function()
{
	// Make sure the source has been set
	if(this.xml_source != null)
	{
		// Fool the async callback
		var self = this;
		var request = GXmlHttp.create();
		// Download asynchronously the hotspots data
		request.open("GET", this.xml_source, true);
		// Once completed, start parsing
		request.onreadystatechange = function() 
		{
			if (request.readyState == 4)
				self.parseHotspotsStatus(request.responseXML);
		}
		request.send(null);
	}
	else
		return false;
}

HotspotsMap.prototype.parseHotspotsStatus = function(xml_doc)
{
	var html_list = "";
	// Init marker icons
	var upIcon = this.createIcon("../images/hotspots_status_map_up.png", new GSize(20, 34),
	                        "../images/hotspots_status_map_shadow.png", new GSize(37, 34),
	                        new GPoint(10, 20), new GPoint(10, 1));
	var downIcon = this.createIcon("../images/hotspots_status_map_down.png", new GSize(20, 34),
	                          "../images/hotspots_status_map_shadow.png", new GSize(37, 34),
	                          new GPoint(10, 20), new GPoint(10, 1));
	var unknownIcon = this.createIcon("../images/hotspots_status_map_unknown.png", new GSize(22, 34),
	                          "../images/hotspots_status_map_blank.png", new GSize(22, 34),
	                          new GPoint(11, 30), new GPoint(11, 1));                       
	// Parse the XML DOM
	var hotspots = xml_doc.documentElement.getElementsByTagName("hotspot");
	for (var i = 0; i < hotspots.length; i++)
	{
		var hotspotId = hotspots[i].getElementsByTagName("hotspotId");
		var gis = hotspots[i].getElementsByTagName("gisCenterLatLong");
		if(hotspotId.length == 1 && gis.length == 1)
		{
			// Extract GIS data
			var point = new GPoint(parseFloat(gis[0].getAttribute("long")), parseFloat(gis[0].getAttribute("lat")));
			var status = hotspots[i].getElementsByTagName("globalStatus");
			var markerIcon;
			if(status.length == 1)
			{
				switch(status[0].firstChild.nodeValue)
				{
					case "100":
						markerIcon = upIcon; // Hotspot is up
						break;
					case "0":
						markerIcon = downIcon; // Hotspot is down
						break;
					default:
						markerIcon = unknownIcon; // Unknown hotspot status
				}
			}
			else
				markerIcon = unknownIcon; // Unknown hotspot status
			
			// Prepare fragment that will go in the sidebar
			var html = this.buildHtmlFromHotspot(hotspots[i], markerIcon);
			html_list += html + "<p/><a href=\"#\" onClick=\"" + this.external_object_name +".openInfoBubble('" + hotspotId[0].firstChild.nodeValue + "');\">Show me on the map</a><hr width='95%'/>";
			
			// Create, save as ID and add the marker
			var marker = this.createInfoBubble(point, markerIcon, html);
			// markers is a global var
			this.markers[hotspotId[0].firstChild.nodeValue] = marker;
			this.map.addOverlay(marker);
		}
	}

	// Load the prepared HTML fragment in the right-hand listphoto
	this.hotspots_info_list.innerHTML = html_list;
}

HotspotsMap.prototype.redraw = function()
{
	for(i = 0;i < this.markers.length;i++)
		this.map.removeOverlay(this.markers[i]);
	this.hotspots_info_list.innerHTML = "Loading, please wait...";
	this.loadHotspotsStatus();
}

HotspotsMap.prototype.setHotspotsInfoList = function(hotspots_info_list)
{
	this.hotspots_info_list = document.getElementById(hotspots_info_list);
}

HotspotsMap.prototype.setInitialPosition = function(lat, lng, zoom)
{
	this.map.centerAndZoom(new GPoint(lng, lat), zoom);
}

HotspotsMap.prototype.setOnClickListener = function(callback_fct)
{
	GEvent.addListener(this.map, 'click', callback_fct);
}

HotspotsMap.prototype.setXmlSourceUrl = function(xml_source)
{
	this.xml_source = xml_source;
}