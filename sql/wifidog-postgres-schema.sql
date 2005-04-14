--
-- PostgreSQL database dump
--

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;

--
-- TOC entry 2 (OID 0)
-- Name: wifidog; Type: DATABASE; Schema: -; Owner: wifidog
--

CREATE DATABASE wifidog WITH TEMPLATE = template0 ENCODING = 'UNICODE';


\connect wifidog wifidog

SET client_encoding = 'UNICODE';
SET check_function_bodies = false;

SET search_path = public, pg_catalog;

--
-- TOC entry 5 (OID 192397)
-- Name: administrators; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE administrators (
    user_id character varying(45) DEFAULT ''::character varying NOT NULL
);


--
-- TOC entry 6 (OID 192400)
-- Name: token_status; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE token_status (
    token_status character varying(10) NOT NULL
);


--
-- TOC entry 7 (OID 192404)
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
-- TOC entry 8 (OID 192410)
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
-- TOC entry 9 (OID 192420)
-- Name: users; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE users (
    user_id character varying(45) NOT NULL,
    pass character varying(32) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    account_status integer,
    validation_token character varying(64) DEFAULT ''::character varying NOT NULL,
    reg_date timestamp without time zone DEFAULT now() NOT NULL,
    username text,
    account_origin text NOT NULL,
    never_show_username boolean DEFAULT false,
    real_name text,
    website text,
    prefered_locale text,
    CONSTRAINT check_account_origin_not_empty CHECK ((account_origin <> ''::text)),
    CONSTRAINT check_user_not_empty CHECK (((user_id)::text <> ''::text))
);


