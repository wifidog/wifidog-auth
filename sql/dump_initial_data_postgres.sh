#!/bin/sh
echo "\connect wifidog;"
echo "BEGIN;"

pg_dump -a -D --username=wifidog -t token_status

pg_dump -a -D --username=wifidog -t venue_types
pg_dump -a -D --username=wifidog -t node_deployment_status
pg_dump -a -D --username=wifidog -t content_display_location
echo "INSERT INTO networks (network_id, is_default_network, network_authenticator_class, network_authenticator_params) VALUES ('default-network', true, 'AuthenticatorLocalUser', '\'default-network\'');";
echo "INSERT INTO nodes (network_id, node_id, name) VALUES ('default-network', 'default', 'Unknown node');"

pg_dump -a -D --username=wifidog -t schema_info
pg_dump -a -D --username=wifidog -t locales
echo "COMMIT;"