--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: wifidog; Type: DATABASE; Schema: -; Owner: -
--

CREATE DATABASE wifidog WITH TEMPLATE = template0 ENCODING = 'UTF8';


\connect wifidog

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'Standard public schema';


--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: -
--

CREATE PROCEDURAL LANGUAGE plpgsql;


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: connections; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE connections (
    conn_id integer NOT NULL,
    token_id character varying(32) DEFAULT ''::character varying NOT NULL,
    timestamp_in timestamp without time zone,
    node_id character varying(32),
    node_ip character varying(15),
    timestamp_out timestamp without time zone,
    user_id character varying(45) DEFAULT ''::character varying NOT NULL,
    user_mac character varying(18),
    user_ip character varying(16),
    last_updated timestamp without time zone NOT NULL,
    incoming bigint,
    outgoing bigint,
    max_total_bytes integer,
    max_incoming_bytes integer,
    max_outgoing_bytes integer,
    expiration_date timestamp without time zone,
    logout_reason integer
);


--
-- Name: connections_conn_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE connections_conn_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: connections_conn_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE connections_conn_id_seq OWNED BY connections.conn_id;


--
-- Name: content; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_available_display_areas; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_available_display_areas (
    display_area text NOT NULL
);


--
-- Name: content_available_display_pages; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_available_display_pages (
    display_page text NOT NULL
);


SET default_with_oids = false;

--
-- Name: content_clickthrough_log; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_display_log; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_embedded_content; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_embedded_content (
    embedded_content_id text NOT NULL,
    embedded_file_id text,
    fallback_content_id text,
    parameters text,
    attributes text
);


--
-- Name: content_file; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_file_image; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_file_image (
    pictures_id text NOT NULL,
    width integer,
    height integer,
    hyperlink_url text
);


--
-- Name: content_flickr_photostream; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_group; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_group_element; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_group_element_has_allowed_nodes; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_group_element_has_allowed_nodes (
    content_group_element_id text NOT NULL,
    node_id text NOT NULL,
    allowed_since timestamp without time zone DEFAULT now()
);


--
-- Name: content_has_owners; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_has_owners (
    content_id text NOT NULL,
    user_id text NOT NULL,
    is_author boolean DEFAULT false NOT NULL,
    owner_since timestamp without time zone DEFAULT now()
);


--
-- Name: content_iframe; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_iframe (
    iframes_id text NOT NULL,
    url text,
    width integer,
    height integer
);


SET default_with_oids = false;

--
-- Name: content_key_value_pairs; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_key_value_pairs (
    content_id text NOT NULL,
    "key" text NOT NULL,
    value text
);


SET default_with_oids = true;

--
-- Name: content_langstring_entries; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_langstring_entries (
    langstring_entries_id text NOT NULL,
    langstrings_id text,
    locales_id text,
    value text DEFAULT ''::text
);


--
-- Name: content_rss_aggregator; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_rss_aggregator_feeds; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
-- Name: content_shoutbox_messages; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_shoutbox_messages (
    message_content_id text NOT NULL,
    shoutbox_id text NOT NULL,
    origin_node_id text NOT NULL,
    author_user_id text NOT NULL,
    creation_date timestamp without time zone DEFAULT now()
);


--
-- Name: content_type_filters; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE content_type_filters (
    content_type_filter_id text NOT NULL,
    content_type_filter_label text,
    content_type_filter_rules text NOT NULL,
    CONSTRAINT content_type_filter_rules_not_empty_string CHECK ((content_type_filter_rules <> ''::text))
);


SET default_with_oids = true;

--
-- Name: locales; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE locales (
    locales_id text NOT NULL
);


--
-- Name: network_has_content; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE network_has_content (
    network_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    display_page text DEFAULT 'portal'::text NOT NULL,
    display_area text DEFAULT 'main_area_middle'::text NOT NULL,
    display_order integer DEFAULT 1 NOT NULL
);


SET default_with_oids = false;