--
-- TOC entry 10 (OID 192430)
-- Name: node_owners; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_owners (
    node_id character varying(32) NOT NULL,
    user_id character varying(45) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 11 (OID 192432)
-- Name: node_deployment_status; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_deployment_status (
    node_deployment_status character varying(32) NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 12 (OID 192434)
-- Name: venue_types; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE venue_types (
    venue_type text NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 13 (OID 192439)
-- Name: venues; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE venues (
    name text NOT NULL,
    description text
) WITHOUT OIDS;


--
-- TOC entry 14 (OID 192444)
-- Name: schema_info; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE schema_info (
    tag text NOT NULL,
    value text
);


--
-- TOC entry 15 (OID 214063)
-- Name: locales; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE locales (
    locales_id text NOT NULL
);


--
-- TOC entry 16 (OID 214077)
-- Name: content; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content (
    content_id text NOT NULL,
    content_type text NOT NULL,
    title text,
    description text,
    project_info text,
    sponsor_info text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    CONSTRAINT content_type_not_empty_string CHECK ((content_type <> ''::text))
);


--
-- TOC entry 17 (OID 214102)
-- Name: content_has_owners; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content_has_owners (
    content_id text NOT NULL,
    user_id text NOT NULL,
    is_author boolean NOT NULL,
    owner_since timestamp without time zone DEFAULT now()
);


--
-- TOC entry 18 (OID 214118)
-- Name: langstring_entries; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE langstring_entries (
    langstring_entries_id text NOT NULL,
    langstrings_id text,
    locales_id text,
    value text DEFAULT ''::text
);


--
-- TOC entry 19 (OID 214134)
-- Name: content_group; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content_group (
    content_group_id text NOT NULL,
    is_artistic_content boolean DEFAULT false NOT NULL,
    is_locative_content boolean DEFAULT false NOT NULL,
    content_selection_mode text
);


--
-- TOC entry 20 (OID 214145)
-- Name: content_group_element; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content_group_element (
    content_group_element_id text NOT NULL,
    content_group_id text NOT NULL,
    display_order integer DEFAULT 1,
    displayed_content_id text,
    force_only_allowed_node boolean
);


--
-- TOC entry 21 (OID 214166)
-- Name: content_group_element_portal_display_log; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content_group_element_portal_display_log (
    user_id text NOT NULL,
    content_group_element_id text NOT NULL,
    display_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    node_id text
);


--
-- TOC entry 22 (OID 214186)
-- Name: user_has_content; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE user_has_content (
    user_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 23 (OID 214202)
-- Name: node_has_content; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_has_content (
    node_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 24 (OID 214218)
-- Name: network_has_content; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE network_has_content (
    network_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 25 (OID 214271)
-- Name: content_group_element_has_allowed_nodes; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE content_group_element_has_allowed_nodes (
    content_group_element_id text NOT NULL,
    node_id text NOT NULL,
    allowed_since timestamp without time zone DEFAULT now()
);


--
-- TOC entry 29 (OID 214009)
-- Name: idx_token; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token ON connections USING btree (token);


--
-- TOC entry 30 (OID 214010)
-- Name: idx_token_status_and_user_id; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token_status_and_user_id ON connections USING btree (token_status, user_id);


--
-- TOC entry 32 (OID 214011)
-- Name: idx_unique_username_and_account_origin; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE UNIQUE INDEX idx_unique_username_and_account_origin ON users USING btree (username, account_origin);


--
-- TOC entry 44 (OID 214165)
-- Name: idx_content_group_element_content_group_id; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_content_group_element_content_group_id ON content_group_element USING btree (content_group_id);


--
-- TOC entry 26 (OID 214012)
-- Name: administrators_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_pkey PRIMARY KEY (user_id);


--
-- TOC entry 27 (OID 214014)
-- Name: token_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY token_status
    ADD CONSTRAINT token_status_pkey PRIMARY KEY (token_status);


--
-- TOC entry 28 (OID 214016)
-- Name: connections_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT connections_pkey PRIMARY KEY (conn_id);


--
-- TOC entry 31 (OID 214018)
-- Name: nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (node_id);


--
-- TOC entry 33 (OID 214020)
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 34 (OID 214022)
-- Name: node_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT node_owners_pkey PRIMARY KEY (node_id, user_id);


--
-- TOC entry 35 (OID 214024)
-- Name: node_deployment_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_deployment_status
    ADD CONSTRAINT node_deployment_status_pkey PRIMARY KEY (node_deployment_status);


--
-- TOC entry 36 (OID 214026)
-- Name: venue_types_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY venue_types
    ADD CONSTRAINT venue_types_pkey PRIMARY KEY (venue_type);


--
-- TOC entry 37 (OID 214028)
-- Name: schema_info_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY schema_info
    ADD CONSTRAINT schema_info_pkey PRIMARY KEY (tag);


--
-- TOC entry 38 (OID 214068)
-- Name: locales_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY locales
    ADD CONSTRAINT locales_pkey PRIMARY KEY (locales_id);


--
-- TOC entry 39 (OID 214084)
-- Name: content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT content_pkey PRIMARY KEY (content_id);


--
-- TOC entry 40 (OID 214108)
-- Name: content_has_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT content_has_owners_pkey PRIMARY KEY (content_id, user_id);


--
-- TOC entry 41 (OID 214124)
-- Name: langstring_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY langstring_entries
    ADD CONSTRAINT langstring_entries_pkey PRIMARY KEY (langstring_entries_id);


--
-- TOC entry 42 (OID 214139)
-- Name: content_group_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT content_group_pkey PRIMARY KEY (content_group_id);


--
-- TOC entry 43 (OID 214151)
-- Name: content_group_element_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT content_group_element_pkey PRIMARY KEY (content_group_element_id);


--
-- TOC entry 45 (OID 214172)
-- Name: content_group_element_portal_display_log_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_portal_display_log
    ADD CONSTRAINT content_group_element_portal_display_log_pkey PRIMARY KEY (user_id, content_group_element_id, display_timestamp);


--
-- TOC entry 46 (OID 214192)
-- Name: user_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT user_has_content_pkey PRIMARY KEY (user_id, content_id);


--
-- TOC entry 47 (OID 214208)
-- Name: node_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT node_has_content_pkey PRIMARY KEY (node_id, content_id);


--
-- TOC entry 48 (OID 214224)
-- Name: network_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT network_has_content_pkey PRIMARY KEY (network_id, content_id);


--
-- TOC entry 49 (OID 214277)
-- Name: content_group_element_has_allowed_nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT content_group_element_has_allowed_nodes_pkey PRIMARY KEY (content_group_element_id, node_id);


--
-- TOC entry 51 (OID 214030)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT "$1" FOREIGN KEY (token_status) REFERENCES token_status(token_status);


--
-- TOC entry 50 (OID 214034)
-- Name: administrators_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 52 (OID 214038)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 53 (OID 214042)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 57 (OID 214046)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 58 (OID 214050)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 54 (OID 214054)
-- Name: fk_node_deployment_status; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_node_deployment_status FOREIGN KEY (node_deployment_status) REFERENCES node_deployment_status(node_deployment_status) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 55 (OID 214058)
-- Name: fk_venue_types; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_venue_types FOREIGN KEY (venue_type) REFERENCES venue_types(venue_type) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 56 (OID 214073)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "$1" FOREIGN KEY (prefered_locale) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- TOC entry 59 (OID 214086)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$1" FOREIGN KEY (title) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 60 (OID 214090)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$2" FOREIGN KEY (description) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 61 (OID 214094)
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$3" FOREIGN KEY (project_info) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 62 (OID 214098)
-- Name: $4; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$4" FOREIGN KEY (sponsor_info) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 63 (OID 214110)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 64 (OID 214114)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$2" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 65 (OID 214126)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY langstring_entries
    ADD CONSTRAINT "$1" FOREIGN KEY (langstrings_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 66 (OID 214130)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY langstring_entries
    ADD CONSTRAINT "$2" FOREIGN KEY (locales_id) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 67 (OID 214141)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 68 (OID 214153)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 69 (OID 214157)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$2" FOREIGN KEY (content_group_id) REFERENCES content_group(content_group_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 70 (OID 214161)
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$3" FOREIGN KEY (displayed_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 71 (OID 214174)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_portal_display_log
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 72 (OID 214178)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_portal_display_log
    ADD CONSTRAINT "$2" FOREIGN KEY (content_group_element_id) REFERENCES content_group_element(content_group_element_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 73 (OID 214182)
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_portal_display_log
    ADD CONSTRAINT "$3" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 74 (OID 214194)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 75 (OID 214198)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 76 (OID 214210)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 77 (OID 214214)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 78 (OID 214226)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 79 (OID 214279)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content_group_element(content_group_element_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 80 (OID 214283)
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$2" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3 (OID 2200)
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


