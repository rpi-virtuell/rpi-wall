<?php

namespace rpi\Wall;

use rpi\Wall\Matrix\Helper;

class Group extends \stdClass {

	public $ID;
	public $slug;
	public String $url;
	public $post;
	public $group_status = null;
	public $pending_days;
	public $matrix_server_home = 'matrix.rpi-virtuell.de';
	public $matrix_server_base = 'rpi-virtuell.de';


	/**
	 * Todo
	 * stati: pending, acreated
	 * erstellen des channels nach der mindestzahl von PLG
	 * nach pending phase Channel schließen
	 */

	/**
	 * @param int|WP_Post|null $post
	 *
	 * @return Group
	 */

	public function __construct($post_id){

		$this->post = get_post($post_id,ARRAY_A);
		$this->ID = $post_id;
		$this->group_status = $this->get('pl_group_status');
		$this->slug = 'dibes_plg_'.$this->ID;
		$this->title = 'PLG '.substr(preg_replace('/[^\w\s-]/i','',$this->post->post_title),0,40);
		$this->channel_url = "https://{$this->matrix_server_home}/#/room/#{$this->slug}:rpi-virtuell.de";
		$this->pending_days = get_option('rpi_wall_pl_group_pending_days',7);

		$this->start_PLG_link = $this->get_starlink();


		add_action('init',['rpi\Wall\Group','init_cronjob']);
		//incomming
		add_action('init',['rpi\Wall\Group','init_handle_requests']);

	}

	/**
	 * @return string
	 */
	public function get_status(){
		return $this->get('pl_group_status');
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return !empty($this->get_status());
	}

	/**
	 * @return bool
	 */
	public function is_ready(){
		return $this->get_status() === 'ready';
	}
	/**
	 * @return bool
	 */
	public function is_pending(){
		return $this->get_status() === 'pending';
	}

	/**
	 * @return bool
	 */
	public function is_founded(){
		return $this->get_status() === 'founded';
	}

	/**
	 * @return bool
	 */
	public function is_closed(){
		return $this->get_status() === 'closed';
	}
	/**
	 * @param string $status ready|pending|founded|closed or null
	 *
	 * @return void
	 */
	public function set_status(string $status){
		if(null === $status){
			delete_post_meta($this->ID,'pl_group_status');
		}else{
			update_post_meta($this->ID,'pl_group_status',$status);
			if($status == 'pending')
				$this->set_status_time();
		}

	}

	/**
	 * @return void
	 */
	protected function set_status_time(){
		update_post_meta($this->ID,'pl_group_status_timestamp',time());
	}

	/**
	 * remove members and status
	 * if pending time out and noch enouph user du found a PLG
	 *
	 * @return void
	 */
	public function reset_status(){

		$this->remove_members();

		delete_post_meta($this->ID,'pl_group_status_timestamp');

		$this->set_status(null);
	}

	/**
	 * @return Date
	 */
	public function get_status_date(){

		return  date('d.n.Y',$this->get('pl_group_status_timestamp')) ;
	}

	public function get_pending_time(){

		if($this->group_status = 'pending'){
			$daySeconds = 86400;

			$end_time = $this->get('pl_group_status_timestamp') +   ($this->pending_days * $daySeconds);
			$pendingtime = $end_time - time();

			$days = floor($pendingtime/86400);
			$hours = floor(($pendingtime - $days*86400) / 3600);
			$minutes = floor(($pendingtime / 60) % 60);

			$format =  '%d Tage, %d Stunden und %d Sekunden';
			$timeformated = sprintf($format, $days, $hours,$minutes);

			return apply_filters('rpi_wall_pendingtime',$timeformated, $days, $hours,$minutes);
		}
		return '';

	}

	/**
	 * @param string $context email|html|matrix
	 *
	 * @return string
	 */
	public function get_matrix_link(string $context = 'html'){
		switch ($context){
			case 'html':
				return '<a href="'.$this->channel_url.'">#'.$this->slug.':rpi-virtuell.de</a>';
				break;
			case 'matrix':
				return "#{$this->slug}:rpi-virtuell.de";
				break;
			case 'email':
				return $this->channel_url;
				break;
		}
	}

	/**
	 * @return array WP_User[]
	 */
	public function get_likers(){

		return get_users(array(
			"include"=>wp_ulike_get_likers_list_per_post('ulike','likers_list',$this->ID,100)
		));
	}

	/**
	 * @return int
	 */
	public function get_likers_amount(){

		return get_post_meta($this->ID, 'like_amount',true);

	}

	/**
	 * @return array user Ids
	 */
	public function get_likers_Ids(){

		return wp_ulike_get_likers_list_per_post('ulike','likers_list',$this->ID,100);
	}

