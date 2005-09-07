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
/**@file schema_validate.php
 * Network status page
 * @author Copyright (C) 2004 Benoit Grégoire
 */
error_reporting(E_ALL);
require_once BASEPATH.'config.php';
require_once BASEPATH.'classes/AbstractDb.php';
require_once BASEPATH.'classes/Session.php';
define('REQUIRED_SCHEMA_VERSION', 28);

/** Check that the database schema is up to date.  If it isn't, offer to update it. */
function validate_schema()
{
	global $db;
	$db->ExecSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false);
	if (empty ($row))
	{
		echo "<html><head><h1>"._("Unable to retrieve schema version.  The database schema is too old to be updated.")."</h1></html></head>";
		exit ();
	}
	else
		if ($row['value'] < REQUIRED_SCHEMA_VERSION)
		{
//			if (!empty ($_REQUEST['schema_update_confirm']) && $_REQUEST['schema_update_confirm'] == 'on')
//			{
				update_schema();
/*			}
			else
			{
				echo "<html><head><h1>";
				echo _("The database schema is not up to date.  Do you want to try to update it?  This operation is irreversible.");
				echo "</h1><form name='login_form' method='get'>\n";
				echo "<input type='submit' name='submit' value='"._("Try to update database schema")."'>\n";
				echo _("Yes, I am sure:")."<input type='checkbox' name='schema_update_confirm'>\n";
				echo "</form>\n";
				echo "</html></head>";
				exit ();
			} */
		}
		else
		{
			//echo "<html><head><h1>The database schema is up to date.</h1></html></head>";
			//exit();
		}
}

/**
 * Auto create an administrator user with the first authenticator available
 */
