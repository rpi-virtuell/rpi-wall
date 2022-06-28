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
require_once("shortcodes.php");
require_once("inc/member.php");
require_once("inc/group.php");
require_once("inc/tabs.php");
require_once("inc/member-page.php");
require_once("inc/matrix-helper.php");
require_once("inc/message.php");


use rpi\Wall;

class RpiWall
{

    protected $max_stars_per_comment = 5;
    protected $group_member_min = 3;

    public function __construct()
    {


        add_action('wp_enqueue_scripts', [$this, 'custom_style_and_scripts']);

        add_filter('body_class', [$this, 'add_group_status_class']);
	    add_action('post_class', [$this, 'add_group_status_class']);

        add_action('blocksy:comments:after', [$this, 'display_likers_container']);


	    add_action('blocksy:loop:card:start', [$this, 'display_cards_status_triangle']);
	    add_action('blocksy:loop:card:end', [$this, 'display_cards_group_info']);


        add_filter('wp_ulike_ajax_respond', [$this, 'wp_ulike_ajax_respond'], 20, 4);


	    //incomming
	    add_action('init', ['rpi\Wall\Group', 'init_handle_requests']);

	    add_action('init', ['rpi\Wall\Group', 'init_cronjob']);

        add_action('init', ['rpi\Wall\member', 'init_handle_request']);

		add_action('init', [$this, 'test']);

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

        //check  rpi_wall_group_status

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


	function display_cards_status_triangle(){
		$group = new rpi\Wall\Group(get_the_ID());
		$status = $group->get_status();
		if($status){
			echo '<div class="rpi-wall-group-status-triangle '.$status.'"></div>';
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
        $group = new rpi\Wall\Group(get_the_ID());
        $group->display();
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
        wp_enqueue_script('rpi-wall-script', plugin_dir_url(__FILE__) . 'assets/js/custom-scripts.js', array('jquery'));
	    wp_localize_script( 'rpi-wall-script', 'wall', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

	    wp_enqueue_style('tabs', plugin_dir_url(__FILE__) . 'assets/css/tabs.css');

    }

	function test(){

		if (isset($_GET['admin_test'])) {
			foreach ([3,4,5] as $user_id){
				$member = new Wall\member($user_id);
				$member->like_group(41);
				$member->like_group(46);
				$member->like_group(55);
			}
			foreach ([5,6] as $user_id){
				$member = new Wall\member($user_id);
				$member->like_group(72);
				$member->like_group(478);

			}

			$member = new Wall\member(6);
			$member->like_group(480);
		}


	}
}

new RpiWall();
new MemberPage();
new Wall\RPIWallInstaller();
new Wall\Shortcodes();