	/**
	 * @return array WP_User[]
	 */
	public function all_get_comments_liker() {

		$likers = get_users( ['include',$this->get_comment_liker_Ids()] );
		return $likers;
	}
	/**
	 * @return array $user_id[]
	 */
	public function all_get_comments_likerIds(){

		$comments = get_comments(['post_id'=>$this->id]);

		$likers = [];
		foreach($comments as $comment){
			$ids =$this->get_comment_liker_Ids($comment->comment_ID);
			foreach ($ids as $user_id){
				$likers[]=$user_id ;
			}
		}
		return array_unique($likers);

	}
	/**
	 * @return array $user_id[]
	 */
	public function get_comment_liker_Ids($comment_id){
		return wp_ulike_get_likers_list_per_post('ulike_comments','likers_list',$comment_id,100);
	}

	/**
	 * @return array $user_id[]
	 */
	public function get_comment_likescount(){
		$likes =0;
		foreach (get_comments([ 'post_id' => $this->ID]) as $comment){
			$likes += intval(wp_ulike_get_comment_likes($comment->comment_ID));
		}
		return $likes;
	}

	/**
	 * @return string comma separated matrix user_ids
	 */
	public function get_members_matrix_ids(){
		$ids =$this->get_members_matrix_ids();
		$return =[];
		foreach ($ids as $id){
			if($matrixId = get_user_meta($id,'matrixId',true)){
				$return[] = $matrixId;
			}else{
				$return[] = '@'.get_user_by('ID',$id)->user_login.':'.$this->matrix_server_base .'(?)';

			}

		}
	}

	/**
	 * @return array Member[]
	 *
	 */
	public function get_members(){
		$members = [];
		foreach ( $this->get_memberIds() as $member_id ){
			$members[] = new Member($member_id);
		}
		return $members;
	}

	/**
	 * @param \WP_User $user_id
	 *
	 * @return Member
	 */
	public function get_member($user_id){
		return new Member($user_id);
	}

	/**
	 * @return int
	 */
	public function get_members_amount(){
		return count($this->get_memberIds());
	}

	/**
	 * @return array
	 */
	public function get_memberIds(){
		return (array) get_post_meta('rpi_wall_member_id');
	}

	/**
	 * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
	 * @return void
	 */
	protected function remove_members(){
		foreach ($this->get_memberIds() as $user_id){
			delete_post_meta($this->ID, 'rpi_wall_member_id', $user_id);
			delete_user_meta($user_id, 'rpi_wall_group_id', $this->ID);
		};
	}

	/**
	 * @return array $user_id[]
	 */
	public function get_watcherIds(){
		return get_post_meta($this->ID, 'rpi_wall_watcher_id');
	}

	/**
	 * @return array WP_User[]
	 */
	public function get_watcher(){
		return get_users(['include'=>$this->get_watcherIds()]);
	}

	/**
	 * @return int
	 */
	public function get_watcher_amount(){
		return count($this->get_watcherIds());
	}

	/**
	 * get metakey value
	 *
	 * @param $key
	 * @return mixed
	 */
	protected function get($key){
		return get_post_meta($this->ID,$key, true);
	}

	/**
	 * @return string <embed>widgetcontent</embed>
	 *
	 * Todo
	 */
	protected function get_toolbar(){
		return '';
	}

	/**
	 * @param string $room_id
	 * @return void
	 */
	protected function set_room_id(string $room_id){
		update_post_meta($this->ID,'pl_group_matrix_room_id');
	}

	protected function get_matrix_channel_id(){
		return $this->get('pl_group_matrix_room_id');
	}
	protected function get_joined_member_matrixId($user_login) {
		return Matrix\Helper::getUser($user_login);
	}

