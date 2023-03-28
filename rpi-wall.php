<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           Rpi_Wall
 *
 * @wordpress-plugin
 * Plugin Name:       rpi Pinnwand
 * Plugin URI:        https://github.com/rpi-virtuell/rpi-wall/
 * Description:       Wordpress Pinnwand PLG Plugin entwickelt für das Projekt Schule Evangelisch Digital des Comenius-Institutes
 * Version:           2.0.0
 * Author:            Joachim Happel
 * Author URI:        https://github.com/johappel
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rpi-wall
 * Domain Path:       /languages
 */

require_once("rpi-wall-installer.php");
require_once("rpi-wall-ajax-handler.php");
require_once("rpi-wall-form-handler.php");
require_once("shortcodes.php");
require_once("inc/member.php");
require_once("inc/group.php");
require_once("inc/tabs.php");
require_once("inc/member-page.php");
require_once("inc/message.php");
require_once("inc/protocol.php");
require_once("inc/termin.php");
require_once("inc/matrix.php");
require_once("inc/toolbar.php");

if (!defined('KONTO_SERVER')) {
    if (getenv('KONTO_SERVER')) // env var is set in apache2.conf
    {
        define('KONTO_SERVER', getenv('KONTO_SERVER'));
    } else // .htaccess Eintrag fehlt: SetEnv KONTO_SERVER "https://my-wordpress-website.com"
    {
        wp_die('Environmental Var KONTO_SERVER is not defined');
    }
}


use rpi\Wall;
use rpi\Wall\Message;
use rpi\Wall\Shortcodes;


class RpiWall
{

    public $matrix;
    protected $max_stars_per_comment = 5;
    protected $group_member_min = 3;
    protected $installer;

