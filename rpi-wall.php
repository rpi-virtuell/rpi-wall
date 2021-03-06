<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/johappel
 * @since             1.0.0
 * @package           Rpi_Wall
 *
 * @wordpress-plugin
 * Plugin Name:       rpi Pinnwand
 * Plugin URI:        https://github.com/rpi-virtuell/rpi-wall/
 * Description:       Wordpress Pinnwand Plugin entwickelt für das Projekt DiBeS
 * Version:           1.0.0
 * Author:            Joachim Happel
 * Author URI:        https://github.com/johappel
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rpi-wall
 * Domain Path:       /languages
 */

require_once("rpi-wall-installer.php");
require_once("rpi-wall-ajax-handler.php");
require_once("shortcodes.php");
require_once("inc/member.php");
require_once("inc/group.php");
require_once("inc/tabs.php");
require_once("inc/member-page.php");
require_once("inc/message.php");
require_once("inc/protocol.php");
require_once("inc/matrix.php" );
require_once("inc/toolbar.php");

use rpi\Wall;
use rpi\Wall\Message;


class RpiWall
{

    protected $max_stars_per_comment = 5;
    protected $group_member_min = 3;
    protected $installer;
    public $matrix;

    public function __construct()
    {

	    add_action('wp_enqueue_scripts', [$this, 'custom_style_and_scripts']);

        add_filter('body_class', [$this, 'add_group_status_class']);
        add_action('post_class', [$this, 'add_group_status_class']);

	    add_action('blocksy:content:top', function () {
            if (is_post_type_archive('wall') && current_user_can('publish_walls')) {
                RpiWall::modal('form', 'Neuer Eintrag', do_shortcode('[acfe_form name="create-pin"]'));

            } else {

                if (get_post_type() === 'wall' && is_user_logged_in()) {
                    if (get_post()->post_author == get_current_user_id() || current_user_can('edit_others_walls')) {

                        RpiWall::modal('form', 'Bearbeiten', do_shortcode('[acfe_form name="edit-pin"]'));
                    }
                }
                if (get_post_type() === 'protokoll') {
                    RpiWall::modal('protocol', 'Bearbeiten', do_shortcode('[acfe_form name="edit-protocol"]'));
                }
                if (is_post_type_archive('member') && !is_user_logged_in()) {
                    wp_footer();
                    die();
                }
            }


	    });

        add_filter( 'acf/load_field/name=matrixid', ['rpi\Wall\Member', 'set_default_matrixId' ] );

        //Toolbar
	    add_filter('body_class', ['\rpi\Wall\Toolbar','add_toolbar_class_to_body']);
        add_action('wp_body_open', ['rpi\Wall\Toolbar', 'display_toolbar']);

        add_action('acfe/form/submit/form=constitution', function ($form, $post_id){
           Wall\Toolbar::update_toolbar_status($form,$post_id,'constituted');
        },10 ,2);

        add_action('acfe/form/submit/form=constitution_date', function ($form, $post_id){
            do_action('new_meeting_date', get_post_meta($post_id ,'date_of_meeting', true),  $post_id);
            Wall\Toolbar::update_toolbar_status($form,$post_id,'meeting_planned');
        },10 ,2);

        // Pin Display

//        add_action('blocksy:hero:title:before',[$this,'display_constituted_group_title']);

        add_action('blocksy:hero:before', [$this, 'add_tabs_to_pin_view']);


        add_action('blocksy:hero:before', ['rpi\Wall\Group', 'display_watcher_area']);
        add_action('blocksy:comments:after', [$this, 'display_likers_container']);

        // Pinboard Carddisplay

        add_action('blocksy:loop:card:start', [$this, 'display_cards_status_triangle']);
        add_action('blocksy:loop:card:end', [$this, 'display_cards_group_info']);

        add_action('blocksy:loop:card:start', [$this, 'display_cards_member']);

        add_filter('wp_ulike_ajax_respond', [$this, 'wp_ulike_ajax_respond'], 20, 4);

        //incomming
        add_action('init', ['rpi\Wall\Group', 'init_handle_requests']);

        add_action('init', ['rpi\Wall\Group', 'init_cronjob']);

        add_action('init', ['rpi\Wall\Member', 'init_handle_request']);
        add_action('init', ['rpi\Wall\Member', 'init_cronjob'],5);

        add_action('wp', [$this, 'redirect_to_users_member_page']);

        // TODO USED FOR DEBUG NEEDS TO BE DELETED BEFORE LAUNCH
        add_action('init', [$this, 'test']);

        /**
	     * ToDo add to cronjob
	     */
        add_action('wp_head', function (){
            global $post;
            if($post->post_type == 'wall'){
                $this->installer->sync_taxonomies_of_pin_members($post->ID, $post,false);
            }
            if($post->post_type == 'member'){
                 $this->installer->sync_taxonomies_of_members($post->ID , $post,false);
            }

        });


        add_action('save_post_wall', [$this, 'on_new_pin'], 10, 3);
        add_action('save_post_member', [$this, 'on_new_member'], 10, 3);
        add_action('wp_insert_comment', [$this, 'on_new_comment'], 99, 2);


	    add_filter('acf/load_field/name=display_name', function($field){
			$user = get_userdata(get_current_user_id());

		    $field['choices'] = array();
		    $field['choices'][$user->nickname] = $user->nickname;
		    $field['choices'][$user->user_login] = $user->user_login;
		    $field['choices'][$user->first_name] = $user->first_name .' '. $user->last_name;

			return $field;

	    }, 10, 1);

        $this->installer = new Wall\RPIWallInstaller();

//        add_action('init', function (){
//	        if(get_current_user_id()==2 && is_singular('wall')){
//		        $matrix = new Wall\Matrix();
//		        $matrix->tests(get_the_ID());
//	        }
//        });


    }