--
-- Name: network_has_profile_templates; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE network_has_profile_templates (
    network_id text NOT NULL,
    profile_template_id text NOT NULL
);


--
-- Name: stakeholders; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE stakeholders (
    user_id text NOT NULL,
    role_id text NOT NULL,
    object_id text NOT NULL,
    CONSTRAINT user_has_roles_objct_id_not_empty_string CHECK ((object_id <> ''::text))
);


--
-- Name: network_stakeholders; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE network_stakeholders (
)
INHERITS (stakeholders);


SET default_with_oids = true;

--
-- Name: networks; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE networks (
    network_id text NOT NULL,
    network_authenticator_class text NOT NULL,
    network_authenticator_params text,
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
    connection_limit_window interval,
    connection_limit_network_max_total_bytes bigint,
    connection_limit_network_max_usage_duration interval,
    connection_limit_node_max_total_bytes bigint,
    connection_limit_node_max_usage_duration interval,
    CONSTRAINT networks_gmaps_map_type CHECK ((gmaps_map_type <> ''::text)),
    CONSTRAINT networks_name CHECK ((name <> ''::text)),
    CONSTRAINT networks_network_authenticator_class CHECK ((network_authenticator_class <> ''::text)),
    CONSTRAINT networks_validation_email_from_address CHECK ((validation_email_from_address <> ''::text))
);


SET default_with_oids = false;

--
-- Name: node_deployment_status; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE node_deployment_status (
    node_deployment_status character varying(32) NOT NULL
);


SET default_with_oids = true;

--
-- Name: node_has_content; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE node_has_content (
    node_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    display_page text DEFAULT 'portal'::text NOT NULL,
    display_area text DEFAULT 'main_area_middle'::text NOT NULL,
    display_order integer DEFAULT 1 NOT NULL
);


SET default_with_oids = false;

--
-- Name: node_stakeholders; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE node_stakeholders (
)
INHERITS (stakeholders);


SET default_with_oids = true;

--
-- Name: nodes; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
    gw_id text NOT NULL,
    last_heartbeat_sys_uptime integer,
    last_heartbeat_wifidog_uptime integer,
    last_heartbeat_sys_memfree integer,
    last_heartbeat_sys_load real,
    connection_limit_node_max_total_bytes_override bigint,
    connection_limit_node_max_usage_duration_override interval
);


SET default_with_oids = false;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions (
    permission_id text NOT NULL,
    stakeholder_type_id text NOT NULL,
    CONSTRAINT permission_rules_id_not_empty_string CHECK ((permission_id <> ''::text))
);


--
-- Name: profile_fields; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE profile_fields (
    profile_field_id text NOT NULL,
    profile_id text,
    profile_template_field_id text,
    content_id text,
    last_modified timestamp without time zone DEFAULT now()
);


--
-- Name: profile_template_fields; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE profile_template_fields (
    profile_template_field_id text NOT NULL,
    profile_template_id text NOT NULL,
    display_label_content_id text,
    admin_label_content_id text,
    content_type_filter_id text,
    display_order integer DEFAULT 1,
    semantic_id text
);


--
-- Name: profile_templates; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE profile_templates (
    profile_template_id text NOT NULL,
    profile_template_label text,
    creation_date timestamp without time zone DEFAULT now()
);


