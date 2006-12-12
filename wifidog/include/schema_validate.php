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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Define current database schema version
 */
define('REQUIRED_SCHEMA_VERSION', 51);
/** Used to test a new shecma version before modyfying the database */
define('SCHEMA_UPDATE_TEST_MODE', false);
/**
 * Check that the database schema is up to date.  If it isn't, offer to update it.
 *
 * @return void
 */
function validate_schema() {

    $db = AbstractDb :: getObject();

    // Init values
    $row = null;

    try {
        // Check the schema info
        $db->execSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false);
    }
    catch (Exception $e) {
        /* Be quiet */
    }

    if (empty ($row)) {
        echo "<html><body>";
        echo "<h1>" . _("Unable to retrieve schema version. The database schema is too old to be updated.") . "</h1>";
        echo "<h2>" . _("Try running the") . " <a href='" . BASE_URL_PATH . "install.php'>" . _("installation script") . "</a>.</h2>\n";
        echo "</html></body>";
        exit ();
    }
    else {
        if ($row['value'] < REQUIRED_SCHEMA_VERSION) {
            update_schema();
        }
    }
}

/**
 * Auto create an administrator user with the first authenticator available
 *
 * @return void
 */
function check_users_not_empty() {

    $db = AbstractDb :: getObject();

    // Extract the first account origin, assume it's the default
    $network = Network :: getDefaultNetwork();

    if (!empty ($network)) {
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE account_origin = '{$network->getId()}' LIMIT 1", $row, false);

        if ($row == null) {
            echo "<html><head><h1>";
            echo _("No user matches the default network, a new user admin/admin will be created. Change the password as soon as possible !");
            echo "</html></head>";
            $sql = "BEGIN;";
            $sql .= "INSERT INTO users (user_id, username, pass, email, account_status, validation_token, account_origin) VALUES ('admin_original_user_delete_me', 'admin', 'ISMvKXpXpadDiUoOSoAfww==', 'test_user_please@delete.me', 1, 'df16cc4b1d0975e267f3425eaac31950', '$default_account_origin');";
            $sql .= "INSERT INTO administrators (user_id) VALUES ('admin_original_user_delete_me');";
            $sql .= "COMMIT;";
            $db->execSqlUpdate($sql, $row, false);
            exit;
        }
    }
    else {
        echo "<html><head><h1>";
        echo _("Could not get a default network!");
        echo "</html></head>";
        exit ();
    }
}

/**
 * Prints the standard update message to which version the database schema
 * will be updated
 *
 * @param int $version Version to which the database schema will be updated
 *
 * @return void
 */
function printUpdateVersion($version) {
    if (isset ($version)) {
        echo "<h2>Preparing SQL statements to update schema to version <i>$version</i></h2>";
        @ ob_flush();
        flush();
    }
}

/**
 * Try to bring the database schema up to date.
 *
 * @return void
 */
function update_schema() {

    $db = AbstractDb :: getObject();

    echo "<html><head><h1>\n";
    echo _("Trying to update the database schema.");
    echo "</h1>\n";
    $db->execSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false);

    if (empty ($row)) {
        echo "<h1>" . _("Unable to retrieve schema version.  The database schema is too old to be updated.") . "</h1>";
        exit ();
    }
    else {
        $schema_version = $row['value'];

        for ($i = $schema_version +1; $i <= REQUIRED_SCHEMA_VERSION; $i++) {
            $retval = real_update_schema($i);
            if (!$retval) {
                echo "<h1>Update to schema $i failed!</h1>";
                exit (1);
            }
            else {
                echo "<h2>Update to schema $i successfull</h2>";
            }
            @ ob_flush();
            flush();
        }
        if (SCHEMA_UPDATE_TEST_MODE == false) {
            echo "<h2>Vacuuming database (this might take a little while)</h2>";
            @ ob_flush();
            flush();
            $db->execSqlUniqueRes("VACUUM ANALYZE;\n", $row, true);
            echo "<h2>Vacuuming complete</h2>";
            @ ob_flush();
            flush();
        }

    }
}
/**
 * Try to bring the database schema up to date.
 * @param $schema_version The target schema
 * @return void
 */
