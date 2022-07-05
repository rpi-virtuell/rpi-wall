<?php

namespace rpi\Wall;

use  rpi\Wall;
use WP_Post;

class RPIWallInstaller
{

    public function __construct()
    {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_custom_fields'));
        add_action('init', array($this, 'register_options_pages'));
        add_action('wp_login', array($this, 'sync_user_member_relation'), 10, 2);
        add_filter('author_link', array($this, 'change_author_link_to_user_profile'), 10, 3);
        add_action('save_post_wall', array($this, 'sync_taxonomy_of_member_with_pin'), 10, 3);
        add_action('before_delete_post', array($this, 'delete_member_taxonomy_on_pin_deletion'), 10, 2);
	    add_filter('manage_posts_columns',array($this,'add_new_message_columns'),10,2);
	    add_action('manage_message_posts_custom_column',array($this,'display_message_recipients_column'),10,2);

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
            'capability_type' => 'post',
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
            'capability_type' => 'post',
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
	 * Empfängerspalte in admin columns hinzufügen
	 * filter hook manage_posts_columns
	 *
	 * @param $columns
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public function add_new_message_columns($columns, $post_type ){
		if($post_type == 'message'){
			$columns['recipients'] = 'Empfänger';
		}
		return $columns;
	}

	/**
	 * Empfängerspalte  mit Empfängern aus dem mety key 'rpi_wall_message_recipient' befüllen
	 * @param $name
	 * @param $post_id
	 *
	 * @return void
	 */
	function display_message_recipients_column($name, $post_id) {

		switch ($name) {
			case 'recipients':
				$recipients = get_post_meta($post_id, 'rpi_wall_message_recipient');
				if($recipients && count($recipients) >0){
					$users =[];
					foreach ($recipients as $user_id){
						$user = get_userdata($user_id);
						if($user instanceof \WP_User){
							$users[]= $user->display_name;
						}

					}
					echo implode(', ',$users);

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
            "hierarchical" => false,
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
                                    'label' => 'RPI Gruppen Erstellung möglich Notice',
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
                                    'default_value' => 'Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '150',
                                ),
                                array(
                                    'key' => 'field_rpi_wall_founded_header',
                                    'label' => 'Gruppe gegründet Header',
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
                                    'label' => 'Gruppe gegründet Notice',
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
                                    'default_value' => 'Zu diesem Pinwandeintrag hat sich eine PLG gegründet.',
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
                                    'label' => 'Keine Gruppe gegründet Header',
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
                                    'label' => 'Keine Gruppe gegründet Notice',
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
                                    'default_value' => 'Wenn du zu den Interessierten gehörst, wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.',
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
        return home_url("/member/" . $author_nicename . "/");
    }

    /**
     * @param int $post_ID
     * @param WP_Post $post
     * @param bool $update
     * @return void
     */
    public function sync_taxonomy_of_member_with_pin(int $post_ID, WP_Post $post, bool $update = false)
    {
        $new_tags = [];
        $group = new Group($post_ID);
        $members = array_merge($group->get_memberIds(), $group->get_likers_Ids());
        $members = get_posts(["post_type" => "member", "author__in" => $members]);

        $taxonomies = get_post_taxonomies($post_ID);
        foreach ($taxonomies as $taxonomy) {
            $group_tags = wp_get_post_terms($post_ID, $taxonomy);
            foreach ($members as $m) {
                $member = $m->ID;
                $member_tags = wp_get_post_terms($member, $taxonomy);
                foreach ($group_tags as $group_tag) {
                    if (!in_array($group_tag, $member_tags) && $group_tag instanceof \WP_Term) {
                        $new_tags[] = $group_tag->term_id;
                    }
                }
                $new_tags = array_merge(array_column($member_tags, 'term_id'), $new_tags);
                wp_set_post_terms($member, $new_tags, $taxonomy);
            }
        }
    }

    public function delete_member_taxonomy_on_pin_deletion(int $postid, WP_Post $post)
    {
        if ($post->post_type === 'pin') {
            $group = new Group($postid);
            $members = array_merge($group->get_memberIds(), $group->get_likers_Ids());
            $members = get_posts(["post_type" => "member", "author__in" => $members]);

            $taxonomies = get_post_taxonomies($postid);
            foreach ($taxonomies as $taxonomy) {
                foreach ($members as $member) {
                    $member = new Member($member);
                    $member_tags = [];
                    $member_groups = $member->get_group_Ids();
                    foreach ($member_groups as $member_group) {
                        if ($group->ID === $member_group) {
                            continue;
                        } else {
                            $group_tags = wp_get_post_terms($member_group, $taxonomy);
                            foreach ($group_tags as $group_tag) {
                                if (is_a($group_tag, 'WP_Term') && !in_array($group_tag->slug, $member_tags)) {
                                    $member_tags[] = $group_tag->slug;
                                }
                            }
                        }
                    }
                    wp_set_post_terms($member, $member_tags, '$taxonomy');
                }
            }
        }
    }


}
