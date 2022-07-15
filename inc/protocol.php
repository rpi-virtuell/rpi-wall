<?php

namespace rpi\Wall;

class protocol {


	public function __construct() {


			add_filter( 'acf/load_field/name=teilnehmende', [ $this, 'acf_load_member' ] );
			add_filter( 'acf/load_field/name=group_ID', [ $this, 'acf_load_goupid' ] );
			add_filter( 'acf/load_field/name=autor', [ $this, 'acf_load_autor' ] );
			add_shortcode( 'protokoll', [ $this, 'display_protokoll_form' ] );
			add_action('acf_frontend/save_post', [ $this, 'on_acf_submit_form'], 10, 2);

	}


	static function display_protokoll_form() {

		if ( $group = self::get_group() ) {
			echo "Protokoll der Gruppe " . $group->title . " " . date( 'd.m.Y' );
			echo do_shortcode( '[frontend_admin form="975"]' );
		}
	}

	static function get_group(){
		if(isset($_GET['plg_id'])) {
			$group_id = intval( $_GET['plg_id'] );

			if ( $group_id > 0 ) {
				$group = new Group( $group_id );

				if ( $group ) {
					return $group;
				}
			}
		}
		return false;
	}

	static function acf_load_member($field){
		$field['choices'] = array();

		if($group = self::get_group()){

			foreach ($group->get_members() as $member){

				$field['choices'][ $member->ID ] = $member->name;
			};

		}

		return $field;
	}

	static function acf_load_goupid($field){
		if($group = self::get_group()){

			$field['default_value'] =$group->ID;
			return $field;
		}


	}
	static function acf_load_autor($field){


			$user = wp_get_current_user();
			if(is_a($user ,'\WP_User' )){
				$field['default_value'] = $user->display_name;
			}
			return $field;


	}

	function on_acf_submit_form( $form, $post_id) {
//
//		if($form['id'] == 975){
//			$post = get_post($post_id);
//
//			$group_id = get_field('');
//			//$post->title  = "Protokoll der Gruppe " . $group->title . " " . date( 'd.m.Y' );
//		}
//
//
//
//
//		if(is_admin()) 			return;
//


	}

}
new protocol();
