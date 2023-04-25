<?php

namespace rpi\Wall;

use core_reportbuilder\local\filters\date;
use  rpi\Wall;
use WP_Post;


class RPIWallInstaller
{

    public function __construct()
    {
        add_action('init', array($this, 'add_custom_capabilities'));
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_custom_fields'));
        add_action('wp_head', array($this, 'redirect_wall_cat_to_facet'));
        add_action('init', array($this, 'register_options_pages'));
        add_action('wp_login', array($this, 'sync_user_member_relation'), 10, 2);
        add_filter('author_link', array($this, 'change_author_link_to_user_profile'), 10, 3);
        add_action('save_post_wall', array($this, 'update_taxonomy_of_member_on_pin_save'), 10, 3);
        add_action('before_delete_post', array($this, 'delete_member_taxonomy_on_pin_deletion'), 10, 2);
        add_filter('manage_posts_columns', array($this, 'add_new_termin_columns'), 10, 2);
        add_action('manage_termin_posts_custom_column', array($this, 'display_termin_date_column'), 10, 2);
        add_filter('manage_posts_columns', array($this, 'add_new_message_columns'), 10, 2);
        add_action('manage_message_posts_custom_column', array($this, 'display_message_recipients_column'), 10, 2);
        add_filter('notify_post_author', array($this, 'prefix_filter_sent_comment_notification'), 10, 2);

        add_action('pre_get_posts', array($this, 'alter_wall_query'));
    }

    /**
     * @param bool $maybe_notify
     * @param int $comment_ID
     * @return false
     */
    public function prefix_filter_sent_comment_notification(bool $maybe_notify, int $comment_ID): bool
    {
        return false;
    }

    public function add_custom_capabilities()
    {
        $roles = ['administrator', 'editor'];
        foreach ($roles as $roleslug) {
            $role = get_role($roleslug);
            $role->add_cap('write_redaktion_message');

            $role->add_cap('edit_walls');
            $role->add_cap('edit_wall');
            $role->add_cap('edit_others_walls');
            $role->add_cap('read_private_walls');
            $role->add_cap('publish_walls');
            $role->add_cap('read_walls');
            $role->add_cap('delete_others_walls');
            $role->add_cap('edit_published_walls');
            $role->add_cap('delete_published_walls');
            $role->add_cap('delete_walls');

            $role->add_cap('manage_wall_tags');
            $role->add_cap('edit_wall_tags');
            $role->add_cap('delete_wall_tags');
            $role->add_cap('assign_wall_tags');

            $role->add_cap('manage_wall_cat');
            $role->add_cap('edit_wall_cat');
            $role->add_cap('delete_wall_cat');
            $role->add_cap('assign_wall_cat');

            $role->add_cap('manage_badge');
            $role->add_cap('edit_badge');
            $role->add_cap('delete_badge');
            $role->add_cap('assign_badge');

            $role->add_cap('manage_section');
            $role->add_cap('edit_section');
            $role->add_cap('delete_section');
            $role->add_cap('assign_section');

            /*
            if($roleslug === 'editor'){
                $role->remove_cap('edit_member');
                $role->remove_cap('edit_members');
                $role->remove_cap('edit_others_members');
                $role->remove_cap('read_private_members');
                $role->remove_cap('publish_members');
                $role->remove_cap('read_members');
                $role->remove_cap('delete_others_members');
                $role->remove_cap('edit_published_members');
                $role->remove_cap('delete_published_members');
                $role->remove_cap('delete_members');
            }
            */
            if ($roleslug === 'administrator') {

                $role->add_cap('edit_member');
                $role->add_cap('edit_members');
                $role->add_cap('edit_others_members');
                $role->add_cap('read_private_members');
                $role->add_cap('publish_members');
                $role->add_cap('read_members');
                $role->add_cap('delete_others_members');
                $role->add_cap('edit_published_members');
                $role->add_cap('delete_published_members');
                $role->add_cap('delete_members');
            }

            $role->add_cap('edit_events');
            $role->add_cap('edit_others_events');
            $role->add_cap('read_private_events');
            $role->add_cap('publish_posts');
            $role->add_cap('read_event');
            $role->add_cap('delete_others_events');
            $role->add_cap('edit_published_event');
            $role->add_cap('delete_published_events');
            $role->add_cap('delete_event');

            $role->add_cap('manage_termin_event');
            $role->add_cap('edit_termin_event');
            $role->add_cap('delete_termin_event');
            $role->add_cap('assign_termin_event');
        }
        /// Author capabilities

        $roles = ['author', 'subscriber'];
        foreach ($roles as $roleslug) {
            $role = get_role($roleslug);

            $role->add_cap('edit_wall');
            $role->add_cap('publish_walls');
            $role->add_cap('read_walls');
            $role->add_cap('delete_walls');

            $role->add_cap('edit_wall_tags');
            $role->add_cap('assign_wall_tags');
            $role->add_cap('manage_wall_tags');

            $role->add_cap('assign_wall_cat');

            $role->add_cap('assign_badge');

            $role->add_cap('assign_section');

        }
    }

