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
/**@file venues_status_map.js
 * JavaScript functions to display a hotspot status map
 * @author Copyright (C) 2005 François Proulx
 */

// Global vars
var markers = Array();

function onLoad(venues_status_xml_url, lat, lng) 
{ 
	// Create the map
	var map = createMap(new GPoint(lng, lat), 5);
	var venuesList = document.getElementById("map_venues_list");
	loadVenuesStatus(map, venuesList, venues_status_xml_url);
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

function loadVenuesStatus(map, venuesList, document_url)
{
	// Download the data in venues status XML
	var request = GXmlHttp.create();
	request.open("GET", document_url, true);
	// Once finished, start parsing
	request.onreadystatechange = function() 
	{
		if (request.readyState == 4)
			parseVenuesStatus(map, venuesList, request.responseXML);
	}
	request.send(null);
}

function parseVenuesStatus(map, venuesList, xmlDoc)
{
	var venuesListHtml = "";
	// Init marker icons
	var upIcon = createIcon("../images/venues_status_map_up.png", new GSize(22, 34),
	                        "../images/venues_status_map_blank.png", new GSize(22, 34),
	                        new GPoint(11, 30), new GPoint(11, 1));
	var downIcon = createIcon("../images/venues_status_map_down.png", new GSize(22, 34),
	                          "../images/venues_status_map_blank.png", new GSize(22, 34),
	                          new GPoint(11, 30), new GPoint(11, 1));
	// Parse the XML DOM
	var venues = xmlDoc.documentElement.getElementsByTagName("venue");
	for (var i = 0; i < venues.length; i++) {
		var venueId = venues[i].getElementsByTagName("venueId");
		var gis = venues[i].getElementsByTagName("gisLatLong");
		if(venueId.length ==1 && gis.length == 1)
		{
			var point = new GPoint(parseFloat(gis[0].getAttribute("long")), parseFloat(gis[0].getAttribute("lat")));
			var status = venues[i].getElementsByTagName("status");
			if(status.length == 1)
			{
				var markerIcon;
				switch(status[0].firstChild.nodeValue)
				{
					case "up":
						markerIcon = upIcon;
						break;
					case "down":
						markerIcon = downIcon;
						break;
					default:
						markerIcon = downIcon;
				}

				// Prepare fragment that will go in the sidebar
				var html = createHtmlFromVenueNode(venues[i], markerIcon);
				venuesListHtml += html + "<p/><a href=\"#\" onClick=\"openInfoBubble('" + venueId[0].firstChild.nodeValue + "');\">Show me on the map</a><hr/>";
				
				// Create, save as ID and add the marker
				var marker = createInfoBubble(point, markerIcon, html);
				// markers is a global var
				markers[venueId[0].firstChild.nodeValue] = marker;
				map.addOverlay(marker);
			}
		}
		/*
		else
			alert("The venue No " + venueId.item(0).firstChild.nodeValue + " did not have any GIS coords.");*/
	}

	// Load the prepared fragment
	venuesList.innerHTML = venuesListHtml;
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
	var html = "<img src='" + icon.image + "'>";
	
	var name = venue_node.getElementsByTagName("name");
	if(name.length == 1)
		html += "<b>" + name[0].firstChild.nodeValue + "</b><br/>";
		
	var streetAddress = venue_node.getElementsByTagName("streetAddress");
	if(streetAddress.length == 1)
		html += "<i>" + streetAddress[0].firstChild.nodeValue + "</i><br/>";
		
	var websiteUrl = venue_node.getElementsByTagName("webSiteUrl");
	if(websiteUrl.length == 1)
		html += "<a href='" + websiteUrl[0].firstChild.nodeValue + "'>Visit the Website</a>";
		
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