    public function add_tabs_to_pin_view()
    {
        if (is_singular('wall')) {
            $group = new Wall\Group(get_the_ID());
            if ($group->get_toolbar_status() === "constituted"){
                $tabs = new \rpi\Wall\Tabs('tabset');

                $tabs->addTab(['label' => 'Pin', 'name' => 'pin', 'content' => '<div id ="rpi_tab_pin_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$pin_icon, 'checked' => true]);
                $tabs->addTab(['label' => 'Gruppe', 'name' => 'group', 'content' => '<div id ="rpi_tab_group_content"></div>', 'icon' => \rpi\Wall\Shortcodes::$group_icon]);

                echo '<script>var rpi_wall ={user_ID: "' . get_the_author_meta("ID") . '"};</script>';
                echo '<script>rpi_wall.allowedtabs = ' . json_encode($tabs->get_allowed_tabs()) . ';</script>';

                $tabs->display();
            }
        }
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
            $actor =  $member->get_link();
        } else {
            $actor = $comment->comment_author;
        }

        new Wall\Message($group, 'comment', null, $actor, $replace_data);
	    $currentMember = new Wall\Member();
	    if(!$currentMember->is_watched_group($group->ID)){
		    $currentMember->toggle_watch_group($group->ID);
	    }

    }

    public function on_new_member(int $post_ID, WP_Post $post, bool $update)
    {
        if (!$update) {

	        $member = new Wall\Member($post->post_author);
	        $msg = new \stdClass();
			$msg->subject = '[DiBeS]Neues Mitglied '.$member->name;
			$msg->body = 'Bitte prüfen: '.$member->get_link();

            Wall\Message::send_messages($orga =[2,3], $msg);
        }

    }

    public function on_new_pin(int $post_ID, WP_Post $post, bool $update)
    {
        if (!$update) {

            new Wall\Message(new Wall\Group($post_ID), 'create', null, get_current_user_id());

			$currentMember = new Wall\Member();
			if(!$currentMember->is_watched_group($post_ID)){
				$currentMember->toggle_watch_group($post_ID);
	        }
        }

    }

    public function redirect_to_users_member_page()
    {

        if (strpos($_SERVER['REQUEST_URI'], '/member_profile') !== false) {
	        if(is_user_logged_in()){
		        $member = new Wall\Member(wp_get_current_user());
		        $user_url = $member->get_member_profile_permalink();
		        wp_redirect($user_url);

	        }else{
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

    /**
     * blocksy:loop:card:end action
     * @return void
     */

    function display_cards_group_info()
    {

        $group = new rpi\Wall\Group(get_the_ID());
        $group->display_short_info();

    }

    function display_likers_container()
    {
        if(is_singular('wall')){
	        $group = new rpi\Wall\Group(get_the_ID());
	        $group->display();
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
                <?php $bio = substr(get_the_author_meta('description'),0, 250);
                if(!empty($bio)){?>
                <div class="member-card-bio">
                    <?php echo $bio ?>
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

	/**
     * öffnet den Inhalt in einem Overlay Popup
     *
	 * @param string $id
	 * @param string $content
	 *
	 * @return void
	 */
    static function modal($id = 'form', $label='Bearbeiten' ,$content =''){

        ?>
	    <div class="ct-container rpi-wall-buttons">
	        <a class="fea-submit-button button button-primary" id="btn-open-modal-<?php echo $id;?>" href="#modal-<?php echo $id;?>"><?php echo $label;?></a>
        </div>
        <div id="modal-<?php echo $id;?>">
            <div  class="modal-wrapper">
	            <div  id="btn-close-modal" class="close-modal-<?php echo $id;?>">
                    <button class="button button-primary">X</button>
                </div>
                <div class="modal-content"><?php echo $content ?></div>
	            <?php  the_taxonomies() ;  ?>

            </div>
        </div>
        <script>jQuery("#btn-open-modal-<?php echo $id;?>").animatedModal();</script>

        <?php

    }

    public function get_group_status()
    {

        return get_post_meta(get_the_ID(), ' rpi_wall_group_status', true);

    }

    public function has_group()
    {
        return (bool)$this->get_group_status();
    }



    public function custom_style_and_scripts()
    {
        wp_enqueue_style('rpi-wall-style', plugin_dir_url(__FILE__) . 'assets/css/custom-style.css');
        wp_enqueue_style('rpi-wall-style-modal-norm', plugin_dir_url(__FILE__) . 'assets/css/normalize.min.css');
        wp_enqueue_style('rpi-wall-style-modal-anim', plugin_dir_url(__FILE__) . 'assets/css/animate.min.css');
        wp_enqueue_script('rpi-wall-script', plugin_dir_url(__FILE__) . 'assets/js/custom-scripts.js', array('jquery'));
        wp_enqueue_script('rpi-wall-style-modal', plugin_dir_url(__FILE__) . 'assets/js/animatedModal.js', array('jquery'));
        wp_localize_script('rpi-wall-script', 'wall', array('ajaxurl' => admin_url('admin-ajax.php')));


        wp_enqueue_style('tabs', plugin_dir_url(__FILE__) . 'assets/css/tabs.css');

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


}


new Wall\RpiWallAjaxHandler();
new RpiWall();
new MemberPage();
new Wall\Shortcodes();


