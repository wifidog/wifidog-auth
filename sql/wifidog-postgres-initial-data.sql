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
