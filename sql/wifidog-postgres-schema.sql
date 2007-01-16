--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: wifidog; Type: DATABASE; Schema: -; Owner: wifidog
--

CREATE DATABASE wifidog WITH TEMPLATE = template0 ENCODING = 'UTF8';


\connect wifidog

SET client_encoding = 'UTF8';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: administrators; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE administrators (
    user_id character varying(45) DEFAULT ''::character varying NOT NULL
);


--
-- Name: connections; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
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
-- Name: content; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content (
    content_id text NOT NULL,
    content_type text NOT NULL,
    title text,
    description text,
    project_info text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    is_persistent boolean DEFAULT false,
    long_description text,
    title_is_displayed boolean DEFAULT true NOT NULL,
    last_update_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT content_type_not_empty_string CHECK ((content_type <> ''::text))
);


--
-- Name: content_available_display_areas; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_available_display_areas (
    display_area text NOT NULL
);


--
-- Name: content_available_display_pages; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_available_display_pages (
    display_page text NOT NULL
);


SET default_with_oids = false;

--
-- Name: content_clickthrough_log; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_clickthrough_log (
    user_id text NOT NULL,
    content_id text NOT NULL,
    first_clickthrough_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    node_id text NOT NULL,
    destination_url text NOT NULL,
    num_clickthrough integer DEFAULT 1 NOT NULL,
    last_clickthrough_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT content_clickthrough_log_destination_url_check CHECK ((destination_url <> ''::text))
);


SET default_with_oids = true;

--
-- Name: content_display_log; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_display_log (
    user_id text NOT NULL,
    content_id text NOT NULL,
    first_display_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    node_id text NOT NULL,
    last_display_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    num_display integer DEFAULT 1 NOT NULL
);


--
-- Name: content_embedded_content; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_embedded_content (
    embedded_content_id text NOT NULL,
    embedded_file_id text,
    fallback_content_id text,
    parameters text,
    attributes text
);


--
-- Name: content_file; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_file (
    files_id text NOT NULL,
    filename text,
    mime_type text,
    remote_size bigint,
    url text,
    data_blob oid,
    local_binary_size bigint
);


--
-- Name: content_file_image; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_file_image (
    pictures_id text NOT NULL,
    width integer,
    height integer,
    hyperlink_url text
);


--
-- Name: content_flickr_photostream; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_flickr_photostream (
    flickr_photostream_id text NOT NULL,
    api_key text,
    photo_selection_mode text DEFAULT 'PSM_GROUP'::text NOT NULL,
    user_id text,
    user_name text,
    tags text,
    tag_mode character varying(10) DEFAULT 'any'::character varying,
    group_id text,
    random boolean DEFAULT true NOT NULL,
    min_taken_date timestamp without time zone,
    max_taken_date timestamp without time zone,
    photo_batch_size integer DEFAULT 10,
    photo_count integer DEFAULT 1,
    display_title boolean DEFAULT true NOT NULL,
    display_description boolean DEFAULT false NOT NULL,
    display_tags boolean DEFAULT false NOT NULL,
    preferred_size text,
    requests_cache text,
    cache_update_timestamp timestamp without time zone,
    api_shared_secret text,
    photo_display_mode text DEFAULT 'PDM_GRID'::text NOT NULL
);


--
-- Name: content_group; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_group (
    content_group_id text NOT NULL,
    content_changes_on_mode text DEFAULT 'ALWAYS'::text NOT NULL,
    content_ordering_mode text DEFAULT 'RANDOM'::text NOT NULL,
    display_num_elements integer DEFAULT 1 NOT NULL,
    allow_repeat text DEFAULT 'YES'::text NOT NULL,
    CONSTRAINT display_at_least_one_element CHECK ((display_num_elements > 0))
);


--
-- Name: content_group_element; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_group_element (
    content_group_element_id text NOT NULL,
    content_group_id text NOT NULL,
    display_order integer DEFAULT 1,
    displayed_content_id text,
    force_only_allowed_node boolean,
    valid_from_timestamp timestamp without time zone,
    valid_until_timestamp timestamp without time zone
);


--
-- Name: content_group_element_has_allowed_nodes; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_group_element_has_allowed_nodes (
    content_group_element_id text NOT NULL,
    node_id text NOT NULL,
    allowed_since timestamp without time zone DEFAULT now()
);


--
-- Name: content_has_owners; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_has_owners (
    content_id text NOT NULL,
    user_id text NOT NULL,
    is_author boolean DEFAULT false NOT NULL,
    owner_since timestamp without time zone DEFAULT now()
);


--
-- Name: content_iframe; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_iframe (
    iframes_id text NOT NULL,
    url text,
    width integer,
    height integer
);


SET default_with_oids = false;

--
-- Name: content_key_value_pairs; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_key_value_pairs (
    content_id text NOT NULL,
    "key" text NOT NULL,
    value text
);


