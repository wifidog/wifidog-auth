\connect wifidog;
BEGIN;
--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Data for Name: token_status; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO token_status (token_status) VALUES ('UNUSED');
INSERT INTO token_status (token_status) VALUES ('INUSE');
INSERT INTO token_status (token_status) VALUES ('USED');


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Data for Name: venue_types; Type: TABLE DATA; Schema: public; Owner: wifidog
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


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Data for Name: node_deployment_status; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO node_deployment_status (node_deployment_status) VALUES ('DEPLOYED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_PLANNING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_TESTING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('NON_WIFIDOG_NODE');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('PERMANENTLY_CLOSED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('TEMPORARILY_CLOSED');


--
-- PostgreSQL database dump complete
--

INSERT INTO nodes (node_id, name) VALUES ('default', 'Unknown node');
--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Data for Name: schema_info; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO schema_info (tag, value) VALUES ('schema_version', '24');


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Data for Name: locales; Type: TABLE DATA; Schema: public; Owner: wifidog
--

INSERT INTO locales (locales_id) VALUES ('fr');
INSERT INTO locales (locales_id) VALUES ('en');


--
-- PostgreSQL database dump complete
--

COMMIT;