	static function init_handle_requests(){
		if(isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['group']) ){
			$group = new Group($_REQUEST['group']);
			if( 'plgstart' == $_REQUEST['action']  && 'start' == $group->check_hash($_REQUEST['hash'])){

				$group->start_pending();
			}

		}
	}

	static function init_cronjob(){

		// check alle Gruppen, die keinen status, aber likers haben
		// wenn minimum likers erreicht: Gründungsphase zu starten

		$args =[
			'post_type' => 'wall',
			'numberposts'=> -1,
			'meta_query'=>[
				'relation' => 'AND',
				[
					'key' => 'like_amount',
					'value' => get_option('pl_group_min_required_members', 3),
					'compare' => '>=',
					'type' => 'NUMERIC'
				],
				[
					'key' => 'pl_group_status',
					'compare' => 'NOT EXISTS'
				]
			]
		];

		$posts = get_posts($args);
		foreach ($posts as $post){
			$group = new Group($post->ID);
			$group->set_status('ready');
			new Message($group,'ready',['orga','group']);
			do_action('rpi_wall_pl_group_ready', $group);
		}


		// check alle Gruppen, die den status pending haben und die pending time abgelaufen ist
		// wenn genug Mitglieder gejoined: create matrix room
		// wenn nicht genug Mitglieder : reset
		$daySeconds = 86400;
		$pending_add =  $daySeconds * get_option('rpi_wall_pl_group_pending_days',7);

		$args =[
			'post_type' => 'wall',
			'mumberposts'=> -1,
			'meta_query'=>[
				'relation' => 'AND',
				[
					'key' => 'pl_group_status',
					'value' => 'pending',
					'compare' => '='
				],
				[
					'key' => 'pl_group_status_timestamp',
					'value' => time()-$pending_add,
					'compare' => '>=',
					'type' => 'NUMERIC'
				]
			]
		];

		$posts = get_posts($args);
		foreach ($posts as $post) {
			$group = new Group( $post->ID );
			if($group->get_members_amount()< get_option('pl_group_min_required_members', 3)){

				$group->reset_status();
				new Message($group, 'reset');
				do_action('rpi_wall_pl_group_reset', $group);

			}else{

				$group->create_room();
				$group->set_status('founded');
				new Message($group, 'founded');

				do_action('rpi_wall_pl_group_founded', $group);
			}

		}

	}

	protected function start_pending(){

		$this->set_status('pending');
		new Message($this,'pending');
		do_action('rpi_wall_pl_group_pending', $this);

	}

	protected function create_room(){

		$room_id = Matrix\Helper::create_room($this);
		/**
		 * Message to orga channel
		 * E-Mails to likers
		 */
		do_action('rpi_wall_pl_group_room_created', $room_id);
	}



	public function get_starlink($label = 'Gruppe gründen'){
		return '<a class="button" href="'.get_home_url().'?action=plgstart&hash='.$this->get_hash('start').'&group='.$this->ID.'">'.$label.'</p>';
	}

	public function get_current_users_joinlink($label = 'Gruppe beitreten'){
		$member = new Member(get_current_user_id());
		$hash = $member->get_join_hash($this->ID);
		return '<a class="button" href="'.get_home_url().'?action=plgjoin&hash='.$hash.'&member='.$member->ID.'">'.$label.'</p>';
	}

	/**
	 * @param string $type start|join
	 *
	 * @return array|string|string[]
	 */
	protected function get_hash($type='start'){

		$hash = str_replace(md5($this->slug.'_start_founding_plg'),'-','');

		if($type === 'join'){
			$hash = str_replace(md5($this->slug.'_join_plg'),'-','');
		}
		return $hash;

	}

	/**
	 * @param $hash
	 *
	 * @return false|string   start|join
	 */
	protected function check_hash($hash){

		if($hash == str_replace(md5($this->slug.'_start_founding_plg'),'-','')){
			return 'start';
		}

		if($hash == str_replace(md5($this->slug.'_join_plg'),'-','')){
			return 'join';
		}
		return false;
	}


	//outputs
	public function display(){



		switch ($this->get_status()){
			case'ready':
				$notice   = get_option('rpi_wall_ready_notice','Du kannst die Gründungsphase jetzt starten. Alle interessierten werden dann angeschrieben und haben eine Woche Zeit, der PLG beizutreten.');
				$button   = $this->get_starlink();
				break;
			case'pending':
				$headline = get_option('rpi_wall_not_founded_header','Interessiert an einer Professionellen Lerngruppe (PLG) zu diesem Kontext?');
				$notice   = get_option('rpi_wall_pending_notice','Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?');
				$button   = $this->get_current_users_joinlink();
				break;
			case'founded':
				$headline = get_option('rpi_wall_founded_header','Professionelle Lerngruppe (PLG) zu diesem Kontext');
				$notice   = get_option('rpi_wall_founded_header','Zu diesem Pinwandeintrag hat sich eine PLG gegründet.');
				$button   = $this->get_current_users_joinlink('Beitritt anfragen');
				break;
			case'closed':
				$headline = get_option('rpi_wall_founded_header','Professionelle Lerngruppe (PLG) zu diesem Kontext');
				$notice = get_option('rpi_wall_founded_header','Zu diesem Pinwandeintrag hat sich eine PLG gegründet.');
				break;
			default:
				$headline = get_option('rpi_wall_not_founded_header','Interessiert an einer Professionellen Lerngruppe (PLG) zu diesem Kontext?');
				$notice =  get_option('rpi_wall_not_founded_notice','Wenn du dich mit Klick auf das + Intresse zeigst, in Kontext dieses Beitrags eine PLG zu gründen, wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.');

		}

		echo '<div class="gruppe">';

		echo '<div class="gruppe-header">'.$headline.'</div>';

		echo '<div class="gruppe-liker">';
		echo do_shortcode('[wp_ulike  style="wpulike-heart"]');
		echo '</div>';

		if($button){
			echo '<div class="gruppe-button">'.$button.'</div>';
		}
		echo '<div class="gruppe-footer"><span class="notice">'.$notice.'</span></div>';

		echo '</div>'; //end gruppe
	}

	public function display_short_info(){

		return;

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
			$likes += intval(wp_ulike_get_comment_likes($comment->comment_ID));
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


	}
}