--
-- Name: profiles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE profiles (
    profile_id text NOT NULL,
    profile_template_id text,
    creation_date timestamp without time zone DEFAULT now(),
    is_visible boolean DEFAULT true
);


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE role_has_permissions (
    role_id text NOT NULL,
    permission_id text NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE roles (
    role_id text NOT NULL,
    role_description_content_id text,
    is_system_role boolean DEFAULT false NOT NULL,
    stakeholder_type_id text NOT NULL,
    role_creation_date timestamp without time zone DEFAULT now(),
    CONSTRAINT roles_rules_id_not_empty_string CHECK ((role_id <> ''::text))
);


SET default_with_oids = true;

--
-- Name: schema_info; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE schema_info (
    tag text NOT NULL,
    value text
);


SET default_with_oids = false;

--
-- Name: server; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE server (
    server_id text NOT NULL,
    creation_date date DEFAULT now() NOT NULL,
    default_virtual_host text NOT NULL
);


--
-- Name: server_stakeholders; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE server_stakeholders (
)
INHERITS (stakeholders);


--
-- Name: stakeholder_types; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE stakeholder_types (
    stakeholder_type_id text NOT NULL,
    CONSTRAINT stakeholder_types_id_not_empty_string CHECK ((stakeholder_type_id <> ''::text))
);


--
-- Name: token_lots; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE token_lots (
    token_lot_id text NOT NULL,
    token_lot_comment text,
    token_lot_creation_date timestamp without time zone DEFAULT now() NOT NULL
);


SET default_with_oids = true;

--
-- Name: token_status; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE token_status (
    token_status character varying(10) NOT NULL
);


SET default_with_oids = false;

--
-- Name: token_templates; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE token_templates (
    token_template_id text NOT NULL,
    token_template_network text NOT NULL,
    token_template_creation_date timestamp without time zone DEFAULT now() NOT NULL,
    token_max_incoming_data integer,
    token_max_outgoing_data integer,
    token_max_total_data integer,
    token_max_connection_duration interval,
    token_max_usage_duration interval,
    token_max_wall_clock_duration interval,
    token_max_age interval,
    token_is_reusable boolean DEFAULT true
);


--
-- Name: tokens; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tokens (
    token_id text NOT NULL,
    token_template_id text,
    token_status text,
    token_lot_id text,
    token_creation_date timestamp without time zone DEFAULT now() NOT NULL,
    token_issuer text NOT NULL,
    token_owner text
);


--
-- Name: tokens_template_valid_nodes; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tokens_template_valid_nodes (
    token_template_id text NOT NULL,
    token_valid_at_node text NOT NULL
);


SET default_with_oids = true;

--
-- Name: user_has_content; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE user_has_content (
    user_id text NOT NULL,
    content_id text NOT NULL,
    subscribe_timestamp timestamp without time zone DEFAULT now() NOT NULL
);


SET default_with_oids = false;

--
-- Name: user_has_profiles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE user_has_profiles (
    user_id text NOT NULL,
    profile_id text NOT NULL
);


SET default_with_oids = true;

--
-- Name: users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
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
    open_id_url text,
    CONSTRAINT check_user_not_empty CHECK (((user_id)::text <> ''::text))
);


SET default_with_oids = false;

--
-- Name: venue_types; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE venue_types (
    venue_type text NOT NULL
);


--
-- Name: venues; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE venues (
    name text NOT NULL,
    description text
);


--
-- Name: virtual_hosts; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE virtual_hosts (
    virtual_host_id text NOT NULL,
    hostname text NOT NULL,
    creation_date date DEFAULT now() NOT NULL,
    ssl_available boolean DEFAULT false NOT NULL,
    gmaps_api_key text,
    default_network text NOT NULL,
    CONSTRAINT virtual_hosts_hostname_check CHECK ((hostname <> ''::text))
);


--
-- Name: conn_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE connections ALTER COLUMN conn_id SET DEFAULT nextval('connections_conn_id_seq'::regclass);


--
-- Name: connections_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT connections_pkey PRIMARY KEY (conn_id);


--
-- Name: content_available_display_areas_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_available_display_areas
    ADD CONSTRAINT content_available_display_areas_pkey PRIMARY KEY (display_area);


--
-- Name: content_clickthrough_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_pkey PRIMARY KEY (content_id, user_id, node_id, destination_url);


--
-- Name: content_display_location_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_available_display_pages
    ADD CONSTRAINT content_display_location_pkey PRIMARY KEY (display_page);


--
-- Name: content_display_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT content_display_log_pkey PRIMARY KEY (content_id, user_id, node_id);


--
-- Name: content_group_element_has_allowed_nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT content_group_element_has_allowed_nodes_pkey PRIMARY KEY (content_group_element_id, node_id);


