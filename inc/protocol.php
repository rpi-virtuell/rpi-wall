<?php

namespace rpi\Wall;

class protocol {


	public function __construct() {


			add_filter( 'acf/load_field/name=rpi_wall_protocol_leader', [ $this, 'acf_load_member' ] );
			add_filter( 'acf/load_field/name=teilnehmende', [ $this, 'acf_load_member' ] );
			add_filter( 'acf/load_field/name=gruppenname', [ $this, 'acf_load_groupname' ] );
			add_filter( 'acf/load_field/name=date_of_meeting', [ $this, 'acf_load_date_of_meeting' ] );
			add_filter( 'acf/load_field/name=group_ID', [ $this, 'acf_load_goupid' ] );
			add_filter( 'acf/load_field/name=rpi_wall_protocol_groupid', [ $this, 'acf_load_goupid' ] );
			add_filter( 'acf/load_field/name=autor', [ $this, 'acf_load_autor' ] );
			add_shortcode( 'protokoll', [ $this, 'display_protokoll_form' ] );
			add_action('acfe/form/submit/post/form=create-protocol', [ $this, 'on_acf_submit_new_protocol'], 10, 5);
			add_action('acfe/form/submit/post/form=edit-protocol', [ $this, 'on_acf_update_protocol'], 10, 5);

			add_filter('the_content', [ $this, 'display'],);
			add_filter('the_title', [ $this, 'display_title'],10,2);
	}

