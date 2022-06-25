<?php

namespace rpi\Wall;


class member extends \stdClass
{

    public $ID;
    public $name;
    public string $url;
    public $post;  //CPT member WP_Post
    public $user;

	/**
	 * @param WP_User|int $user
	 */
    public function __construct($user = 0)
    {


	    if(is_a($user, 'WP_User')){
		    $this->user = $user;
		    $this->ID = $user->ID;


	    }else{
		    if ($user === 0) {
			    $this->ID = get_current_user_id();
		    }else{
			    $this->ID = $user;
		    }

		    $this->user = get_userdata($this->ID);

		    if(!$this->user){
				return new \WP_Error('404','User not found');
			}
	    }



        $posts = get_posts(array(
            'post_status' => 'any',
            'post_type' => 'member',
            'author' => $this->ID
        ));
        $this->post = reset($posts);

        $this->name = $this->user->display_name;

        $this->url = $this->get_member_profile_permalink();



    }

    public function get_member_profile_permalink()
    {
        return wp_ulike_pro_get_user_profile_permalink($this->ID);
    }

	public function get_liked_group_Ids(){
		$ids = get_user_meta($this->ID, 'rpi_wall_liked_group_id');

		if(is_array($ids)){
			return get_user_meta($this->ID, 'rpi_wall_liked_group_id');
		}
		return array();

	}
	public function is_liked_group($groupId){
		return in_array($groupId, $this->get_liked_group_Ids());
	}

	public function like_group($groupId){

		if(!$this->is_liked_group($groupId)){
			$this->toggle_like_group($groupId);
		}
	}
	public function un_like_group($groupId){

		if($this->is_liked_group($groupId)){
			$this->toggle_like_group($groupId);
		}
	}


	public function toggle_like_group($groupId)
	{
		if($this->ID > 0){
			if (!$this->is_liked_group($groupId)) {
				add_post_meta($groupId, 'rpi_wall_liker_id', $this->ID);
				add_user_meta($this->ID, 'rpi_wall_liked_group_id', $groupId);
				$action = 'like';
			}else{
				delete_post_meta($groupId, 'rpi_wall_liker_id', $this->ID);
				delete_user_meta($this->ID, 'rpi_wall_liked_group_id', $groupId);
				$action = 'unlike';
			}
			do_action('rpi_wall_member_joined_group',$this->ID, $groupId, $action);
		}



	}
	public function _toggle_like_group($groupId){

		$process  = new \wp_ulike_cta_process( array(
			'item_id'       =>  $groupId,
			'item_type'     =>  'post',
			'item_template' =>  "wpulike-heart",
			'user_id'       =>  $this->ID
		) );
		//toggle like/unlike
		$process->update();
	}

	public function is_in_group_or_likes_group($groupId){
		return in_array($groupId,get_assigned_group_Ids());
	}

	public function get_assigned_group_Ids(){
		return array_merge($this->get_group_Ids(),$this->get_liked_group_Ids());
	}

	public function get_query_all_groups($args = array()){


		$args = wp_parse_args($args,
		[
			'post_type' => 'wall',
			'post__in' => $this->get_assigned_group_Ids()
		]);

		$query = new \WP_Query($args);
		return $query;

	}

	public function get_query_pending_groups($stati = array ('pending')){
		$query = new \WP_Query([
			'post_type' => 'wall',
			'post__in' => $this->get_assigned_group_Ids(),
			'meta_query'=>[
				'key' => 'pl_group_status',
				'value' => $stati,
				'compare' => 'IN'
			]
		]);
		return $query;
	}
	public function get_query_my_posts($args = array()){
		$args = wp_parse_args($args,[
			'post_type' => 'wall',
			'post_author'=>$this->ID
		]);
		$query = new \WP_Query($args);
		return $query;
	}
	public function get_my_comments_query($args = array()){

		$props = wp_parse_args($args,[
			'author__in'=>[$this->ID]
		]);

		$comments_query = new \WP_Comment_Query( $props );
		$comments = $comments_query->comments;
		foreach ($comments as $key=>$comment){
			$comments[$key]->post = get_post($comment->comment_post_ID);
		}
		return $comments;
	}

