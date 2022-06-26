<?php
/*
Plugin Name: Pinnwand
Plugin URI: https://github.com/rpi-virtuell/rpi-material-input-template
Description: Wordpress Pinnwand Plugin
Version: 1.0
Author: joachim Happel
Author URI: https://github.com/johappel
License: A "Slug" license name e.g. GPL2
*/

require_once("rpi-wall-installer.php");
require_once("shortcodes.php");
require_once("inc/member.php");
require_once("inc/group.php");
require_once("inc/matrix-helper.php");
require_once("inc/message.php");


use rpi\Wall;

class RpiWall
{

    /**
     * wp_ulike_get_meta_data_default
     * is_wp_ulike
     *
     * function wp_ulike_get_popular_items_info( $args = array() ){
     *
     * $defaults = array(
     * "type"       => 'post',
     * "rel_type"   => 'wall',
     * "status"     => 'like',
     * "user_id"    => '',
     * "order"      => 'DESC',
     * "is_popular" => true,
     * "period"     => 'all',
     * "offset"     => 1,
     * "limit"      => 10
     * );
     *
     * wp_ulike_get_popular_items_ids
     *
     * wp_ulike_get_likers_list_per_post
     *
     * wp_ulike_get_best_likers_info
     * wp_ulike_is_user_liked
     */


    protected $max_stars_per_comment = 5;
    protected $group_member_min = 3;

    public function __construct()
    {


        add_action('wp_enqueue_scripts', [$this, 'custom_style_and_scripts']);

        add_filter('body_class', [$this, 'add_group_status_class']);
	    add_action('post_class', [$this, 'add_group_status_class']);

        add_action('blocksy:comments:after', [$this, 'display_likers_container']);


	    add_action('blocksy:loop:card:end', [$this, 'display_cards_group_info']);


        add_filter('wp_ulike_ajax_respond', [$this, 'wp_ulike_ajax_respond'], 20, 4);


        add_action('init', ['rpi\Wall\Group', 'init_cronjob']);
        //incomming
        add_action('init', ['rpi\Wall\Group', 'init_handle_requests']);

        add_action('init', ['rpi\Wall\member', 'init_handle_request']);

	    add_action('wp_ajax_rpi_wall_toggle_like',[$this,'ajax_toggle_group_like'] );
	    add_action('wp_ajax_nopriv_rpi_wall_toggle_like',[$this,'ajax_toggle_group_like'] );


        add_action('blocksy:loop:before', function (){
			echo '<div class="rpi-wall-buttons">';
			echo do_shortcode('[frontend_admin form="28"]');
			echo '</div>';
        });



    }

	public function ajax_toggle_group_like(){


		$response = ['success'=>false];
		if(isset($_POST['group_id'])){
			$group = new Wall\Group($_POST['group_id']);
			if($group && $group->is_not_founded()){

				$member = new Wall\member();
				if($member->is_in_group($group->ID)){
					echo json_encode($response);
					die();
				}

				if($group->is_pending()){
					$member->join_group($group->ID);
					$is_member = $member->is_in_group($group->ID);
					$amount =$group->get_members_amount();
					$is_liker = true;
				}else{

					$member->toggle_like_group($group->ID);
					$is_liker = $member->is_liked_group($group->ID);
					$amount =$group->get_likers_amount();
					$is_member = false;
				}

				$response = [
					'success'   => true,
					'is_liker'  => $is_liker,
					'is_member' => $is_member,
					'amount'    => $amount,
					'likers'    => $group->display_liker(),
					'members'   => $group->display_member()
				];

			}

		}
		echo json_encode($response);
		die();


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

        //check pl_group_status

        do_action('user_do_like', $post_ID, $status);

        return $respond;
    }


	public function add_group_status_class( $classes ){
		if('wall'=== get_post_type()){
			$group = new Wall\Group(get_the_ID());
			$classes[]= $group->get_status();
		}

		return $classes;
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
        $group = new rpi\Wall\Group(get_the_ID());
        $group->display();
    }


    public function get_group_status()
    {

        return get_post_meta(get_the_ID(), 'pl_group_status', true);

    }

    public function has_group()
    {
        return (bool)$this->get_group_status();
    }

    public function custom_style_and_scripts()
    {
        wp_enqueue_style('rpi-wall-style', plugin_dir_url(__FILE__) . 'assets/css/custom-style.css');
        wp_enqueue_script('rpi-wall-script', plugin_dir_url(__FILE__) . 'assets/js/custom-scripts.js', array('jquery'));
	    wp_localize_script( 'rpi-wall-script', 'wall', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

    }


}

new RpiWall();
new Wall\RPIWallInstaller();
new Wall\Shortcodes();
