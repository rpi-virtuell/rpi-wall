<?php
namespace rpi\Wall;

class MemberInstaller
{

    public function __construct()
    {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('wp_login', array($this, 'sync_user_member_relation'), 10, 2);
        add_shortcode('rpi-userprofile', array($this, 'get_user_profile_tags'));
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
            "rewrite" => ["slug" => "member", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-list-view",
            "supports" => [
                'title',
                "editor",
            ],
            'taxonomies' => [],
            "show_in_graphql" => false,
        ];

        register_post_type("member", $args);

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
            "menu_icon" => "dashicons-list-view",
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
        register_taxonomy("badge", ["member"], $args);

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
        register_taxonomy("rpi_tag", ["member"], $args);

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
        register_taxonomy("schooltype", ["member"], $args);

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
        register_taxonomy("profession", ["member"], $args);
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
                    'ID' => $user->ID,
                    'post_title' => $user->display_name,
                    'post_status' => 'publish',
                    'post_author' => $user->ID,
                    'post_type' => 'member'
                ));
            }
        }
    }

    public function get_user_profile_tags($atts)
    {
        global $wp_ulike_pro_current_user;

        if (isset($atts['content']) && is_a($wp_ulike_pro_current_user, 'WP_User')) {
            echo '<ul>';
            $member = get_page_by_title($wp_ulike_pro_current_user->display_name, 'OBJECT', 'member');
            if (post_type_exists($atts['content'])) {
                //TODO: Gruppen Link einfügen (Link auf Pinns mit gruppen)
            } elseif (taxonomy_exists($atts['content'])) {
                $terms = wp_get_post_terms($member->ID, $atts['content']);
                foreach ($terms as $term) {
                    if (is_a($term, 'WP_Term')) {
                        echo '<a href="' . site_url() . '/' . $atts['content'] . '/' . $term->slug . '">' . $term->name . '</a>';
                        echo '<br>';
                    }
                }
            }
            echo '</ul>';
        }
    }

}