SET default_with_oids = true;

--
-- Name: content_langstring_entries; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_langstring_entries (
    langstring_entries_id text NOT NULL,
    langstrings_id text,
    locales_id text,
    value text DEFAULT ''::text
);


--
-- Name: content_rss_aggregator; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_rss_aggregator (
    content_id text NOT NULL,
    number_of_display_items integer DEFAULT 10 NOT NULL,
    algorithm_strength real DEFAULT 0.75 NOT NULL,
    max_item_age interval,
    feed_expansion text DEFAULT 'FIRST'::text NOT NULL,
    feed_ordering text DEFAULT 'REVERSE_DATE'::text NOT NULL,
    display_empty_feeds boolean DEFAULT true NOT NULL
);


--
-- Name: content_rss_aggregator_feeds; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_rss_aggregator_feeds (
    content_id text NOT NULL,
    url text NOT NULL,
    bias real DEFAULT 1 NOT NULL,
    default_publication_interval integer,
    title text
);


SET default_with_oids = false;

--
-- Name: content_shoutbox_messages; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE content_shoutbox_messages (
    message_content_id text NOT NULL,
    shoutbox_id text NOT NULL,
    origin_node_id text NOT NULL,
    author_user_id text NOT NULL,
    creation_date timestamp without time zone DEFAULT now()
);


SET default_with_oids = true;

--
-- Name: locales; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE locales (
    locales_id text NOT NULL
);


--
-- Name: network_has_content; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE network_has_content (
    network_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    display_page text DEFAULT 'portal'::text NOT NULL,
    display_area text DEFAULT 'main_area_middle'::text NOT NULL,
    display_order integer DEFAULT 1 NOT NULL
);


--
-- Name: network_stakeholders; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE network_stakeholders (
    network_id text NOT NULL,
    user_id character varying(45) NOT NULL,
    is_admin boolean DEFAULT false NOT NULL,
    is_stat_viewer boolean DEFAULT false NOT NULL
);


--
-- Name: networks; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE networks (
    network_id text NOT NULL,
    network_authenticator_class text NOT NULL,
    network_authenticator_params text,
    is_default_network boolean DEFAULT false NOT NULL,
    name text DEFAULT 'Unnamed network'::text NOT NULL,
    creation_date date DEFAULT now() NOT NULL,
    homepage_url text,
    tech_support_email text,
    validation_grace_time interval DEFAULT '00:20:00'::interval NOT NULL,
    validation_email_from_address text DEFAULT 'validation@wifidognetwork'::text NOT NULL,
    allow_multiple_login boolean DEFAULT false NOT NULL,
    allow_splash_only_nodes boolean DEFAULT false NOT NULL,
    allow_custom_portal_redirect boolean DEFAULT false NOT NULL,
    gmaps_initial_latitude numeric(16,6),
    gmaps_initial_longitude numeric(16,6),
    gmaps_initial_zoom_level integer,
    gmaps_map_type text DEFAULT 'G_NORMAL_MAP'::text NOT NULL,
    theme_pack text,
    CONSTRAINT networks_gmaps_map_type CHECK ((gmaps_map_type <> ''::text)),
    CONSTRAINT networks_name CHECK ((name <> ''::text)),
    CONSTRAINT networks_network_authenticator_class CHECK ((network_authenticator_class <> ''::text)),
    CONSTRAINT networks_validation_email_from_address CHECK ((validation_email_from_address <> ''::text))
);


SET default_with_oids = false;

--
-- Name: node_deployment_status; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE node_deployment_status (
    node_deployment_status character varying(32) NOT NULL
);


SET default_with_oids = true;

--
-- Name: node_has_content; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE node_has_content (
    node_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    display_page text DEFAULT 'portal'::text NOT NULL,
    display_area text DEFAULT 'main_area_middle'::text NOT NULL,
    display_order integer DEFAULT 1 NOT NULL
);


--
-- Name: node_stakeholders; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE node_stakeholders (
    node_id character varying(32) NOT NULL,
    user_id character varying(45) NOT NULL,
    is_owner boolean DEFAULT false NOT NULL,
    is_tech_officer boolean DEFAULT false NOT NULL
);


--
-- Name: nodes; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE nodes (
    node_id character varying(32) DEFAULT ''::character varying NOT NULL,
    name text,
    last_heartbeat_ip character varying(16),
    last_heartbeat_timestamp timestamp without time zone DEFAULT now(),
    creation_date date DEFAULT now(),
    home_page_url text,
    last_heartbeat_user_agent text,
    description text,
    map_url text,
    public_phone_number text,
    public_email text,
    mass_transit_info text,
    node_deployment_status character varying(32) DEFAULT 'IN_PLANNING'::character varying NOT NULL,
    venue_type text DEFAULT 'Other'::text,
    max_monthly_incoming bigint,
    max_monthly_outgoing bigint,
    quota_reset_day_of_month integer,
    latitude numeric(16,6),
    longitude numeric(16,6),
    civic_number text,
    street_name text,
    city text,
    province text,
    country text,
    postal_code text,
    network_id text NOT NULL,
    last_paged timestamp without time zone,
    is_splash_only_node boolean DEFAULT false,
    custom_portal_redirect_url text,
    gw_id text NOT NULL
);


