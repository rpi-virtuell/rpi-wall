<?php

namespace rpi\Wall;

use  rpi\Wall\Message;

class RPIWallInstaller
{

    public function __construct()
    {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_custom_fields'));
        add_action('init', array($this, 'register_options_pages'));
        add_action('wp_login', array($this, 'sync_user_member_relation'), 10, 2);
    }

    public function register_post_types()
    {
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
            'capability_type' => 'post',
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "Member", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-admin-users",
            "supports" => [
                'title',
                "editor",
            ],
            'taxonomies' => [],
            "show_in_graphql" => false,
        ];

        register_post_type("Member", $args);

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
         * Post Type: Gruppe.
         */

        $labels = [
            "name" => __("Gruppen", "blocksy"),
            "singular_name" => __("Gruppe", "blocksy"),
        ];

        $args = [
            "label" => __("Gruppen", "blocksy"),
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
            "rewrite" => ["slug" => "group", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor"],
            "menu_icon" => "dashicons-groups",
            "taxonomies" => ["channel"],
            "show_in_graphql" => false,
        ];

        register_post_type("group", $args);
    }

    function register_taxonomies()
    {

        /**
         * Taxonomy: Badges.
         */

        $labels = [
            "name" => __("Badges", "blocksy"),
            "singular_name" => __("Badge", "blocksy"),
        ];


        $args = [
            "label" => __("Badges", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'badge', 'with_front' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "badge",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => true,
            "show_in_graphql" => false,
        ];
        register_taxonomy("badge", ["Member"], $args);

        /**
         * Taxonomy: Tags.
         */

        $labels = [
            "name" => __("Tags", "blocksy"),
            "singular_name" => __("Tag", "blocksy"),
        ];


        $args = [
            "label" => __("Tag", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'rpi_tag', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "rpi_tag",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
        ];
        register_taxonomy("rpi_tag", ["Member"], $args);

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
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'schooltype', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "schooltype",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
        ];
        register_taxonomy("schooltype", ["Member"], $args);

        /**
         * Taxonomy: profession.
         */

        $labels = [
            "name" => __("Professionen(Zielgruppen)", "blocksy"),
            "singular_name" => __("Profession(Zielgruppe)", "blocksy"),
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
        ];
        register_taxonomy("profession", ["Member"], $args);
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
                        'key' => 'field_pl_group_min_required_members',
                        'label' => 'Niedrigste Menge an Mitgliedern',
                        'name' => 'pl_group_min_required_members',
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
                        'sub_fields' =>   $this->prepare_field_type_template_arrays(),
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
                        'key' => 'field_rpi_matrix_server_home', // TODO: ggf muss dieses und server base angepasst werden
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
                        'key' => 'field_rpi_wall_founded_header',
                        'label' => 'RPI Wall gefunden Header',
                        'name' => 'rpi_wall_founded_header',
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
                        'key' => 'field_rpi_wall_not_founded_header',
                        'label' => 'RPI Wall nicht gefunden Header',
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
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_rpi_wall_pending_notice',
                        'label' => 'Gruppe in der Gründungsphase',
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
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_rpi_wall_founded_notice',
                        'label' => 'Gruppen gründung durchgeführt',
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
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_rpi_wall_ready_notice',
                        'label' => 'Gründungsphasen Notiz',
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
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
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
                'post_type' => 'Member',
                'author' => $user->ID
            ));
            if (is_array($member) && !empty(reset($member))) {
                return;
            } else {
                $member = wp_insert_post(array(
                    'ID' => $user->ID,
                    'post_title' => $user->display_name,
                    'post_status' => 'publish',
                    'post_author' => $user->ID,
                    'post_type' => 'Member'
                ));
            }
        }
    }

}
