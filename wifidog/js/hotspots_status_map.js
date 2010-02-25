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
 * JavaScript class for displaying a hotspot status map using Google Maps
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

// Create a Global array that will contain refs to markers
var markers = new Array();

// Translations
function HotspotsMapTranslations(browser_support, homepage, show_on_map, loading)
{
    this.browser_support = browser_support;
    this.homepage = homepage;
    this.show_on_map = show_on_map;
    this.loading = loading;
}

// Constructor
function HotspotsMap(viewport, external_object_name, translations, images_path)
{
    // Init translations
    this.translations = translations;

    if (GBrowserIsCompatible()) {
        // Create the map attribute
        this.map = new GMap2(document.getElementById(viewport));
        this.map.addControl(new GLargeMapControl());
        this.map.addControl(new GMapTypeControl());

        // Create the array that will contain refs to markers
        //markers = Array();

        // Init source url
        this.xml_source = null;

        // This is quite stupide, but it's needed since we need to build onclick urls
        this.external_object_name = external_object_name;

        // Init server path
        this.images_path = images_path;
    } else {
        alert(this.translations.browser_support);
    }
}

HotspotsMap.prototype.getGPointFromPostalCode = function(postal_code)
{

    // Save the reference to "this" in a global var for async needs
    var self = this;
    GDownloadUrl("geocoder.php?postal_code=" + postal_code, function(data, responseCode) {
        // To ensure against HTTP errors that result in null or bad data,
        // always check status code is equal to 200 before processing the data
        if(responseCode == 200) {
	        var root_node = GXml.parse(data).documentElement
	    	self.findClosestHotspotByCoords(new GLatLng(GXml.value(root_node.getElementsByTagName("lat")[0]), GXml.value(root_node.getElementsByTagName("long")[0])));
        }else if(responseCode == -1) {
    alert("Data request timed out. Please try later.");
  } else { 
    alert("Request resulted in error. Check XML file is retrievable.");
  }

	});
}


HotspotsMap.prototype.findClosestHotspotByPostalCode = function(postal_code)
{
    if (postal_code != undefined && markers.length > 0) {
        this.getGPointFromPostalCode(postal_code);
    }
}

HotspotsMap.prototype.findClosestHotspotByCoords = function(coord)
{
    if (coord != null && markers.length > 0) {
        // Init values
        var dist = null;
        var hotspot_id = null;

        // For each registered markers
        for(i in markers) {
        	   if(markers[i] && markers[i].getPoint) {
	            // Compute the distance in meters between the two points
	            tmp = coord.distanceFrom( markers[i].getPoint());
	            if(dist == null || tmp < dist) {
	                dist = tmp
	                hotspot_id = i;
	            }
            }
        }

        // If a hotspot has been found, pop the blowup balloon
        if(hotspot_id != null) {
            this.openInfoBubble(hotspot_id);
        }
    }
}

HotspotsMap.prototype.buildHtmlFromHotspot = function(hotspot_element, icon)
{
    // Init HTML
    var html = "<table><tr><td><img src='" + icon.image + "' /></td><td>";

    /*
     * Hotspot name
     */
    var name = hotspot_element.getElementsByTagName("name");

    if (name.length == 1) {
        html += "<b>" + GXml.value(name[0]) + "</b>";
    }

    html += "<br />";

    /*
     * Civic number
     */
    var civicNumber = hotspot_element.getElementsByTagName("civicNumber");

    if (civicNumber.length == 1) {
        html += "<i>" + GXml.value(civicNumber[0]) + ",&nbsp;</i>";
    }

    /*
     * Street address
     */
    var streetAddress = hotspot_element.getElementsByTagName("streetAddress");

    if (streetAddress.length == 1) {
        html += "<i>" + GXml.value(streetAddress[0]) + ",&nbsp;</i>";
    }

    html += "<br />";

    /*
     * City
     */
    var city = hotspot_element.getElementsByTagName("city");

    if (city.length == 1) {
        html += "<i>" + GXml.value(city[0]) + ",&nbsp;</i>";
    }

    /*
     * Province
     */
    var province = hotspot_element.getElementsByTagName("province");

    if (province.length == 1) {
        html += "<i>" + GXml.value(province[0]) + ",&nbsp;</i>";
    }

    html += "<br />";

    /*
     * Postal code (ZIP)
     */
    var postalCode = hotspot_element.getElementsByTagName("postalCode");

    if (postalCode.length == 1) {
        html += "<i>" + GXml.value(postalCode[0]) + ",&nbsp;</i>";
    }

    /*
     * Country
     */
    var country = hotspot_element.getElementsByTagName("country");

    if (country.length == 1) {
        html += "<i>" + GXml.value(country[0]) + "</i>";
    }

    html += "<br />";

    /*
     * Phone
     */
    var phone = hotspot_element.getElementsByTagName("contactPhoneNumber");

    if (phone.length == 1) {
        html += "<i>" + GXml.value(phone[0]) + "</i><br />";
    }

    /*
     * Transit
     */
    var transit = hotspot_element.getElementsByTagName("massTransitInfo");

    if (transit.length == 1) {
        html += "<b>" + GXml.value(transit[0]) + "</b><br />";
    }

    /*
     * Website
     */
    var websiteUrl = hotspot_element.getElementsByTagName("webSiteUrl");

    if (websiteUrl.length == 1) {
        html += "<a href='" + GXml.value(websiteUrl[0]) + "'>" + this.translations.homepage + "</a>";
    }

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

    GEvent.addListener(marker, "click", function()
    {
        marker.openInfoWindowHtml(html);
    });
    return marker;
}