	/**
	 * @param $group_id
	 *
	 * @return int[]|\WP_Post[]
	 */
	static function get_protocols($group_id){

		$args = [
			'post_type'=>'protokoll',
			'numberposts' => -1,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'rpi_wall_protocol_groupid',
					'value' => $group_id,
					'compare' => '=',
					'type' => 'NUMERIC'
				],
				[
					'key' => 'rpi_wall_protocol_groupid',
					'compare' => 'EXISTS',
				]
			]
		];
		return get_posts($args);

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
		}elseif(get_the_ID()>0){

			if('wall'===get_post_type()){
				return new Group(get_the_ID());
			}elseif ('protokoll'=== get_post_type()){

				$group_id = get_post_meta(get_the_ID(),'rpi_wall_protocol_groupid', true);
				if(intval($group_id)>0){
					return new Group( $group_id );
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
	static function acf_load_groupname($field){
		if($group = self::get_group()){

			$field['default_value'] = $group->post->post_title;


		}

		return $field;
	}

	static function acf_load_goupid($field){
		if($group = self::get_group()){

			$field['default_value'] =$group->ID;

		}
		return $field;

	}
	static function acf_load_date_of_meeting($field){
		if($group = self::get_group()){

			$field['default_value'] = get_post_meta($group->ID,'date_of_meeting', true);

		}
		return $field;

	}
	static function acf_load_autor($field){


			$user = wp_get_current_user();
			if(is_a($user ,'\WP_User' )){
				$field['default_value'] = $user->display_name;
			}
			return $field;


	}

	/**
	 * ToDo messages on actions:  new_group_protocol,  update_group_protocol, new_meeting_date
	 *
	 */

	/**
	 * @param $post_id
	 * @param $type
	 * @param $args
	 * @param $form
	 * @param $action
	 *
	 * @return void
	 */

	function on_acf_update_protocol( $post_id, $type, $args, $form, $action) {

		$group_id = get_field('rpi_wall_protocol_groupid', $post_id);
		if($group_id) {

			$next_date = get_post_meta($post_id,'rpi_wall_protocol_orga_next_meeting_date',true);

			if(time() < strtotime($next_date)){
				update_post_meta($group_id,'date_of_meeting', $next_date );
				do_action('new_meeting_date', $next_date, $group_id );
			}elseif(!$next_date){
				delete_post_meta($group_id,'date_of_meeting');
			}
			do_action('update_group_protocol', $post_id,  $group_id );
		}
	}

	function on_acf_submit_new_protocol( $post_id, $type, $args, $form, $action) {


		$post = get_post($post_id);

		$group_id = get_field('rpi_wall_protocol_groupid', $post_id);

		if($group_id) {
			$group = new Group( $group_id );
			$post->post_title  = $group->title . " [" . date( 'd.m.Y' )."]";
			$post->post_status = 'publish';

			wp_update_post($post);

			$next_date = get_post_meta($post_id,'rpi_wall_protocol_orga_next_meeting_date',true);

			$old_date = get_post_meta($group_id,'date_of_meeting', true);
			if($old_date){
				if($old_date && strtotime($old_date) < strtotime($next_date)){
					update_post_meta($group_id,'date_of_meeting', $next_date );

					do_action('new_meeting_date', $next_date, $group_id );
				}
			}else{
				update_post_meta($group_id,'date_of_meeting', $next_date );
			}

			do_action('new_group_protocol', $post_id,  $group_id );
		}
	}

	static function display_title($title, $post_id){
		if(is_singular('protokoll') && 'protokoll' === get_post_type($post_id)){

			return ''.get_field("constitution_gruppenname",get_field('rpi_wall_protocol_groupid')).': Protokoll vom '.get_the_date('d.m.Y');

		}
		return $title;
	}
	static function display($content){

		$fields = (get_field_objects());
		//var_dump('<pre>',$fields); die();


		if('protokoll' === get_post_type()){

			$content .= '<div class="protokoll-container">';

			$members = get_field('teilnehmende');
			$content .= '<h3>'.$fields['teilnehmende']['label'].':</h3> <ul>';
			foreach ($members as $member_id){
				$member = new Member($member_id);
				$content .= '<li>'.$member->get_link().'</li>';
			}
			$content .= '</ul>';

			$content .= '</div>';

			$content .= '<h2>Teilziel</h2>';

			$content .= '<div class="protokoll-container">';

			$c = get_field('rpi_wall_protocol_meeting_goal');
			$content .= '<h3>'.$fields['rpi_wall_protocol_meeting_goal']['label'].':</h3>';
			$content .= '<p>'.nl2br($c).'</p>';


			$c = get_field('rpi_wall_protocol_agenda');
			$content .= '<h3>'.$fields['rpi_wall_protocol_agenda']['label'].':</h3>';
			$content .= ''.$c.'';


			$content .= '</div>';

			$content .= '<h2>Reflexion</h2>';

			$content .= '<div class="protokoll-container">';



			$content .= '<h3>'.$fields['rpi_wall_protocol_was_hat_gut_geklappt']['label'].'</h3>';
			$content .= '<div>'.nl2br(get_field('rpi_wall_protocol_was_hat_gut_geklappt')).'</div>';

			$content .= '<h3>'.$fields['rpi_wall_protocol_was_wollen_wir_verbessern']['label'].'</h3>';
			$content .= '<div>'.nl2br(get_field('rpi_wall_protocol_was_wollen_wir_verbessern')).'</div>';



			$b = get_field('rpi_wall_protocol_is_schoolwork_sighted');
			if($b){
				$label = $fields['rpi_wall_protocol_is_schoolwork_sighted']['message']. '? Ja';
				$c = '<p>'.get_field('rpi_wall_protocol_schoolwork_notices').'</p>';
			}else{
				$c = '';
				if(isset($fields['rpi_wall_protocol_is_schoolwork_sighted']['message'])){
					$label = $fields['rpi_wall_protocol_is_schoolwork_sighted']['message']. '? Nein';
				}else{
					$label = $c;
				}

			}
			if($label){
				$content .= '<h3>'.$label.'</h3>'.nl2br($c);
			}


			$content .= '</div>';
			$content .= '<h2>Absprachen</h2>';
			$content .= '<div class="protokoll-container">';


			$rows = get_field('rpi_wall_protocol_orga');
			$content .= '<h3>'.$fields['rpi_wall_protocol_orga']['label'].'</h3>';
			foreach ($fields['rpi_wall_protocol_orga']['sub_fields'] as $field){
				$content .= '<div><p><strong>'.$field['label'].'</strong></p><p>'.nl2br($rows[$field['name']]).'</p></div>';
			}

			$next_date = get_field('rpi_wall_protocol_orga_next_meeting_date');
			if(!$next_date){
				$next_date = 'Ein nächster Termin wird noch abgesprochen.';
			}

			$content .= '<h3>'.$fields['rpi_wall_protocol_orga_next_meeting_date']['label'].'</h3>';
			$content .= '<div>'.$next_date.'</div>';

			$content .= '<h3>'.$fields['rpi_wall_protocol_orga_todo']['label'].'</h3>';
			$content .= '<div>'.nl2br(get_field('rpi_wall_protocol_orga_todo')).'</div>';



			$c = get_field('rpi_wall_protocol_notices');
			$content .= '<h3>'.$fields['rpi_wall_protocol_notices']['label'].':</h3>';
			$content .= ''.nl2br($c).'';

			$content .= '</div>';

			$c = get_field('rpi_wall_protocol_result');
			$content .= '<h2>'.$fields['rpi_wall_protocol_result']['label'].'</h2>';
			$content .= '<div class="protokoll-container">';
			$content .= ''.$c.'';
			$content .= '</div>';


			$b = get_field('rpi_wall_protocol_is_public_result');
			if($b){
				$label = $fields['rpi_wall_protocol_is_public_result']['message']. ' Ja';
				$c = 'Veröffentlicht unter: <a href="'.get_permalink(get_field('rpi_wall_protocol_groupid')).'#group">'.get_field("constitution_gruppenname", get_field('rpi_wall_protocol_groupid')).'</a>';
				$content .= '<p>'.$label.': '.$c. '</p>';
			}


			$content .= '<hr>';

			$member = new Member(get_field('rpi_wall_protocol_leader'));
			$content .= '<p><strong>'.$fields['rpi_wall_protocol_leader']['label'].'</strong>: '.$member->get_link().'<br>';

			$content .= '<strong>Protokoll</strong>:  '. get_the_date().', '.get_the_author().'</p>';
			?>


			<?php
		}
		return $content;
	}

}
new protocol();