--
-- Name: content_group_element_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT content_group_element_pkey PRIMARY KEY (content_group_element_id);


--
-- Name: content_group_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT content_group_pkey PRIMARY KEY (content_group_id);


--
-- Name: content_has_owners_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT content_has_owners_pkey PRIMARY KEY (content_id, user_id);


--
-- Name: content_key_value_pairs_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_key_value_pairs
    ADD CONSTRAINT content_key_value_pairs_pkey PRIMARY KEY (content_id, "key");


--
-- Name: content_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content
    ADD CONSTRAINT content_pkey PRIMARY KEY (content_id);


--
-- Name: content_rss_aggregator_feeds_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_rss_aggregator_feeds
    ADD CONSTRAINT content_rss_aggregator_feeds_pkey PRIMARY KEY (content_id, url);


--
-- Name: content_rss_aggregator_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_rss_aggregator
    ADD CONSTRAINT content_rss_aggregator_pkey PRIMARY KEY (content_id);


--
-- Name: content_shoutbox_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_pkey PRIMARY KEY (message_content_id);


--
-- Name: content_type_filters_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_type_filters
    ADD CONSTRAINT content_type_filters_pkey PRIMARY KEY (content_type_filter_id);


--
-- Name: files_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_file
    ADD CONSTRAINT files_pkey PRIMARY KEY (files_id);


--
-- Name: flickr_photostream_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_flickr_photostream
    ADD CONSTRAINT flickr_photostream_pkey PRIMARY KEY (flickr_photostream_id);


--
-- Name: iframes_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_iframe
    ADD CONSTRAINT iframes_pkey PRIMARY KEY (iframes_id);


--
-- Name: langstring_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT langstring_entries_pkey PRIMARY KEY (langstring_entries_id);


--
-- Name: locales_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY locales
    ADD CONSTRAINT locales_pkey PRIMARY KEY (locales_id);


--
-- Name: network_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT network_has_content_pkey PRIMARY KEY (network_id, content_id);


--
-- Name: network_has_profile_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY network_has_profile_templates
    ADD CONSTRAINT network_has_profile_templates_pkey PRIMARY KEY (network_id, profile_template_id);


--
-- Name: network_stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT network_stakeholders_pkey PRIMARY KEY (user_id, role_id, object_id);


--
-- Name: networks_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY networks
    ADD CONSTRAINT networks_pkey PRIMARY KEY (network_id);


--
-- Name: node_deployment_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY node_deployment_status
    ADD CONSTRAINT node_deployment_status_pkey PRIMARY KEY (node_deployment_status);


--
-- Name: node_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT node_has_content_pkey PRIMARY KEY (node_id, content_id);


--
-- Name: node_stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT node_stakeholders_pkey PRIMARY KEY (user_id, role_id, object_id);


--
-- Name: nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT nodes_pkey PRIMARY KEY (node_id);


--
-- Name: permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);


--
-- Name: pictures_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY content_file_image
    ADD CONSTRAINT pictures_pkey PRIMARY KEY (pictures_id);


--
-- Name: profile_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY profile_fields
    ADD CONSTRAINT profile_fields_pkey PRIMARY KEY (profile_field_id);


--
-- Name: profile_template_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY profile_template_fields
    ADD CONSTRAINT profile_template_fields_pkey PRIMARY KEY (profile_template_field_id);


--
-- Name: profile_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY profile_templates
    ADD CONSTRAINT profile_templates_pkey PRIMARY KEY (profile_template_id);


--
-- Name: profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY profiles
    ADD CONSTRAINT profiles_pkey PRIMARY KEY (profile_id);


--
-- Name: role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);


--
-- Name: schema_info_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY schema_info
    ADD CONSTRAINT schema_info_pkey PRIMARY KEY (tag);


--
-- Name: server_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY server
    ADD CONSTRAINT server_pkey PRIMARY KEY (server_id);


--
-- Name: server_stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY server_stakeholders
    ADD CONSTRAINT server_stakeholders_pkey PRIMARY KEY (user_id, role_id, object_id);


