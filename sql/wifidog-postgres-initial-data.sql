\connect wifidog;
--- The default admin user, delete or change password as soon as possible.  The password is admin 
INSERT INTO users (user_id, pass, email, account_status) VALUES ('admin', 'ISMvKXpXpadDiUoOSoAfww==', 'test_user_please@delete.me', 1, 'df16cc4b1d0975e267f3425eaac31950');
INSERT INTO administrators (user_id) VALUES ('admin');
--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET SESSION AUTHORIZATION 'wifidog';

SET search_path = public, pg_catalog;

--
-- Data for TOC entry 2 (OID 299872)
-- Name: token_status; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO token_status (token_status) VALUES ('UNUSED');
INSERT INTO token_status (token_status) VALUES ('INUSE');
INSERT INTO token_status (token_status) VALUES ('USED');


INSERT INTO nodes (node_id, name, rss_url) VALUES ('default', 'Unknown node', NULL);
--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET SESSION AUTHORIZATION 'wifidog';

SET search_path = public, pg_catalog;

--
-- Data for TOC entry 2 (OID 318918)
-- Name: node_deployment_status; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO node_deployment_status (node_deployment_status) VALUES ('DEPLOYED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_PLANNING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_TESTING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('NON_WIFIDOG_NODE');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('PERMANENTLY_CLOSED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('TEMPORARILY_CLOSED');


--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET SESSION AUTHORIZATION 'wifidog';

SET search_path = public, pg_catalog;

--
-- Data for TOC entry 2 (OID 566556)
-- Name: venue_types; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO venue_types (venue_type) VALUES ('Airline');
INSERT INTO venue_types (venue_type) VALUES ('Airport Terminal/Lounge');
INSERT INTO venue_types (venue_type) VALUES ('Bus Station');
INSERT INTO venue_types (venue_type) VALUES ('Business/Conference Center');
INSERT INTO venue_types (venue_type) VALUES ('Cafe/Coffee Shop');
INSERT INTO venue_types (venue_type) VALUES ('Camp Ground');
INSERT INTO venue_types (venue_type) VALUES ('Community Network');
INSERT INTO venue_types (venue_type) VALUES ('Convention Center');
INSERT INTO venue_types (venue_type) VALUES ('Cruise Ship');
INSERT INTO venue_types (venue_type) VALUES ('Copy Center/Business Services');
INSERT INTO venue_types (venue_type) VALUES ('Entertainment Venues');
INSERT INTO venue_types (venue_type) VALUES ('Gas/Petrol Station');
INSERT INTO venue_types (venue_type) VALUES ('Hospital');
INSERT INTO venue_types (venue_type) VALUES ('Hotel');
INSERT INTO venue_types (venue_type) VALUES ('Internet Cafe');
INSERT INTO venue_types (venue_type) VALUES ('Kiosk');
INSERT INTO venue_types (venue_type) VALUES ('Library');
INSERT INTO venue_types (venue_type) VALUES ('Marina/Harbour');
INSERT INTO venue_types (venue_type) VALUES ('Motorway Travel Center/TruckStop');
INSERT INTO venue_types (venue_type) VALUES ('Office Building/Complex');
INSERT INTO venue_types (venue_type) VALUES ('Other');
INSERT INTO venue_types (venue_type) VALUES ('Park');
INSERT INTO venue_types (venue_type) VALUES ('Pay Phone/Booth');
INSERT INTO venue_types (venue_type) VALUES ('Port/Ferry Terminal');
INSERT INTO venue_types (venue_type) VALUES ('Residential Housing/Apt Bldg');
INSERT INTO venue_types (venue_type) VALUES ('Restaurant/Bar/Pub');
INSERT INTO venue_types (venue_type) VALUES ('School/University');
INSERT INTO venue_types (venue_type) VALUES ('Shopping Center');
INSERT INTO venue_types (venue_type) VALUES ('Sports Arena/Venue');
INSERT INTO venue_types (venue_type) VALUES ('Store/Retail Shop');
INSERT INTO venue_types (venue_type) VALUES ('Train');
INSERT INTO venue_types (venue_type) VALUES ('Train/Rail Station');
INSERT INTO venue_types (venue_type) VALUES ('Water Travel');
INSERT INTO venue_types (venue_type) VALUES ('Wi-Fi Zone');