	/**
	 * helper function Mitglieder einer Gruppe sollen nicht zugleich liker sein
	 * @return void
	 */
	public function clean_likes(){

		foreach ($this->get_group_Ids() as $group_id){
			if($this->is_liked_group($group_id)){
				$this->un_like_group($group_id);
			}
		}
	}



    protected function join_group($groupId)
    {
	    if ($this->is_in_group($groupId) || $this->ID < 1 ){
            return false;
        }
        add_post_meta($groupId, 'rpi_wall_member_id', $this->ID);
        add_user_meta($this->ID, 'rpi_wall_group_id', $groupId);

		$this->un_like_group($groupId);

		do_action('rpi_wall_member_joined_group',$this->ID, $groupId);
    }

    public function leave_group($groupId)
    {
        delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID);
		delete_user_meta($this->ID, 'rpi_wall_group_id', $groupId);
	    do_action('rpi_wall_member_left_group', $this, $groupId);
        return true;

    }


    public function watch_group($groupid)
    {

    }

    public function get_group_Ids()
    {
        return get_user_meta($this->id, 'rpi_wall_group_id');
    }

    public
    function is_in_group($group_id): bool
    {
        $groups = (array)get_user_meta($this->ID, 'rpi_wall_group_id');
		return in_array($group_id, $groups);
    }

    public
    function set_message_read($post_id)
    {
    }

    public
    function get_messages()
    {
        return get_posts([
            'post_type' => 'message',
            'mumberposts' => -1,
            'meta_query' => [
                'key' => 'recipient',
                'value' => $this->ID,
                'compare' => '=',
                'type' => 'NUMERIC'
            ]
        ]);
    }

    public
    function current_user_is_member()
    {
        if (get_current_user_id() === $this->ID) {
            return true;
        } else {
            return false;
        }
    }

    public
    function current_member_can($capability)
    {
        return user_can($this->ID, $capability);
    }


    /**
     * init action
     *
     * @return bool|void
     */
    public function init_handle_request()
    {


        if (isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['new_group_member'])) {


            if ('plgjoin' == $_REQUEST['action']) {

                $member = new member(intval($_REQUEST['new_group_member']));
                $member->validate_and_join($_REQUEST['hash']);

                wp_redirect($member->get_member_profile_permalink());
                die();


            }
            return false;
        }
    }

    /**
     * @param $joinhash
     *
     * @return bool
     */
    public function validate_and_join($joinhash)
    {

        $groups = unserialize(get_user_meta($this->ID, 'rpi_wall_group_hash', true),);


        foreach ($groups as $group_id => $hash) {

            if ($hash === $joinhash) {
                $this->join_group($group_id);
                return;
            }
        }
    }

    /**
     * @param $group_id  Group
     *
     * @return string hash
     */
    public function get_join_hash($group_id)
    {


        $groups = unserialize(get_user_meta($this->ID, 'rpi_wall_group_hash', true));

        $hash = wp_hash($this->name.$group_id, 'nonce');

        $groups[$group_id] = $hash;

        update_user_meta($this->ID, 'rpi_wall_group_hash', serialize($groups));

        return $groups[$group_id];

    }


    /**
     * @param $group_id Group
     *
     * @return string html link
     */

    public function get_joinlink($group_id)
    {

        $hash = $this->get_join_hash($group_id);
        return '<a href="' . get_home_url() . '?action=plgjoin&hash=' . $hash . '&member=' . $this->ID . '">Gruppe beitreten</p>';

    }

	public function display_gravtar($size = 96){
		echo get_avatar($this->ID,$size);
	}
	public function display($size = 15){
		Shortcodes::display_user($this->ID,$size);
	}

}