    public function __construct()
    {
        //session_start();

        add_action('wp_enqueue_scripts', [$this, 'custom_style_and_scripts']);

        add_action('facetwp_query_args', [$this, 'facetwp_injection'], 10, 2);
        //add_action('pre_get_posts', [$this, 'query_tags']);

        add_action('pre_get_posts', [$this, 'query_member']);

        /**** beobachte beiträge filtern ****/
        add_filter('facetwp_facet_display_value', function ($label, $params) {

            if ('beobachtet' == $params['facet']['name']) {

                $label = 'Nur beobachtete Einträge';

            }

            return $label;
        }, 99, 2);

        add_filter('facetwp_facet_filter_posts', function ($return, $params) {
            global $wp_query;

            if ('beobachtet' == $params['facet']['name']) {
                $args = $wp_query->query_vars;
                $args['meta_query'][] = array(
                    'key' => 'rpi_wall_watcher_id',
                    'compare' => '=',
                    'value' => get_current_user_id()
                );
                $posts = get_posts($args);
                foreach ($posts as $post) {
                    $post_ids[] = $post->ID;
                }

                return $post_ids;
            }

            return $return;
        }, 10, 2);
        /**** beobachte beiträge filtern END ****/

        add_filter('body_class', [$this, 'add_group_status_class']);
        add_action('post_class', [$this, 'add_group_status_class']);


        add_action('blocksy:hero:title:before', function () {
            if (get_post_type() === 'termin') {
                ?>
                <div class="termin-date">

                    <?php
                    $timestamp = date(DATE_ATOM, strtotime(get_post_meta(get_the_ID(), 'termin_date', true)));
                    echo Shortcodes::getWochentag($timestamp) . ' ' . date('j', strtotime(get_post_meta(get_the_ID(), 'termin_date', true))) . '. ' . Shortcodes::getMonat($timestamp);
                    echo "<br>";
                    echo date('H:i', strtotime(get_post_meta(get_the_ID(), 'termin_date', true))) . ' - ' . date('H:i', strtotime(get_post_meta(get_the_ID(), 'termin_enddate', true)))

                    ?>
                </div>
                <?php
            }
        });

        add_action('blocksy:content:top', function () {
            if ((is_post_type_archive('wall') || is_tax() && current_user_can('publish_walls'))) {
                ob_start();
                ?>
                <div class="ct-container rpi-wall-tutorial-header">
                    <span><?php echo get_option('options_rpi_label_general_textfields_group_rpi_wall_main_header', 'Willkommen auf der Pinnwand! Hier kannst du eigene Fragestellungen einbringen und findest spannende Impulse zu verschiedenen Themen. Außerdem kannst dich an Fragen durch Kommentare beteiligen oder Professionelle Lerngruppen (PLG´s) zum intensiveren Austausch zu einem Thema finden.') ?></span>
                </div>
                <?php
                echo ob_get_clean();
                if (is_user_logged_in()) {
                    RpiWall::modal('form', 'Neuer Eintrag', do_shortcode('[acfe_form name="create-pin"]'));
                } else {
                    RpiWall::modal('form', 'Neuer Eintrag', '<p> Um einen Pin erstellen zu können, musst du dich erst anmelden!</p> <a href="account-modal" data-id="account" data-state="out" class="ct-header-account button">Anmelden</a> ');
                }

            } else {
                if (get_post_type() === 'wall' && is_user_logged_in()) {
                    if (get_post()->post_author == get_current_user_id() || current_user_can('edit_others_walls')) {
                        RpiWall::modal('form', 'Bearbeiten', do_shortcode('[acfe_form name="edit-pin"]'));
                    }
                }
                if (get_post_type() === 'protokoll') {

                    global $post;
                    if (is_user_logged_in()) {
                        $member = new Wall\Member(get_current_user_id());
                        $groupId = get_post_meta(get_the_ID(), 'rpi_wall_protocol_groupid', true);
                        if ($member->is_in_group($groupId) || current_user_can('manage_options')) {
                            RpiWall::modal('protocol', 'Bearbeiten', do_shortcode('[acfe_form name="edit-protocol"]'));
                        } else {
                            ob_start();
                            ?>
                            <div class="ct-container rpi-wall-tutorial-header">
                                <span> Nur Gruppenmitglieder können dieses Protokoll sehen </span>
                            </div>
                            <?php
                            add_filter('the_content', function () {
                                return ob_get_clean();
                            });
                        }
                    } else {
                        ob_start();
                        ?>
                        <div class="ct-container rpi-wall-tutorial-header">
                            <span> Nur Gruppenmitglieder können dieses Protokoll sehen </span>
                        </div>
                        <?php
                        add_filter('the_content', function () {
                            return ob_get_clean();
                        });
                    }

                }
                if (is_post_type_archive('member')) {
                    if (!is_user_logged_in()) {
                        wp_footer();
                        die();
                    } else {
                        $member = new Wall\Member(get_current_user_id());
                        ob_start();
                        ?>
                        <div class="ct-container rpi-wall-tutorial-header">
                        <span><?php echo get_option('options_rpi_label_general_textfields_group_rpi_member_main_header', 'Unser Netzwerk lebt von allen, die gute Fragen stellen, Erfahrungen teilen, Kompetenzen einbringen und Perspektiven eröffnen.  Stell dich mit ein paar Worten und einem Avatarbild vor. Mit jeder Aktivität im Netzwerk wächst auch dein Profil.') ?>
                        <a href="<?php echo $member->url ?>"> Zu deinem Profil</a>
                        </span>
                        </div>
                        <?php
                        echo ob_get_clean();
                    }
                }
            }
            if (is_post_type_archive('wall')):
                ?>
                <div class="ct-container rpi-wall-filters">
                    <?php echo do_shortcode('[rpi_wall_filter]'); ?>
                </div>
            <?php
            endif;

            if (is_post_type_archive('member')):
                ?>
                <div class="ct-container rpi-wall-filters">
                    <?php echo do_shortcode('[rpi_member_filter]'); ?>
                </div>
            <?php
            endif;

        });

        /* facetwp paging needs to disable blocksy pager!!! */
        add_action('blocksy:loop:after', function () {
            echo '<div class="rpi-wall-paging">';
            //count pin items
            echo facetwp_display('facet', 'pagecount');
            //enable pager
            echo facetwp_display('facet', 'paging');
            echo '</div>';
        });

        /* is public hint */
        add_action('blocksy:comments:title:after', function () {
            if (is_singular('wall')) {
                if (get_post_meta(get_the_ID(), 'public', true)) {
                    echo '<div class="pin_is_public_message">';
                    echo 'Dieser Pin ist öffentlich und damit auch alle Kommentare';
                    echo '</div>';
                }
            }

        });

        add_filter('acf/load_field/name=matrixid', ['rpi\Wall\Member', 'set_default_matrixId']);

        //Toolbar
        add_filter('body_class', ['\rpi\Wall\Toolbar', 'add_toolbar_class_to_body']);
        add_filter('cmplz_site_needs_cookiewarning', ['\rpi\Wall\Toolbar', 'hide_cookie_warning']);
        add_action('wp_body_open', function () {
            if ((isset($_GET['widgetId']) || isset($_GET['roomId'])) && get_post_type() == "wall") {
                $group = new Wall\Group(get_the_ID());
                $roomId = isset($_GET['roomId']) ? $_GET['roomId'] : 'unknown';
                if ($group->get_matrix_room_id() === $roomId) {
                    Wall\Toolbar::display_toolbar($group, true);
                }
                $split = explode('_', $_GET['widgetId']);
                if ($group->get_matrix_room_id() === $split[0]) {
                    Wall\Toolbar::display_toolbar($group, true);
                }
            }
        });


        // Pin Display

//        add_action('blocksy:hero:title:before',[$this,'display_constituted_group_title']);


        add_action('blocksy:single:top', ['rpi\Wall\Group', 'display_watcher_area']);

        add_action('blocksy:single:top', [$this, 'add_ob_to_capture_pin_content']);
        add_action('blocksy:single:bottom', [$this, 'add_tabs_to_pin_view']);


        // Pinboard Carddisplay


//        add_action('blocksy:loop:card:start', [$this, 'display_cards_status_triangle']);
        add_action('blocksy:loop:card:start', [$this, 'display_cards_pin_icon']);
        add_action('blocksy:loop:card:start', [$this, 'display_cards_watch_icon']);

        add_action('blocksy:loop:card:end', [$this, 'display_cards_group_info']);

        add_action('blocksy:loop:card:start', [$this, 'display_cards_member']);

        add_filter('wp_ulike_ajax_respond', [$this, 'wp_ulike_ajax_respond'], 20, 4);


        //incomming
        add_action('init', ['rpi\Wall\Group', 'init_handle_requests']);

        add_action('init', ['rpi\Wall\Group', 'init_cronjob']);

        add_action('init', ['rpi\Wall\Member', 'init_handle_request']);
        add_action('init', ['rpi\Wall\Member', 'init_cronjob'], 5);

        add_action('wp', [$this, 'redirect_to_users_member_page']);

        // TODO USED FOR DEBUG NEEDS TO BE DELETED BEFORE LAUNCH
//		add_action( 'init', [ $this, 'test' ] );

        add_action('wp_footer', [$this, 'initialize_message_counter']);

        /**
         * ToDo add to cronjob
         */
        add_action('wp_head', function () {
            global $post;
            if ($post->post_type == 'wall') {
                $this->installer->sync_taxonomies_of_pin_members($post->ID, $post, false);
            }
            if ($post->post_type == 'member') {
                $this->installer->sync_taxonomies_of_members($post->ID, $post, false);
            }

            echo '<script> var rpi_wall; </script>';

        });


        //add_action('save_post_wall', [$this, 'on_new_pin'], 10, 3);

        add_action('acfe/form/submit/form=create-pin', [$this, 'on_new_pin'], 10, 2);

        add_action('save_post_member', [$this, 'on_new_member'], 10, 3);
        add_action('wp_insert_comment', [$this, 'on_new_comment'], 99, 2);


        add_filter('acf/load_field/name=display_name', function ($field) {
            $user = get_userdata(get_current_user_id());

            $field['choices'] = array();
            $field['choices'][$user->nickname] = $user->nickname;
            $field['choices'][$user->user_login] = $user->user_login;
            $field['choices'][$user->first_name] = $user->first_name . ' ' . $user->last_name;

            return $field;

        }, 10, 1);

        $this->installer = new Wall\RPIWallInstaller();

        add_action('wp', function () {

            if (get_current_user_id() == 2 && is_singular(['wall'])) {
	            $matrix = new Wall\Matrix();
                $matrix->tests(get_the_ID());
            }
        });


    }

