<?php

namespace rpi\Wall;


class Member extends \stdClass
{

    public $ID;
    public $name;
    public $url;
    public $post;  //CPT Member WP_Post
    public $user;
    public $link;

    /**
     * Member Object
     *
     * @param WP_User|int $user
     */
    public function __construct($user = 0)
    {


        if (is_a($user, 'WP_User')) {
            $this->user = $user;
            $this->ID = $user->ID;


        } else {
            if ($user === 0) {
                $this->ID = get_current_user_id();
            } else {
                $this->ID = $user;
            }

			$this->user = get_userdata($this->ID);

            if (!$this->user) {

				if(intval($user ) > 0 ){

					//remove deleted user from groups

					if(get_post_type()=='wall' ){

						delete_post_meta(get_the_ID(), 'rpi_wall_watcher_id',$user);
						delete_post_meta(get_the_ID(), 'rpi_wall_liker_id',$user);
						delete_post_meta(get_the_ID(), 'rpi_wall_member_id',$user);

					}

					echo '<pre>';
					echo 'ungültiger User: Member->ID =0 (called ID:'.$user.')<br>';
					debug_print_backtrace(null, 5);
					echo '</pre>';
				}


            }

        }

		if(is_a($this->user,'WP_User')){
			$this->name = $this->user->display_name;

			$posts = get_posts(array(
				'post_status' => 'any',
				'post_type' => 'member',
				'author' => $this->ID
			));


			if (count($posts) > 0) {
				if(count ($posts)>1){
					//remove member duplicates
					for($i=0;$i<count ($posts);$i++){
						if($i>0){
							wp_delete_post($posts[$i]->ID, true);
						}
					}
				}
				$this->post = reset($posts);
			} else {
				//create member post
				$this->post = $this->setup($this->ID);
			}
			if($this->post){
				$this->url = $this->get_member_profile_permalink();
				$this->link = $this->get_link();
			}

		}


    }

    public function get_member_profile_permalink()
    {
        return get_permalink($this->post);

    }

    public function get_link()
    {
        return '<a href="' . $this->url . '" class="member_link-">' . $this->name . '</a>';

    }

	public function get_unread_messages_count(){
		return get_user_meta($this->ID,'rpi_wall_unread_messages_count', true);
	}


    static function set_default_matrixId($field){

		$user = wp_get_current_user();

		$matrix_id =  '@'.$user->user_login.':rpi-virtuell.de';
	    $field['default_value'] =$matrix_id;
	    return $field;
    }



	/**
	 * WATCHING
	 */

	public function is_watched_group($groupId)
	{
		return in_array($groupId, $this->get_watched_group_Ids());
	}

	public function toggle_watch_group($groupId)
	{
		if ($this->ID > 0) {

			if (!$this->is_watched_group($groupId)) {
				add_post_meta($groupId, 'rpi_wall_watcher_id', $this->ID);
				add_user_meta($this->ID, 'rpi_wall_watched_group_id', $groupId);
				$action = 'watch';

			} else {
				delete_post_meta($groupId, 'rpi_wall_watcher_id', $this->ID);
				delete_user_meta($this->ID, 'rpi_wall_watched_group_id', $groupId);
				$action = 'unwatch';
			}
			//recalc watchers_amount
			$return = update_post_meta($groupId, 'rpi_wall_watchers_amount', count($this->get_watched_group_Ids()));

			if($return){
				do_action('rpi_wall_watch_group', $this->ID, $groupId, $action);
			}
			return $return;

		}
		return false;
	}

	public function get_watched_group_Ids()
	{
		$ids = get_user_meta($this->ID, 'rpi_wall_watched_group_id');

		if (is_array($ids)) {
			return get_user_meta($this->ID, 'rpi_wall_watched_group_id');
		}
		return array();
	}



    /**
     * LIKE GROUP
     */

    /**
     * @return array|mixed
     */
    public function get_liked_group_Ids()
    {
        $ids = get_user_meta($this->ID, 'rpi_wall_liked_group_id');

        if (is_array($ids)) {
            return get_user_meta($this->ID, 'rpi_wall_liked_group_id');
        }
        return array();

    }

