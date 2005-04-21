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
define('REQUIRED_SCHEMA_VERSION', 9);

/** Check that the database schema is up to date.  If it isn't, offer to update it. */
function validate_schema()
{
	global $db;

	check_users_not_empty();
	$db->ExecSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false);
	if (empty ($row))
	{
		echo "<html><head><h1>"._("Unable to retrieve schema version.  The database schema is too old to be updated.")."</h1></html></head>";
		exit ();
	}
	else
		if ($row['value'] < REQUIRED_SCHEMA_VERSION)
		{
			if (!empty ($_REQUEST['schema_update_confirm']) && $_REQUEST['schema_update_confirm'] == 'on')
			{
				update_schema();
			}
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
			}
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
	global $AUTH_SOURCE_ARRAY;
	if (!empty ($AUTH_SOURCE_ARRAY))
	{
		$default_account_origin = array_values(array_keys($AUTH_SOURCE_ARRAY));
		$default_account_origin = $default_account_origin[0];
	}

	if (!empty ($default_account_origin))
	{
		global $db;
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE account_origin = '$default_account_origin' LIMIT 1", $row, false);
		if ($row == null)
		{
			echo "<html><head><h1>";
			echo _("No user matches the default account origin, a new user admin/admin will be created. Change the password as soon as possible !");
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
		echo _("Could not get a default account origin, make sure you config.php has at least one AUTH_SOURCE_ARRAY entry.");
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
				$sql .= "UPDATE users SET username='$user_id', user_id='".get_guid()."', account_origin='".LOCAL_USER_ACCOUNT_ORIGIN."' WHERE user_id='$user_id';\n";
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
        if($schema_version < $new_schema_version)
        {
            echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>"; 
            $sql .=
            "CREATE TABLE flickr_photostream
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
        if($schema_version < $new_schema_version)
        {
            echo "<h2>Preparing SQL statements to update schema to version  $new_schema_version</h2>"; 
            $sql .=
            "CREATE TABLE files
            (
              files_id text NOT NULL,
              filename text,
              mime_type text,
              binary_data bytea,
              remote_size int8,
              CONSTRAINT files_pkey PRIMARY KEY (files_id)
            );";
        }
        
		$db->ExecSqlUpdate("BEGIN;\n$sql\nCOMMIT;\n", true);
		echo "</html></head>";
		exit ();
	}
}
?>