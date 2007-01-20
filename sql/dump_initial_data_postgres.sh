#!/bin/sh
echo "\connect wifidog;"
echo "BEGIN;"

pg_dump -a -D --username=wifidog -t token_status

pg_dump -a -D --username=wifidog -t venue_types
pg_dump -a -D --username=wifidog -t node_deployment_status
pg_dump -a -D --username=wifidog -t content_available_display_areas
pg_dump -a -D --username=wifidog -t content_available_display_pages
echo "INSERT INTO networks (network_id, is_default_network, network_authenticator_class, network_authenticator_params) VALUES ('default-network', true, 'AuthenticatorLocalUser', '\'default-network\'');";
echo "INSERT INTO nodes (network_id, node_id, gw_id, name) VALUES ('default-network', 'default', 'default', 'My first node');"
echo "INSERT INTO servers (server_id, is_default_server, name) VALUES ('localhost', true, default);"

pg_dump -a -D --username=wifidog -t schema_info
pg_dump -a -D --username=wifidog -t locales

echo "COMMIT;"