--
-- Name: schema_info; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE schema_info (
    tag text NOT NULL,
    value text
);


--
-- Name: servers; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE servers (
    server_id text NOT NULL,
    is_default_server boolean DEFAULT false NOT NULL,
    name text DEFAULT 'Unnamed server'::text NOT NULL,
    creation_date date DEFAULT now() NOT NULL,
    hostname text DEFAULT 'localhost'::text NOT NULL,
    ssl_available boolean DEFAULT false NOT NULL,
    gmaps_api_key text,
    CONSTRAINT servers_hostname CHECK ((name <> ''::text)),
    CONSTRAINT servers_name CHECK ((name <> ''::text))
);


--
-- Name: token_status; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE token_status (
    token_status character varying(10) NOT NULL
);


--
-- Name: user_has_content; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE user_has_content (
    user_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
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
    prefered_locale text,
    CONSTRAINT check_user_not_empty CHECK (((user_id)::text <> ''::text))
);


SET default_with_oids = false;

--
-- Name: venue_types; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE venue_types (
    venue_type text NOT NULL
);


--
-- Name: venues; Type: TABLE; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE TABLE venues (
    name text NOT NULL,
    description text
);


--
-- Name: administrators_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_pkey PRIMARY KEY (user_id);


--
-- Name: connections_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT connections_pkey PRIMARY KEY (conn_id);


--
-- Name: content_available_display_areas_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_available_display_areas
    ADD CONSTRAINT content_available_display_areas_pkey PRIMARY KEY (display_area);


--
-- Name: content_clickthrough_log_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_pkey PRIMARY KEY (content_id, user_id, node_id, destination_url);


--
-- Name: content_display_location_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_available_display_pages
    ADD CONSTRAINT content_display_location_pkey PRIMARY KEY (display_page);


--
-- Name: content_display_log_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT content_display_log_pkey PRIMARY KEY (content_id, user_id, node_id);


--
-- Name: content_group_element_has_allowed_nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT content_group_element_has_allowed_nodes_pkey PRIMARY KEY (content_group_element_id, node_id);


--
-- Name: content_group_element_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT content_group_element_pkey PRIMARY KEY (content_group_element_id);


--
-- Name: content_group_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT content_group_pkey PRIMARY KEY (content_group_id);


--
-- Name: content_has_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT content_has_owners_pkey PRIMARY KEY (content_id, user_id);


--
-- Name: content_key_value_pairs_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_key_value_pairs
    ADD CONSTRAINT content_key_value_pairs_pkey PRIMARY KEY (content_id, "key");


--
-- Name: content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content
    ADD CONSTRAINT content_pkey PRIMARY KEY (content_id);


--
-- Name: content_rss_aggregator_feeds_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_rss_aggregator_feeds
    ADD CONSTRAINT content_rss_aggregator_feeds_pkey PRIMARY KEY (content_id, url);


--
-- Name: content_rss_aggregator_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_rss_aggregator
    ADD CONSTRAINT content_rss_aggregator_pkey PRIMARY KEY (content_id);


--
-- Name: content_shoutbox_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_pkey PRIMARY KEY (message_content_id);


--
-- Name: files_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_file
    ADD CONSTRAINT files_pkey PRIMARY KEY (files_id);


--
-- Name: flickr_photostream_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_flickr_photostream
    ADD CONSTRAINT flickr_photostream_pkey PRIMARY KEY (flickr_photostream_id);


--
-- Name: iframes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_iframe
    ADD CONSTRAINT iframes_pkey PRIMARY KEY (iframes_id);


--
-- Name: langstring_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT langstring_entries_pkey PRIMARY KEY (langstring_entries_id);


--
-- Name: locales_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY locales
    ADD CONSTRAINT locales_pkey PRIMARY KEY (locales_id);


--
-- Name: network_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT network_has_content_pkey PRIMARY KEY (network_id, content_id);


--
-- Name: network_stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT network_stakeholders_pkey PRIMARY KEY (network_id, user_id);


--
-- Name: networks_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY networks
    ADD CONSTRAINT networks_pkey PRIMARY KEY (network_id);


--
-- Name: node_deployment_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY node_deployment_status
    ADD CONSTRAINT node_deployment_status_pkey PRIMARY KEY (node_deployment_status);


--
-- Name: node_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT node_has_content_pkey PRIMARY KEY (node_id, content_id);