    public function register_post_types()
    {
        /**
         * Post Type: Pin.
         */

        $labels = [
            "name" => __("Pins", "blocksy"),
            "singular_name" => __("Pin", "blocksy"),
        ];

        $args = [
            "label" => __("Pin", "blocksy"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => true,
            "exclude_from_search" => false,
            'capability_type' => 'wall',
            'capabilities' => array(
                'edit_post' => 'edit_wall',
                'edit_posts' => 'edit_walls',
                'edit_others_posts' => 'edit_others_walls',
                'read_private_posts' => 'read_private_walls',
                'publish_posts' => 'publish_walls',
                'read_post' => 'read_walls',
                'delete_others_posts' => 'delete_others_walls',
                'edit_published_posts' => 'edit_published_walls',
                'delete_published_posts' => 'delete_published_walls',
                'delete_posts' => 'delete_walls',
            ),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "wall", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-pressthis",
            "supports" => [
                'title',
                'author',
                "editor",
                "comments",
            ],
            'taxonomies' => ['wall-tag', "wall-cat"],
            "show_in_graphql" => false,
        ];

        register_post_type("wall", $args);


        /**
         * Post Type: Mitglied.
         */

        $labels = [
            "name" => __("Mitglieder", "blocksy"),
            "singular_name" => __("Mitglied", "blocksy"),
        ];

        $args = [
            "label" => __("Mitglieder", "blocksy"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => true,
            "exclude_from_search" => false,
            'capability_type' => ['members', 'member'],
            'capabilities' => array(
                'edit_post' => 'edit_member',
                'edit_posts' => 'edit_members',
                'edit_others_posts' => 'edit_others_members',
                'edit_published_posts' => 'edit_published_members',
                'read_private_posts' => 'read_private_members',
                'publish_posts' => 'publish_members',
                'read_post' => 'read_member',
                'delete_others_posts' => 'delete_others_members',
                'delete_published_posts' => 'delete_published_members',
                'delete_posts' => 'delete_members',
            ),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "member", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-admin-users",
            "supports" => [
                'title',
                "editor",
            ],
            'taxonomies' => ['wall-tag', "badge", 'section', 'profession'],
            "show_in_graphql" => false,
        ];

        register_post_type("member", $args);

        /**
         * Post Type: Nachricht.
         */

        $labels = [
            "name" => __("Nachrichten", "blocksy"),
            "singular_name" => __("Nachricht", "blocksy"),
        ];

        $args = [
            "label" => __("Nachrichten", "blocksy"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "message", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor"],
            "menu_icon" => "dashicons-email",
            "taxonomies" => ["channel"],
            "show_in_graphql" => false,
        ];

        register_post_type("message", $args);

        /**
         * Post Type: Termine.
         */

        $labels = [
            "name" => __("Termine", "blocksy"),
            "singular_name" => __("Termine", "blocksy"),
        ];

        $args = [
            "label" => __("Termine", "blocksy"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            'capability_type' => 'event',
            'capabilities' => array(
                'edit_posts' => 'edit_events',
                'edit_others_posts' => 'edit_others_events',
                'read_private_posts' => 'read_private_events',
                'publish_posts' => 'publish_posts',
                'read_post' => 'read_event',
                'delete_others_posts' => 'delete_others_events',
                'edit_published_posts' => 'edit_published_event',
                'delete_published_posts' => 'delete_published_events',
                'delete_posts' => 'delete_event',
            ),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "termin", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor"],
            "menu_icon" => "dashicons-calendar-alt",
            "taxonomies" => ["termin_event"],
            "show_in_graphql" => false,
        ];

        register_post_type("termin", $args);

    }

    public function add_new_termin_columns($columns, $post_type)
    {
        if ($post_type == 'termin') {
            $columns['termine_date'] = 'Termindatum';
            $columns['category'] = 'Ereignis';
        }
        return $columns;
    }

    public function display_termin_date_column($name, $post_id)
    {
        switch ($name) {
            case 'category':
                $termin_event = get_the_terms($post_id, 'termin_event');
                if (!empty($termin_event) && is_array($termin_event)) {
                    echo reset($termin_event)->name;
                }
                break;
            case 'termine_date':
                echo date('d.m.Y H:i', strtotime(get_post_meta($post_id, 'termin_date', true))) .
                    ' - ' .
                    date('H:i', strtotime(get_post_meta($post_id, 'termin_enddate', true)));
        }
    }

    /**
     * Empfängerspalte in admin columns hinzufügen
     * filter hook manage_posts_columns
     *
     * @param $columns
     * @param $post_type
     *
     * @return mixed
     */
    public function add_new_message_columns($columns, $post_type)
    {
        if ($post_type == 'message') {
            $columns['content'] = 'Mitteilung';
            $columns['recipients'] = 'Empfänger';
        }

        return $columns;
    }

    /**
     * Empfängerspalte mit Empfängern aus dem mety key 'rpi_wall_message_recipient' befüllen
     *
     * @param $name
     * @param $post_id
     *
     * @return void
     */
    function display_message_recipients_column($name, $post_id)
    {

        switch ($name) {
            case 'content':
                echo get_the_content(null, true, $post_id);
                break;
            case 'recipients':
                $recipients = get_post_meta($post_id, 'rpi_wall_message_recipient');
                if ($recipients && count($recipients) > 0) {
                    $users = [];
                    foreach ($recipients as $user_id) {
                        $user = get_userdata($user_id);
                        if ($user instanceof \WP_User) {
                            $users[] = $user->display_name;
                        }

                    }
                    echo implode(', ', $users);

                }

        }
    }


    function register_taxonomies()
    {

        /**
         * Taxonomy: Tags.
         */

        $labels = [
            "name" => __("Tags", "blocksy"),
            "singular_name" => __("Tag", "blocksy"),
        ];


        $args = [
            "label" => __("Tags", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => false,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'wall-tag', 'with_front' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "wall-tag",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_wall_tags',
                'edit_terms' => 'edit_wall_tags',
                'delete_terms' => 'delete_wall_tags',
                'assign_terms' => 'assign_wall_tags'
            ),
        ];
        register_taxonomy("wall-tag", ["wall", "member"], $args);

        /**
         * Taxonomy: Pin Kategorien.
         */

        $labels = [
            "name" => __("Pin Kategorien", "blocksy"),
            "singular_name" => __("Pin Kategorie", "blocksy"),
        ];


        $args = [
            "label" => __("Pin Kategorien", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'wall-cat', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "wall-cat",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_wall_cat',
                'edit_terms' => 'edit_wall_cat',
                'delete_terms' => 'delete_wall_cat',
                'assign_terms' => 'assign_wall_cat'
            ),
        ];
        register_taxonomy("wall-cat", ["wall"], $args);


        /**
         * Taxonomy: Badges.
         */

        $labels = [
            "name" => __("Auszeichnungen", "blocksy"),
            "singular_name" => __("Auszeichnung", "blocksy"),
        ];


        $args = [
            "label" => __("Auszeichnung", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => false,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'badge', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "badge",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_badge',
                'edit_terms' => 'edit_badge',
                'delete_terms' => 'delete_badge',
                'assign_terms' => 'assign_badge'
            ),

        ];
        register_taxonomy("badge", ["member"], $args);

        /**
         * Taxonomy: Section.
         */

        $labels = [
            "name" => __("Bereiche", "blocksy"),
            "singular_name" => __("Bereich", "blocksy"),
        ];


        $args = [
            "label" => __("Bereich", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'section', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "section",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_section',
                'edit_terms' => 'edit_section',
                'delete_terms' => 'delete_section',
                'assign_terms' => 'assign_section'
            ),
        ];
        register_taxonomy("section", ["member"], $args);

        /**
         * Taxonomy: profession.
         */

        $labels = [
            "name" => __("Perspektiven", "blocksy"),
            "singular_name" => __("Perspektiven", "blocksy"),
        ];


        $args = [
            "label" => __("Profession", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'profession', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "profession",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
            /*
            "capabilities" => array(
                'manage_terms' => 'manage_professions',
                'edit_terms' => 'edit_professions',
                'delete_terms' => 'delete_professions',
                'assign_terms' => 'assign_professions'
            ),
            */
        ];
        register_taxonomy("profession", ["member"], $args);
        /**
         * Taxonomy: Event.
         */

        $labels = [
            "name" => __("Ereignisse", "blocksy"),
            "singular_name" => __("Ereignis", "blocksy"),
        ];


        $args = [
            "label" => __("Ereignis", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'termin_event', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "termin_event",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_termin_event',
                'edit_terms' => 'edit_termin_event',
                'delete_terms' => 'delete_termin_event',
                'assign_terms' => 'assign_termin_event'
            ),
        ];
        register_taxonomy("termin_event", ["termin"], $args);

    }

    function register_custom_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_rpi_wall_settings',
                'title' => 'RPI Wall Einstellungen',
                'fields' =>
                    array(
                        array(
                            'key' => 'field_rpi_wall_general_settings',
                            'label' => 'Allgemein',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),
                        array(
                            'key' => 'field_rpi_group_min_required_members',
                            'label' => 'Niedrigste Menge an Mitgliedern',
                            'name' => 'rpi_group_min_required_members',
                            'type' => 'number',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'min' => '',
                            'max' => '',
                            'step' => '',
                        ),
                        array(
                            'key' => 'field_rpi_wall_pl_group_pending_days',
                            'label' => 'Zeitlimit für die Erstellung einer Gruppe',
                            'name' => 'rpi_wall_pl_group_pending_days',
                            'type' => 'number',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'min' => '',
                            'max' => '',
                            'step' => '',
                        ),

                        array(
                            'key' => 'field_rpi_wall_message_template_settings',
                            'label' => 'Nachrichten',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),

                        array(
                            'key' => 'field_rpi_network_name',
                            'label' => 'Netzwerk Name',
                            'name' => 'network_name',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),

                        array(
                            'key' => 'field_rpi_moderation_email',
                            'label' => 'Email der Moderation',
                            'name' => 'moderation_email',
                            'type' => 'email',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                        ),
                        array(
                            'key' => 'field_rpi_allowed_moderators',
                            'label' => 'Organisationsmitglieder (Moderatoren)',
                            'name' => 'allowed_moderators',
                            'type' => 'user',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'role' => '',
                            'allow_null' => 0,
                            'multiple' => 1,
                            'return_format' => 'array',
                            'acfe_bidirectional' => array(
                                'acfe_bidirectional_enabled' => '0',
                            ),
                        ),
                        array(
                            'key' => 'field_rpi_wall_memberpage_posts_per_page',
                            'label' => 'Menge der angezeigten Nachrichten',
                            'name' => 'rpi_wall_memberpage_posts_per_page',
                            'type' => 'number',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '6',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'min' => '',
                            'max' => '',
                            'step' => '',
                        ),
                        array(
                            'key' => 'field_rpi_message_templates',
                            'label' => '',
                            'name' => 'rpi_message_templates',
                            'type' => 'group',
                            'instructions' => 'Mögliche Variablen
                                    %grouptitle%
                                    %posttitle%
                                    %postlink%
                                    %actorname%
                                    %actorlink%
                                    %memberamount%
                                    %channellink%
                                    %likeramount%',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'layout' => 'block',
                            'acfe_seamless_style' => 0,
                            'acfe_group_modal' => 0,
                            'sub_fields' => $this->prepare_field_type_template_arrays(),
                        ),
                        array(
                            'key' => 'field_rpi_matrix_settings',
                            'label' => 'Matrix',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),
                        array(
                            'key' => 'field_rpi_matrix_bot_token',
                            'label' => 'Bot Token',
                            'name' => 'matrix_bot_token',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_rpi_matrix_server_home',
                            'label' => 'Matrix Heimat Server',
                            'name' => 'matrix_server_home',
                            'type' => 'text',
                            'instructions' => 'Name des Matrix Servers',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => 'example.com',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_rpi_matrix_server_base',
                            'label' => 'Matrix Server Domain',
                            'name' => 'matrix_server_base',
                            'type' => 'text',
                            'instructions' => 'Name der Base Domain',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => 'Base-Server.de',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
	                    array(
		                    'key' => 'field_rpi_matrix_channel_prefix',
		                    'label' => 'Matrix Channel Prefix',
		                    'name' => 'matrix_channel_prefix',
		                    'type' => 'text',
		                    'instructions' => 'Zeichenfolge in Kleinbuchstaben, mit der alle Matrix-Channel-Slugs (z.B:  #<b><i>rpi</i></b>_plg_1234) beginnen sollen',
		                    'required' => 0,
		                    'conditional_logic' => 0,
		                    'wrapper' => array(
			                    'width' => '',
			                    'class' => '',
			                    'id' => '',
		                    ),
		                    'frontend_admin_display_mode' => 'edit',
		                    'readonly' => 0,
		                    'default_value' => '',
		                    'placeholder' => 'dibes',
		                    'prepend' => '',
		                    'append' => '',
		                    'maxlength' => '',
	                    ),
	                    array(
		                    'key' => 'field_rpi_matrix_group_slug',
		                    'label' => 'Gruppen Kürzel (plg, ag, gruppe...)',
		                    'name' => 'matrix_group_slug',
		                    'type' => 'text',
		                    'instructions' => 'kleinbuchstaben für den zweiten Teil des Channel-Slugs (z.B: #rpi_<b><i>plg</i></b>_1234)',
		                    'required' => 0,
		                    'conditional_logic' => 0,
		                    'wrapper' => array(
			                    'width' => '',
			                    'class' => '',
			                    'id' => '',
		                    ),
		                    'frontend_admin_display_mode' => 'edit',
		                    'readonly' => 0,
		                    'default_value' => 'plg',
		                    'placeholder' => 'plg',
		                    'prepend' => '',
		                    'append' => '',
		                    'maxlength' => '',
	                    ),
                        array(
                            'key' => 'field_rpi_matrix_bot_welcome_message',
                            'label' => 'Matrix Bot Willkommens Nachricht',
                            'name' => 'matrix_bot_welcome_message',
                            'prefix' => 'acf',
                            'type' => 'acfe_code_editor',
                            'instructions' => 'Willkommensnachricht des Bots beim erstellen des Raums',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'mode' => 'text/html',
                            'indent_unit' => 4,
                            'readonly' => 0,
                            'default_value' => '<strong>Willkommen Chatraum unserer Lerngemeinschaft</strong>
der auch über den <a href="%postlink%">Pinwandeintrag</a> erreichbar ist.
Schreibt den anderen durch ein kurzes "Hallo" in diesen Chat und wenn ihr möchtet, auch etwas über euch selbst und was dazu bewegt hat, dieser Gemeinschaft beizutreten.<br>
<br>
<strong>Toolbar einblenden</strong><br>
Du kannst die eingebundene Toolbar direkt aus diesem Chatfenster nutzen. Lies dazu die zweite Nachicht "Toolbar nutzen" 
<br>
<strong>Nächste Schritte</strong>
<ul>
	<li>Zuerst findet gemeinsam einen Termin für das erste konstitutierende Treffen. Vielleicht hilft euch <strong><a href="https://nuudel.digitalcourage.de/">Nuudel</a></strong> bei der <strong>Terminfindung</strong></li>
	<li>Die Person die den Terminfindungsprozess initiiert hat, trägt den vereinbarten Termin in der Toolbar ein und klickt dazu auf "Planungstermin setzen".</li>
	<li>Beim ersten Treffen verabredet ihr mit Hilfe eines Planungsbogens Ziele und Vorgehensweisen dieser Gruppe fest.</li>
	<li>Für allen weiteren Treffen steht euch je ein Arbeitssturkturbogen zur Verfügung, der effektiv hilft, das angestrebte Ziel zu erreichen.</li>
</ul>Viel Erfolg!',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                            'rows' => 10,
                            'max_rows' => '',
                        )
                    , array(
                        'key' => 'field_rpi_matrix_bot_toolbar_tutorial',
                        'label' => 'Matrix Bot Toolbar Tutorial',
                        'name' => 'matrix_bot_toolbar_tutorial',
                        'prefix' => 'acf',
                        'type' => 'acfe_code_editor',
                        'instructions' => 'Tutorial Nachricht um die Nutzung der Toolbar zu erklären',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'frontend_admin_display_mode' => 'edit',
                        'mode' => 'text/html',
                        'indent_unit' => 4,
                        'readonly' => 0,
                        'default_value' => '<strong>Toolbar nutzen</strong><br>
Du kannst die eingebundene Toolbar direkt aus diesem Chatfenster nutzen: 
Klicke in der oberen rechten Ecke auf das Infosymbol <strong>(i)</strong>  
und anchließend weiter unten auf "Toolbar", um diese dauerhaft anzuzeigen(Siehe Abbildung im Anhang) 
<a href="https://schule-evangelisch-digital.de/wp-content/uploads/2022/09/toolbar.png"></a>
Du kannst in der Toolbar Werkzeuge hinzufügen, in dem du auf das [+] und dann auf "Werkzeug hinzufügen" klickst. In dem sich öffnenden Formular 
gibst du jeweils links die Beschriftung des Buttons ein und rechts daneben die URL zu dem Werkzeug,
welches beim Klick auf den Button geöffnet werden soll. Zum Beispiel: https://cloud.rpi-virtuell.de/index.php/s/YGDbNxERATo8WjA',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                        'rows' => 10,
                        'max_rows' => '',
                    ),
                        array(
                            'key' => 'field_rpi_matrix_bot_planung_tutorial',
                            'label' => 'Matrix Bot Planungsbogen Tutorial',
                            'name' => 'matrix_bot_planung_tutorial',
                            'prefix' => 'acf',
                            'type' => 'acfe_code_editor',
                            'instructions' => 'Tutorial Nachricht für den Planungsbogen',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'mode' => 'text/html',
                            'indent_unit' => 4,
                            'readonly' => 0,
                            'default_value' => '<strong>Planungsbogen ausfüllen</strong><br/>Im ersten, konstituierenden 
Treffen legt eine PLG das übergeordnetes Gesamtziel fest, das die Gruppe mit Ihrer gemeinsamen Arbeit verfolgt
und an dem sich dann auch die weiteren Treffen ausrichten sollen. Gebt der Gruppe einen Namen, der zu der 
Zielformulierung passt. <br/>Anwesende, Gruppename und Zielformulierung trägt <strong>eine</strong> Person der Gruppe in den Planungsbogen ein: 
Klicke dazu auf den Button "Planungsbogen" in der Toolbar.',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                            'rows' => 10,
                            'max_rows' => '',
                        ),
                        array(
                            'key' => 'field_rpi_matrix_bot_protocol_tutorial',
                            'label' => 'Matrix Bot Arbeitsstrukturbogen Tutorial',
                            'name' => 'matrix_bot_protocol_tutorial',
                            'prefix' => 'acf',
                            'type' => 'acfe_code_editor',
                            'instructions' => 'Tutorial Nachricht für den Planungsbogen',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'mode' => 'text/html',
                            'indent_unit' => 4,
                            'readonly' => 0,
                            'default_value' => '<strong>Wie geht es weiter?</strong><br/>
Die Planung ist geschafft. <strong>Vereinbart den nächsten gemeinsamen Termin</strong>. Für alle folgenden Treffen 
bekommt ihr ein weiteres Unterstützungswerkzeug. Das findet ihr ebenfalls in der Toolbar über den Button "Arbeitsstrukturbogen".     
<br/>
<br/><strong>Arbeiten mit dem Arbeitsstrukturbogen</strong><br/>Jedes Treffen hat eine 
festgelegte Struktur, die dabei hilft, effizient zusammenzuarbeiten, das Ziel im Auge zu behalten und erfolgreich zu
Ergebnissen zu kommen. Diese umfasst vier Abschnitte: 
Teilziel, Reflexion, Absprachen und Ergebnisse.<br/>
Im Abschnitt (Teilziel) legt ihr unter "Ziel des Treffens" ein Teilziel/-schritt fest, das auf dem Weg zum Gesamtziel 
(Planungsbogen) liegt. Davon abhängig ergeben sich Tagesordnungspunkte. Bestimmt vorab eine Person, 
die die Leitung/Moderation des Treffens hat.<br/>
Am Ende Eures Treffens werden erst die Abschnitte "Reflexion", "Absprachen" ausgefüllt und "Ergebnisse" ausgefüllt.
Formuliert im letzten Abschnitt in wenigen Worten, wo ihr dem Gesamtziel näher gekommen seid.
<br/>
Bitte beachte, dass immer <strong>nur eine</strong> Person den Bogen ausfüllen kann. 
<br/> 
',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                            'rows' => 10,
                            'max_rows' => '',
                        ),
                        array(
                            'key' => 'field_rpi_matrix_bot_review',
                            'label' => 'Matrix Bot Abschlusssitzung',
                            'name' => 'matrix_bot_review',
                            'prefix' => 'acf',
                            'type' => 'acfe_code_editor',
                            'instructions' => 'Abschlussnachricht nach der Abschlusssitzung und schließen der Gruppe',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'mode' => 'text/html',
                            'indent_unit' => 4,
                            'readonly' => 0,
                            'default_value' => '<strong>Die Abschlusssitzungsbogen wurde abgespeichert </strong><br/>
Durch das Abspeichern des Abschlusssitzungsbogens wurde die PLG geschlossen.
<strong>Was bedeutet das?</strong><br/>
Durch das Schließen der PLG gilt das Unternehmen der PLG als beendet. Das bedeutet, dass es nicht mehr möglich ist neue
Arbeitssturkturbögen zu erstellen.
Die Kommentare und Matrixchat sowie die Toolbar sind weiterhin verwendbar.
<br/> 
',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                            'rows' => 10,
                            'max_rows' => '',
                        ),
                        array(
                            'key' => 'field_rpi_toolbar',
                            'label' => 'Toolbar',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),
                        array(
                            'key' => 'field_rpi_toolbar_kickoff_template',
                            'label' => 'Kickoff Template Link',
                            'name' => 'toolbar_kickoff_template',
                            'type' => 'text',
                            'instructions' => 'Link zum Kickoff Template',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => 'Base-Server.de',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_rpi_toolbar_protocol_template',
                            'label' => 'Protocol Template Link',
                            'name' => 'toolbar_protocol_template',
                            'type' => 'text',
                            'instructions' => 'Link zum Protokoll Template',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'readonly' => 0,
                            'default_value' => '',
                            'placeholder' => 'Base-Server.de',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_rpi_text_labels',
                            'label' => 'Text Label',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),
                        array(
                            'key' => 'field_rpi_label_general_textfields_group',
                            'label' => '',
                            'name' => 'rpi_label_general_textfields_group',
                            'type' => 'group',
                            'instructions' => 'Diese Felder werden für unterschiedliche Textbereiche in Bereichen der Pinnwand angezeigt',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'layout' => 'block',
                            'acfe_seamless_style' => 0,
                            'acfe_group_modal' => 0,
                            'sub_fields' =>
                                array(
                                    array(
                                        'key' => 'field_rpi_wall_main_header',
                                        'label' => 'Pinnwand Header Einleitung',
                                        'name' => 'rpi_wall_main_header',
                                        'type' => 'textarea',
                                        'instructions' => 'options_rpi_label_general_textfields_group_rpi_wall_main_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Willkommen auf der Pinnwand! Hier kannst du eigene Fragestellungen einbringen und findest spannende Impulse zu verschiedenen Themen. Außerdem kannst dich an Fragen durch Kommentare beteiligen oder Professionelle Lerngruppen (PLG´s) zum intensiveren Austausch zu einem Thema finden.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '',
                                    ),
                                    array(
                                        'key' => 'field_rpi_member_main_header',
                                        'label' => 'Netzwerk (Member) Header Einleitung',
                                        'name' => 'rpi_member_main_header',
                                        'type' => 'textarea',
                                        'instructions' => 'options_rpi_label_general_textfields_group_rpi_member_main_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Unser Netzwerk lebt von allen, die gute Fragen stellen, Erfahrungen teilen, Kompetenzen einbringen und Perspektiven eröffnen.  Stell dich mit ein paar Worten und einem Avatarbild vor. Mit jeder Aktivität im Netzwerk wächst auch dein Profil.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '',
                                    ),
                                ),
                        ),
                        array(
                            'key' => 'field_rpi_label_member_profile_textfields_group',
                            'label' => '',
                            'name' => 'rpi_label_member_profile_textfields_group',
                            'type' => 'group',
                            'instructions' => 'Diese Felder werden im Profil Bereich der User angezeigt',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'layout' => 'block',
                            'acfe_seamless_style' => 0,
                            'acfe_group_modal' => 0,
                            'sub_fields' =>
                                array(
                                    array(
                                        'key' => 'field_rpi_member_bio_default_text',
                                        'label' => 'Der Default Text der Bio eines Users',
                                        'name' => 'rpi_member_bio_default_text',
                                        'type' => 'textarea',
                                        'instructions' => '',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Hier wurde noch keine Bio festgelegt.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '',
                                    ),
                                ),
                        ),
                        array(
                            'key' => 'field_rpi_label_group',
                            'label' => '',
                            'name' => 'rpi_label_group',
                            'type' => 'group',
                            'instructions' => 'Diese Labels werden angezeigt in der Detail Ansicht eines Pins und ändern sich mit den unterschiedlichen Stadien des Pins',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'layout' => 'block',
                            'acfe_seamless_style' => 0,
                            'acfe_group_modal' => 0,
                            'sub_fields' =>
                                array(
                                    array(
                                        'key' => 'field_rpi_wall_ready_header',
                                        'label' => 'RPI Gruppen Erstellung möglich Header',
                                        'name' => 'rpi_wall_ready_header',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_ready_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Die Gründung einer Gruppe ist jetzt möglich.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_ready_notice',
                                        'label' => 'RPI Gruppen Erstellung möglich Notice',
                                        'name' => 'rpi_wall_ready_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_ready_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Mit Klick auf "Gruppe Gründen" werden alle interessierten angeschrieben und haben eine Woche Zeit, der PLG beizutreten.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_pending_header',
                                        'label' => 'Gründungsphase Header',
                                        'name' => 'rpi_wall_pending_header',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_pending_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Wir suchen noch Leute für eine Professionellen Lerngemeinschaft (PLG) zu diesem Kontext',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_pending_notice',
                                        'label' => 'Gründungsphase notice',
                                        'name' => 'rpi_wall_pending_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_pending_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_founder_notice',
                                        'label' => 'Gründungsphase Gründer notice',
                                        'name' => 'rpi_wall_founder_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_founder_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Als Gruppengründer:in kannst du die Beitrittsphase beenden und die Gruppe sofort einrichten.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),

                                    array(
                                        'key' => 'field_rpi_wall_founded_header',
                                        'label' => 'Gruppe gegründet Header',
                                        'name' => 'rpi_wall_founded_header',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_founded_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Professionelle Lerngemeinschaft (PLG) gegründet am:',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_founded_notice',
                                        'label' => 'Gruppe gegründet Notice',
                                        'name' => 'rpi_wall_founded_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_founded_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => '',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_closed_header',
                                        'label' => 'Gruppe geschlossen Header',
                                        'name' => 'rpi_wall_closed_header',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_closed_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Professionelle Lerngemeinschaft (PLG) - Arbeitsphase abgeschlossen',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_closed_notice',
                                        'label' => 'Gruppe geschlossen Notice',
                                        'name' => 'rpi_wall_closed_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_closed_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => '',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_not_founded_header',
                                        'label' => 'Keine Gruppe gegründet Header',
                                        'name' => 'rpi_wall_not_founded_header',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_not_founded_header',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Interessiert an einer Professionellen Lerngemeinschaft (PLG)?',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                    array(
                                        'key' => 'field_rpi_wall_not_founded_notice',
                                        'label' => 'Keine Gruppe gegründet Notice',
                                        'name' => 'rpi_wall_not_founded_notice',
                                        'type' => 'text',
                                        'instructions' => 'options_rpi_label_group_rpi_wall_not_founded_notice',
                                        'required' => 0,
                                        'conditional_logic' => 0,
                                        'wrapper' => array(
                                            'width' => '',
                                            'class' => '',
                                            'id' => '',
                                        ),
                                        'frontend_admin_display_mode' => 'edit',
                                        'readonly' => 0,
                                        'default_value' => 'Klicke auf (+) und du wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.',
                                        'placeholder' => '',
                                        'prepend' => '',
                                        'append' => '',
                                        'maxlength' => '150',
                                    ),
                                ),
                        ),
                        array(
                            'key' => 'field_rpi_termine',
                            'label' => 'Termine',
                            'name' => '',
                            'type' => 'tab',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'placement' => 'top',
                            'endpoint' => 0,
                        ),
                        array(
                            'key' => 'field_ereignis_seiten_relation',
                            'label' => 'Ereignis Seiten Relation',
                            'name' => 'ereignis_seiten_relation',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'acfe_repeater_stylised_button' => 0,
                            'layout' => 'row',
                            'pagination' => 0,
                            'min' => 0,
                            'max' => 0,
                            'collapsed' => '',
                            'button_label' => 'Eintrag hinzufügen',
                            'rows_per_page' => 20,
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_ereignis',
                                    'label' => 'Ereignis',
                                    'name' => 'ereignis',
                                    'aria-label' => '',
                                    'type' => 'acfe_taxonomy_terms',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'taxonomy' => array(
                                        0 => 'termin_event',
                                    ),
                                    'allow_terms' => '',
                                    'allow_level' => '',
                                    'field_type' => 'select',
                                    'default_value' => array(),
                                    'return_format' => 'id',
                                    'ui' => 0,
                                    'allow_null' => 0,
                                    'multiple' => 0,
                                    'save_terms' => 0,
                                    'load_terms' => 0,
                                    'choices' => array(),
                                    'ajax' => 0,
                                    'placeholder' => '',
                                    'search_placeholder' => '',
                                    'layout' => '',
                                    'toggle' => 0,
                                    'allow_custom' => 0,
                                    'other_choice' => 0,
                                    'parent_repeater' => 'field_ereignis_seiten_relation',
                                ),
                                array(
                                    'key' => 'field_zielseite',
                                    'label' => 'Zielseite',
                                    'name' => 'zielseite',
                                    'aria-label' => '',
                                    'type' => 'page_link',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'post_type' => array(
                                        0 => 'page',
                                    ),
                                    'taxonomy' => '',
                                    'allow_archives' => 1,
                                    'multiple' => 0,
                                    'allow_null' => 0,
                                    'parent_repeater' => 'field_ereignis_seiten_relation',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_online_meeting_link',
                            'label' => 'Online Meeting Link',
                            'name' => 'online_meeting_link',
                            'aria-label' => '',
                            'type' => 'url',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                        ),
                    ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'rpi_wall_settings',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'left',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
                'acfe_display_title' => '',
                'acfe_autosync' => '',
                'acfe_form' => 0,
                'acfe_meta' => '',
                'acfe_note' => '',
            ));

        endif;
    }

    private function prepare_field_type_template_arrays(): array
    {
        $result = [];
        foreach (Message::$templates as $key => $template) {
            if (isset($template['subject'], $template['body'])) {
                $result[] = array(
                    'key' => 'field_rpi_message_' . $key . '_template',
                    'label' => 'Rpi Nachrichten ' . $key . ' Vorlage',
                    'name' => 'rpi_message_' . $key . '_template',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'frontend_admin_display_mode' => 'edit',
                    'only_front' => 0,
                    'layout' => 'block',
                    'acfe_seamless_style' => 0,
                    'acfe_group_modal' => 0,
                    'sub_fields' => array(
                        array(
                            'key' => 'field_rpi_message_' . $key . '_template_subject',
                            'label' => 'Betreff',
                            'name' => 'field_rpi_message_' . $key . '_template_subject',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'readonly' => 0,
                            'default_value' => $template['subject'],
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_rpi_message_' . $key . '_template_body',
                            'label' => 'Inhalt',
                            'name' => 'field_rpi_message_' . $key . '_template_body',
                            'type' => 'textarea',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'frontend_admin_display_mode' => 'edit',
                            'only_front' => 0,
                            'readonly' => 0,
                            'default_value' => $template['body'],
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                    ),
                );
            }
        }

        return $result;
    }

    public function register_options_pages()
    {
        if (function_exists('acf_add_options_page')):

            acf_add_options_page(array(
                'page_title' => 'RPI Wall Einstellungen',
                'menu_slug' => 'rpi_wall_settings',
                'menu_title' => 'RPI Wall Einstellungen',
                'capability' => 'edit_posts',
                'position' => '',
                'parent_slug' => 'options-general.php',
                'icon_url' => '',
                'redirect' => true,
                'post_id' => 'options',
                'autoload' => false,
                'update_button' => 'Speichern',
                'updated_message' => 'Einstellungen gespeichert',
            ));

        endif;
    }

    public function sync_user_member_relation($user_login, $user)
    {
        if (is_a($user, 'WP_User')) {
            if (in_array($user->user_login, ['wpadmin', 'admin'])) {
                return;
            }
            $member = get_posts(array(
                'post_status' => 'any',
                'post_type' => 'member',
                'author' => $user->ID
            ));

            if (is_array($member) && !empty(reset($member))) {
                return;
            } else {
                $member = wp_insert_post(array(
                    'post_title' => $user->display_name,
                    'post_status' => 'publish',
                    'post_author' => $user->ID,
                    'post_type' => 'member'
                ));
            }
        }
    }

    public function change_author_link_to_user_profile($link, $author_id, $author_nicename)
    {
        $member = new Member($author_id);

        return $member->get_member_profile_permalink();

    }

    /**
     * @param int $post_ID
     * @param WP_Post $post
     * @param bool $update
     *
     * @return void
     */
    public function update_taxonomy_of_member_on_pin_save(int $post_ID, WP_Post $post, bool $update = false)
    {
        $this->sync_taxonomies_of_pin_members($post_ID, $post, false);
    }

    function sync_taxonomies_of_pin_members(int $post_ID, WP_Post $post, bool $delete)
    {
        if ($post->post_type === 'wall') {
            $group = new Group($post_ID);
            $members = array_merge($group->get_memberIds(), $group->get_likers_Ids());

            $taxonomies = get_object_taxonomies('wall');


            foreach ($taxonomies as $taxonomy) {
                foreach ($members as $user_id) {
                    $member = new Member($user_id);

                    $member_tags = [];
                    $member_groups = array_merge($member->get_group_Ids(), $member->get_watched_group_Ids());
                    foreach ($member_groups as $member_group) {
                        if ($delete && $group->ID === $member_group) {
                            continue;
                        } else {
                            $group_tags = wp_get_post_terms($member_group, $taxonomy);
                            foreach ($group_tags as $group_tag) {

                                if ($group_tag instanceof \WP_Term && !in_array($group_tag->term_id, $member_tags)) {
                                    $member_tags[] = $group_tag->term_id;
                                }
                            }
                        }
                    }

                    wp_set_post_terms($member->post->ID, $member_tags, $taxonomy);
                }
            }
        }
    }

    public function delete_member_taxonomy_on_pin_deletion(int $postid, WP_Post $post)
    {
        $this->sync_taxonomies_of_pin_members($postid, $post, true);
    }

    function sync_taxonomies_of_members(int $post_ID, WP_Post $post, bool $delete)
    {

        if ($post->post_type === 'member') {
            $member = new Member($post->post_author);

            $taxonomies = get_object_taxonomies('wall');
            $pin_ids = $member->get_assigned_group_Ids();

            foreach ($taxonomies as $taxonomy) {
                $term_ids = [];

                foreach ($pin_ids as $post_id) {
                    $terms = wp_get_post_terms($post_id, $taxonomy);
                    foreach ($terms as $term) {
                        if ($term instanceof \WP_Term && !in_array($term->term_id, $term_ids)) {
                            $term_ids[] = $term->term_id;
                        }
                    }
                }
                wp_set_post_terms($member->post->ID, $term_ids, $taxonomy);
            }
        }
    }

    function alter_wall_query(\WP_Query $query)
    {

        $is_main_query = false;

        if (empty($_GET['widgetId']) && $query->is_main_query() && ($query->is_post_type_archive('wall') || $query->get('post_type') === 'wall')) {

            if (!is_user_logged_in()) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'public',
                        'compare' => '=',
                        'value' => '1'
                    )
                ));
            }

        }
    }

	function redirect_wall_cat_to_facet(){

		if(is_tax('wall-cat')){
			$queried_object = get_queried_object () ;
			wp_redirect(home_url().'/wall/?_wall_cats='.$queried_object->slug);

		}
	}
}
