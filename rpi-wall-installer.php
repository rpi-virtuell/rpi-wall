<?php

namespace rpi\Wall;

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
        add_action('init', array($this, 'register_options_pages'));
        add_action('wp_login', array($this, 'sync_user_member_relation'), 10, 2);
        add_filter('author_link', array($this, 'change_author_link_to_user_profile'), 10, 3);
        add_action('save_post_wall', array($this, 'update_taxonomy_of_member_on_pin_save'), 10, 3);
        add_action('before_delete_post', array($this, 'delete_member_taxonomy_on_pin_deletion'), 10, 2);
        add_filter('manage_posts_columns', array($this, 'add_new_message_columns'), 10, 2);
        add_action('manage_message_posts_custom_column', array($this, 'display_message_recipients_column'), 10, 2);

        add_action('pre_get_posts', array($this, 'alter_wall_query'));
    }

    public function add_custom_capabilities()
    {
        $roles = ['administrator', 'editor'];
        foreach ($roles as $roleslug) {
            $role = get_role($roleslug);
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

            $role->add_cap('manage_schooltype');
            $role->add_cap('edit_schooltype');
            $role->add_cap('delete_schooltype');
            $role->add_cap('assign_schooltype');

        }
        /// Author capabilities

        $role = get_role('author');

        $role->add_cap('edit_wall');
        $role->add_cap('publish_walls');
        $role->add_cap('read_walls');
        $role->add_cap('delete_walls');

        $role->add_cap('edit_wall_tags');
        $role->add_cap('assign_wall_tags');

        $role->add_cap('assign_wall_cat');

        $role->add_cap('assign_badge');

        $role->add_cap('assign_schooltype');
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
            "show_ui" => true, // TODO: MIGHT CHANGE LATER WIP
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => true,
            "exclude_from_search" => false,
            'capability_type' => 'member',
            'capabilities' => array(
                'edit_posts' => 'edit_member',
                'edit_others_posts' => 'edit_others_member',
                'read_private_posts' => 'read_private_member',
                'publish_posts' => 'publish_member',
                'read_post' => 'read_member',
                'delete_others_posts' => 'delete_others_member',
                'edit_published_posts' => 'edit_published_member',
                'delete_published_posts' => 'delete_published_member',
                'delete_posts' => 'delete_member',
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
            'taxonomies' => ['wall-tag', "badge", 'schooltype', 'profession'],
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

    }

    /**
     * Empf??ngerspalte in admin columns hinzuf??gen
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
            $columns['recipients'] = 'Empf??nger';
        }

        return $columns;
    }

    /**
     * Empf??ngerspalte  mit Empf??ngern aus dem mety key 'rpi_wall_message_recipient' bef??llen
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
            "show_in_quick_edit" => false,
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
         * Taxonomy: Kategorien.
         */

        $labels = [
            "name" => __("Kategorien", "blocksy"),
            "singular_name" => __("Kategorie", "blocksy"),
        ];


        $args = [
            "label" => __("Kategorien", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'wall-cat', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "wall-cat",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
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
         * Taxonomy: schooltype.
         */

        $labels = [
            "name" => __("Schulformen", "blocksy"),
            "singular_name" => __("Schulform", "blocksy"),
        ];


        $args = [
            "label" => __("Schulform", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => false,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'schooltype', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "schooltype",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_schooltype',
                'edit_terms' => 'edit_schooltype',
                'delete_terms' => 'delete_schooltype',
                'assign_terms' => 'assign_schooltype'
            ),
        ];
        register_taxonomy("schooltype", ["member"], $args);

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
            "hierarchical" => false,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'profession', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => false,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "profession",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
            "sort" => false,
            "show_in_graphql" => false,
            "capabilities" => array(
                'manage_terms' => 'manage_profession',
                'edit_terms' => 'edit_profession',
                'delete_terms' => 'delete_profession',
                'assign_terms' => 'assign_profession'
            ),
        ];
        register_taxonomy("profession", ["member"], $args);
    }

    function register_custom_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_rpi_wall_settings',
                'title' => 'RPI Wall Einstellungen',
                'fields' => array(
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
                        'label' => 'Zeitlimit f??r die Erstellung einer Gruppe',
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
                        'key' => 'field_rpi_message_templates',
                        'label' => '',
                        'name' => 'rpi_message_templates',
                        'type' => 'group',
                        'instructions' => 'M??gliche Variablen
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
                        // TODO: ggf muss dieses und server base angepasst werden
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
                        'key' => 'field_rpi_label_group',
                        'label' => '',
                        'name' => 'rpi_label_group',
                        'type' => 'group',
                        'instructions' => 'Diese Labels werden angezeigt in der Detail Ansicht eines Pins und ??ndern sich mit den unterschiedlichen Stadien des Pins',
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
                                    'label' => 'RPI Gruppen Erstellung m??glich Header',
                                    'name' => 'rpi_wall_ready_header',
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
                                    'default_value' => 'Professionellen Lerngemeinschaft (PLG)',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_ready_notice',
                                    'label' => 'RPI Gruppen Erstellung m??glich Notice',
                                    'name' => 'rpi_wall_ready_notice',
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
                                    'default_value' => 'Mit Klick auf "Gruppe Gr??nden" werden alle interessierten angeschrieben und haben eine Woche Zeit, der PLG beizutreten.',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_pending_header',
                                    'label' => 'Gr??ndungsphase Header',
                                    'name' => 'rpi_wall_pending_header',
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
                                    'default_value' => 'Wir suchen noch Leute f??r eine Professionellen Lerngemeinschaft (PLG) zu diesem Kontext',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_pending_notice',
                                    'label' => 'Gr??ndungsphase notice',
                                    'name' => 'rpi_wall_pending_notice',
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
                                    'default_value' => 'Die Gruppe befindet sich in der Gr??ndungsphase. M??chtest du dabei sein?',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_founded_header',
                                    'label' => 'Gruppe gegr??ndet Header',
                                    'name' => 'rpi_wall_ready_notice',
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
                                    'default_value' => 'Professionelle Lerngemeinschaft (PLG) zu diesem Kontext',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_founded_notice',
                                    'label' => 'Gruppe gegr??ndet Notice',
                                    'name' => 'rpi_wall_founded_notice',
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
                                    'default_value' => 'Zu diesem Pinwandeintrag hat sich eine PLG gegr??ndet.',
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
                                    'default_value' => 'Professionelle Lerngemeinschaft (PLG) zu diesem Kontext',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_closed_notice',
                                    'label' => 'Gruppe geschlossen Notice',
                                    'name' => 'rpi_wall_closed_header',
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
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_not_founded_header',
                                    'label' => 'Keine Gruppe gegr??ndet Header',
                                    'name' => 'rpi_wall_not_founded_header',
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
                                    'default_value' => 'Interessiert an einer Professionellen Lerngemeinschaft (PLG) zu diesem Kontext?',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_not_founded_notice',
                                    'label' => 'Keine Gruppe gegr??ndet Notice',
                                    'name' => 'rpi_wall_not_founded_notice',
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
                                    'default_value' => 'Wenn du zu den Interessierten geh??rst, wirst du automatisch benachrichtigt, sobald sich gen??gend Interessenten gefunden haben.',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                            ),
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

    function alter_wall_query(\WP_Query $query)
    {

        if (empty($_GET['widgetId']) && $query->is_main_query() && !is_user_logged_in() && ($query->is_post_type_archive('wall') || $query->get('post_type') === 'wall')) {

            //TODO: Check wether given widgetID is valid

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
