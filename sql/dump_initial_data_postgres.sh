#!/bin/sh
echo "\connect wifidog;"
echo "BEGIN;"

echo "--- The default admin user, delete or change password as soon as possible.  The password is admin "
echo "INSERT INTO users (user_id, username, pass, email, account_status) VALUES ('admin_original_user_delete_me', 'admin', 'ISMvKXpXpadDiUoOSoAfww==', 'test_user_please@delete.me', 1, 'df16cc4b1d0975e267f3425eaac31950');";

echo "INSERT INTO administrators (user_id) VALUES ('admin_original_user_delete_me');"

pg_dump -a -D --username=wifidog -t token_status

echo "INSERT INTO nodes (node_id, name, rss_url) VALUES ('default', 'Unknown node', NULL);"

pg_dump -a -D --username=wifidog -t node_deployment_status
pg_dump -a -D --username=wifidog -t venue_types
pg_dump -a -D --username=wifidog -t schema_info

echo "COMMIT;"