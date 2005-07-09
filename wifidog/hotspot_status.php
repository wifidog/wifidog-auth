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
/**@file hotspot_status.php
 * Network status page
 * @author Copyright (C) 2004, 2005 Benoit Grégoire and François Proulx
 */

define('BASEPATH', './');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';

if (!empty ($_REQUEST['format']))
	$format = $_REQUEST['format'];
else
	$format = null;

$db->ExecSql("SELECT *, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ORDER BY creation_date", $node_results, false);
switch ($format)
{
	// XML format v1.0 by Françcois proulx <francois.proulx@gmail.com>
	//TODO: rough draft, should be moved to different classes once stabilized
	case "XML":
		require_once BASEPATH.'classes/Network.php';
		require_once BASEPATH.'classes/Node.php';
		
		header("Cache-control: private, no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
		header("Pragma: no-cache");
		
		ob_start();
		
		// Prepare an XML DOM Document that will contain all the data concerning the nodes
		$xmldoc = new DOMDocument();
		$xmldoc->formatOutput = true;
		
		// Root node
		$hotspot_status_root_node = $xmldoc->createElement("wifidogVenuesStatus");
		$hotspot_status_root_node->setAttribute('version', '1.0');
		$xmldoc->appendChild($hotspot_status_root_node);
		
		// Document metadata
		$document_gendate_node = $xmldoc->createElement("generationDateTime", gmdate("Y-m-d\Th:m:s\Z"));
		$hotspot_status_root_node->appendChild($document_gendate_node);
		
		// Network metadata
		$network_metadata_node = $xmldoc->createElement("networkMetadata");
		$network_metadata_node = $hotspot_status_root_node->appendChild($network_metadata_node);
		$network_name_node = $xmldoc->createElement("name", HOTSPOT_NETWORK_NAME);
		$network_metadata_node->appendChild($network_name_node);
		$network_url_node = $xmldoc->createElement("websiteUrl", HOTSPOT_NETWORK_URL);
		$network_metadata_node->appendChild($network_url_node);
		$network_mail_node = $xmldoc->createElement("techSupportEmail", TECH_SUPPORT_EMAIL);
		$network_metadata_node->appendChild($network_mail_node);
		$nodes_count_node = $xmldoc->createElement("venuesCount", count($node_results));
		$network_metadata_node->appendChild($nodes_count_node);
		$network_validusers_node = $xmldoc->createElement("validSubscribedUsersCount", $stats->getNumValidUsers());
		$network_metadata_node->appendChild($network_validusers_node);
		
		// Get number of online users
		$online_users_count = 0;
		if ($node_results)
			foreach ($node_results as $node_row)
				$online_users_count = $stats->getNumOnlineUsers($node_row['node_id']);
			
		$network_onlineusers_node = $xmldoc->createElement("onlineUsersCount", $online_users_count);
		$network_metadata_node->appendChild($network_onlineusers_node);
		
		
		if ($node_results)
		{
			// Nodes statusadata
			$nodes_status_node = $xmldoc->createElement("venuesMetadata");
			$nodes_status_node = $hotspot_status_root_node->appendChild($nodes_status_node);
			
			foreach ($node_results as $node_row)
			{
				$node = $xmldoc->createElement("venue");
				$node = $nodes_status_node->appendChild($node);
				
				// Node ID
				$id = $xmldoc->createElement("venueId", $node_row['node_id']);
				$node->appendChild($id);
				
				// Node name
				if (!empty ($node_row['name']))
				{
					$name = $xmldoc->createElement("name", $node_row['name']);
					$node->appendChild($name);
				}
				
				// Node deployment status
				if (!empty ($node_row['node_deployment_status']))
				{
					$dep_status = $xmldoc->createElement("deploymentStatus", $node_row['node_deployment_status']);
					$node->appendChild($dep_status);
				}
				
				// Creation date
				if (!empty ($node_row['creation_date']))
				{
					$creation_date = $xmldoc->createElement("creationDate", $node_row['creation_date']);
					$node->appendChild($creation_date);
				}
				
				// Last heartbeat
				if (!empty ($node_row['last_heartbeat_timestamp']))
				{
					$creation_date = $xmldoc->createElement("lastHeartbeat", $node_row['last_heartbeat_timestamp']);
					$node->appendChild($creation_date);
				}

				// Node Website URL
				if (!empty ($node_row['home_page_url']))
				{
					$url = $xmldoc->createElement("webSiteUrl", $node_row['home_page_url']);
					$node->appendChild($url);
				}
				
				// Node heartbeat
				if (!empty ($node_row['node_deployment_status']) && $node_row['node_deployment_status'] != 'NON_WIFIDOG_NODE')
				{
					if ($node_row['is_up'] == 't')
						$status = $xmldoc->createElement("status", "up");
					else
						$status = $xmldoc->createElement("status", "down");
					$node->appendChild($status);
				}
				
				// Description
				if (!empty ($node_row['description']))
				{
					$desc = $xmldoc->createElement("description", $node_row['description']);
					$node->appendChild($desc);
				}

				// Map Url
				if (!empty ($node_row['map_url']))
				{
					$map_url = $xmldoc->createElement("mapUrl", htmlspecialchars($node_row['map_url'], ENT_QUOTES));
					$node->appendChild($map_url);
				}
				
				// Mass transit info
				if (!empty ($node_row['mass_transit_info']))
				{
					$transit = $xmldoc->createElement("massTransitInfo", $node_row['mass_transit_info']);
					$node->appendChild($transit);
				}
				
				// Contact e-mail
				if (!empty ($node_row['public_email']))
				{
					$contact_email = $xmldoc->createElement("contactEmail", $node_row['public_email']);
					$node->appendChild($contact_email);
				}
				
				// Contact phone
				if (!empty ($node_row['public_phone_number']))
				{
					$contact_phone = $xmldoc->createElement("contactPhoneNumber", $node_row['public_phone_number']);
					$node->appendChild($contact_phone);
				}
				
				// Street address
				if (!empty ($node_row['street_address']))
				{
					$street_addr = $xmldoc->createElement("streetAddress", $node_row['street_address']);
					$node->appendChild($street_addr);
				}
				
				// Long / Lat
				if (!empty ($node_row['longitude']) && !empty ($node_row['latitude']))
				{
					$gis = $xmldoc->createElement("gisLatLong");
					$gis->setAttribute("lat",  $node_row['latitude']);
					$gis->setAttribute("long",  $node_row['longitude']);
					$node->appendChild($gis);
				}
			}
		}
		
		ob_clean();
		
		// If a XSL transform stylesheet has been specified, try to us it.
		if(defined('XSLT_SUPPORT') && XSLT_SUPPORT == true && !empty($_REQUEST['xslt']) && ($xslt_dom = @DomDocument::load($_REQUEST['xslt'])) !== false)
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
		Header("Cache-control: private, no-cache, must-revalidate");
		Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
		Header("Pragma: no-cache");
		Header("Content-Type: text/xml; charset=UTF-8");

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

		$textnode = $xmldoc->createTextNode(HOTSPOT_NETWORK_NAME._(": Newest HotSpots"));
		$title->appendChild($textnode);

		/* link */
		$link = $xmldoc->createElement("link");
		$channel->appendChild($link);
		$textnode = $xmldoc->createTextNode(HOTSPOT_NETWORK_URL);
		$link->appendChild($textnode);

		/* description */
		$description = $xmldoc->createElement("description");
		$channel->appendChild($description);
		$textnode = $xmldoc->createTextNode(_("WiFiDog list of the most recent HotSpots opened by the network: ").HOTSPOT_NETWORK_NAME);
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
		$textnode = $xmldoc->createTextNode(_("Copyright ").HOTSPOT_NETWORK_NAME);
		$copyright->appendChild($textnode);

		/* managingEditor */

		/* webMaster */

		$webMaster = $xmldoc->createElement("webMaster");
		$channel->appendChild($webMaster);
		$textnode = $xmldoc->createTextNode(TECH_SUPPORT_EMAIL);
		$webMaster->appendChild($textnode);

		/* pubDate */
		$pubDate = $xmldoc->createElement("pubDate");
		$channel->appendChild($pubDate);
		$textnode = $xmldoc->createTextNode(gmdate("D, d M Y H:i:s \G\M\T", time()));
		$pubDate->appendChild($textnode);

		/* lastBuildDate */
		//<lastBuildDate> -- The date-time the last time the content of the channel changed.
		/* Make a request through the database for the latest modification date of an object.  
		 * Maybe it should be an object property? */
		$db->ExecSqlUniqueRes("SELECT EXTRACT(epoch FROM MAX(creation_date)) as date_last_hotspot_opened FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ", $last_hotspot_row, false);

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
		$textnode = $xmldoc->createTextNode(HOTSPOT_NETWORK_NAME);
		$title->appendChild($textnode);
		/* url */
		$url = $xmldoc->createElement("url");
		$image->appendChild($url);
		$textnode = $xmldoc->createTextNode(COMMON_CONTENT_URL.NETWORK_LOGO_NAME);
		$url->appendChild($textnode);
		/* link */
		$link = $xmldoc->createElement("link");
		$image->appendChild($link);
		$textnode = $xmldoc->createTextNode(HOTSPOT_NETWORK_URL);
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

				$guid = $xmldoc->createElement("guid");
				$guid->setAttribute('isPermaLink', 'false');
				$item->appendChild($guid);
				$textnode = $xmldoc->createTextNode(HOTSPOT_NETWORK_URL.$node_row['node_id']);
				$guid->appendChild($textnode);

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

		$textnode = $xmldoc->createTextNode(utf8_encode(HOTSPOT_NETWORK_NAME._(": Newest HotSpots")));
		$title->appendChild($textnode);

		/* link */
		$link = $xmldoc->createElement("link");
		$channel->appendChild($link);
		$textnode = $xmldoc->createTextNode(utf8_encode(HOTSPOT_NETWORK_URL));
		$link->appendChild($textnode);

		/* description */
		$description = $xmldoc->createElement("description");
		$channel->appendChild($description);
		$textnode = $xmldoc->createTextNode(utf8_encode(_("WiFiDog list of the most recent HotSpots opened by the network: ").HOTSPOT_NETWORK_NAME));
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
		$textnode = $xmldoc->createTextNode(utf8_encode(_("Copyright ").HOTSPOT_NETWORK_NAME));
		$copyright->appendChild($textnode);

		/* managingEditor */

		/* webMaster */

		$webMaster = $xmldoc->createElement("webMaster");
		$channel->appendChild($webMaster);
		$textnode = $xmldoc->createTextNode(utf8_encode(TECH_SUPPORT_EMAIL));
		$webMaster->appendChild($textnode);

		/* pubDate */
		$pubDate = $xmldoc->createElement("pubDate");
		$channel->appendChild($pubDate);
		$textnode = $xmldoc->createTextNode(utf8_encode(gmdate("D, d M Y H:i:s \G\M\T", time())));
		$pubDate->appendChild($textnode);

		/* lastBuildDate */
		//<lastBuildDate> -- The date-time the last time the content of the channel changed.
		/* Make a request through the database for the latest modification date of an object.  
		 * Maybe it should be an object property? */
		$db->ExecSqlUniqueRes("SELECT EXTRACT(epoch FROM MAX(creation_date)) as date_last_hotspot_opened FROM nodes WHERE node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE' ", $last_hotspot_row, false);

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
		$textnode = $xmldoc->createTextNode(utf8_encode(HOTSPOT_NETWORK_NAME));
		$title->appendChild($textnode);
		/* url */
		$url = $xmldoc->createElement("url");
		$image->appendChild($url);
		$textnode = $xmldoc->createTextNode(utf8_encode(COMMON_CONTENT_URL.NETWORK_LOGO_NAME));
		$url->appendChild($textnode);
		/* link */
		$link = $xmldoc->createElement("link");
		$image->appendChild($link);
		$textnode = $xmldoc->createTextNode(utf8_encode(HOTSPOT_NETWORK_URL));
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
				$textnode = $xmldoc->createTextNode(utf8_encode(HOTSPOT_NETWORK_URL.$node_row['node_id']));
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
		if ($node_results)
			foreach ($node_results as $node_row)
			{
				$node_row['num_online_users'] = $stats->getNumOnlineUsers($node_row['node_id']);
				$smarty->append("nodes", $node_row);
			}
		$smarty->assign("num_deployed_nodes", count($node_results));

		require_once BASEPATH.'classes/MainUI.php';
		
		if(defined('GMAPS_VENUES_HOTSPOTS_MAP_ENABLED') && GMAPS_VENUES_HOTSPOTS_MAP_ENABLED == true)
		{
			$tool_html = '<p class="indent">'."\n";
			$tool_html .= "<ul class='users_list'>\n";
			$tool_html .= "<li><a href='hotspots_map.php'>"._('Deployed HotSpots map')."</a></li>";
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
?>