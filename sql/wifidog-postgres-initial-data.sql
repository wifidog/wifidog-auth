\connect wifidog;
--- The default admin user, delete or change password as soon as possible.  The password is admin 
INSERT INTO users (user_id, pass, email, account_status, validation_token, reg_date) VALUES ('admin', 'ISMvKXpXpadDiUoOSoAfww==', 'test_user_please@delete.me', 1, 'df16cc4b1d0975e267f3425eaac31950','2005-01-01 00:00:00');
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

INSERT INTO node_deployment_status (node_deployment_status) VALUES ('DEPLOYED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_PLANNING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('IN_TESTING');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('NON_WIFIDOG_NODE');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('PERMANENTLY_CLOSED');
INSERT INTO node_deployment_status (node_deployment_status) VALUES ('TEMPORARILY_CLOSED');

INSERT INTO nodes (node_id, name, rss_url) VALUES ('default', 'Unknown node', NULL);