function check_users_not_empty()
{
	// Extract the first account origin, assume it's the default
	$network = Network::getDefaultNetwork();
	if (!empty ($network))
	{
		global $db;
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE account_origin = '{$network->getId()}' LIMIT 1", $row, false);
		if ($row == null)
		{
			echo "<html><head><h1>";
			echo _("No user matches the default network, a new user admin/admin will be created. Change the password as soon as possible !");
			echo "</html></head>";
			$sql = "BEGIN;";
			$sql .= "INSERT INTO users (user_id, username, pass, email, account_status, validation_token, account_origin) VALUES ('admin_original_user_delete_me', 'admin', 'ISMvKXpXpadDiUoOSoAfww==', 'test_user_please@delete.me', 1, 'df16cc4b1d0975e267f3425eaac31950', '$default_account_origin');";
			$sql .= "INSERT INTO administrators (user_id) VALUES ('admin_original_user_delete_me');";
			$sql .= "COMMIT;";
			$db->ExecSqlUpdate($sql, $row, false);
			exit;
		}
	}
	else
	{
		echo "<html><head><h1>";
		echo _("Could not get a default network!");
		echo "</html></head>";
		exit ();
	}
} /** Try to bring the database schema up to date. */
function update_schema()
{
	global $db;
	echo "<html><head><h1>\n";
	echo _("Trying to update the database schema.");
	echo "</h1>\n";
	$db->ExecSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false);
	if (empty ($row))
	{
		echo "<h1>"._("Unable to retrieve schema version.  The database schema is too old to be updated.")."</h1>";
		exit ();
	}
	else
	{
		$schema_version = $row['value'];
		$sql = '';
		$new_schema_version = 2;
		if ($schema_version < $new_schema_version)
		{

			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE users ADD COLUMN username text;\n";
			$sql .= "ALTER TABLE users ADD COLUMN account_origin text;\n";
			$db->ExecSql("SELECT user_id FROM users", $results, false);
			foreach ($results as $row)
			{
				$user_id = $db->EscapeString($row['user_id']);
				$sql .= "UPDATE users SET username='$user_id', user_id='".get_guid()."', account_origin='LOCAL_USER' WHERE user_id='$user_id';\n";
			}
			$sql .= "CREATE UNIQUE INDEX idx_unique_username_and_account_origin ON users (username, account_origin);\n";
			$sql .= "CREATE UNIQUE INDEX idx_unique_email_and_account_origin ON users USING btree (email, account_origin);\n";
		}

		$new_schema_version = 3;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "DROP INDEX idx_unique_email_and_account_origin;\n";
			$sql .= "ALTER TABLE users DROP CONSTRAINT check_email_not_empty;\n";
		}

		$new_schema_version = 4;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE users ALTER COLUMN account_origin SET NOT NULL;\n";
			$sql .= "ALTER TABLE users ADD CONSTRAINT check_account_origin_not_empty CHECK (account_origin::text <> ''::text);\n";

			//We must skip all other updates because schema 5 must be manually updated:
			$schema_version = 1000000;
		}

		$new_schema_version = 5;
		if ($schema_version == $new_schema_version -1)
		{
			echo "<h1>Recoding database from ISO-8859-1 to UTF-8</h1>";
			echo "<h1>YOU MUST EXECUTE THESE COMMANDS FROM THE COMMAND_LINE:</h1>";
			echo "pg_dump wifidog -U wifidog > wifidog_dump.sql;<br>";
			echo "dropdb wifidog -U wifidog; <br>";
			echo "createdb --encoding=UNICODE --template=template0 -U wifidog wifidog;<br>";
			echo "psql wifidog -U wifidog < wifidog_dump.sql;<br>\n";
			echo " (Note: You may ignore the following errors:  \"ERROR:  permission denied to set session authorization\" and \"ERROR:  must be owner of schema public\")<br>";
			echo "psql wifidog -U wifidog -c \"UPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version'\";<br>";
			exit;
		}
		$new_schema_version = 6;
		if ($schema_version < $new_schema_version)
		{

			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE locales ( \n";
			$sql .= "  locales_id text PRIMARY KEY \n";
			$sql .= "  ); \n";
			$sql .= "  INSERT INTO locales VALUES ('fr'); \n";
			$sql .= "  INSERT INTO locales VALUES ('en'); \n";
			$sql .= "ALTER TABLE users ADD COLUMN never_show_username bool;\n";
			$sql .= "ALTER TABLE users ALTER COLUMN never_show_username SET DEFAULT FALSE;\n";
			$sql .= "ALTER TABLE users ADD COLUMN real_name text;\n";
			$sql .= "ALTER TABLE users ALTER COLUMN real_name SET DEFAULT NULL;\n";
			$sql .= "ALTER TABLE users ADD COLUMN website text;\n";
			$sql .= "ALTER TABLE users ALTER COLUMN website SET DEFAULT NULL;\n";
			$sql .= "ALTER TABLE users ADD COLUMN prefered_locale text REFERENCES locales ON DELETE SET NULL ON UPDATE CASCADE;\n";

			$sql .= "			
						CREATE TABLE content
						(
						content_id text NOT NULL PRIMARY KEY,
						content_type text NOT NULL  CONSTRAINT content_type_not_empty_string CHECK (content_type != ''),
						title text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE,
						description text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE,
						project_info text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE,
						sponsor_info text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE,
						creation_timestamp timestamp DEFAULT now()
						);
			
						CREATE TABLE content_has_owners
						(
						content_id text NOT NULL REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						user_id text NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
						is_author bool NOT NULL,
						owner_since timestamp DEFAULT now(),
						PRIMARY KEY  (content_id, user_id)
						);
						
						CREATE TABLE langstring_entries (
						  langstring_entries_id text NOT NULL PRIMARY KEY,
						  langstrings_id text REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  locales_id text REFERENCES locales ON DELETE RESTRICT ON UPDATE CASCADE,
						  value text  DEFAULT ''
						);
						
						CREATE TABLE content_group (
						  content_group_id text NOT NULL PRIMARY KEY REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  is_artistic_content bool NOT NULL DEFAULT FALSE,
						  is_locative_content bool NOT NULL DEFAULT FALSE,
						  content_selection_mode text
						);
						
						CREATE TABLE content_group_element (
						  content_group_element_id text NOT NULL PRIMARY KEY REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  content_group_id text NOT NULL REFERENCES content_group ON DELETE CASCADE ON UPDATE CASCADE,
						  display_order integer DEFAULT '1',
						  displayed_content_id text REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  force_only_allowed_node bool
						);
						CREATE INDEX idx_content_group_element_content_group_id ON content_group_element (content_group_id);
						
						CREATE TABLE content_group_element_has_allowed_nodes
						(
						content_group_element_id text NOT NULL REFERENCES content_group_element ON DELETE CASCADE ON UPDATE CASCADE,
						node_id text NOT NULL REFERENCES nodes ON DELETE CASCADE ON UPDATE CASCADE,
						allowed_since timestamp DEFAULT now(),
						PRIMARY KEY  (content_group_element_id, node_id)
						);
						
						CREATE TABLE content_group_element_portal_display_log (
						  user_id text NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
						  content_group_element_id text NOT NULL REFERENCES content_group_element ON DELETE CASCADE ON UPDATE CASCADE,
						  display_timestamp timestamp NOT NULL DEFAULT now(),
						  node_id text REFERENCES nodes ON DELETE CASCADE ON UPDATE CASCADE,
						  PRIMARY KEY  (user_id,content_group_element_id, display_timestamp)
						);
						
						CREATE TABLE user_has_content (
						  user_id text NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
						  content_id text NOT NULL REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  subscribe_timestamp timestamp NOT NULL DEFAULT now(),
						  PRIMARY KEY  (user_id,content_id)
						);
						
						CREATE TABLE node_has_content (
						  node_id text NOT NULL REFERENCES nodes ON DELETE CASCADE ON UPDATE CASCADE,
						  content_id text NOT NULL REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  subscribe_timestamp timestamp NOT NULL DEFAULT now(),
						  PRIMARY KEY  (node_id,content_id)
						);
						
						CREATE TABLE network_has_content (
						  network_id text NOT NULL,
						  content_id text NOT NULL REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,
						  subscribe_timestamp timestamp NOT NULL DEFAULT now(),
						  PRIMARY KEY  (network_id,content_id)
						);";
		}

		$new_schema_version = 7;
		if ($schema_version < $new_schema_version)
		{

			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE content ADD COLUMN is_persistent bool;\n";
			$sql .= "ALTER TABLE content ALTER COLUMN is_persistent SET DEFAULT FALSE;\n";

		}

		$new_schema_version = 8;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE flickr_photostream
			            (
			              flickr_photostream_id text NOT NULL,
			              api_key text,
			              photo_selection_mode text NOT NULL DEFAULT 'PSM_GROUP'::text,
			              user_id text,
			              user_name text,
			              tags text,
			              tag_mode varchar(10) DEFAULT 'any'::character varying,
			              group_id text,
			              random bool NOT NULL DEFAULT true,
			              min_taken_date timestamp,
			              max_taken_date timestamp,
			              photo_batch_size int4 DEFAULT 10,
			              photo_count int4 DEFAULT 1,
			              display_title bool NOT NULL DEFAULT true,
			              display_description bool NOT NULL DEFAULT false,
			              display_tags bool NOT NULL DEFAULT false,
			              CONSTRAINT flickr_photostream_pkey PRIMARY KEY (flickr_photostream_id),
			              CONSTRAINT flickr_photostream_content_group_fkey FOREIGN KEY (flickr_photostream_id) REFERENCES content_group (content_group_id) ON UPDATE CASCADE ON DELETE CASCADE
			            );";
		}

		$new_schema_version = 9;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE files
			            (
			              files_id text NOT NULL,
			              filename text,
			              mime_type text,
			              binary_data bytea,
			              remote_size int8,
			              CONSTRAINT files_pkey PRIMARY KEY (files_id)
			            );";
		}

		$new_schema_version = 10;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE files ADD COLUMN url text;";
			$sql .= "ALTER TABLE flickr_photostream ADD COLUMN preferred_size text;";
			$sql .= "CREATE TABLE embedded_content (
							embedded_content_id text NOT NULL,
							embedded_file_id text,
							fallback_content_id text,
						    parameters text,
						    attributes text
						);";
		}

		$new_schema_version = 11;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "DROP TABLE content_group_element_portal_display_log;\n";
			$sql .= "CREATE TABLE content_display_log
			(
			  user_id text NOT NULL REFERENCES users ON UPDATE CASCADE ON DELETE CASCADE,
			  content_id text NOT NULL REFERENCES content ON UPDATE CASCADE ON DELETE CASCADE,
			  first_display_timestamp timestamp NOT NULL DEFAULT now(),
			  node_id text NOT NULL REFERENCES nodes ON UPDATE CASCADE ON DELETE CASCADE,
			  last_display_timestamp timestamp NOT NULL DEFAULT now(),
			  CONSTRAINT content_group_element_portal_display_log_pkey PRIMARY KEY (user_id, content_id, node_id)
			); \n";

		}

		$new_schema_version = 12;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE flickr_photostream DROP CONSTRAINT flickr_photostream_content_group_fkey;";
			$sql .= "ALTER TABLE flickr_photostream ADD CONSTRAINT flickr_photostream_content_fkey FOREIGN KEY (flickr_photostream_id) REFERENCES content (content_id) ON UPDATE CASCADE ON DELETE CASCADE;";
		}

		$new_schema_version = 13;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE content_group DROP COLUMN content_selection_mode;\n";
			$sql .= "ALTER TABLE content_group ADD COLUMN content_changes_on_mode text;\n";
			$sql .= "UPDATE content_group SET content_changes_on_mode='ALWAYS';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN content_changes_on_mode SET DEFAULT 'ALWAYS';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN content_changes_on_mode SET NOT NULL;\n";
			$sql .= "ALTER TABLE content_group ADD COLUMN content_ordering_mode text;\n";
			$sql .= "UPDATE content_group SET content_ordering_mode='RANDOM';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN content_ordering_mode SET DEFAULT 'RANDOM';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN content_ordering_mode SET NOT NULL;\n";

			$sql .= "ALTER TABLE content_group ADD COLUMN display_num_elements int;\n";
			$sql .= "UPDATE content_group SET display_num_elements=1;\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN display_num_elements SET DEFAULT '1';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN display_num_elements SET NOT NULL;\n";
			$sql .= "ALTER TABLE content_group ADD CONSTRAINT display_at_least_one_element CHECK (display_num_elements > 0);\n";

			$sql .= "ALTER TABLE content_group ADD COLUMN allow_repeat text;\n";
			$sql .= "UPDATE content_group SET allow_repeat='YES';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN allow_repeat SET DEFAULT 'YES';\n";
			$sql .= "ALTER TABLE content_group ALTER COLUMN allow_repeat SET NOT NULL;\n";
		}

		$new_schema_version = 14;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE pictures "."( "."pictures_id text NOT NULL PRIMARY KEY REFERENCES files ON DELETE CASCADE ON UPDATE CASCADE, "."width int4, "."height int4".");\n";
		}

		$new_schema_version = 15;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE files ADD COLUMN data_blob oid;
			                    ALTER TABLE files ADD COLUMN local_binary_size int8;
			                    ALTER TABLE files DROP COLUMN binary_data;\n";
		}

		$new_schema_version = 16;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE flickr_photostream ADD COLUMN requests_cache text; \n";
			$sql .= "ALTER TABLE flickr_photostream ADD COLUMN cache_update_timestamp timestamp; \n";
		}

		$new_schema_version = 17;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN max_monthly_incoming int8; \n";
			$sql .= "ALTER TABLE nodes ADD COLUMN max_monthly_outgoing int8; \n";
			$sql .= "ALTER TABLE nodes ADD COLUMN quota_reset_day_of_month int4; \n";
		}

		$new_schema_version = 18;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE content ADD COLUMN long_description text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE;\n";
		}

		$new_schema_version = 19;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE iframes (";
			$sql .= "iframes_id text NOT NULL PRIMARY KEY REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,";
			$sql .= "url text,";
			$sql .= "width int4,";
			$sql .= "height int4";
			$sql .= ");\n";
		}

		$new_schema_version = 20;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN latitude NUMERIC(16, 6);\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN longitude NUMERIC(16, 6);\n";
		}

		$new_schema_version = 21;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE content_rss_aggregator \n";
			$sql .= "( \n";
			$sql .= "content_id text NOT NULL PRIMARY KEY REFERENCES content ON UPDATE CASCADE ON DELETE CASCADE, \n";
			$sql .= "number_of_display_items integer NOT NULL DEFAULT 10, \n";
			$sql .= "algorithm_strength real NOT NULL DEFAULT 0.75, \n";
			$sql .= "max_item_age interval DEFAULT NULL \n";
			$sql .= "); \n";
			
			$sql .= "CREATE TABLE content_rss_aggregator_feeds \n";
			$sql .= "( ";
			$sql .= "content_id text NOT NULL REFERENCES content_rss_aggregator ON UPDATE CASCADE ON DELETE CASCADE, \n";
			$sql .= "url text, \n";
			$sql .= "bias real NOT NULL DEFAULT 1, \n";
			$sql .= "default_publication_interval int DEFAULT NULL, \n";
			$sql .= "PRIMARY KEY(content_id, url) \n";
			$sql .= "); \n";
			$sql .= "ALTER TABLE content_has_owners ALTER COLUMN is_author SET DEFAULT 'f';\n";
			$results = null;
			$db->ExecSql("SELECT node_id, rss_url FROM nodes", $results, false);
			foreach ($results as $row)
			{
				if (!empty ($row['rss_url']))
				{
					//$user_id = $db->EscapeString($row['user_id']);
					$content_id = get_guid();
					$sql .= "\nINSERT INTO content (content_id, content_type) VALUES ('$content_id', 'RssAggregator');\n";
					$sql .= "INSERT INTO content_rss_aggregator (content_id) VALUES ('$content_id');\n";
					$sql .= "INSERT INTO content_rss_aggregator_feeds (content_id, url) VALUES ('$content_id', '".$row['rss_url']."');\n";
					$node = Node :: getObject($row['node_id']);
					$owners = $node->getOwners();
					foreach ($owners as $owner)
					{
						$sql .= "INSERT INTO content_has_owners (content_id, user_id) VALUES ('$content_id', '".$owner->getId()."');\n";
					}
					$sql .= "INSERT INTO node_has_content (content_id, node_id) VALUES ('$content_id', '".$row['node_id']."');\n";
				}
			}
			$sql .= "\nALTER TABLE nodes DROP COLUMN rss_url;\n";
			$sql .= "\nDELETE FROM content WHERE content_type='HotspotRss';\n";
		}

		$new_schema_version = 22;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN civic_number text;\n";
			// Dropping street_address and copying data to street_name for the sake of backward compatibility
			$sql .= "ALTER TABLE nodes ADD COLUMN street_name text;\n";
			$sql .= "UPDATE nodes SET street_name = street_address;\n";
			$sql .= "ALTER TABLE nodes DROP COLUMN street_address;\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN city text;\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN province text;\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN country text;\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN postal_code text;\n";
		}
		
		$new_schema_version = 23;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE node_tech_officers (\n";
			$sql .= "  node_id VARCHAR(32) REFERENCES nodes (node_id),\n";
			$sql .= "  user_id VARCHAR(45) REFERENCES users (user_id),\n";
			$sql .= "PRIMARY KEY (node_id, user_id)\n";
			$sql .= ");\n";
		}
		
		$new_schema_version = 24;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "ALTER TABLE content_rss_aggregator_feeds ADD COLUMN title text; \n";
		}
		
		$new_schema_version = 25;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE node_stakeholders ( \n";
			$sql .= "  node_id VARCHAR(32) REFERENCES nodes (node_id),\n";
			$sql .= "  user_id VARCHAR(45) REFERENCES users (user_id),\n";
			$sql .= "  is_owner BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "  is_tech_officer BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "PRIMARY KEY (node_id, user_id)\n";
			$sql .= ");\n";
			$sql .= "INSERT INTO node_stakeholders (node_id, user_id) SELECT node_id, user_id FROM node_owners UNION SELECT node_id, user_id FROM node_tech_officers;\n";
			$sql .= "UPDATE node_stakeholders SET is_owner = true FROM node_owners WHERE node_stakeholders.node_id = node_owners.node_id AND node_stakeholders.user_id = node_owners.user_id;\n";
			$sql .= "UPDATE node_stakeholders SET is_tech_officer = true FROM node_tech_officers WHERE node_stakeholders.node_id = node_tech_officers.node_id AND node_stakeholders.user_id = node_tech_officers.user_id;";
			$sql .= "DROP TABLE node_owners;\n";
			$sql .= "DROP TABLE node_tech_officers;\n";
		}
			
		$new_schema_version = 26;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
			$sql .= "CREATE TABLE networks ( \n";
			$sql .= "  network_id text NOT NULL PRIMARY KEY,\n";
			$sql .= "  network_authenticator_class text NOT NULL CHECK (network_authenticator_class<>''),\n";
			$sql .= "  network_authenticator_params text,\n";
			$sql .= "  is_default_network boolean NOT NULL DEFAULT FALSE,\n";
			$sql .= "  name text NOT NULL DEFAULT 'Unnamed network' CHECK (name<>''),\n";
			$sql .= "  creation_date date NOT NULL DEFAULT now(),\n";
			$sql .= "  homepage_url text,\n";
			$sql .= "  tech_support_email text,\n";
			$sql .= "  validation_grace_time interval NOT NULL DEFAULT '1200 seconds',\n";
			$sql .= "  validation_email_from_address text NOT NULL CHECK (validation_email_from_address<>'') DEFAULT 'validation@wifidognetwork',\n";
			$sql .= "  allow_multiple_login BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "  allow_splash_only_nodes BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "  allow_custom_portal_redirect BOOLEAN NOT NULL DEFAULT FALSE\n";
			$sql .= ");\n";
			$sql .= "INSERT INTO networks (network_id, network_authenticator_class, network_authenticator_params) SELECT  account_origin, COALESCE('AuthenticatorLocalUser') as network_authenticator_class, account_origin FROM users GROUP BY (account_origin) ORDER BY min(reg_date);\n";
			$sql .= "UPDATE networks SET is_default_network=TRUE WHERE network_id=(SELECT account_origin FROM users GROUP BY (account_origin) ORDER BY min(reg_date) LIMIT 1);\n";
			$sql .= "ALTER TABLE users ADD CONSTRAINT account_origin_fkey FOREIGN KEY (account_origin) REFERENCES networks (network_id) ON UPDATE CASCADE ON DELETE RESTRICT;\n";
			$sql .= "ALTER TABLE nodes ADD COLUMN network_id text; \n";
			$sql .= "UPDATE nodes SET network_id=(SELECT account_origin FROM users GROUP BY (account_origin) ORDER BY min(reg_date) LIMIT 1);\n";			
			$sql .= "ALTER TABLE nodes ALTER COLUMN network_id SET NOT NULL; \n";
			$sql .= "ALTER TABLE nodes ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks ON UPDATE CASCADE ON DELETE RESTRICT;\n";
			$sql .= "ALTER TABLE network_has_content ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks ON UPDATE CASCADE ON DELETE CASCADE;\n";

			$sql .= "CREATE TABLE network_stakeholders ( \n";
			$sql .= "  network_id text REFERENCES networks,\n";
			$sql .= "  user_id VARCHAR(45) REFERENCES users,\n";
			$sql .= "  is_admin BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "  is_stat_viewer BOOLEAN NOT NULL DEFAULT FALSE,\n";
			$sql .= "PRIMARY KEY (network_id, user_id)\n";
			$sql .= ");\n";
		}

		$new_schema_version = 27;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
            $sql .= "ALTER TABLE nodes ADD COLUMN last_paged timestamp;\n";

		}
		$new_schema_version = 28;
		if ($schema_version < $new_schema_version)
		{
			echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>\n";
			$sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
            $sql .= "ALTER TABLE nodes ADD COLUMN is_splash_only_node boolean;\n";
            $sql .= "ALTER TABLE nodes ALTER COLUMN is_splash_only_node SET DEFAULT FALSE;\n";
            $sql .= "ALTER TABLE nodes ADD COLUMN custom_portal_redirect_url text;\n";
            
		}
		$db->ExecSqlUpdate("BEGIN;\n$sql\nCOMMIT;\n", true);
		//$db->ExecSqlUpdate("BEGIN;\n$sql\nROLLBACK;\n", true);
		echo "</html></head>";
		//exit ();
	}
}
?>