--
-- Name: stakeholder_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY stakeholder_types
    ADD CONSTRAINT stakeholder_types_pkey PRIMARY KEY (stakeholder_type_id);


--
-- Name: stakeholders_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY stakeholders
    ADD CONSTRAINT stakeholders_pkey PRIMARY KEY (user_id, role_id, object_id);


--
-- Name: token_lots_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY token_lots
    ADD CONSTRAINT token_lots_pkey PRIMARY KEY (token_lot_id);


--
-- Name: token_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY token_status
    ADD CONSTRAINT token_status_pkey PRIMARY KEY (token_status);


--
-- Name: token_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY token_templates
    ADD CONSTRAINT token_templates_pkey PRIMARY KEY (token_template_id);


--
-- Name: tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_pkey PRIMARY KEY (token_id);


--
-- Name: tokens_template_valid_nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tokens_template_valid_nodes
    ADD CONSTRAINT tokens_template_valid_nodes_pkey PRIMARY KEY (token_template_id, token_valid_at_node);


--
-- Name: user_has_content_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT user_has_content_pkey PRIMARY KEY (user_id, content_id);


--
-- Name: user_has_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY user_has_profiles
    ADD CONSTRAINT user_has_profiles_pkey PRIMARY KEY (user_id, profile_id);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: venue_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY venue_types
    ADD CONSTRAINT venue_types_pkey PRIMARY KEY (venue_type);


--
-- Name: virtual_hosts_hostname_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY virtual_hosts
    ADD CONSTRAINT virtual_hosts_hostname_key UNIQUE (hostname);


--
-- Name: virtual_hosts_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY virtual_hosts
    ADD CONSTRAINT virtual_hosts_pkey PRIMARY KEY (virtual_host_id);


--
-- Name: idx_connections_node_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_connections_node_id ON connections USING btree (node_id);


--
-- Name: idx_connections_timestamp_in; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_connections_timestamp_in ON connections USING btree (timestamp_in);


--
-- Name: idx_connections_user_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_connections_user_id ON connections USING btree (user_id);


--
-- Name: idx_connections_user_mac; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_connections_user_mac ON connections USING btree (user_mac);


--
-- Name: idx_content_display_log; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_content_display_log ON content_display_log USING btree (last_display_timestamp);


--
-- Name: idx_content_group_element_content_group_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_content_group_element_content_group_id ON content_group_element USING btree (content_group_id);


--
-- Name: idx_content_group_element_valid_from_timestamp; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_content_group_element_valid_from_timestamp ON content_group_element USING btree (valid_from_timestamp);


--
-- Name: idx_content_group_element_valid_until_timestamp; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_content_group_element_valid_until_timestamp ON content_group_element USING btree (valid_until_timestamp);


--
-- Name: idx_gw_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX idx_gw_id ON nodes USING btree (gw_id);


--
-- Name: idx_nodes_node_deployment_status; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_nodes_node_deployment_status ON nodes USING btree (node_deployment_status);


--
-- Name: idx_token; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_token ON connections USING btree (token_id);


--
-- Name: idx_token_status; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_token_status ON tokens USING btree (token_status);


--
-- Name: idx_unique_username_and_account_origin; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX idx_unique_username_and_account_origin ON users USING btree (username, account_origin);


--
-- Name: idx_users_topen_id_url; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_users_topen_id_url ON users USING btree (open_id_url);