    public function is_liked_group($groupId)
    {
        return in_array($groupId, $this->get_liked_group_Ids());
    }

    public function like_group($groupId)
    {

        if (!$this->is_liked_group($groupId)) {

            $this->toggle_like_group($groupId);

        }

    }

    public function un_like_group($groupId)
    {

        if ($this->is_liked_group($groupId)) {
            $this->toggle_like_group($groupId);
        }
    }

    public function toggle_like_group($groupId)
    {
        if ($this->ID > 0) {
            if (!$this->is_liked_group($groupId)) {
                add_post_meta($groupId, 'rpi_wall_liker_id', $this->ID);
                add_user_meta($this->ID, 'rpi_wall_liked_group_id', $groupId);
                $action = 'like';

            } else {
                delete_post_meta($groupId, 'rpi_wall_liker_id', $this->ID);
                delete_user_meta($this->ID, 'rpi_wall_liked_group_id', $groupId);
                $action = 'unlike';
            }
            //recalc likers_amount
            if (!$ids = get_post_meta($groupId, 'rpi_wall_liker_id')) {
                $ids = [];
            }
            if( $action == 'like' && !$this->is_watched_group($groupId)){
	            $this->toggle_watch_group($groupId);
            }

			update_post_meta($groupId, 'rpi_wall_likers_amount', count($ids));


            do_action('rpi_wall_like_group', $this->ID, $groupId, $action);
			return $action;

        }


    }

    public function clean_likes()
    {

        foreach ($this->get_group_Ids() as $group_id) {
            if ($this->is_liked_group($group_id)) {
                $this->un_like_group($group_id);
            }
        }
    }

    public function is_in_group_or_likes_group($groupId)
    {
        return in_array($groupId, get_assigned_group_Ids());
    }


    /**
     * MEMBERSHIP
     */


    public function get_group_Ids()
    {
        if (!$ids = get_user_meta($this->ID, 'rpi_wall_group_id')) {
            return [];
        }
        return $ids;
    }

    public function is_in_group($group_id): bool
    {
        $groups = (array)get_user_meta($this->ID, 'rpi_wall_group_id');
        return in_array($group_id, $groups);
    }

    public function join_group($groupId)
    {
        if ($this->is_in_group($groupId) || $this->ID < 1) {
            return false;
        }
	    add_post_meta($groupId, 'rpi_wall_member_id', $this->ID);
        add_user_meta($this->ID, 'rpi_wall_group_id', $groupId);

        if (!$ids = get_post_meta($groupId, 'rpi_wall_member_id')) {
            $ids = [];
        }

        $this->un_like_group($groupId);


	    if(!$this->is_watched_group($groupId)){
		    $this->toggle_watch_group($groupId);
	    }

	    update_post_meta($groupId, 'rpi_wall_members_amount', count($ids));

	    new Message($groupId, 'joined');

		do_action('rpi_wall_member_joined_group', $this->ID, $groupId);
    }

    public function reject_group($groupId)
    {
        //remove requesting liker
        $this->un_like_group($groupId);

		$this->delete_serialized('rpi_wall_group_request',$groupId);

	    delete_post_meta($groupId, 'rpi_wall_member_requests',$this->ID);

		$group = new Group($groupId);
		$msg = new \stdClass();
		$msg->subject = "[{$group->title}] Beitrittsanfrage abgewiesen";
		$msg->body = "Leider wurde deine Beitrittsanfrage für die {$group->title} abgewiesen. Die Gruppe hat ihre Arbeit bereits aufgenommen. 
		Du kannnst aber einen neuen Pinnwandeintrag zun gleichen Thema erzeugen und damit die Voraussetzung für eine weitere Lerngemeinschaft schaffen.";
		Message::send_messages([$this->ID],$msg,'rpi_user_message_requested');
	    $msg->subject = "[{$group->title}] Beitrittsanfrage abgewiesen";
		$actor = new Member(get_current_user_id());
	    $msg->body = "{$actor->get_link()} hat die Beitrittsanfrage von {$this->get_link()} für die {$group->title} abgewiesen.";
	    Message::send_messages($group->get_memberIds(),$msg,'rpi_user_message_requested');
        do_action('rpi_wall_member_group_reject', $this->ID, $groupId);
    }