HotspotsMap.prototype.openInfoBubble = function(bubbleId)
{
    // Trigger click ( NB. markers is a global var )
    GEvent.trigger(markers[bubbleId], "click");
}

HotspotsMap.prototype.loadHotspotsStatus = function()
{
    // Make sure the source has been set
    if (this.xml_source != null) {
        // Drop the pointer to this in a global var for async needs
        var self = this;
        GDownloadUrl(this.xml_source, function(data, responseCode) {
        	self.parseHotspotsStatus(GXml.parse(data));
		});
    } else {
        return false;
    }
}

HotspotsMap.prototype.parseHotspotsStatus = function(xml_doc)
{
    var html_list = "";

    // Detect browser and set extension of image files to use
    if (typeof(window.innerWidth) == "number") {
        // Non-IE
        var image_extension = ".png";
    } else {
        // IE
        var image_extension = ".gif";
    }

    // Init marker icons
    var upIcon = this.createIcon(this.images_path + "HotspotStatusMap/up" + image_extension, new GSize(20, 34),
                                 this.images_path + "HotspotStatusMap/shadow.png", new GSize(37, 34),
                                 new GPoint(10, 20), new GPoint(10, 1));
    var downIcon = this.createIcon(this.images_path + "HotspotStatusMap/down" + image_extension, new GSize(20, 34),
                                   this.images_path + "HotspotStatusMap/shadow.png", new GSize(37, 34),
                                   new GPoint(10, 20), new GPoint(10, 1));
    var unknownIcon = this.createIcon(this.images_path + "HotspotStatusMap/unknown" + image_extension, new GSize(22, 34),
                                      this.images_path + "HotspotStatusMap/blank.gif", new GSize(22, 34),
                                      new GPoint(11, 30), new GPoint(11, 1));

    // Parse the XML DOM
    var hotspots = xml_doc.documentElement.getElementsByTagName("hotspot");

    for (var i = 0; i < hotspots.length; i++) {
        var hotspotId = hotspots[i].getElementsByTagName("hotspotId");
        var gis = hotspots[i].getElementsByTagName("gisCenterLatLong");

        if (hotspotId.length == 1 && gis.length == 1 && gis[0].getAttribute("lat") != "" && gis[0].getAttribute("long") != "" && gis[0].getAttribute("show") == "1") {
            // Extract GIS data
            var point = new GLatLng(parseFloat(gis[0].getAttribute("lat")), parseFloat(gis[0].getAttribute("long")));
            var status = hotspots[i].getElementsByTagName("globalStatus");
            var markerIcon;

            if (status.length == 1) {
                switch (GXml.value(status[0])) {
                case "100":
                    markerIcon = upIcon; // Hotspot is up
                    break;

                case "0":
                    markerIcon = downIcon; // Hotspot is down
                    break;

                default:
                    markerIcon = unknownIcon; // Unknown hotspot status
                    break;
                }
            } else {
                markerIcon = unknownIcon; // Unknown hotspot status
            }

            // Prepare fragment that will go in the sidebar
            var html = this.buildHtmlFromHotspot(hotspots[i], markerIcon);
            html_list += html + "<br /><br /><a href=\"javascript:" + this.external_object_name +".openInfoBubble('" + markers.length + "');\">" + this.translations.show_on_map + "</a><hr width='95%'/>";

            // Create, save as ID and add the marker
            var marker = this.createInfoBubble(point, markerIcon, html);

            // markers is a global var
            //markers[GXml.value(hotspotId[0])] = marker;
            markers[markers.length] = marker;
            this.map.addOverlay(marker);
        }
    }

    // Load the prepared HTML fragment in the right-hand listphoto
    this.hotspots_info_list.innerHTML = html_list;
}

HotspotsMap.prototype.redraw = function()
{
    for (i = 0;i < markers.length;i++) {
        this.map.removeOverlay(markers[i]);
    }

    this.hotspots_info_list.innerHTML = this.translations.loading;
    this.loadHotspotsStatus();
}

HotspotsMap.prototype.setHotspotsInfoList = function(hotspots_info_list)
{
    this.hotspots_info_list = document.getElementById(hotspots_info_list);
}

HotspotsMap.prototype.setInitialPosition = function(lat, lng, zoom)
{
    this.map.setCenter(new GLatLng(lat, lng), zoom);
}

HotspotsMap.prototype.setMapType = function(map_type)
{
    this.map.setMapType(map_type);
}

HotspotsMap.prototype.setOnClickListener = function(callback_fct)
{
    GEvent.addListener(this.map, 'click', callback_fct);
}

HotspotsMap.prototype.setXmlSourceUrl = function(xml_source)
{
    this.xml_source = xml_source;
}