function real_update_schema($targetSchema) {
    $sql = '';
    $db = AbstractDb :: getObject();
    $db->execSqlUniqueRes("SELECT * FROM schema_info WHERE tag='schema_version'", $row, false); //Re-check the schema version, it could have been updated by another thread
    $schema_version = $row['value'];

    $new_schema_version = 2;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE users ADD COLUMN username text;\n";
        $sql .= "ALTER TABLE users ADD COLUMN account_origin text;\n";
        $db->execSql("SELECT user_id FROM users", $results, false);

        foreach ($results as $row) {
            $user_id = $db->escapeString($row['user_id']);
            $sql .= "UPDATE users SET username='$user_id', user_id='" . get_guid() . "', account_origin='LOCAL_USER' WHERE user_id='$user_id';\n";
        }

        $sql .= "CREATE UNIQUE INDEX idx_unique_username_and_account_origin ON users (username, account_origin);\n";
        $sql .= "CREATE UNIQUE INDEX idx_unique_email_and_account_origin ON users USING btree (email, account_origin);\n";
    }

    $new_schema_version = 3;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "DROP INDEX idx_unique_email_and_account_origin;\n";
        $sql .= "ALTER TABLE users DROP CONSTRAINT check_email_not_empty;\n";
    }

    $new_schema_version = 4;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE users ALTER COLUMN account_origin SET NOT NULL;\n";
        $sql .= "ALTER TABLE users ADD CONSTRAINT check_account_origin_not_empty CHECK (account_origin::text <> ''::text);\n";

        // We must skip all other updates because schema 5 must be manually updated:
        $schema_version = 1000000;
    }

    $new_schema_version = 5;
    if ($schema_version == $new_schema_version -1) {
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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content ADD COLUMN is_persistent bool;\n";
        $sql .= "ALTER TABLE content ALTER COLUMN is_persistent SET DEFAULT FALSE;\n";
    }

    $new_schema_version = 8;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE files
                                                        (
                                                          files_id text NOT NULL,
                                                          filename text,
                                                          mime_type text,
                                                          binary_data bytea,
                                                          remote_size bigint,
                                                          CONSTRAINT files_pkey PRIMARY KEY (files_id)
                                                        );";
    }

    $new_schema_version = 10;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE flickr_photostream DROP CONSTRAINT flickr_photostream_content_group_fkey;";
        $sql .= "ALTER TABLE flickr_photostream ADD CONSTRAINT flickr_photostream_content_fkey FOREIGN KEY (flickr_photostream_id) REFERENCES content (content_id) ON UPDATE CASCADE ON DELETE CASCADE;";
    }

    $new_schema_version = 13;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE pictures " . "( " . "pictures_id text NOT NULL PRIMARY KEY REFERENCES files ON DELETE CASCADE ON UPDATE CASCADE, " . "width int4, " . "height int4" . ");\n";
    }

    $new_schema_version = 15;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE files ADD COLUMN data_blob oid;
                                                                ALTER TABLE files ADD COLUMN local_binary_size int8;
                                                                ALTER TABLE files DROP COLUMN binary_data;\n";
    }

    $new_schema_version = 16;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE flickr_photostream ADD COLUMN requests_cache text; \n";
        $sql .= "ALTER TABLE flickr_photostream ADD COLUMN cache_update_timestamp timestamp; \n";
    }

    $new_schema_version = 17;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN max_monthly_incoming int8; \n";
        $sql .= "ALTER TABLE nodes ADD COLUMN max_monthly_outgoing int8; \n";
        $sql .= "ALTER TABLE nodes ADD COLUMN quota_reset_day_of_month int4; \n";
    }

    $new_schema_version = 18;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content ADD COLUMN long_description text REFERENCES content ON DELETE RESTRICT ON UPDATE CASCADE;\n";
    }

    $new_schema_version = 19;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE iframes (";
        $sql .= "iframes_id text NOT NULL PRIMARY KEY REFERENCES content ON DELETE CASCADE ON UPDATE CASCADE,";
        $sql .= "url text,";
        $sql .= "width int4,";
        $sql .= "height int4";
        $sql .= ");\n";
    }

    $new_schema_version = 20;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN latitude NUMERIC(16, 6);\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN longitude NUMERIC(16, 6);\n";
    }

    $new_schema_version = 21;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
        $db->execSql("SELECT node_id, rss_url FROM nodes", $results, false);

        foreach ($results as $row) {
            if (!empty ($row['rss_url'])) {
                $content_id = get_guid();
                $sql .= "\nINSERT INTO content (content_id, content_type) VALUES ('$content_id', 'RssAggregator');\n";
                $sql .= "INSERT INTO content_rss_aggregator (content_id) VALUES ('$content_id');\n";
                $sql .= "INSERT INTO content_rss_aggregator_feeds (content_id, url) VALUES ('$content_id', '" . $row['rss_url'] . "');\n";
                $node = Node :: getObject($row['node_id']);
                $owners = $node->getOwners();

                foreach ($owners as $owner) {
                    $sql .= "INSERT INTO content_has_owners (content_id, user_id) VALUES ('$content_id', '" . $owner->getId() . "');\n";
                }

                $sql .= "INSERT INTO node_has_content (content_id, node_id) VALUES ('$content_id', '" . $row['node_id'] . "');\n";
            }
        }

        $sql .= "\nALTER TABLE nodes DROP COLUMN rss_url;\n";
        $sql .= "\nDELETE FROM content WHERE content_type='HotspotRss';\n";
    }

    $new_schema_version = 22;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE node_tech_officers (\n";
        $sql .= "  node_id VARCHAR(32) REFERENCES nodes (node_id),\n";
        $sql .= "  user_id VARCHAR(45) REFERENCES users (user_id),\n";
        $sql .= "PRIMARY KEY (node_id, user_id)\n";
        $sql .= ");\n";
    }

    $new_schema_version = 24;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content_rss_aggregator_feeds ADD COLUMN title text; \n";
    }

    $new_schema_version = 25;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

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
        $sql .= "INSERT INTO networks (network_id, network_authenticator_class, network_authenticator_params) SELECT  account_origin, COALESCE('AuthenticatorLocalUser') as network_authenticator_class, '\'' || account_origin || '\'' FROM users GROUP BY (account_origin) ORDER BY min(reg_date);\n";
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
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN last_paged timestamp;\n";
    }

    $new_schema_version = 28;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN is_splash_only_node boolean;\n";
        $sql .= "ALTER TABLE nodes ALTER COLUMN is_splash_only_node SET DEFAULT FALSE;\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN custom_portal_redirect_url text;\n";

    }

    $new_schema_version = 29;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE flickr_photostream ADD COLUMN api_shared_secret text;\n";
    }

    $new_schema_version = 30;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE INDEX idx_connections_user_id ON connections (user_id);\n";
        $sql .= "CREATE INDEX idx_connections_user_mac ON connections (user_mac);\n";
        $sql .= "CREATE INDEX idx_connections_node_id ON connections (node_id);\n";
    }

    $new_schema_version = 31;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE content_display_location ( \n";
        $sql .= "  display_location text NOT NULL PRIMARY KEY\n";
        $sql .= ");\n";
        $sql .= "INSERT INTO content_display_location (display_location) VALUES ('portal_page');\n";
        $sql .= "INSERT INTO content_display_location (display_location) VALUES ('login_page');\n";

        $sql .= "ALTER TABLE network_has_content ADD COLUMN display_location text;\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_location SET DEFAULT 'portal_page';\n";
        $sql .= "UPDATE network_has_content SET display_location='portal_page';\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_location SET NOT NULL;\n";
        $sql .= "ALTER TABLE network_has_content ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_location) REFERENCES content_display_location ON UPDATE CASCADE ON DELETE RESTRICT;\n";

        $sql .= "ALTER TABLE node_has_content ADD COLUMN display_location text;\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_location SET DEFAULT 'portal_page';\n";
        $sql .= "UPDATE node_has_content SET display_location='portal_page';\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_location SET NOT NULL;\n";
        $sql .= "ALTER TABLE node_has_content ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_location) REFERENCES content_display_location ON UPDATE CASCADE ON DELETE RESTRICT;\n";

        /* Convert the existing node logos */
        $results = null;
        $db->execSql("SELECT node_id FROM nodes", $results, false);
        define('HOTSPOT_LOGO_NAME', 'hotspot_logo.jpg');

        foreach ($results as $row) {
            $php_logo_path = WIFIDOG_ABS_FILE_PATH . LOCAL_CONTENT_REL_PATH . $row['node_id'] . '/' . HOTSPOT_LOGO_NAME;

            if (file_exists($php_logo_path)) {
                $node_logo_abs_url = $db->escapeString(BASE_URL_PATH . LOCAL_CONTENT_REL_PATH . $row['node_id'] . '/' . HOTSPOT_LOGO_NAME);
                $content_id = get_guid();

                $sql .= "\nINSERT INTO content (content_id, content_type) VALUES ('$content_id', 'Picture');\n";
                $sql .= "INSERT INTO files (files_id, url) VALUES ('$content_id', '$node_logo_abs_url');\n";
                $sql .= "INSERT INTO pictures (pictures_id) VALUES ('$content_id');\n";

                $node = Node :: getObject($row['node_id']);
                $owners = $node->getOwners();

                foreach ($owners as $owner) {
                    $sql .= "INSERT INTO content_has_owners (content_id, user_id) VALUES ('$content_id', '" . $owner->getId() . "');\n";
                }

                $sql .= "INSERT INTO node_has_content (content_id, node_id, display_location) VALUES ('$content_id', '" . $row['node_id'] . "', 'login_page');\n";
            }
        }
    }

    $new_schema_version = 32;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "INSERT INTO locales VALUES ('de');\n";
    }

    $new_schema_version = 33;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE flickr_photostream ADD COLUMN photo_display_mode text;\n";
        $sql .= "ALTER TABLE flickr_photostream ALTER COLUMN photo_display_mode SET DEFAULT 'PDM_GRID'::text;\n";
        $sql .= "UPDATE flickr_photostream SET photo_display_mode = 'PDM_GRID';\n";
        $sql .= "ALTER TABLE flickr_photostream ALTER COLUMN photo_display_mode SET NOT NULL;\n";
    }

    $new_schema_version = 34;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE node_stakeholders DROP CONSTRAINT \"$1\";\n";
        $sql .= "ALTER TABLE node_stakeholders ADD CONSTRAINT nodes_fkey FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;";
    }

    $new_schema_version = 35;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE servers ( \n";
        $sql .= "  server_id text NOT NULL PRIMARY KEY,\n";
        $sql .= "  is_default_server boolean NOT NULL DEFAULT FALSE,\n";
        $sql .= "  name text NOT NULL DEFAULT 'Unnamed server' CHECK (name<>''),\n";
        $sql .= "  creation_date date NOT NULL DEFAULT now(),\n";
        $sql .= "  hostname text NOT NULL DEFAULT 'localhost' CHECK (name<>''),\n";
        $sql .= "  ssl_available BOOLEAN NOT NULL DEFAULT FALSE,\n";
        $sql .= "  gmaps_api_key text\n";
        $sql .= ");\n";
        $sql .= "INSERT INTO servers (server_id, is_default_server, name, creation_date, hostname, ssl_available, gmaps_api_key) VALUES ('" . str_replace(".", "-", $_SERVER['SERVER_NAME']) . "', TRUE, 'Unnamed server', (SELECT creation_date FROM networks GROUP BY (creation_date) ORDER BY min(creation_date) LIMIT 1), '{$_SERVER['SERVER_NAME']}', " . (defined("SSL_AVAILABLE") ? (SSL_AVAILABLE ? "TRUE" : "FALSE") : "FALSE") . ", " . (defined("GMAPS_PUBLIC_API_KEY") ? "'" . GMAPS_PUBLIC_API_KEY . "'" : "''") . ");\n";

        $sql .= "ALTER TABLE networks ADD COLUMN gmaps_initial_latitude NUMERIC(16, 6);\n";
        $sql .= "ALTER TABLE networks ADD COLUMN gmaps_initial_longitude NUMERIC(16, 6);\n";
        $sql .= "ALTER TABLE networks ADD COLUMN gmaps_initial_zoom_level integer;\n";
        $sql .= "ALTER TABLE networks ADD COLUMN gmaps_map_type text CHECK (gmaps_map_type<>'');\n";
        $sql .= "ALTER TABLE networks ALTER COLUMN gmaps_map_type SET DEFAULT 'G_MAP_TYPE';\n";
        $sql .= "UPDATE networks SET gmaps_map_type = 'G_MAP_TYPE';\n";
        $sql .= "ALTER TABLE networks ALTER COLUMN gmaps_map_type SET NOT NULL;\n";
        $sql .= "UPDATE networks SET gmaps_initial_latitude = " . (defined("GMAPS_INITIAL_LATITUDE") ? "'" . GMAPS_INITIAL_LATITUDE . "'" : "'45.494511'") . ", gmaps_initial_longitude = " . (defined("GMAPS_INITIAL_LONGITUDE") ? "'" . GMAPS_INITIAL_LONGITUDE . "'" : "'-73.560285'") . ", gmaps_initial_zoom_level = " . (defined("GMAPS_INITIAL_ZOOM_LEVEL") ? "'" . GMAPS_INITIAL_ZOOM_LEVEL . "'" : "'5'") . ";\n";
    }

    $new_schema_version = 36;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "INSERT INTO locales VALUES ('pt');\n";
    }

    $new_schema_version = 37;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);

        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE flickr_photostream RENAME TO content_flickr_photostream;\n";
        $sql .= "ALTER TABLE embedded_content RENAME TO content_embedded_content;\n";
        $sql .= "ALTER TABLE files RENAME TO content_file;\n";
        $sql .= "ALTER TABLE iframes RENAME TO content_iframe;\n";
        $sql .= "ALTER TABLE langstring_entries RENAME TO content_langstring_entries;\n";
        $sql .= "ALTER TABLE pictures RENAME TO content_file_image;\n";
        $sql .= "ALTER TABLE content_display_location RENAME TO content_available_display_pages; \n";
        $sql .= "ALTER TABLE content_available_display_pages RENAME COLUMN display_location TO display_page; \n";
        $sql .= "UPDATE content_available_display_pages SET display_page = 'login' WHERE display_page = 'login_page';\n";
        $sql .= "UPDATE content_available_display_pages SET display_page = 'portal' WHERE display_page = 'portal_page';\n";
        $sql .= "INSERT INTO content_available_display_pages (display_page) VALUES ('everywhere');\n";
        $sql .= "CREATE TABLE content_available_display_areas ( display_area text PRIMARY KEY);\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('page_header');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('page_footer');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('left_area-top');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('left_area_middle');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('left_area_bottom');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('main_area_top');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('main_area_middle');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('main_area_bottom');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('right_area_top');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('right_area_middle');\n";
        $sql .= "INSERT INTO content_available_display_areas (display_area) VALUES ('right_area_bottom');\n";

        $sql .= "ALTER TABLE network_has_content RENAME COLUMN display_location TO display_page;\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_page SET DEFAULT 'portal'::text;\n";
        $sql .= "ALTER TABLE network_has_content ADD COLUMN display_area text REFERENCES content_available_display_areas ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "UPDATE network_has_content SET display_area = 'main_area_middle';\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_area SET DEFAULT 'main_area_middle'::text;\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_area SET NOT NULL;\n";
        $sql .= "ALTER TABLE network_has_content ADD COLUMN display_order integer;\n";
        $sql .= "UPDATE network_has_content SET display_order = 1;\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_order SET DEFAULT 1;\n";
        $sql .= "ALTER TABLE network_has_content ALTER COLUMN display_order SET NOT NULL;\n";

        $sql .= "ALTER TABLE node_has_content RENAME COLUMN display_location TO display_page;\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_page SET DEFAULT 'portal'::text;\n";
        $sql .= "ALTER TABLE node_has_content ADD COLUMN display_area text REFERENCES content_available_display_areas ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "UPDATE node_has_content SET display_area = 'main_area_middle';\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_area SET DEFAULT 'main_area_middle'::text;\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_area SET NOT NULL;ALTER TABLE node_has_content ADD COLUMN display_order integer;\n";
        $sql .= "UPDATE node_has_content SET display_order = 1;\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_order SET DEFAULT 1;\n";
        $sql .= "ALTER TABLE node_has_content ALTER COLUMN display_order SET NOT NULL;\n";
    }

    $new_schema_version = 38;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "UPDATE content_available_display_areas SET display_area='left_area_top' WHERE display_area='left_area-top';\n";
    }

    $new_schema_version = 39;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";

        // Update to new Gmaps v2 constants
        $sql .= "ALTER TABLE networks ALTER COLUMN gmaps_map_type SET DEFAULT 'G_NORMAL_MAP'::text;\n";
        $sql .= "UPDATE networks SET gmaps_map_type='G_NORMAL_MAP' WHERE gmaps_map_type = 'G_MAP_TYPE'; \n";
        $sql .= "UPDATE networks SET gmaps_map_type='G_HYBRID_MAP' WHERE gmaps_map_type = 'G_HYBRID_TYPE'; \n";
        $sql .= "UPDATE networks SET gmaps_map_type='G_SATELLITE_MAP' WHERE gmaps_map_type = 'G_SATELLITE_TYPE'; \n";

        // Use formula given here : http://www.google.com/apis/maps/documentation/upgrade.html#Upgrade
        $sql .= "UPDATE networks SET gmaps_initial_zoom_level = 17 - gmaps_initial_zoom_level; \n";
    }
    $new_schema_version = 40;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE networks ADD COLUMN theme_pack text;\n";
        $sql .= "ALTER TABLE networks ALTER COLUMN theme_pack SET DEFAULT NULL;\n";
    }

    $new_schema_version = 41;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "INSERT INTO locales (locales_id) VALUES('ja');\n";
    }

    $new_schema_version = 42;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content_file ADD COLUMN creation_date TIMESTAMP;\n";
        $sql .= "ALTER TABLE content_file ALTER COLUMN creation_date SET DEFAULT NOW();\n";
        $sql .= "ALTER TABLE content_file ADD COLUMN last_update_date TIMESTAMP;\n";
        $sql .= "ALTER TABLE content_file ALTER COLUMN last_update_date SET DEFAULT NOW();\n";
        $sql .= "UPDATE content_file SET creation_date = NOW(), last_update_date = NOW();\n";
        $sql .= "ALTER TABLE content_file_image ADD COLUMN hyperlink_url TEXT;\n";
    }

    $new_schema_version = 43;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE content_clickthrough_log ( \n";
        $sql .= "  user_id text REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE,\n";
        $sql .= "  content_id text NOT NULL REFERENCES content ON UPDATE CASCADE ON DELETE CASCADE,\n";
        $sql .= "  clickthrough_timestamp timestamp NOT NULL DEFAULT now(),\n";
        $sql .= "  node_id text NOT NULL REFERENCES nodes ON UPDATE CASCADE ON DELETE CASCADE,\n";
        $sql .= "  destination_url text NOT NULL CHECK (destination_url<>'')\n";
        $sql .= ");\n";
    }

    $new_schema_version = 44;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "INSERT INTO locales (locales_id) VALUES('es');\n";
    }

    $new_schema_version = 45;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content DROP COLUMN sponsor_info;\n";
        $sql .= "ALTER TABLE users DROP CONSTRAINT account_origin_fkey;\n";
        $sql .= "ALTER TABLE users ADD CONSTRAINT account_origin_fkey FOREIGN KEY (account_origin) REFERENCES networks (network_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE nodes DROP CONSTRAINT network_id_fkey;\n";
        $sql .= "ALTER TABLE nodes ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks (network_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE users DROP CONSTRAINT check_account_origin_not_empty;\n";
        $sql .= "ALTER TABLE connections DROP CONSTRAINT fk_nodes;\n";
        $sql .= "ALTER TABLE connections ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes (node_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE connections DROP CONSTRAINT fk_users;\n";
        $sql .= "ALTER TABLE connections ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE node_stakeholders DROP CONSTRAINT \"$2\";\n";
        $sql .= "ALTER TABLE node_stakeholders ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE network_stakeholders DROP CONSTRAINT \"$1\";\n";
        $sql .= "ALTER TABLE network_stakeholders ADD CONSTRAINT fk_network FOREIGN KEY (network_id) REFERENCES networks (network_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE network_stakeholders DROP CONSTRAINT \"$2\";\n";
        $sql .= "ALTER TABLE network_stakeholders ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE;\n";
        $sql .= "ALTER TABLE content ADD COLUMN title_is_displayed bool;\n";
        $sql .= "ALTER TABLE content ALTER COLUMN title_is_displayed SET DEFAULT true;\n";
        $sql .= "UPDATE content SET title_is_displayed=true;\n";
        $sql .= "ALTER TABLE content ALTER COLUMN title_is_displayed SET NOT NULL;\n\n";
    }

    $new_schema_version = 46;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content_group_element ADD COLUMN valid_from_timestamp timestamp;\n";
        $sql .= "ALTER TABLE content_group_element ADD COLUMN valid_until_timestamp timestamp;\n";
        $sql .= "CREATE INDEX idx_content_group_element_valid_from_timestamp ON content_group_element (valid_from_timestamp);\n";
        $sql .= "CREATE INDEX idx_content_group_element_valid_until_timestamp ON content_group_element (valid_until_timestamp);\n";
    }

    $new_schema_version = 47;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE users DROP COLUMN real_name;\n";
        $sql .= "ALTER TABLE users DROP COLUMN website;\n";
        $sql .= "ALTER TABLE content_display_log ADD COLUMN num_display integer;\n";
        $sql .= "UPDATE content_display_log SET num_display=1;\n";
        $sql .= "ALTER TABLE content_display_log ALTER COLUMN num_display SET NOT NULL;\n";
        $sql .= "ALTER TABLE content_display_log ALTER COLUMN num_display SET DEFAULT 1;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ADD COLUMN num_clickthrough integer;\n";
        $sql .= "UPDATE content_clickthrough_log SET num_clickthrough=1;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ALTER COLUMN num_clickthrough SET NOT NULL;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ALTER COLUMN num_clickthrough SET DEFAULT 1;\n";
        $sql .= "ALTER TABLE content_clickthrough_log RENAME COLUMN clickthrough_timestamp TO first_clickthrough_timestamp;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ADD COLUMN last_clickthrough_timestamp timestamp;\n";
        $sql .= "UPDATE content_clickthrough_log SET last_clickthrough_timestamp=first_clickthrough_timestamp;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ALTER COLUMN last_clickthrough_timestamp SET NOT NULL;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ALTER COLUMN last_clickthrough_timestamp SET DEFAULT NOW();\n";
    }
    $new_schema_version = 48;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "DELETE FROM content_clickthrough_log WHERE user_id IS NULL;\n";
        $sql .= "ALTER TABLE content_clickthrough_log ALTER COLUMN user_id SET NOT NULL;\n";
        $results = null;
        $db->execSql("SELECT COUNT(*) as num_clickthrough, MIN(first_clickthrough_timestamp) as first_clickthrough_timestamp, MAX(last_clickthrough_timestamp) as last_clickthrough_timestamp, user_id, content_id, node_id, destination_url FROM content_clickthrough_log GROUP BY user_id, content_id, node_id, destination_url HAVING COUNT(*) > 1", $results, false);
        if ($results) {
            foreach ($results as $row) {
                $sql .= "DELETE FROM content_clickthrough_log WHERE user_id='$row[user_id]' AND content_id='$row[content_id]' AND node_id='$row[node_id]' AND destination_url='$row[destination_url]';\n";
                if (!empty ($row['user_id'])) {
                    $sql .= "INSERT INTO content_clickthrough_log (num_clickthrough, first_clickthrough_timestamp, last_clickthrough_timestamp, user_id, content_id, node_id, destination_url) VALUES ($row[num_clickthrough], '$row[first_clickthrough_timestamp]', '$row[last_clickthrough_timestamp]', '$row[user_id]', '$row[content_id]', '$row[node_id]', '$row[destination_url]');\n";
                }
            }
        }
        $sql .= "ALTER TABLE content_clickthrough_log ADD CONSTRAINT content_clickthrough_log_pkey PRIMARY KEY(content_id, user_id, node_id, destination_url);\n";
        $sql .= "ALTER TABLE content_display_log DROP CONSTRAINT content_group_element_portal_display_log_pkey;\n";
        $sql .= "ALTER TABLE content_display_log ADD CONSTRAINT content_display_log_pkey PRIMARY KEY(content_id, user_id, node_id);\n";

    }
    $new_schema_version = 49;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE content_rss_aggregator ADD COLUMN feed_expansion text;\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN feed_expansion SET DEFAULT 'FIRST';\n";
        $sql .= "UPDATE content_rss_aggregator SET feed_expansion='FIRST';\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN feed_expansion SET NOT NULL;\n";

        $sql .= "ALTER TABLE content_rss_aggregator ADD COLUMN feed_ordering text;\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN feed_ordering SET DEFAULT 'REVERSE_DATE';\n";
        $sql .= "UPDATE content_rss_aggregator SET feed_ordering='REVERSE_DATE';\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN feed_ordering SET NOT NULL;\n";

        $sql .= "ALTER TABLE content_rss_aggregator ADD COLUMN display_empty_feeds boolean;\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN display_empty_feeds SET DEFAULT TRUE;\n";
        $sql .= "UPDATE content_rss_aggregator SET display_empty_feeds=TRUE;\n";
        $sql .= "ALTER TABLE content_rss_aggregator ALTER COLUMN display_empty_feeds SET NOT NULL;\n";
    }
    $new_schema_version = 50;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "ALTER TABLE nodes ADD COLUMN gw_id text;\n";
        $sql .= "UPDATE nodes SET gw_id=node_id;\n";
        $db->execSql("SELECT node_id FROM nodes", $results, false);
        foreach ($results as $row) {
            $sql .= "UPDATE nodes SET node_id='" . get_guid() . "' WHERE node_id='{$row['node_id']}';\n";
        }
        $sql .= "ALTER TABLE nodes ALTER COLUMN gw_id SET NOT NULL;\n";
        $sql .= "CREATE UNIQUE INDEX idx_gw_id ON nodes (gw_id);\n";

    }
    $new_schema_version = 51;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "CREATE TABLE content_key_value_pairs \n";
        $sql .= "( ";
        $sql .= "content_id text NOT NULL REFERENCES content ON UPDATE CASCADE ON DELETE CASCADE, \n";
        $sql .= "key text NOT NULL, \n";
        $sql .= "value text, \n";
        $sql .= "PRIMARY KEY(content_id, key) \n";
        $sql .= "); \n";
        $sql .= "ALTER TABLE content ADD COLUMN last_update_timestamp timestamp;\n";
        $sql .= "ALTER TABLE content ALTER COLUMN last_update_timestamp SET DEFAULT now();\n";
        $sql .= "UPDATE content SET last_update_timestamp=CURRENT_TIMESTAMP;\n";
        $sql .= "UPDATE content SET last_update_timestamp=last_update_date FROM content_file WHERE content_id=files_id;\n";
        $sql .= "ALTER TABLE content ALTER COLUMN last_update_timestamp SET NOT NULL;\n";
        $sql .= "ALTER TABLE content_file DROP COLUMN last_update_date;\n";
        $sql .= "ALTER TABLE content_file DROP COLUMN creation_date;\n";
        $sql .= "ALTER TABLE content_group DROP COLUMN is_artistic_content;\n";
        $sql .= "ALTER TABLE content_group DROP COLUMN is_locative_content;\n";
    }
    /*
    $new_schema_version = ;
    if ($schema_version < $new_schema_version && $new_schema_version <= $targetSchema) {
        printUpdateVersion($new_schema_version);
        $sql .= "\n\nUPDATE schema_info SET value='$new_schema_version' WHERE tag='schema_version';\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";
    }
     */
    if (SCHEMA_UPDATE_TEST_MODE) {
        $retval = $db->execSqlUpdate("BEGIN;\n$sql\nROLLBACK;\n", true);

    }
    else {
        $retval = $db->execSqlUpdate("BEGIN;\n$sql\nCOMMIT;\n", true);
    }
    @ ob_flush();
    flush();
    return $retval;

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */