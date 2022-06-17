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

require_once ("inc/group.php");
require_once ("inc/member.php");
require_once ("inc/matrix-helper.php");
require_once ("inc/message.php");
require_once ("member-installer.php");


class RpiWall{

	/**
	 * wp_ulike_get_meta_data_default
	 * is_wp_ulike
	 *
	 * function wp_ulike_get_popular_items_info( $args = array() ){

		$defaults = array(
			"type"       => 'post',
			"rel_type"   => 'wall',
			"status"     => 'like',
			"user_id"    => '',
			"order"      => 'DESC',
			"is_popular" => true,
			"period"     => 'all',
			"offset"     => 1,
			"limit"      => 10
		);
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

	public function __construct() {

		add_shortcode( 'user_pinned_posts', [$this,'wp_ulike_pro_get_current_user_pinned_posts'] );

		add_action( 'wp_enqueue_scripts', [$this,'custom_style_and_scripts'] );
		add_action( 'init', [$this,'test'] );


		add_action( 'wp_ulike_before_template', [$this,'display_group_header']);
		add_action( 'wp_ulike_after_template', [$this,'display_group_footer']);

		add_action( 'blocksy:comments:after', function (){
			echo do_shortcode('[wp_ulike  style="wpulike-heart"]');
		} );

		add_action( 'blocksy:loop:card:end', function (){
			echo '<div class="plg-wrapper">';
			//ToDo gruppe ermitteln
			$group_status =get_post_meta(get_the_ID(),'status_pl_group', true);
			if($group_status){
				if($group_status == 'founded'){
					echo '<div class="plg plg-exists">Eine PLG wurde gegründet</div>';
				}elseif($group_status == 'pending'){
					echo '<div class="plg plg-exists">Gründungsprozess gestartet</div>';
				}


			}else{
				$likers = wp_ulike_get_likers_list_per_post('ulike','likers_list',get_the_ID(),10);
				$counted = count($likers);

				if($counted==0) return;

				if($counted >= $this->group_member_min){
					echo '<div class="plg plg-ready">'.$counted.' Interessierte: <a href="'.get_the_permalink().'">PLG gründen</a>?</div>';

				}else{
					echo '<div class="plg">';
					echo sprintf("An PLG interessiert: <b>%s</b>", $counted .'/'.$this->group_member_min);
					echo '</div>';
				}
			}
			$likes = 0;

			foreach (get_comments([ 'post_id' => get_the_ID()]) as $comment){
				$likes += wp_ulike_get_comment_likes($comment->comment_ID);
			}
			$max_likes = $this->max_stars_per_comment;
			if($likes>0){
				$z = $likes;
				if($likes > $max_likes) {
					$z = $max_likes;
					$addlikes = $likes - $max_likes;

					echo '<style>#more-likes-'.get_the_ID().'::after{ content: "+' . $addlikes . '";}</style>';
				}

				echo '<div class="hot-comments">';
				for($i=0;$i<$z; $i++){
					echo '<i id="more-likes-'.get_the_ID().'" class="wp_ulike_star_icon ulp-icon-star"></i>';

				}


				echo '</div>';
			}
			echo '</div>';

		} );


		add_filter( 'wp_ulike_ajax_respond', [$this, 'wp_ulike_ajax_respond'], 20, 4 );
	}

	/**
	 *
	 * @param array $respond
	 * @param integer $post_ID
	 * @param string $status   //like or dislike
	 * @param array $args        //Anzeigeoptionen für die Likebuttons
	 * @return array
	 */

	public function wp_ulike_ajax_respond($respond, $post_ID, $status, $args){

		//check pl_group_status

		do_action ('user_do_like',$post_ID, $status);

		return $respond;
	}




	public function test(){

		//var_dump('<pre>',wp_ulike_get_popular_items_ids(["user_id"=>get_current_user_id(),"rel_type"=>"wall"]));die();
		//var_dump('<pre>',wp_ulike_get_popular_items_ids(["rel_type"=>"wall"]));die();
		//var_dump('<pre>',wp_ulike_get_likers_list_per_post('ulike','likers_list',55,1999));die();
	}

	/**
	 * blocksy:loop:card:end action
	 * @return void
	 */
	public function display_group(){

	}

	/**
	 * wp_ulike_before_template action
	 *
	 * @param $wp_ulike_template
	 *
	 * @return void
	 */
	public function display_group_header($wp_ulike_template){
		if($wp_ulike_template['slug']=='post'){
			echo '<div class="gruppe-header">Interesse, hierzu eine PLG zu gründen?</div><div class="gruppe">';
		}
	}

	/**
	 * wp_ulike_after_template action
	 *
	 * @param $wp_ulike_template
	 *
	 * @return void
	 */
	public function display_group_footer($wp_ulike_template){

		if($wp_ulike_template['slug']=='post') {
			echo '</div>';
			if($wp_ulike_template['total_likes']>=$this->group_member_min && $this->has_group()){
				echo '<div class="gruppe-footer"><button class="button">PLG jetzt gründen</button></div>';
			}
		}
	}

	public function get_group_status(){

		return get_post_meta(get_the_ID(),'pl_group_status', true);

	}

	public function has_group(){
		return (bool) $this->get_group_status();
	}

	public function custom_style_and_scripts(){
		wp_enqueue_style( 'rpi-wall-style', plugin_dir_url(__FILE__).'assets/css/custom-style.css' );
		wp_enqueue_script( 'rpi-wall-scripts', plugin_dir_url(__FILE__).'assets/js/custom-scripts.js', array(), '1.0.0', true );


	}

	function wp_ulike_pro_get_current_user_pinned_posts($atts){

		global $post;

		$attributes = shortcode_atts( array(
			'post_type' => 'wall',
			'past_days' => '',
			'per_page'  => 10
		), $atts );

		if( strpos( $attributes['post_type'], ',' ) ){
			$attributes['post_type'] = explode(',', $attributes['post_type']);
		}

		$currentUser = is_user_logged_in() ? get_current_user_id() : wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
		$getPosts    = NULL;

		if( empty( $attributes['past_days'] ) ){
			$pinnedItems = wp_ulike_get_meta_data( $currentUser, 'user', 'post_status', true );
			// Exclude like status
			$pinnedItems = ! empty( $pinnedItems ) ? array_filter($pinnedItems, function($v, $k) {
				return $v == 'like';
			}, ARRAY_FILTER_USE_BOTH) : NULL;

			if( ! empty( $pinnedItems ) ){
				$getPosts = get_posts( array(
					'post_type'      => $attributes['post_type'],
					'post_status'    => array( 'publish', 'inherit' ),
					'posts_per_page' => $attributes['per_page'],
					'post__in'       => array_reverse( array_keys( $pinnedItems ) ),
					'orderby'        => 'post__in'
				) );
			}

		} else {
			$getPosts = wp_ulike_get_most_liked_posts( $attributes['per_page'], $attributes['post_type'], 'post', array(
				'start' => wp_ulike_pro_get_past_time( $attributes['past_days'] ),
				'end'   => current_time( 'mysql' )
			), array( 'like' ), false, 1, $currentUser );
		}



        echo '<div class="wp-ulike-pro-items-container user_pinned_posts">';
		if( ! empty( $getPosts ) ){
			foreach ( $getPosts as $post ) :
				setup_postdata( $post );
                blocksy_render_archive_card();

			endforeach;
			wp_reset_postdata();

		}
        echo '</div>';



	}
}
new RpiWall();
new MemberInstaller();
