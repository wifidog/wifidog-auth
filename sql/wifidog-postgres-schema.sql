--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

--
-- TOC entry 2 (OID 0)
-- Name: wifidog; Type: DATABASE; Schema: -; Owner: wifidog
--

CREATE DATABASE wifidog WITH TEMPLATE = template0 ENCODING = 'LATIN1';


\connect wifidog wifidog

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET search_path = public, pg_catalog;

--
-- TOC entry 4 (OID 299867)
-- Name: administrators; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE administrators (
    user_id character varying(45) DEFAULT ''::character varying NOT NULL
);


--
-- TOC entry 5 (OID 299872)
-- Name: token_status; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE token_status (
    token_status character varying(10) NOT NULL
);


--
-- TOC entry 6 (OID 299881)
-- Name: connections; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE connections (
    conn_id serial NOT NULL,
    token character varying(32) DEFAULT ''::character varying NOT NULL,
    token_status character varying(10) DEFAULT 'UNUSED'::character varying NOT NULL,
    timestamp_in timestamp without time zone,
    node_id character varying(32),
    node_ip character varying(15),
    timestamp_out timestamp without time zone,
    user_id character varying(45) DEFAULT ''::character varying NOT NULL,
    user_mac character varying(18),
    user_ip character varying(16),
    last_updated timestamp without time zone NOT NULL,
    incoming bigint,
    outgoing bigint
);


--
-- TOC entry 7 (OID 299895)
-- Name: nodes; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE nodes (
    node_id character varying(32) DEFAULT ''::character varying NOT NULL,
    name text,
    rss_url text,
    last_heartbeat_ip character varying(16),
    last_heartbeat_timestamp timestamp without time zone DEFAULT now(),
    creation_date date DEFAULT now(),
    home_page_url text,
    last_heartbeat_user_agent text,
    description text,
    map_url text,
    street_address text,
    public_phone_number text,
    public_email text,
    mass_transit_info text,
    node_deployment_status character varying(32) DEFAULT 'IN_PLANNING'::character varying NOT NULL,
    venue_type text DEFAULT 'Other'::text
);


--
-- TOC entry 8 (OID 299906)
-- Name: users; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE users (
    user_id character varying(45) NOT NULL,
    pass character varying(32) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    account_status integer,
    validation_token character varying(64) DEFAULT ''::character varying NOT NULL,
    reg_date timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT check_email_not_empty CHECK (((email)::text <> ''::text)),
    CONSTRAINT check_user_not_empty CHECK (((user_id)::text <> ''::text))
);


--
-- TOC entry 9 (OID 300988)
-- Name: node_owners; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_owners (
    node_id character varying(32) NOT NULL,
    user_id character varying(45) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 10 (OID 318918)
-- Name: node_deployment_status; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_deployment_status (
    node_deployment_status character varying(32) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 11 (OID 566556)
-- Name: venue_types; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE venue_types (
    venue_type text NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 12 (OID 566561)
-- Name: venues; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE venues (
    name text NOT NULL,
    description text
) WITHOUT OIDS;


--
-- TOC entry 13 (OID 680734)
-- Name: schema_info; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE schema_info (
    tag text NOT NULL,
    value text
);


--
-- TOC entry 17 (OID 300919)
-- Name: idx_token; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token ON connections USING btree (token);


--
-- TOC entry 18 (OID 300920)
-- Name: idx_token_status_and_user_id; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token_status_and_user_id ON connections USING btree (token_status, user_id);


--
-- TOC entry 14 (OID 299870)
-- Name: administrators_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_pkey PRIMARY KEY (user_id);


--
-- TOC entry 15 (OID 299874)
-- Name: token_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY token_status
    ADD CONSTRAINT token_status_pkey PRIMARY KEY (token_status);


--
-- TOC entry 16 (OID 299889)
-- Name: connections_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT connections_pkey PRIMARY KEY (conn_id);


--
-- TOC entry 19 (OID 299901)
-- Name: nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (node_id);


--
-- TOC entry 20 (OID 299912)
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 21 (OID 310107)
-- Name: node_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT node_owners_pkey PRIMARY KEY (node_id, user_id);


--
-- TOC entry 22 (OID 318920)
-- Name: node_deployment_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_deployment_status
    ADD CONSTRAINT node_deployment_status_pkey PRIMARY KEY (node_deployment_status);


--
-- TOC entry 23 (OID 566585)
-- Name: venue_types_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY venue_types
    ADD CONSTRAINT venue_types_pkey PRIMARY KEY (venue_type);


--
-- TOC entry 24 (OID 680739)
-- Name: schema_info_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY schema_info
    ADD CONSTRAINT schema_info_pkey PRIMARY KEY (tag);


--
-- TOC entry 26 (OID 299891)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT "$1" FOREIGN KEY (token_status) REFERENCES token_status(token_status);


--
-- TOC entry 25 (OID 299914)
-- Name: administrators_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 27 (OID 300909)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 28 (OID 300913)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 31 (OID 310097)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 32 (OID 310101)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 29 (OID 318922)
-- Name: fk_node_deployment_status; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_node_deployment_status FOREIGN KEY (node_deployment_status) REFERENCES node_deployment_status(node_deployment_status) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 30 (OID 566588)
-- Name: fk_venue_types; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_venue_types FOREIGN KEY (venue_type) REFERENCES venue_types(venue_type) ON UPDATE CASCADE ON DELETE RESTRICT;


