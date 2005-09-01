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
/**@file gmaps_hotspots_status_map.js
 * JavaScript functions to display a hotspot status map using Google Maps
 * @author Copyright (C) 2005 Francois Proulx <francois.proulx@gmail.com>
 */

// Global vars
var markers = Array();

function loadHotspotsMap(hotspots_status_xml_url, lat, lng, zoom) 
{ 
	// Create the map
	if(lat != null && lng != null)
		point = new GPoint(lng, lat);
	else
		point = null;
	var map = createMap(point, zoom);
	var hotspotsList = document.getElementById("map_hotspots_list");
	if(hotspots_status_xml_url != null)
		loadHotspotsStatus(map, hotspotsList, hotspots_status_xml_url);
	return map;
}

function createMap(centerPoint, zoomLevel)
{
	// Center and zoom on the map
	var map = new GMap(document.getElementById("map_frame"));
	map.addControl(new GLargeMapControl());
	map.addControl(new GMapTypeControl());
	if(centerPoint != null && zoomLevel != null)
		map.centerAndZoom(centerPoint, zoomLevel);
	return map;
}

function setMapClickCallback(map, callback)
{
	GEvent.addListener(map, 'click', callback);
}

function loadHotspotsStatus(map, hotspotsList, document_url)
{
	// Download the data in hotspots status XML
	var request = GXmlHttp.create();
	request.open("GET", document_url, true);
	// Once finished, start parsing
	request.onreadystatechange = function() 
	{
		if (request.readyState == 4)
			parseHotspotsStatus(map, hotspotsList, request.responseXML);
	}
	request.send(null);
}

function parseHotspotsStatus(map, hotspotsList, xmlDoc)
{
	var hotspotsListHtml = "";
	// Init marker icons
	var upIcon = createIcon("../images/hotspots_status_map_up.png", new GSize(20, 34),
	                        "../images/hotspots_status_map_shadow.png", new GSize(37, 34),
	                        new GPoint(10, 20), new GPoint(10, 1));
	var downIcon = createIcon("../images/hotspots_status_map_down.png", new GSize(20, 34),
	                          "../images/hotspots_status_map_shadow.png", new GSize(37, 34),
	                          new GPoint(10, 20), new GPoint(10, 1));
	var unknownIcon = createIcon("../images/hotspots_status_map_unknown.png", new GSize(22, 34),
	                          "../images/hotspots_status_map_blank.png", new GSize(22, 34),
	                          new GPoint(11, 30), new GPoint(11, 1));
	                          
	// Parse the XML DOM
	var hotspots = xmlDoc.documentElement.getElementsByTagName("hotspot");
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
			var html = createHtmlFromHotspot(hotspots[i], markerIcon);
			hotspotsListHtml += html + "<p/><a href=\"#\" onClick=\"openInfoBubble('" + hotspotId[0].firstChild.nodeValue + "');\">Show me on the map</a><hr width='95%'/>";
			
			// Create, save as ID and add the marker
			var marker = createInfoBubble(point, markerIcon, html);
			// markers is a global var
			markers[hotspotId[0].firstChild.nodeValue] = marker;
			map.addOverlay(marker);
		}
	}

	// Load the prepared HTML fragment in the right-hand listphoto
	hotspotsList.innerHTML = hotspotsListHtml;
}

function createIcon(imageUrl, iSize, shadowUrl, sSize, iconAnchor, bubbleAnchor)
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

function createHtmlFromHotspot(hotspot_element, icon)
{
	var html = "<table><tr><td><img src='" + icon.image + "'></td><td>";
	
	var name = hotspot_element.getElementsByTagName("name");
	if(name.length == 1)
		html += "<b>" + name[0].firstChild.nodeValue + "</b><br/>";
	
	var civicNumber = hotspot_element.getElementsByTagName("civicNumber");
	if(civicNumber.length == 1)
		html += "<i>" + civicNumber[0].firstChild.nodeValue + ",&nbsp;</i>";
			
	var streetAddress = hotspot_element.getElementsByTagName("streetAddress");
	if(streetAddress.length == 1)
		html += "<i>" + streetAddress[0].firstChild.nodeValue + ",&nbsp;</i><br/>";
		
	var city = hotspot_element.getElementsByTagName("city");
	if(city.length == 1)
		html += "<i>" + city[0].firstChild.nodeValue + ",&nbsp;</i>";
	
	var province = hotspot_element.getElementsByTagName("province");
	if(province.length == 1)
		html += "<i>" + province[0].firstChild.nodeValue + ",&nbsp;</i>";
		
	var postalCode = hotspot_element.getElementsByTagName("postalCode");
	if(postalCode.length == 1)
		html += "<i>" + postalCode[0].firstChild.nodeValue + ",&nbsp;</i>";
	
	var country = hotspot_element.getElementsByTagName("country");
	if(country.length == 1)
		html += "<i>" + country[0].firstChild.nodeValue + "</i><br/>";
		
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

function createInfoBubble(point, icon, html) 
{
	var marker = new GMarker(point, icon);
	GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(html);
	});
	return marker;
}

function openInfoBubble(bubbleId)
{
	// Trigger click ( NB. markers is a global var )
	GEvent.trigger(markers[bubbleId], "click");
}