    public function request_group($groupId)
    {
        $plg = new Group($groupId);
        if ($this->is_in_group($groupId) || $this->ID < 1) {
            return false;
        }
		if(empty($plg->get_toolbar_status())){

			$this->join_group($groupId);
			return $groupId;

		}

	    $this->like_group($groupId);

	    $timestamp = time();
	    $contdown = $plg->get_countdown($timestamp);

	    $hash = wp_hash(strval($groupId) . strval(time()), 'nonce');
	    $this->set_serialized('rpi_wall_group_request',$groupId,['timestamp'=>$timestamp,'hash'=>$hash]);
	    add_post_meta($groupId,'rpi_wall_member_requests', $this->ID);

        $user_ids = $plg->get_memberIds();
        if (is_array($user_ids)) {

            $msg = new \stdClass();
            $msg->subject = '[' . $plg->title . '] Beitrittsanfrage';
            $msg->body = "Hallo zusammen,\n\nIch bin <a href='{$this->get_member_profile_permalink()}'>{$this->name}</a> und würde gerne der Arbeitsgruppe beitreten." .
                "Wenn etwas dagegen spricht, bitte meine Anfrage auf dem Pinnwandeintrag " . $plg->link . " ablehnen. Eine Ablehnung ist noch $contdown möglich";

            Message::send_messages($user_ids, $msg,'rpi_user_message_requested');

            do_action('rpi_wall_member_request_group', $this->ID, $groupId, $plg->get_memberIds(), $hash, $msg);
        }


    }

    public function get_rejectlink($hash, $pending)
    {

        if (is_user_logged_in()) {
            return '<a class="button" href="' . get_home_url() . '?action=plgreject&hash=' . $hash . '&new_group_member=' . $this->ID . '">Anfrage von ' . $this->name . ' ablehnen</a>'
                   ."<script>jQuery('.gruppe-footer .notice').html('Beitrittsanfragen können von jedem Gruppenmitglied abgewiesen werden.<br>Verbleibende Zeit: $pending');</script>";
        }

    }

