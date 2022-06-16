<?php

class Group extends stdClass {

	public $ID;
	public $slug;
	public String $url;
	public WP_Post $post;
	public $is_group = false;
	public $group_status = null;
	public $likes_total= 0;
	public $comments_count = 0;
	public $last_coment;
	public $comments_likes = 0;
	public $pending_days;
	public $matrix_server_home = 'matrix.rpi-virtuell.de';
	public $matrix_server_base = 'rpi-virtuell.de';


	/**
	 * @param int|WP_Post|null $post
	 *
	 * @return Group
	 */

	public function __construct($post){

		$this->post = get_post($post,ARRAY_A);
		$this->ID = $post->ID;
		$this->group_status = $this->get('pl_group_status');
		$this->is_group = $this->exits();
		$this->slug = 'dibes_plg_'.$this->ID;
		$this->title = 'PLG '.substr(preg_replace('/[^\w\s-]/i','',$post->post_title),0,40);
		$this->url = get_permalink($post->ID);
		$this->channel_url = "https://{$this->matrix_server_home}/#/room/#{$this->slug}:rpi-virtuell.de";
		$this->pending_days = get_option('rpi_wall_pl_group_pending_days',7);


	}

	public function get_status(){
		return $this->get('pl_group_status');
	}

	public function exists() {
		return !empty($this->get_status());
	}

	public function is_pending(){
		return $this->get_status() === 'pending';
	}

	public function is_founded(){
		return $this->get_status() === 'founded';
	}


	public function set_status($status){
		if(null === $status){
			delete_post_meta($this->ID,'pl_group_status');
		}else{
			update_post_meta($this->ID,'pl_group_status',$status);
		}
		$this->set_status_time();
	}
	public function set_status_time(){
		update_post_meta($this->ID,'pl_group_status_timestamp',time());
	}

	public function reset_status(){
		$this->set_status(null);
	}

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

	public function get_matrix_link(){}
	public function get_lickers(){}
	public function get_lickers_amount(){}
	public function get_lickers_Ids(){}

	/**
	 * @return string comma separated matrix user_ids
	 */
	public function get_lickers_matrix_ids(){}
	public function get_members(){}
	public function get_members_amount(){}
	public function get_memberIds(){}

	/**
	 * @return WP_User[]
	 */
	public function remove_members(){}
	public function get_watcher(){}
	public function get_watcher_amount(){}
	public function get_watcherIds(){}

	public function send_message(){}
	public function create_message(){}

	protected function get($key){
		return get_post_meta($this->ID,$key, true);
	}

	protected function get_toolbar(){
		return '';
	}
	protected function create_matrix_channel(){

		$token = "syt_cnBpLXdhbGwtYm90_meZpGbTJUOxoVTQEkEYL_1LWFkV";

		$request = new HttpRequest();
		$request->setUrl('https://'.$this->matrix_server_home.'/_matrix/client/v3/user_directory/search');
		$request->setMethod(HTTP_METH_POST);

		$request->setHeaders([
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer '.$token
		]);

		/**
		 * Member, die bereits in der Matrix sind ermitteln
		 */

		$matrix_user_ids=[];

		foreach ( $this->get_members()  as  $member ){

			$request->setBody('{
		        "limit": 1,
		        "search_term": "'.$member->login_name.':"
			}');

			try {
				$response = $request->send();

				$respond = $response->getBody();

				if($respond && isset($respond->results)){
					$matrix_user = $respond->results[0];
					$matrix_user_ids[]=$matrix_user->user_id;
				}

			} catch (HttpException $ex) {
				echo $ex;
			}
		}

		/**
		 * Channel erstellen
		 */
		$toolbar = $this->get_toolbar();

		$request->setBody('{"name":"'.$this->title.'","visibility":"private","preset":"public_chat","room_alias_name":"'.$this->slug.'","topic":"'.$toolbar.'","initial_state":[]}');

		$this->set_status('founded');


		/**
		 * Action Hook for
		 * Message to orga channel
		 * E-Mails to likers
		 */
		do_action('rpi_wall_pl_group_after_channel_created', $this);

	}
}


