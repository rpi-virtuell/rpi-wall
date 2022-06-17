<?php
namespace \rpiWall;

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
			if($status == 'pending')
				$this->set_status_time();
		}

	}
	public function set_status_time(){
		update_post_meta($this->ID,'pl_group_status_timestamp',time());
	}

	public function reset_status(){
		//ToDo remove existing group_member
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

	public function get_likers(){}
	public function get_likers_amount(){}
	public function get_likers_Ids(){}

	/**
	 * @return string comma separated matrix user_ids
	 */
	public function get_members_matrix_ids(){}

	/**
	 * @return void
	 *
	 */
	public function get_members(){


	}
	public function get_member($user_id){}
	public function get_members_amount(){}
	public function get_memberIds(){
		$return =get_post_meta('rpi_wall_member_id');
	}

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

	/**
	 * @param string $room_id
	 *
	 * @return void
	 */
	protected function set_matrix_channel_id(string $room_id){
		update_post_meta($this->ID,'pl_group_matrix_room_id');
	}

	protected function get_matrix_channel_id(){
		return $this->get('pl_group_matrix_room_id');
	}
	protected function get_joined_member_matrixId($user_login) {

		$token = get_option( 'matrix_bot_token' );

		$request = new HttpRequest();
		$request->setUrl( 'https://' . $this->matrix_server_home . '/_matrix/client/v3/user_directory/search' );
		$request->setMethod( HTTP_METH_POST );

		$request->setHeaders( [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token
		] );

		$request->setBody('{
		        "limit": 1,
		        "search_term": "'.$user_login.':"
			}');

		try {
			$response = $request->send();

			$respond = $response->getBody();

			if($respond && isset($respond->results)){
				$matrix_user = $respond->results[0];
				if(isset($matrix_user->user_id)){
					return $matrix_user->user_id;
				}

			}

		} catch (HttpException $ex) {
			echo $ex;
		}

		return false;


	}

	protected function create_matrix_channel(){

		$token = get_option('matrix_bot_token');

		$request = new HttpRequest();
		$request->setUrl('https://'.$this->matrix_server_home.'/_matrix/client/v3/user_directory/search');
		$request->setMethod(HTTP_METH_POST);

		$request->setHeaders([
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer '.$token
		]);


		/**
		 * Channel erstellen
		 */
		$toolbar = $this->get_toolbar();


		$reponse = $request->setBody('{"name":"'.$this->title.'","visibility":"private","preset":"public_chat","room_alias_name":"'.$this->slug.'","topic":"'.$toolbar.'","initial_state":[]}');

		/**
		 * Todo
		 * $response->getBody()
		 * //output
		 *		{
		 *          "room_id": "!SDWXfNPQFYBplBTQfM:rpi-virtuell.de",
		 *          "room_alias": "#78969:rpi-virtuell.de"
		 *       }
		 */

		$this->set_matrix_channel_id($room_id);
		$this->set_status('founded');



		/**
		 * Action Hook for
		 * Message to orga channel
		 * E-Mails to likers
		 */
		do_action('rpi_wall_pl_group_after_channel_created', $this);

	}
}