--
-- Name: profile_template_fields_semantic_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX profile_template_fields_semantic_id ON profile_template_fields USING btree (semantic_id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "$1" FOREIGN KEY (prefered_locale) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$1" FOREIGN KEY (title) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT "$1" FOREIGN KEY (langstrings_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$1" FOREIGN KEY (content_group_element_id) REFERENCES content_group_element(content_group_element_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$1" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_file_image
    ADD CONSTRAINT "$1" FOREIGN KEY (pictures_id) REFERENCES content_file(files_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_iframe
    ADD CONSTRAINT "$1" FOREIGN KEY (iframes_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_rss_aggregator
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_rss_aggregator_feeds
    ADD CONSTRAINT "$1" FOREIGN KEY (content_id) REFERENCES content_rss_aggregator(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$2" FOREIGN KEY (description) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_has_owners
    ADD CONSTRAINT "$2" FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_langstring_entries
    ADD CONSTRAINT "$2" FOREIGN KEY (locales_id) REFERENCES locales(locales_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$2" FOREIGN KEY (content_group_id) REFERENCES content_group(content_group_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group_element_has_allowed_nodes
    ADD CONSTRAINT "$2" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY user_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$2" FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT "$2" FOREIGN KEY (display_area) REFERENCES content_available_display_areas(display_area) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$3" FOREIGN KEY (project_info) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_group_element
    ADD CONSTRAINT "$3" FOREIGN KEY (displayed_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_display_log
    ADD CONSTRAINT "$3" FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT "$3" FOREIGN KEY (display_area) REFERENCES content_available_display_areas(display_area) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: $5; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content
    ADD CONSTRAINT "$5" FOREIGN KEY (long_description) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: account_origin_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY users
    ADD CONSTRAINT account_origin_fkey FOREIGN KEY (account_origin) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_content_id_fkey FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_node_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_node_id_fkey FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_clickthrough_log_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_clickthrough_log
    ADD CONSTRAINT content_clickthrough_log_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_key_value_pairs_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_key_value_pairs
    ADD CONSTRAINT content_key_value_pairs_content_id_fkey FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_author_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_author_user_id_fkey FOREIGN KEY (author_user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_message_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_message_content_id_fkey FOREIGN KEY (message_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_origin_node_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_origin_node_id_fkey FOREIGN KEY (origin_node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: content_shoutbox_messages_shoutbox_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_shoutbox_messages
    ADD CONSTRAINT content_shoutbox_messages_shoutbox_id_fkey FOREIGN KEY (shoutbox_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: display_location_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_page) REFERENCES content_available_display_pages(display_page) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: display_location_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_has_content
    ADD CONSTRAINT display_location_fkey FOREIGN KEY (display_page) REFERENCES content_available_display_pages(display_page) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: fk_network; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT fk_network FOREIGN KEY (object_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_network; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY server_stakeholders
    ADD CONSTRAINT fk_network FOREIGN KEY (object_id) REFERENCES server(server_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_node_deployment_status; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_node_deployment_status FOREIGN KEY (node_deployment_status) REFERENCES node_deployment_status(node_deployment_status) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_nodes FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_nodes; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT fk_nodes FOREIGN KEY (object_id) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_roles; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_stakeholders
    ADD CONSTRAINT fk_roles FOREIGN KEY (role_id) REFERENCES roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_roles; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY node_stakeholders
    ADD CONSTRAINT fk_roles FOREIGN KEY (role_id) REFERENCES roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_roles; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY server_stakeholders
    ADD CONSTRAINT fk_roles FOREIGN KEY (role_id) REFERENCES roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_tokens; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_tokens FOREIGN KEY (token_id) REFERENCES tokens(token_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: fk_users; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY connections
    ADD CONSTRAINT fk_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fk_venue_types; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT fk_venue_types FOREIGN KEY (venue_type) REFERENCES venue_types(venue_type) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: flickr_photostream_content_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY content_flickr_photostream
    ADD CONSTRAINT flickr_photostream_content_fkey FOREIGN KEY (flickr_photostream_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_has_profile_templates_network_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_profile_templates
    ADD CONSTRAINT network_has_profile_templates_network_id_fkey FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_has_profile_templates_profile_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_profile_templates
    ADD CONSTRAINT network_has_profile_templates_profile_template_id_fkey FOREIGN KEY (profile_template_id) REFERENCES profile_templates(profile_template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY network_has_content
    ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: network_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY nodes
    ADD CONSTRAINT network_id_fkey FOREIGN KEY (network_id) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: permissions_stakeholder_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY permissions
    ADD CONSTRAINT permissions_stakeholder_type_id_fkey FOREIGN KEY (stakeholder_type_id) REFERENCES stakeholder_types(stakeholder_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_fields_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_fields
    ADD CONSTRAINT profile_fields_content_id_fkey FOREIGN KEY (content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_fields_profile_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_fields
    ADD CONSTRAINT profile_fields_profile_id_fkey FOREIGN KEY (profile_id) REFERENCES profiles(profile_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_fields_profile_template_field_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_fields
    ADD CONSTRAINT profile_fields_profile_template_field_id_fkey FOREIGN KEY (profile_template_field_id) REFERENCES profile_template_fields(profile_template_field_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_template_fields_admin_label_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_template_fields
    ADD CONSTRAINT profile_template_fields_admin_label_content_id_fkey FOREIGN KEY (admin_label_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_template_fields_content_type_filter_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_template_fields
    ADD CONSTRAINT profile_template_fields_content_type_filter_id_fkey FOREIGN KEY (content_type_filter_id) REFERENCES content_type_filters(content_type_filter_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_template_fields_display_label_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_template_fields
    ADD CONSTRAINT profile_template_fields_display_label_content_id_fkey FOREIGN KEY (display_label_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profile_template_fields_profile_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profile_template_fields
    ADD CONSTRAINT profile_template_fields_profile_template_id_fkey FOREIGN KEY (profile_template_id) REFERENCES profile_templates(profile_template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: profiles_profile_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY profiles
    ADD CONSTRAINT profiles_profile_template_id_fkey FOREIGN KEY (profile_template_id) REFERENCES profile_templates(profile_template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: role_has_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: role_has_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: roles_role_description_content_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_role_description_content_id_fkey FOREIGN KEY (role_description_content_id) REFERENCES content(content_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: roles_stakeholder_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_stakeholder_type_id_fkey FOREIGN KEY (stakeholder_type_id) REFERENCES stakeholder_types(stakeholder_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: server_default_virtual_host_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY server
    ADD CONSTRAINT server_default_virtual_host_fkey FOREIGN KEY (default_virtual_host) REFERENCES virtual_hosts(virtual_host_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: stakeholders_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY stakeholders
    ADD CONSTRAINT stakeholders_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: stakeholders_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY stakeholders
    ADD CONSTRAINT stakeholders_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: token_templates_token_template_network_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY token_templates
    ADD CONSTRAINT token_templates_token_template_network_fkey FOREIGN KEY (token_template_network) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_template_valid_nodes_token_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens_template_valid_nodes
    ADD CONSTRAINT tokens_template_valid_nodes_token_template_id_fkey FOREIGN KEY (token_template_id) REFERENCES token_templates(token_template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_template_valid_nodes_token_valid_at_node_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens_template_valid_nodes
    ADD CONSTRAINT tokens_template_valid_nodes_token_valid_at_node_fkey FOREIGN KEY (token_valid_at_node) REFERENCES nodes(node_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_token_issuer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_token_issuer_fkey FOREIGN KEY (token_issuer) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_token_lot_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_token_lot_id_fkey FOREIGN KEY (token_lot_id) REFERENCES token_lots(token_lot_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_token_owner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_token_owner_fkey FOREIGN KEY (token_owner) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tokens_token_status_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_token_status_fkey FOREIGN KEY (token_status) REFERENCES token_status(token_status) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: tokens_token_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_token_template_id_fkey FOREIGN KEY (token_template_id) REFERENCES token_templates(token_template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_has_profiles_profile_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY user_has_profiles
    ADD CONSTRAINT user_has_profiles_profile_id_fkey FOREIGN KEY (profile_id) REFERENCES profiles(profile_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_has_profiles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY user_has_profiles
    ADD CONSTRAINT user_has_profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: virtual_hosts_default_network_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY virtual_hosts
    ADD CONSTRAINT virtual_hosts_default_network_fkey FOREIGN KEY (default_network) REFERENCES networks(network_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