--
-- Name: node_stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT node_stakeholders_pkey PRIMARY KEY (node_id, user_id);


--
-- Name: nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (node_id);


--
-- Name: pictures_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY content_file_image
    ADD CONSTRAINT pictures_pkey PRIMARY KEY (pictures_id);


--
-- Name: schema_info_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY schema_info
    ADD CONSTRAINT schema_info_pkey PRIMARY KEY (tag);


--
-- Name: servers_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY servers
    ADD CONSTRAINT servers_pkey PRIMARY KEY (server_id);


--
-- Name: token_status_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY token_status
    ADD CONSTRAINT token_status_pkey PRIMARY KEY (token_status);


--
-- Name: user_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT user_has_content_pkey PRIMARY KEY (user_id, content_id);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: venue_types_pkey; Type: CONSTRAINT; Schema: public; Owner: wifidog; Tablespace: 
--

ALTER TABLE ONLY venue_types
    ADD CONSTRAINT venue_types_pkey PRIMARY KEY (venue_type);


--
-- Name: idx_connections_node_id; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_connections_node_id ON connections USING btree (node_id);


--
-- Name: idx_connections_user_id; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_connections_user_id ON connections USING btree (user_id);


--
-- Name: idx_connections_user_mac; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_connections_user_mac ON connections USING btree (user_mac);


--
-- Name: idx_content_group_element_content_group_id; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_content_group_element_content_group_id ON content_group_element USING btree (content_group_id);


--
-- Name: idx_content_group_element_valid_from_timestamp; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_content_group_element_valid_from_timestamp ON content_group_element USING btree (valid_from_timestamp);


--
-- Name: idx_content_group_element_valid_until_timestamp; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_content_group_element_valid_until_timestamp ON content_group_element USING btree (valid_until_timestamp);


--
-- Name: idx_gw_id; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE UNIQUE INDEX idx_gw_id ON nodes USING btree (gw_id);


--
-- Name: idx_token; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_token ON connections USING btree (token);


--
-- Name: idx_token_status_and_user_id; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE INDEX idx_token_status_and_user_id ON connections USING btree (token_status, user_id);


--
-- Name: idx_unique_username_and_account_origin; Type: INDEX; Schema: public; Owner: wifidog; Tablespace: 
--

CREATE UNIQUE INDEX idx_unique_username_and_account_origin ON users USING btree (username, account_origin);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT "$1" FOREIGN KEY (token_status) REFERENCES token_status(token_status);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "$1" FOREIGN KEY (prefered_locale) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$1" FOREIGN KEY (title) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT "$1" FOREIGN KEY (langstrings_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content_group_element(content_group_element_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_file_image
    ADD CONSTRAINT "$1" FOREIGN KEY (pictures_id) REFERENCES content_file(files_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_iframe
    ADD CONSTRAINT "$1" FOREIGN KEY (iframes_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_rss_aggregator
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_rss_aggregator_feeds
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content_rss_aggregator(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$2" FOREIGN KEY (description) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$2" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT "$2" FOREIGN KEY (locales_id) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$2" FOREIGN KEY (content_group_id) REFERENCES content_group(content_group_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$2" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (display_area) REFERENCES content_available_display_areas(display_area) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$3" FOREIGN KEY (project_info) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$3" FOREIGN KEY (displayed_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$3" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$3" FOREIGN KEY (display_area) REFERENCES content_available_display_areas(display_area) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $5; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$5" FOREIGN KEY (long_description) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: account_origin_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY users
    ADD CONSTRAINT account_origin_fkey FOREIGN KEY (account_origin) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: administrators_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY administrators
    ADD CONSTRAINT administrators_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_content_id_fkey FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_node_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_node_id_fkey FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_key_value_pairs_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_key_value_pairs
    ADD CONSTRAINT content_key_value_pairs_content_id_fkey FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_author_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_author_user_id_fkey FOREIGN KEY (author_user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_message_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_message_content_id_fkey FOREIGN KEY (message_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_origin_node_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_origin_node_id_fkey FOREIGN KEY (origin_node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_shoutbox_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_shoutbox_id_fkey FOREIGN KEY (shoutbox_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: display_location_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_page) REFERENCES content_available_display_pages(display_page) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: display_location_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_page) REFERENCES content_available_display_pages(display_page) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: fk_network; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT fk_network FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_node_deployment_status; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_node_deployment_status FOREIGN KEY (node_deployment_status) REFERENCES node_deployment_status(node_deployment_status) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_venue_types; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_venue_types FOREIGN KEY (venue_type) REFERENCES venue_types(venue_type) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: flickr_photostream_content_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY content_flickr_photostream
    ADD CONSTRAINT flickr_photostream_content_fkey FOREIGN KEY (flickr_photostream_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: nodes_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wifidog
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT nodes_fkey FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

