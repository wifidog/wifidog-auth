--
-- PostgreSQL database dump
--

--
-- TOC entry 1 (OID 0)
-- Name: wifidog; Type: DATABASE; Schema: -; Owner: wifidog
--

CREATE DATABASE wifidog WITH TEMPLATE = template0 ENCODING = 'LATIN1';


\connect wifidog wifidog

SET search_path = public, pg_catalog;

--
-- TOC entry 21 (OID 17142)
-- Name: plpgsql_call_handler(); Type: FUNC PROCEDURAL LANGUAGE; Schema: public; Owner: postgres
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    AS '$libdir/plpgsql', 'plpgsql_call_handler'
    LANGUAGE c;


--
-- TOC entry 20 (OID 17143)
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: public; Owner: 
--

CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql HANDLER plpgsql_call_handler;


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
    incoming integer DEFAULT 0 NOT NULL,
    outgoing integer DEFAULT 0 NOT NULL,
    node_id character varying(32),
    node_ip character varying(15),
    timestamp_out timestamp without time zone,
    user_id character varying(45) DEFAULT ''::character varying NOT NULL,
    user_mac character varying(18),
    user_ip character varying(16),
    last_updated timestamp without time zone NOT NULL
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
    last_heartbeat_timestamp timestamp without time zone
);


--
-- TOC entry 9 (OID 299906)
-- Name: users; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE users (
    user_id character varying(45) NOT NULL,
    pass character varying(32) DEFAULT ''::character varying NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    account_status integer,
    validation_token character varying(64) DEFAULT ''::character varying NOT NULL,
    reg_date timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 10 (OID 300988)
-- Name: node_owners; Type: TABLE; Schema: public; Owner: wifidog
--

CREATE TABLE node_owners (
    node_id information_schema.cardinal_number NOT NULL,
    user_id information_schema.cardinal_number NOT NULL
) WITHOUT OIDS;


--
-- TOC entry 15 (OID 300919)
-- Name: idx_token; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token ON connections USING btree (token);


--
-- TOC entry 16 (OID 300920)
-- Name: idx_token_status_and_user_id; Type: INDEX; Schema: public; Owner: wifidog
--

CREATE INDEX idx_token_status_and_user_id ON connections USING btree (token_status, user_id);


--
-- TOC entry 12 (OID 299870)
-- Name: administrators_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_pkey PRIMARY KEY (user_id);


--
-- TOC entry 13 (OID 299874)
-- Name: token_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY token_status
    ADD CONSTRAINT token_status_pkey PRIMARY KEY (token_status);


--
-- TOC entry 14 (OID 299889)
-- Name: connections_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT connections_pkey PRIMARY KEY (conn_id);


--
-- TOC entry 17 (OID 299901)
-- Name: nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (node_id);


--
-- TOC entry 18 (OID 299912)
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 19 (OID 300990)
-- Name: node_owner_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT node_owner_pkey PRIMARY KEY (node_id, user_id);


--
-- TOC entry 23 (OID 299891)
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT "$1" FOREIGN KEY (token_status) REFERENCES token_status(token_status);


--
-- TOC entry 22 (OID 299914)
-- Name: administrators_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 24 (OID 300909)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 25 (OID 300913)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 26 (OID 300993)
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 27 (OID 300997)
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_owners
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 2 (OID 2200)
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


--
-- TOC entry 8 (OID 299895)
-- Name: COLUMN nodes.last_heartbeat_ip; Type: COMMENT; Schema: public; Owner: wifidog
--

COMMENT ON COLUMN nodes.last_heartbeat_ip IS 'The last IP the node''s gateway pinged the auth server from.';


--
-- TOC entry 11 (OID 300988)
-- Name: TABLE node_owners; Type: COMMENT; Schema: public; Owner: wifidog
--

COMMENT ON TABLE node_owners IS 'Which user are owner of a node and can view statistics, etc.';