    /**
     * öffnet den Inhalt in einem Overlay Popup
     *
     * @param string $id
     * @param string $content
     *
     * @return void
     */
    static function modal($id = 'form', $label = 'Bearbeiten', $content = '')
    {

        ?>
        <div class="ct-container rpi-wall-buttons">
            <a class="fea-submit-button button button-primary" id="btn-open-modal-<?php echo $id; ?>"
               href="#modal-<?php echo $id; ?>"><?php echo $label; ?></a>
        </div>
        <div id="modal-<?php echo $id; ?>">
            <div class="modal-wrapper">
                <div id="btn-close-modal" class="close-modal-<?php echo $id; ?>">
                    <button class="button button-primary">X</button>
                </div>
                <div class="modal-content"><?php echo $content ?></div>

            </div>
        </div>
        <script>jQuery("#btn-open-modal-<?php echo $id;?>").animatedModal();</script>

        <?php

    }

    public function add_ob_to_capture_pin_content()
    {
        if (is_singular('wall')) {
            ob_start();
        }
    }

    /**
     * Legt die Anzahl der Pinwand Posts fest
     *
     * @param $query_args
     * @param $class
     *
     * @return mixed
     */
    public function facetwp_injection($query_args, $class)
    {
        // Blocksy Pager muss ausgeschaltet sein!
        if ('wall' === $query_args['post_type']) {
            $blocksy = get_option('theme_mods_blocksy');
            if ($blocksy) {
                $posts_per_page = $blocksy['wall_archive_archive_per_page'];
                $query_args['posts_per_page'] = $posts_per_page;
            }
        }
        if ('member' === $query_args['post_type']) {
            $blocksy = get_option('theme_mods_blocksy');
            if ($blocksy) {
                $posts_per_page = $blocksy['member_archive_archive_per_page'];
                $query_args['posts_per_page'] = $posts_per_page;
            }
        }

        return $query_args;
    }

