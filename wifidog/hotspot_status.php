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
 * Network status page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2004-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/Network.php');

if (!empty ($_REQUEST['format']))
    $format = $db->escapeString($_REQUEST['format']);
else
    $format = null;

if (!empty ($_REQUEST['network_id']))
    $network = Network :: getObject($db->escapeString($_REQUEST['network_id']));
else
    $network = Network :: getDefaultNetwork(true);

if ($network)
{
    switch ($format)
    {
        // XML format v1.0 by François proulx <francois.proulx@gmail.com>
        case "XML" :
            // Query the database, sorting by node name
            $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '".$db->escapeString($network->getId())."' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY name", $node_results, false);

            require_once('classes/Node.php');

            header("Cache-control: private, no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
            header("Pragma: no-cache");

            ob_start();

            // Prepare an XML DOM Document that will contain all the data concerning the nodes
            $xmldoc = new DOMDocument();
            $xmldoc->formatOutput = true;

            // Root node
            $hotspot_status_root_node = $xmldoc->createElement("wifidogHotspotsStatus");
            $hotspot_status_root_node->setAttribute('version', '1.0');
            $xmldoc->appendChild($hotspot_status_root_node);

            // Document metadata
            $document_gendate_node = $xmldoc->createElement("generationDateTime", gmdate("Y-m-d\Th:m:s\Z"));
            $hotspot_status_root_node->appendChild($document_gendate_node);

            // Network metadata
            $network = Network :: getCurrentNetwork();
            $network_metadata_node = $xmldoc->createElement("networkMetadata");
            $network_metadata_node = $hotspot_status_root_node->appendChild($network_metadata_node);
            $network_name_node = $xmldoc->createElement("networkUri", htmlspecialchars($network->getHomepageURL(), ENT_QUOTES));
            $network_metadata_node->appendChild($network_name_node);
            $network_name_node = $xmldoc->createElement("name", htmlspecialchars($network->getName(), ENT_QUOTES));
            $network_metadata_node->appendChild($network_name_node);
            $network_url_node = $xmldoc->createElement("websiteUrl", htmlspecialchars($network->getHomepageURL(), ENT_QUOTES));
            $network_metadata_node->appendChild($network_url_node);
            $email = Network :: GetCurrentNetwork()->getTechSupportEmail();
            if (!empty ($email))
            {
                $network_mail_node = $xmldoc->createElement("techSupportEmail", $email);
                $network_metadata_node->appendChild($network_mail_node);
            }
            $nodes_count_node = $xmldoc->createElement("hotspotsCount", count($node_results));
            $network_metadata_node->appendChild($nodes_count_node);
            $network_validusers_node = $xmldoc->createElement("validSubscribedUsersCount", $network->getNumValidUsers());
            $network_metadata_node->appendChild($network_validusers_node);

            // Get number of online users
            $online_users_count = $network->getNumOnlineUsers();
            $network_onlineusers_node = $xmldoc->createElement("onlineUsersCount", $online_users_count);
            $network_metadata_node->appendChild($network_onlineusers_node);

            if ($node_results)
            {
                // Hotspots metadata
                $hotspots_metadata_node = $xmldoc->createElement("hotspots");
                $hotspots_metadata_node = $hotspot_status_root_node->appendChild($hotspots_metadata_node);

                foreach ($node_results as $node_row)
                {
                    $node = Node :: getObject($node_row['node_id']);
                    $network = $node->getNetwork();
                    $hotspot = $xmldoc->createElement("hotspot");
                    $hotspot = $hotspots_metadata_node->appendChild($hotspot);

                    // Hotspot ID
                    $id = $xmldoc->createElement("hotspotId", $node_row['node_id']);
                    $hotspot->appendChild($id);

                    // Hotspot name
                    if (!empty ($node_row['name']))
                    {
                        $name = $xmldoc->createElement("name", htmlspecialchars($node_row['name'], ENT_QUOTES));
                        $hotspot->appendChild($name);
                    }

                    // (1..n) A Hotspot has many node
                    // WARNING For now, we are simply duplicating the hotspot data in node
                    // Until wifidog implements full abstractiong hotspot vs nodes
                    $nodes = $xmldoc->createElement("nodes");
                    $hotspot->appendChild($nodes);
                    if ($nodes)
                    {
                        $node = $xmldoc->createElement("node");
                        $nodes->appendChild($node);

                        // Node ID
                        $nodeId = $xmldoc->createElement("nodeId", $node_row['node_id']);
                        $node->appendChild($nodeId);

                        if (!empty ($node_row['creation_date']))
                        {
                            $creation_date = $xmldoc->createElement("creationDate", $node_row['creation_date']);
                            $node->appendChild($creation_date);
                        }

                        if (!empty ($node_row['node_deployment_status']) && $node_row['node_deployment_status'] != 'NON_WIFIDOG_NODE')
                        {
                            if ($node_row['is_up'] == 't')
                                $status = $xmldoc->createElement("status", "up");
                            else
                                $status = $xmldoc->createElement("status", "down");
                            $node->appendChild($status);
                        }

                        if (!empty ($node_row['longitude']) && !empty ($node_row['latitude']))
                        {
                            $gis = $xmldoc->createElement("gisLatLong");
                            $gis->setAttribute("lat", $node_row['latitude']);
                            $gis->setAttribute("long", $node_row['longitude']);
                            $node->appendChild($gis);
                        }
                    }

                    // Hotspot opening date ( for now it's called creation_date )
                    if (!empty ($node_row['creation_date']))
                    {
                        $opening_date = $xmldoc->createElement("openingDate", $node_row['creation_date']);
                        $hotspot->appendChild($opening_date);
                    }

                    // Hotspot Website URL
                    if (!empty ($node_row['home_page_url']))
                    {
                        $url = $xmldoc->createElement("webSiteUrl", htmlspecialchars($node_row['home_page_url'], ENT_QUOTES));
                        $hotspot->appendChild($url);
                    }

                    // Hotspot global status
                    if (!empty ($node_row['node_deployment_status']) && $node_row['node_deployment_status'] != 'NON_WIFIDOG_NODE')
                    {
                        // Until we implement the complete node / hotspot paradigm,
                        // we are simply stating that up = 100% and down = 0%
                        if ($node_row['is_up'] == 't')
                            $status = $xmldoc->createElement("globalStatus", "100");
                        else
                            $status = $xmldoc->createElement("globalStatus", "0");
                        $hotspot->appendChild($status);
                    }

                    // Description
                    if (!empty ($node_row['description']))
                    {
                        $desc = $xmldoc->createElement("description", htmlspecialchars($node_row['description'], ENT_QUOTES));
                        $hotspot->appendChild($desc);
                    }

                    // Map Url
                    if (!empty ($node_row['map_url']))
                    {
                        $map_url = $xmldoc->createElement("mapUrl", htmlspecialchars($node_row['map_url'], ENT_QUOTES));
                        $hotspot->appendChild($map_url);
                    }

                    // Mass transit info
                    if (!empty ($node_row['mass_transit_info']))
                    {
                        $transit = $xmldoc->createElement("massTransitInfo", htmlspecialchars($node_row['mass_transit_info'], ENT_QUOTES));
                        $hotspot->appendChild($transit);
                    }

                    // Contact e-mail
                    if (!empty ($node_row['public_email']))
                    {
                        $contact_email = $xmldoc->createElement("contactEmail", $node_row['public_email']);
                        $hotspot->appendChild($contact_email);
                    }

                    // Contact phone
                    if (!empty ($node_row['public_phone_number']))
                    {
                        $contact_phone = $xmldoc->createElement("contactPhoneNumber", $node_row['public_phone_number']);
                        $hotspot->appendChild($contact_phone);
                    }

                    // Civic number
                    if (!empty ($node_row['civic_number']))
                    {
                        $civic_nbr = $xmldoc->createElement("civicNumber", $node_row['civic_number']);
                        $hotspot->appendChild($civic_nbr);
                    }

                    // Street address
                    if (!empty ($node_row['street_name']))
                    {
                        $street_addr = $xmldoc->createElement("streetAddress", $node_row['street_name']);
                        $hotspot->appendChild($street_addr);
                    }

                    // City
                    if (!empty ($node_row['city']))
                    {
                        $city = $xmldoc->createElement("city", $node_row['city']);
                        $hotspot->appendChild($city);
                    }

                    // Province
                    if (!empty ($node_row['province']))
                    {
                        $province = $xmldoc->createElement("province", $node_row['province']);
                        $hotspot->appendChild($province);
                    }

                    // Postal code
                    if (!empty ($node_row['postal_code']))
                    {
                        $postal_code = $xmldoc->createElement("postalCode", $node_row['postal_code']);
                        $hotspot->appendChild($postal_code);
                    }

                    // Country
                    if (!empty ($node_row['country']))
                    {
                        $country = $xmldoc->createElement("country", $node_row['country']);
                        $hotspot->appendChild($country);
                    }

                    // Long / Lat
                    if (!empty ($node_row['longitude']) && !empty ($node_row['latitude']))
                    {
                        $gisCenter = $xmldoc->createElement("gisCenterLatLong");
                        $gisCenter->setAttribute("lat", $node_row['latitude']);
                        $gisCenter->setAttribute("long", $node_row['longitude']);
                        $hotspot->appendChild($gisCenter);
                    }
                }
            }

            ob_clean();

            // If a XSL transform stylesheet has been specified, try to us it.
            if (defined('XSLT_SUPPORT') && XSLT_SUPPORT == true && !empty ($_REQUEST['xslt']) && ($xslt_dom = @ DomDocument :: load($_REQUEST['xslt'])) !== false)
            {
                // Load the XSLT
                $xslt_proc = new XsltProcessor();
                $xslt_proc->importStyleSheet($xslt_dom);

                // Prepare HTML
                header("Content-Type: text/html; charset=UTF-8");
                echo $xslt_proc->transformToXML($xmldoc);
            }
            else
            {
                header("Content-Type: text/xml; charset=UTF-8");
                echo $xmldoc->saveXML();
            }

            break;
        case "RSS" :
            // Query the database, sorting by creation date
            $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '".$db->escapeString($network->getId())."' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY creation_date", $node_results, false);

            Header("Cache-control: private, no-cache, must-revalidate");
            Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
            Header("Pragma: no-cache");
            Header("Content-Type: text/xml; charset=UTF-8");

            // Network metadata
            $network = Network :: getCurrentNetwork();
            $xmldoc = new DOMDocument();
            $xmldoc->formatOutput = true;
            //$xmldoc->encoding="iso-8859-15";
            $rss = $xmldoc->createElement("rss");
            $xmldoc->appendChild($rss);
            $rss->setAttribute('version', '2.0');

            /* channel */
            $channel = $xmldoc->createElement("channel");
            $rss->appendChild($channel);

            /**************** Required channel elements ********************/
            /* title */
            $title = $xmldoc->createElement("title");
            $title = $channel->appendChild($title);

            $textnode = $xmldoc->createTextNode($network->getName()._(": Newest HotSpots"));
            $title->appendChild($textnode);

            /* link */
            $link = $xmldoc->createElement("link");
            $channel->appendChild($link);
            $textnode = $xmldoc->createTextNode($network->getHomepageURL());
            $link->appendChild($textnode);

            /* description */
            $description = $xmldoc->createElement("description");
            $channel->appendChild($description);
            $textnode = $xmldoc->createTextNode(_("WiFiDog list of the most recent HotSpots opened by the network: ").$network->getName());
            $description->appendChild($textnode);

            /****************** Optional channel elements *******************/
            /* language */
            /**@todo Make language selectable */
            $language = $xmldoc->createElement("language");
            $channel->appendChild($language);
            $textnode = $xmldoc->createTextNode("en-CA");
            $language->appendChild($textnode);

            /* copyright */
            $copyright = $xmldoc->createElement("copyright");
            $channel->appendChild($copyright);
            $textnode = $xmldoc->createTextNode(_("Copyright ").$network->getName());
            $copyright->appendChild($textnode);

            /* managingEditor */

            /* webMaster */
            $email = Network :: GetCurrentNetwork()->getTechSupportEmail();
            if (!empty ($email))
            {
                $webMaster = $xmldoc->createElement("webMaster");
                $channel->appendChild($webMaster);
                $textnode = $xmldoc->createTextNode($email);
                $webMaster->appendChild($textnode);
            }

            /* pubDate */
            $pubDate = $xmldoc->createElement("pubDate");
            $channel->appendChild($pubDate);
            $textnode = $xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", time()));
            $pubDate->appendChild($textnode);

            /* lastBuildDate */
            //<lastBuildDate> -- The date-time the last time the content of the channel changed.
            /* Make a request through the database for the latest modification date of an object.
             * Maybe it should be an object property? */
            $db->execSqlUniqueRes("SELECT EXTRACT(epoch FROM MAX(creation_date)) as date_last_hotspot_opened FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ", $last_hotspot_row, false);

            $lastBuildDate = $xmldoc->createElement("lastBuildDate");
            $channel->appendChild($lastBuildDate);
            $textnode = $xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", $last_hotspot_row['date_last_hotspot_opened']));
            $lastBuildDate->appendChild($textnode);

            /* category */
            /* Specify one or more categories that the channel belongs to.
             *  Follows the same rules as the <item>-level category element.*/

            /* generator */
            $generator = $xmldoc->createElement("generator");
            $channel->appendChild($generator);
            $textnode = $xmldoc->createTextNode(WIFIDOG_NAME." ".WIFIDOG_VERSION);
            $generator->appendChild($textnode);

            /* docs */
            $docs = $xmldoc->createElement("docs");
            $channel->appendChild($docs);
            $textnode = $xmldoc->createTextNode("http://blogs.law.harvard.edu/tech/rss");
            $docs->appendChild($textnode);

            /* cloud */
            /* Allows processes to register with a cloud to be notified of updates to the channel, implementing a lightweight publish-subscribe protocol for RSS feeds.*/

            /* ttl */
            /* ttl stands for time to live. It's a number of minutes that indicates how long a channel can be cached before refreshing from the source.*/

            /* image */
            $image = $xmldoc->createElement("image");
            $channel->appendChild($image);

            /* title */
            $title = $xmldoc->createElement("title");
            $image->appendChild($title);
            $textnode = $xmldoc->createTextNode($network->getName());
            $title->appendChild($textnode);
            /* url */
            $url = $xmldoc->createElement("url");
            $image->appendChild($url);
            $textnode = $xmldoc->createTextNode(COMMON_CONTENT_URL.NETWORK_LOGO_NAME);
            $url->appendChild($textnode);
            /* link */
            $link = $xmldoc->createElement("link");
            $image->appendChild($link);
            $textnode = $xmldoc->createTextNode($network->getHomepageURL());
            $link->appendChild($textnode);
            /* width */
            /*
             $width = $xmldoc->createElement("width");
             $image->appendChild($width);
             $textnode = $xmldoc->createTextNode('135');
             $width->appendChild($textnode);
            */
            /* height */
            /*
             $height = $xmldoc->createElement("height");
             $image->appendChild($height);
             $textnode = $xmldoc->createTextNode('109');
             $height->appendChild($textnode);
            */
            /* description */
            /*
             $description = $xmldoc->createElement("description");
             $image->appendChild($description);
             $textnode = $xmldoc->createTextNode("Le portail des TIC");
             $description->appendChild($textnode);
            */

            /* rating */
            /* textInput */
            /* skipHours */
            /* skipDays */

            $i = 0;

            if ($node_results)
                foreach ($node_results as $node_row)
                {

                    $item = $xmldoc->createElement("item");
                    $item = $channel->appendChild($item);

                    /* title */
                    /* lom_1_2_title_langstrings_id */
                    $title = $xmldoc->createElement("title");
                    $item->appendChild($title);
                    $title_str = $node_row['name'];
                    $textnode = $xmldoc->createTextNode($title_str);
                    $title->appendChild($textnode);

                    /* link */
                    if (!empty ($node_row['home_page_url']))
                    {
                        $link = $xmldoc->createElement("link");
                        $item->appendChild($link);
                        $textnode = $xmldoc->createTextNode($node_row['home_page_url']);
                        $link->appendChild($textnode);
                    }

                    /* description */
                    $description = $xmldoc->createElement("description");
                    $item->appendChild($description);
                    $description_text = '<p>';

                    if ($node_row['node_deployment_status'] != 'NON_WIFIDOG_NODE')
                    {
                        if ($node_row['is_up'] == 't')
                        {
                            $description_text .= "<img src='".BASE_URL_PATH."images/hotspot_status_up.png'> ";
                        }
                        else
                        {
                            $description_text .= "<img src='".BASE_URL_PATH."images/hotspot_status_down.png'> ";
                        }
                    }

                    if (!empty ($node_row['description']))
                    {
                        $description_text .= $node_row['description'];
                    }
                    $description_text .= "</p>\n";
                    $description_text .= "<p>\n";
                    $description_text .= ""._("Address:")." ";

                    // Civic number
                    if (!empty ($node_row['civic_number']))
                    {
                        $description_text .= $node_row['civic_number'].", ";
                    }

                    if (!empty ($node_row['street_name']))
                    {
                        $description_text .= $node_row['street_name'].", ";
                    }

                    // City
                    if (!empty ($node_row['city']))
                    {
                        $description_text .= $node_row['city'].", ";
                    }

                    // Province
                    if (!empty ($node_row['province']))
                    {
                        $description_text .= $node_row['province'].", ";
                    }

                    // Postal code
                    if (!empty ($node_row['postal_code']))
                    {
                        $description_text .= $node_row['postal_code'].", ";
                    }

                    // Country
                    if (!empty ($node_row['country']))
                    {
                        $description_text .= $node_row['country'];
                    }

                    if (!empty ($node_row['map_url']))
                    {
                        $description_text .= " <a href='".$node_row['map_url']."'>"._("See Map")."</a> ";
                    }
                    $description_text .= "<br/>\n";
                    if (!empty ($node_row['mass_transit_info']))
                    {
                        $description_text .= ""._("Mass transit:")." ".$node_row['mass_transit_info']."<br/>\n";
                    }
                    $description_text .= "</p>\n";
                    if (!empty ($node_row['public_email']) || !empty ($node_row['public_phone_number']))
                    {
                        $description_text .= "<p>"._("Contact:");

                        if (!empty ($node_row['public_phone_number']))
                        {
                            $description_text .= " $node_row[public_phone_number] ";
                        }
                        if (!empty ($node_row['public_email']))
                        {
                            $description_text .= " <a href='mailto:".$node_row['public_email']."'>$node_row[public_email]</a> ";
                        }
                        $description_text .= "</p>\n";
                    }
                    $textnode = $xmldoc->createTextNode($description_text);
                    $description->appendChild($textnode);

                    /* author */
                    /*
                     $author = $xmldoc->createElement("author");
                     $item->appendChild($author);
                     $textnode = $xmldoc->createTextNode($author_vcard->GetEmail().' ('.$author_vcard->GetName().')');
                     $author->appendChild($textnode);
                    */
                    /* category */

                    /* comments */
                    /** Link to page once page is available **/
                    /* enclosure */
                    /* guid */

                    /* guid */
                    if (!empty($node_row['home_page_url']))
                    {
                        $guid = $xmldoc->createElement("guid");
                        $guid->setAttribute('isPermaLink', 'false');
                        $item->appendChild($guid);
                        $textnode = $xmldoc->createTextNode($node_row['home_page_url']);
                        $guid->appendChild($textnode);
                    }

                    /* pubDate */
                    $pubDate = $xmldoc->createElement("pubDate");
                    $item->appendChild($pubDate);
                    $textnode = $xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", $node_row['creation_date_epoch']));
                    $pubDate->appendChild($textnode);

                    /* source */
                }
            @ ob_clean();
            echo $xmldoc->saveXML();
            break;
        case "WIFI411_CSV" :
            // Query the database, sorting by creation date
            $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '".$db->escapeString($network->getId())."' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY name", $node_results, false);

            /* Header("Cache-control: private, no-cache, must-revalidate");
             Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
             Header("Pragma: no-cache");
             Header("Content-Type: text/xml; charset=UTF-8");*/

            $xmldoc = new DOMDocument();
            $xmldoc->formatOutput = true;
            //$xmldoc->encoding="iso-8859-15";
            $rss = $xmldoc->createElement("rss");
            $xmldoc->appendChild($rss);
            $rss->setAttribute('version', '2.0');

            /* channel */
            $channel = $xmldoc->createElement("channel");
            $rss->appendChild($channel);

            /**************** Required channel elements ********************/
            /* title */
            $title = $xmldoc->createElement("title");
            $title = $channel->appendChild($title);

            $textnode = $xmldoc->createTextNode(utf8_encode($network->getName()._(": Newest HotSpots")));
            $title->appendChild($textnode);

            /* link */
            $link = $xmldoc->createElement("link");
            $channel->appendChild($link);
            $textnode = $xmldoc->createTextNode(utf8_encode($network->getHomepageURL()));
            $link->appendChild($textnode);

            /* description */
            $description = $xmldoc->createElement("description");
            $channel->appendChild($description);
            $textnode = $xmldoc->createTextNode(utf8_encode(_("WiFiDog list of the most recent HotSpots opened by the network: ").$network->getName()));
            $description->appendChild($textnode);

            /****************** Optional channel elements *******************/
            /* language */
            /**@todo Make language selectable */
            $language = $xmldoc->createElement("language");
            $channel->appendChild($language);
            $textnode = $xmldoc->createTextNode("en-CA");
            $language->appendChild($textnode);

            /* copyright */
            $copyright = $xmldoc->createElement("copyright");
            $channel->appendChild($copyright);
            $textnode = $xmldoc->createTextNode(utf8_encode(_("Copyright ").$network->getName()));
            $copyright->appendChild($textnode);

            /* managingEditor */

            /* webMaster */
            $email = Network :: GetCurrentNetwork()->getTechSupportEmail();
            if (!empty ($email))
            {
                $webMaster = $xmldoc->createElement("webMaster");
                $channel->appendChild($webMaster);
                $textnode = $xmldoc->createTextNode($email);
                $webMaster->appendChild($textnode);
            }

            /* pubDate */
            $pubDate = $xmldoc->createElement("pubDate");
            $channel->appendChild($pubDate);
            $textnode = $xmldoc->createTextNode(utf8_encode(gmdate("D, d M Y H:i:s \G\M\T", time())));
            $pubDate->appendChild($textnode);

            /* lastBuildDate */
            //<lastBuildDate> -- The date-time the last time the content of the channel changed.
            /* Make a request through the database for the latest modification date of an object.
             * Maybe it should be an object property? */
            $db->execSqlUniqueRes("SELECT EXTRACT(epoch FROM MAX(creation_date)) as date_last_hotspot_opened FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ", $last_hotspot_row, false);

            $lastBuildDate = $xmldoc->createElement("lastBuildDate");
            $channel->appendChild($lastBuildDate);
            $textnode = $xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", $last_hotspot_row['date_last_hotspot_opened']));
            $lastBuildDate->appendChild($textnode);

            /* category */
            /* Specify one or more categories that the channel belongs to.
             *  Follows the same rules as the <item>-level category element.*/

            /* generator */
            $generator = $xmldoc->createElement("generator");
            $channel->appendChild($generator);
            $textnode = $xmldoc->createTextNode(utf8_encode(WIFIDOG_NAME." ".WIFIDOG_VERSION));
            $generator->appendChild($textnode);

            /* docs */
            $docs = $xmldoc->createElement("docs");
            $channel->appendChild($docs);
            $textnode = $xmldoc->createTextNode(utf8_encode("http://blogs.law.harvard.edu/tech/rss"));
            $docs->appendChild($textnode);

            /* cloud */
            /* Allows processes to register with a cloud to be notified of updates to the channel, implementing a lightweight publish-subscribe protocol for RSS feeds.*/

            /* ttl */
            /* ttl stands for time to live. It's a number of minutes that indicates how long a channel can be cached before refreshing from the source.*/

            /* image */
            $image = $xmldoc->createElement("image");
            $channel->appendChild($image);

            /* title */
            $title = $xmldoc->createElement("title");
            $image->appendChild($title);
            $textnode = $xmldoc->createTextNode(utf8_encode($network->getName()));
            $title->appendChild($textnode);
            /* url */
            $url = $xmldoc->createElement("url");
            $image->appendChild($url);
            $textnode = $xmldoc->createTextNode(utf8_encode(COMMON_CONTENT_URL.NETWORK_LOGO_NAME));
            $url->appendChild($textnode);
            /* link */
            $link = $xmldoc->createElement("link");
            $image->appendChild($link);
            $textnode = $xmldoc->createTextNode(utf8_encode($network->getHomepageURL()));
            $link->appendChild($textnode);
            /* width */
            /*
             $width = $xmldoc->createElement("width");
             $image->appendChild($width);
             $textnode = $xmldoc->createTextNode('135');
             $width->appendChild($textnode);
            */
            /* height */
            /*
             $height = $xmldoc->createElement("height");
             $image->appendChild($height);
             $textnode = $xmldoc->createTextNode('109');
             $height->appendChild($textnode);
            */
            /* description */
            /*
             $description = $xmldoc->createElement("description");
             $image->appendChild($description);
             $textnode = $xmldoc->createTextNode("Le portail des TIC");
             $description->appendChild($textnode);
            */

            /* rating */
            /* textInput */
            /* skipHours */
            /* skipDays */

            $i = 0;

            if ($node_results)
                foreach ($node_results as $node_row)
                {

                    $item = $xmldoc->createElement("item");
                    $item = $channel->appendChild($item);

                    /* title */
                    /* lom_1_2_title_langstrings_id */
                    $title = $xmldoc->createElement("title");
                    $item->appendChild($title);
                    $title_str = $node_row['name'];
                    $textnode = $xmldoc->createTextNode(utf8_encode($title_str));
                    $title->appendChild($textnode);

                    /* link */
                    if (!empty ($node_row['home_page_url']))
                    {
                        $link = $xmldoc->createElement("link");
                        $item->appendChild($link);
                        $textnode = $xmldoc->createTextNode(utf8_encode($node_row['home_page_url']));
                        $link->appendChild($textnode);
                    }

                    /* description */
                    $description = $xmldoc->createElement("description");
                    $item->appendChild($description);
                    $description_text = '<p>';
                    if ($node_row['node_deployment_status'] != 'NON_WIFIDOG_NODE')
                    {
                        if ($node_row['is_up'] == 't')
                        {
                            $description_text .= "<img src='".BASE_URL_PATH."images/hotspot_status_up.png'> ";
                        }
                        else
                        {
                            $description_text .= "<img src='".BASE_URL_PATH."images/hotspot_status_down.png'> ";
                        }
                    }

                    if (!empty ($node_row['description']))
                    {
                        $description_text .= $node_row['description'];
                    }
                    $description_text .= "</p>\n";
                    $description_text .= "<p>\n";
                    if (!empty ($node_row['street_address']))
                    {
                        $description_text .= ""._("Address:")." ".$node_row['street_address']." ";
                    }
                    if (!empty ($node_row['map_url']))
                    {
                        $description_text .= " <a href='".$node_row['map_url']."'>"._("See Map")."</a> ";
                    }
                    $description_text .= "<br/>\n";
                    if (!empty ($node_row['mass_transit_info']))
                    {
                        $description_text .= ""._("Mass transit:")." ".$node_row['mass_transit_info']."<br/>\n";
                    }
                    $description_text .= "</p>\n";
                    if (!empty ($node_row['public_email']) || !empty ($node_row['public_phone_number']))
                    {
                        $description_text .= "<p>"._("Contact:");

                        if (!empty ($node_row['public_phone_number']))
                        {
                            $description_text .= " $node_row[public_phone_number] ";
                        }
                        if (!empty ($node_row['public_email']))
                        {
                            $description_text .= " <a href='".$node_row['public_email']."'>$node_row[public_email]</a> ";
                        }
                        $description_text .= "</p>\n";
                    }
                    $textnode = $xmldoc->createTextNode(utf8_encode($description_text));
                    $description->appendChild($textnode);

                    /* author */
                    /*
                     $author = $xmldoc->createElement("author");
                     $item->appendChild($author);
                     $textnode = $xmldoc->createTextNode($author_vcard->GetEmail().' ('.$author_vcard->GetName().')');
                     $author->appendChild($textnode);
                    */
                    /* category */

                    /* comments */
                    /** Link to page once page is available **/
                    /* enclosure */
                    /* guid */

                    $guid = $xmldoc->createElement("guid");
                    $guid->setAttribute('isPermaLink', 'false');
                    $item->appendChild($guid);
                    $textnode = $xmldoc->createTextNode(utf8_encode($network->getHomepageURL().$node_row['node_id']));
                    $guid->appendChild($textnode);

                    /* pubDate */
                    $pubDate = $xmldoc->createElement("pubDate");
                    $item->appendChild($pubDate);
                    $textnode = $xmldoc->createTextNode(utf8_encode(gmdate("D, d M Y H:i:s \G\M\T", $node_row['creation_date_epoch'])));
                    $pubDate->appendChild($textnode);

                    /* source */
                }
            ob_clean();
            echo $xmldoc->saveXML();
            break;
        default :
            // Query the database, sorting by node name
            $db->execSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '".$db->escapeString($network->getId())."' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY name", $node_results, false);

            if ($node_results)
                foreach ($node_results as $node_row)
                {
                    $node = Node :: getObject($node_row['node_id']);
                    $node_row['num_online_users'] = $node->getNumOnlineUsers();
                    $smarty->append("nodes", $node_row);
                }
            $smarty->assign("num_deployed_nodes", count($node_results));

            require_once('classes/MainUI.php');

            if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true)
            {
                $tool_html = '<p class="indent">'."\n";
                $tool_html .= "<ul class='users_list'>\n";
                $tool_html .= "<li><a href='".BASE_NON_SSL_PATH."hotspots_map.php'>"._('Deployed HotSpots map')."</a></li>";
                $tool_html .= "</ul>\n";
                $tool_html .= '</p>'."\n";
            }
            else
                $tool_html = "";

            $ui = new MainUI();
            $ui->setToolContent($tool_html);
            $ui->setTitle(_("Hotspot list"));
            $ui->setMainContent($smarty->fetch("templates/hotspot_status.html"));
            $ui->display();
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>