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
 * @author Copyright (C) 2005 François Proulx
 */

// Global vars
var markers = Array();

function onLoad(hotspots_status_xml_url, lat, lng, zoom) 
{ 
	// Create the map
	var map = createMap(new GPoint(lng, lat), zoom);
	var hotspotsList = document.getElementById("map_hotspots_list");
	loadVenuesStatus(map, hotspotsList, hotspots_status_xml_url);
}

function createMap(centerPoint, zoomLevel)
{
	// Center and zoom on the map
	var map = new GMap(document.getElementById("map_frame"));
	map.addControl(new GLargeMapControl());
	map.addControl(new GMapTypeControl());
	map.centerAndZoom(centerPoint, zoomLevel);
	return map;
}

// Debug function
function activateShowCoords(map)
{
	GEvent.addListener(map, 'click', function(overlay, point) {
		if (point)
		{
			var marker = new GMarker(point);
			marker.open
			GEvent.addListener(marker, "click", function() {
				marker.openInfoWindowHtml(point.x + " " + point.y);
			});
			map.addOverlay(marker);
		}
	});
}

function loadVenuesStatus(map, hotspotsList, document_url)
{
	// Download the data in venues status XML
	var request = GXmlHttp.create();
	request.open("GET", document_url, true);
	// Once finished, start parsing
	request.onreadystatechange = function() 
	{
		if (request.readyState == 4)
			parseVenuesStatus(map, hotspotsList, request.responseXML);
	}
	request.send(null);
}

function parseVenuesStatus(map, hotspotsList, xmlDoc)
{
	var venuesListHtml = "";
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
	var venues = xmlDoc.documentElement.getElementsByTagName("venue");
	for (var i = 0; i < venues.length; i++) 
	{
		var venueId = venues[i].getElementsByTagName("venueId");
		var gis = venues[i].getElementsByTagName("gisLatLong");
		if(venueId.length ==1 && gis.length == 1)
		{
			// Extract GIS data
			var point = new GPoint(parseFloat(gis[0].getAttribute("long")), parseFloat(gis[0].getAttribute("lat")));
			var status = venues[i].getElementsByTagName("status");
			var markerIcon;
			if(status.length == 1)
			{
				switch(status[0].firstChild.nodeValue)
				{
					case "up":
						markerIcon = upIcon; // Hotspot is up
						break;
					case "down":
						markerIcon = downIcon; // Hotspot is down
						break;
					default:
						markerIcon = unknownIcon; // Unknown hotspot status
				}
			}
			else
				markerIcon = unknownIcon; // Unknown hotspot status
			
			// Prepare fragment that will go in the sidebar
			var html = createHtmlFromVenueNode(venues[i], markerIcon);
			venuesListHtml += html + "<p/><a href=\"#\" onClick=\"openInfoBubble('" + venueId[0].firstChild.nodeValue + "');\">Show me on the map</a><hr width='95%'/>";
			
			// Create, save as ID and add the marker
			var marker = createInfoBubble(point, markerIcon, html);
			// markers is a global var
			markers[venueId[0].firstChild.nodeValue] = marker;
			map.addOverlay(marker);
		}
	}

	// Load the prepared HTML fragment in the right-hand listphoto
	hotspotsList.innerHTML = venuesListHtml;
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

function createHtmlFromVenueNode(venue_node, icon)
{
	var html = "<table><tr><td><img src='" + icon.image + "'></td><td>";
	
	var name = venue_node.getElementsByTagName("name");
	if(name.length == 1)
		html += "<b>" + name[0].firstChild.nodeValue + "</b><br/>";
		
	/* Too long ... ?!
	var desc = venue_node.getElementsByTagName("description");
	if(desc.length == 1)
		html += "<i>" + desc[0].firstChild.nodeValue + "</i><br/>";*/
		
	var streetAddress = venue_node.getElementsByTagName("streetAddress");
	if(streetAddress.length == 1)
		html += "<i>" + streetAddress[0].firstChild.nodeValue + "</i><br/>";
		
	var phone = venue_node.getElementsByTagName("contactPhoneNumber");
	if(phone.length == 1)
		html += "<i>" + phone[0].firstChild.nodeValue + "</i><br/>";
		
	var transit = venue_node.getElementsByTagName("massTransitInfo");
	if(transit.length == 1)
		html += "<b>" + transit[0].firstChild.nodeValue + "</b><br/>";
		
	var websiteUrl = venue_node.getElementsByTagName("webSiteUrl");
	if(websiteUrl.length == 1)
		html += "<a href='" + websiteUrl[0].firstChild.nodeValue + "'>Visit their Website</a>";
	
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