    public function query_tags(&$query)
    {


        if (is_tax('wall-tag') && is_post_type_archive('wall')) {
            //$query->set('post_type','wall');

        }

    }

    public function query_member(WP_Query &$query)
    {
        if (is_tax('wall-tag') && !isset($_GET['wall-tag'])) {
            $term = get_queried_object();

            wp_redirect(home_url() . '/member?wall-tag=' . $term->name);
        }

        if (!is_admin() && $query->is_main_query() && is_post_type_archive('member')) {


            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => 'hideme',
                    'compare' => "!=",
                    'value' => true
                ),
                array(
                    'key' => 'hideme',
                    'compare' => "NOT EXISTS",

                ),
            );
            $query->set('meta_query', $meta_query);

            $query->set('post_type', 'member');
            $query->set('orderby', 'post_title');
            $query->set('order', 'ASC');
        }

    }

    public function add_tabs_to_pin_view()
    {
        if (is_singular('wall')) {

            $tabs = new \rpi\Wall\Tabs('tabset');

            $tabs->addTab(['label' => 'Pin',
                'name' => 'pin',
                'content' => ob_get_clean(),
                'icon' => \rpi\Wall\Shortcodes::$pin_icon,
                'checked' => true
            ]);
            $tabs->addTab(['label' => 'Gruppe',
                'name' => 'group',
                'content' => $this->get_group_tab_of_pin_view(),
                'icon' => \rpi\Wall\Shortcodes::$group_icon
            ]);

            $tabs->display();

            echo '<script>rpi_wall ={user_ID: "' . get_the_author_meta("ID") . '"};</script>';
            echo '<script>rpi_wall.allowedtabs = ' . json_encode($tabs->get_allowed_tabs()) . ';</script>';
        }
    }

    public function get_group_tab_of_pin_view()
    {


        ob_start();
        $group = new Wall\Group(get_the_ID());
        ?>
        <header class="entry-header">
            <h1 class="page-title"> <?php echo !empty(get_field("constitution_gruppenname")) ? get_field("constitution_gruppenname") : $group->post->post_title?> </h1>
        </header>
        <?php
        $group->display();
        $currentUser = get_current_user_id();
        if ($group->is_founded() && $currentUser != 0 && ($group->has_member($currentUser) || current_user_can('manage_options'))) {
            ?>
            <details class="group-tab-matrix-detail">
                <summary style="cursor:pointer"><strong><?php echo \rpi\Wall\Shortcodes::$element_icon ?> Zur Matrix
                        Gruppe</strong></summary>
                <br>
                <a class="button button-primary" href="<?php echo $group->get_matrix_link() ?>" target="_blank">im
                    Browser matrix.rpi-virtuell.de</a>
                <a class="button button-secondary" href="<?php  echo $group->get_matrix_link('client')?>"
                   target="_blank">über
                    die Element App</a>
                <br>
                <br>
                <em>Für sichere Kommunikation nutzen wir <b><a
                                href="https://element.io/personal">Element</a></b>,<br/> den Messenger für die
                    Matrix mit vielen Features für professionelle Lerngemeinschaften.</em>
            </details>
            <?php
            Wall\Toolbar::display_toolbar($group, false);
            ?>
            <div class="constituted-post-head">
                <?php $protocols = Wall\protocol::get_protocols($group->ID);
                if (sizeof($protocols) > 0) {
                    ?>
                    <h5>Ergebnisse der Treffen:</h5>
                    <div>
                        <?php foreach ($protocols as $protocol) {
                            $protocol_result = get_field("rpi_wall_protocol_result", $protocol->ID);
                            $publish_result = get_field('rpi_wall_protocol_is_public_result', $protocol->ID);
                            if (!empty($protocol_result) && $publish_result) {
                                ?>
                                <details class="constituted-post-protocol">
                                    <summary><h5><?php echo date('d.m.Y', strtotime($protocol->post_date)) ?></h5>
                                    </summary> <?php
                                    ?>
                                    <p><?php echo $protocol_result ?></p>
                                </details> <?php
                            }
                        } ?>
                    </div>
                <?php } ?>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    public function on_new_comment($comment_id, WP_Comment $comment)
    {

        $group = new Wall\Group($comment->comment_post_ID);
        $url = get_comment_link($comment);


        $replace_data = [
            'search' => [
                '%commentlink%',
                '%commentcontent%'
            ],
            'replace' => [
                '<a href="' . $url . '" class="comment-link">' . $group->title . '</a>',
                $comment->comment_content
            ]
        ];


        if ($comment->user_id > 0) {
            $member = new Wall\Member($comment->user_id);
            $actor = $member->get_link();
        } else {
            $actor = $comment->comment_author;
        }

        new Wall\Message($group, 'comment', null, $actor, $replace_data);
        $currentMember = new Wall\Member();
        if (!$currentMember->is_watched_group($group->ID)) {
            $currentMember->toggle_watch_group($group->ID);
        }

    }

    public function on_new_member(int $post_ID, WP_Post $post, bool $update)
    {
        if (!$update) {

            $member = new Wall\Member($post->post_author);
            $msg = new \stdClass();
            $msg->subject = '[DiBeS]Neues Mitglied ' . $member->name;
            $msg->body = 'Bitte prüfen: ' . $member->get_link();

            Wall\Message::send_messages($orga = [2, 3], $msg);
        }

    }

    public function on_new_pin($form, $post_ID)
    {


        $posts = get_posts(['post_type' => 'wall', 'numberposts' => 1]);
        $post_ID = $posts[0]->ID;


        new Wall\Message(new Wall\Group($post_ID), 'creator', [get_current_user_id()], get_current_user_id());
        //new Wall\Message(new Wall\Group($post_ID), 'create', null, get_current_user_id());
        $currentMember = new Wall\Member();


        if (get_post_meta($post_ID, 'plg_liker', true)) {
            $currentMember->like_group($post_ID);
        } else {
            $currentMember->toggle_watch_group($post_ID);
        }


        ?>
        <script>
            location.href = '/?p=<?php echo $post_ID;?>';
        </script>
        <?php
    }

    public function redirect_to_users_member_page()
    {

        if (strpos($_SERVER['REQUEST_URI'], '/member_profile') !== false) {
            if (is_user_logged_in()) {
                $member = new Wall\Member(wp_get_current_user());
                $user_url = $member->get_member_profile_permalink();
                wp_redirect($user_url);

            } else {
                wp_redirect(wp_login_url());

            }
            die();

        }

    }

    /**
     *
     * @param array $respond
     * @param integer $post_ID
     * @param string $status //like or dislike
     * @param array $args //Anzeigeoptionen für die Likebuttons
     *
     * @return array
     */

    public function wp_ulike_ajax_respond($respond, $post_ID, $status, $args)
    {

        //check  rpi_wall_group_status

        do_action('user_do_like', $post_ID, $status);

        return $respond;
    }

    public function add_group_status_class($classes)
    {
        if ('wall' === get_post_type()) {
            $group = new Wall\Group(get_the_ID());
            $classes[] = $group->get_status();
            if ($is_public = get_post_meta(get_the_ID(), 'public', true)) {
                $classes[] = 'is_public_pin';
            };

        }

        return $classes;
    }

    function display_cards_status_triangle()
    {
        $group = new rpi\Wall\Group(get_the_ID());
        $status = $group->get_status();
        if ($status) {
            echo '<div class="rpi-wall-group-status-triangle ' . $status . '"></div>';
        }
    }

    function display_cards_pin_icon()
    {
        if (get_post_type() === 'wall' && is_archive()) {
            echo '<a href="' . get_post_permalink() . '#pin' . '" class="pin-title-icon pin">' . Wall\Shortcodes::$pin_icon . '</a>';
            $group = new Wall\Group(get_the_ID());
            $status = $group->get_status();
            if ($status || $group->get_likers_amount() > 0) {
                echo '<a href="' . get_post_permalink() . '#group' . '" class="pin-title-icon group">' . Wall\Shortcodes::$group_icon . '</a>';
            }

        }
    }

    function display_cards_watch_icon()
    {
        Wall\Group::display_watcher_area();
    }

    /**
     * blocksy:loop:card:end action
     * @return void
     */

    function display_cards_group_info()
    {

        if (is_post_type_archive('wall')) {
            $group = new rpi\Wall\Group(get_the_ID());
            $group->display_short_info();

        }

    }

    function display_cards_member()
    {

        if (get_post_type() === "member") {
            $user_id = get_the_author_meta('ID');
            $member = new Wall\Member($user_id);
            ob_start();
            ?>
            <div class="member-card">
                <div class="member-card-head">
                    <a href="<?php echo $member->get_member_profile_permalink() ?>">
                        <?php echo get_avatar($user_id) ?>
                    </a>
                    <a href="<?php echo $member->get_member_profile_permalink() ?>">
                        <h4 class="member-card-name">
                            <?php echo $member->name ?>
                        </h4>
                    </a>
                </div>
                <?php $bio = substr(get_the_author_meta('description'), 0, 250);
                if (!empty($bio)) {
                    ?>
                    <div class="member-card-bio">
                        <?php //echo $bio
                        ?>
                    </div>
                <?php } ?>
                <div class="member-card-tags">
                    <?php
                    $taxonomies = get_post_taxonomies(get_the_ID());
                    foreach ($taxonomies as $taxonomy) {
                        $taxonomy_obj = get_taxonomy($taxonomy);
                        $terms = get_the_terms(get_the_ID(), $taxonomy);
                        if (!empty($terms)) {
                            ?>
                            <p class="member-card-taxonomy">
                                <?php echo $taxonomy_obj->label . ': ' ?>
                                <?php
                                foreach ($terms as $term) {
                                    ?>
                                    <a href="<?php echo get_home_url() . '/' . $taxonomy_obj->name . '/' . $term->slug ?>"><?php echo $term->name . ' ' ?></a>
                                    <?php
                                }
                                ?>
                            </p>
                            <?php
                        }
                    }
                    ?>
                </div>

            </div>

            <?php
            echo ob_get_clean();
        }
    }

    public function has_group()
    {
        return (bool)$this->get_group_status();
    }

    public function get_group_status()
    {

        return get_post_meta(get_the_ID(), ' rpi_wall_group_status', true);

    }

    public function custom_style_and_scripts()
    {
        wp_enqueue_style('tabs', plugin_dir_url(__FILE__) . 'assets/css/tabs.css');

        wp_enqueue_style('rpi-wall-style', plugin_dir_url(__FILE__) . 'assets/css/custom-style.css');
        wp_enqueue_style('rpi-wall-style-modal-norm', plugin_dir_url(__FILE__) . 'assets/css/normalize.min.css');
        wp_enqueue_style('rpi-wall-style-modal-anim', plugin_dir_url(__FILE__) . 'assets/css/animate.min.css');
        wp_enqueue_style('rpi-wall-style-termin-calender', plugin_dir_url(__FILE__) . 'assets/css/termin-calender.css');
        wp_enqueue_script('rpi-wall-script-termin-calender', plugin_dir_url(__FILE__) . 'assets/js/termin-calender.js', array('jquery'));
        wp_enqueue_script('rpi-wall-style-modal', plugin_dir_url(__FILE__) . 'assets/js/animatedModal.js', array('jquery'));
        wp_enqueue_script('rpi-wall-script', plugin_dir_url(__FILE__) . 'assets/js/custom-scripts.js', array('jquery'), false, true);
        wp_localize_script('rpi-wall-script', 'wall', array('ajaxurl' => admin_url('admin-ajax.php')));


    }

    function test()
    {
        if (isset($_GET['admin_test'])) {
            foreach ([3, 4, 5] as $user_id) {
                $member = new Wall\Member($user_id);
                $member->like_group(41);
                $member->like_group(46);
                $member->like_group(55);
            }
            foreach ([5, 6] as $user_id) {
                $member = new Wall\Member($user_id);
                $member->like_group(72);
                $member->like_group(478);

            }

            $member = new Wall\Member(6);
            $member->like_group(480);
        }
    }

    public function initialize_message_counter()
    {
        if (is_user_logged_in()) {
            $member = new Wall\Member();
            $message_count = $member->get_unread_messages_count();
            /*
                        echo '<script>' .
                             'var mc='.$message_count.'; ' .
                             'var src = jQuery(".ct-button.message-bell img").attr("src"); ' .
                             'if(mc>0) ' .
                             '  src = src.replace("bell.png", "bell_red.png");' .
                             'else ' .
                             '  src = src.replace("bell_red.png", "bell.png");' .
                             'jQuery(".ct-button.message-bell img").attr("src", src);' .
                             '</script>';
            */

            ?>
            <script>
                var rpi_wall_bell = '<?php echo plugin_dir_url(__FILE__) . '/assets/img/bell.png' ?>';
                var rpi_wall_bell_red = '<?php echo plugin_dir_url(__FILE__) . '/assets/img/bell_red.png' ?>';
                var rpi_wall_message_btn = jQuery(".ct-button.message-bell");
                var rpi_wall_message_count = <?php echo $message_count; ?>;
                if (rpi_wall_message_count > 0) {
                    rpi_wall_message_btn.html('<div><img src="' + rpi_wall_bell_red + '"></div><div id="message-count">' + rpi_wall_message_count + '</div>');
                } else {
                    rpi_wall_message_btn.html('<div><img src="' + rpi_wall_bell + '"></div><div id="message-count"></div>');
                }
            </script>
            <?php


            if ($message_count == "0") {
                $message_count = "";
            }
            echo '<script>jQuery(document).ready($ => { $("#message-count").html("' . $message_count . '")})</script>';


        }
    }
}


new RpiWall();
new Wall\RpiWallAjaxHandler();
new MemberPage();
new Wall\Shortcodes();
new RpiWallFormHandler();