    public function leave_group($groupId)
    {
        delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID);
        delete_user_meta($this->ID, 'rpi_wall_group_id', $groupId);
        do_action('rpi_wall_member_left_group', $this, $groupId);
        return true;

    }

    public function get_assigned_group_Ids()
    {

        $ids = array_merge($this->get_group_Ids(), $this->get_liked_group_Ids());
        return $ids;
    }

    public function get_query_all_groups($args = array())
    {

        //if (!empty($postids = $this->get_assigned_group_Ids())) {
        if (!empty($postids = $this->get_group_Ids())) {
            $args = wp_parse_args($args,
                [
                    'post_type' => 'wall',
                    'post__in' => $postids
                ]);

            $query = new \WP_Query($args);

            return $query;
        }
        return false;
    }

    public function get_query_watched_groups($args = array())
    {

        if (!empty($postids = $this->get_watched_group_Ids())) {
            $args = wp_parse_args($args,
                [
                    'post_type' => 'wall',
                    'post__in' => $postids
                ]);

            $query = new \WP_Query($args);
            return $query;

        }
        return false;

    }

    public function get_query_created_pins($args = array())
    {
        $args = wp_parse_args($args,
            [
                'post_type' => 'wall',
                'author' => $this->ID
            ]);

        $query = new \WP_Query($args);
        return $query;

    }


    public function get_query_pending_groups($stati = array('pending'))
    {
        if (!empty($postids = $this->get_assigned_group_Ids())) {
            $query = new \WP_Query([
                'post_type' => 'wall',
                'post__in' => $postids,
                'meta_query' => [
                    'key' => 'rpi_wall_group_status',
                    'value' => $stati,
                    'compare' => 'IN'
                ]
            ]);
            return $query;
        }
        return false;
    }




    /**
     * USER MESSAGES
     */

    public function set_message_read($message_id)
    {
	    return $this->set_serialized('rpi_read_messages',$message_id);
    }

    public function set_message_unread($message_id)
    {
	    return $this->delete_serialized('rpi_read_messages',$message_id);

    }


	/**
	 * TODO add needed capabilities und roles for moderation
	 * @param $capability
	 *
	 * @return bool
	 */
    public function current_member_can($capability)
    {
        return user_can($this->ID, $capability);
    }

	/**
	 * GROUPS QUERY
	 *
	 * @return \WP_Query
	 */
    public function get_query_my_posts($args = array())
    {
        $args = wp_parse_args($args, [
            'post_type' => 'wall',
            'post_author' => $this->ID
        ]);
        $query = new \WP_Query($args);
        return $query;
    }

	/**
	 * COMENTS QUERY
	 *
	 * @param $args
	 *
	 * @return int[]|\WP_Comment[]
	 */
	public function get_my_comments_query($args = array())
    {

	    $comments_per_page=2;
	    $all_comments = wp_count_comments();

        $props = wp_parse_args($args, [
            'author__in' => [$this->ID]

        ]);

        $comments_query = new \WP_Comment_Query($props);
        $comments = $comments_query->comments;
        foreach ($comments as $key => $comment) {
            $comments[$key]->post = get_post($comment->comment_post_ID);
        }
        return $comments;
    }


    /**
     * init action
     *
     * @return bool|void
     */
    static function init_handle_request()
    {

        if (isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['new_group_member'])) {


            if ('plgjoin' == $_REQUEST['action']) {

                $member = new Member(intval($_REQUEST['new_group_member']));
                $groupId = $member->validate_and_join($_REQUEST['hash']);

                if ($groupId) {
                    wp_redirect(get_permalink($groupId).'#group');
                } else {
                    wp_redirect(home_url());
                }

                die();


            } elseif ('plgrequest' == $_REQUEST['action']) {

                $member = new Member(intval($_REQUEST['new_group_member']));

                $groupId = $member->validate_and_request($_REQUEST['hash']);

                if ($groupId) {
                    wp_redirect(get_permalink($groupId).'#group');
                } else {
                    wp_redirect(home_url());
                }

                die();

            }
	        if ('plgreject' == $_REQUEST['action']) {

		        $member = new Member(intval($_REQUEST['new_group_member']));
		        $groupId = $member->validate_and_reject($_REQUEST['hash']);

		        if ($groupId) {
			        wp_redirect(get_permalink($groupId).'#group');
		        } else {
			        wp_redirect(home_url());
		        }

		        die();

	        }
            return false;
        }
    }

    /**
     * init action
     *
     * @return bool|void
     */
    static function init_cronjob()
    {

	    $daySeconds = 86400;
	    $pending = $daySeconds * get_option('options_rpi_wall_pl_group_pending_days');

	    $args = [
            'meta_query' => [
                [
                    'key' => 'rpi_wall_group_request',
                    'compare' => 'EXISTS'
                ]
            ]
        ];


	    $users = get_users($args);

		foreach ($users as $user) {
            if ($user instanceof \WP_User) {
				$member = new Member($user);

	            $groups = $member->get_serialized('rpi_wall_group_request');

	            foreach ($groups as $group_id => $props) {

                    //Wartezeit abgelaufen
					if (time() > $props['timestamp']+$pending ) {
                        $member->join_group($group_id);                             // gruppe beitreten & interesse ende
	                    $member->delete_serialized('rpi_wall_group_request',$group_id);       // request löschen
						delete_post_meta($group_id,'rpi_wall_member_requests',$member->ID);

                    }
                }
            }
        }
    }


    /**
     * check join request
     * @param $joinhash
     *
     * @return bool
     */
    public function validate_and_join($joinhash)
    {

        $groups = $this->get_serialized('rpi_wall_group_hash');
        foreach ($groups as $group_id => $hash) {

            if ($hash === $joinhash) {
                $this->join_group($group_id);
                return $group_id;
            }
        }
    }

    /**
     * check reject request
     * @param $joinhash
     *
     * @return bool
     */
    public function validate_and_reject($joinhash)
    {

        $groups = $this->get_serialized('rpi_wall_group_request');

		foreach ($groups as $group_id => $props) {


            if ($props['hash'] === $joinhash) {
                $this->reject_group($group_id);
                return $group_id;
            }
        }
    }

    /**
     * check founded group join request
     *
     * @param $joinhash
     *
     * @return bool
     */
    public function validate_and_request($joinhash)
    {

        $groups = $this->get_serialized('rpi_wall_group_hash');

        foreach ($groups as $group_id => $hash) {

            if ($hash === $joinhash) {
                $this->request_group($group_id);
                return $group_id;
            }
        }
    }

    /**
     * get user hash
     * @param $group_id  Group
     *
     * @return string hash
     */
    public function get_join_hash(int $group_id)
    {


        $groups = $this->get_serialized('rpi_wall_group_hash');

        $hash = wp_hash($this->name . $group_id, 'nonce');

        $groups[$group_id] = $hash;

        update_user_meta($this->ID, 'rpi_wall_group_hash', serialize($groups));

        return $groups[$group_id];

    }

    /**
     * link to join a group
     * @param $group_id Group
     *
     * @return string html link
     */
    public function get_joinlink(int $group_id, $label = 'Gruppe beitreten')
    {

        $hash = $this->get_join_hash($group_id);
	    return '<a href="' . get_home_url() . '?action=plgjoin&hash=' . $hash . '&new_group_member=' . $this->ID . '" class="button">' . $label . '</a>';

    }

    public function display($size = 15)
    {
        Shortcodes::display_user($this->ID, $size);
    }

	/**
	 * HELPER für usermeta
	 * @param $meta_key
	 * @param $key
	 *
	 * @return array|false|mixed
	 */
	public function get_serialized($meta_key, $key = false){

		$val = get_user_meta($this->ID,$meta_key,true);
		if($val && is_string($val)){
			$values = unserialize($val);
		}else{
			$values = array();
		}
		if(false !== $key){
			return isset($values[$key])?$values[$key]:false;
		}else{
			return $values;
		}

	}

	public function delete_serialized($meta_key,$key){
		$values = $this->get_serialized($meta_key);
		unset($values[$key]);
		return update_user_meta($this->ID,$meta_key,serialize($values));
	}

	public function set_serialized($meta_key,$key,$value = true){
		$values = $this->get_serialized($meta_key);
		$values[$key] = $value;
		return update_user_meta($this->ID,$meta_key,serialize($values));
	}


	/**
	 * creates a not existing  "member" CPT
	 * @return array|void|\WP_Post|null
	 */
	public function setup($user_id)
    {

	    /**
	     * @todo Member werden aus irgendwelchen Gründen dupliziert
	     */
		if ( $user_id>0 ) {
			global $wpdb;

			$members = get_posts(array(
				'post_status' => ['publish','trash'],
				'post_type' => 'member',
				'author__in' => [$user_id]
			));
			if(count($members)>0){
				return reset($members);
			}
			if(in_array($this->user->user_login, ['wpadmin', 'admin'] )){
				return false;
			} else {


		        $member_id = wp_insert_post( array(
					'post_title'  => $this->user->user_login,
			        'post_status' => 'publish',
			        'post_author' => $user_id,
			        'post_type'   => 'member'
		        ) );

		        return get_post($member_id);
	        }


        }
    }
	/**
	 * @uses action_hook delete_user
	 *
	 * @return void
	 */
	static function on_delete_user(int $user_id ){

		$member = new Member($user_id);
		if(is_a($member, 'rpi\Wall\Member')){

			$member_id = $member->post->ID;

			$groups = $member->get_group_Ids();

			foreach ($groups as $group_id){
				$member->leave_group($group_id);
			}

			$groups = $member->get_liked_group_Ids();
			foreach ($groups as $group_id){
				$member->un_like_group($group_id);
			}
			$groups = $member->get_watched_group_Ids();
			foreach ($groups as $group_id){
				$member->toggle_watch_group($group_id);
			}

			global $wpdb;
			$table = $wpdb->prefix .'posts';

			global $wpdb;
			$wpdb->query($wpdb->prepare("DELETE FROM wp_posts WHERE %s = 'message' AND post_content LIKE %s", [$table,'%?p='.$member_id]));

			wp_delete_post($member_id, true);

		}



	}
}
