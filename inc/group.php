<?php

namespace rpi\Wall;

use Aryess\PhpMatrixSdk\MatrixClient;

class Group extends \stdClass
{

    public $ID;
    public $slug;
    public string $url;
    public $post;
    public $title;
    public $group_status = null;
    public $pending_days;
    public $group_member_min;
    public $matrix_server_home = 'matrix.rpi-virtuell.de';
    public $matrix_server_base = 'rpi-virtuell.de';
    public $link;


    /**
     * @param int|WP_Post|null $post
     *
     * @return Group
     */

    public function __construct($post)
    {
        if (is_a($post, 'WP_POST')) {
            $this->ID = $post->ID;
            $this->post = $post;
        } else {
            $this->post = get_post($post);
            if (!$this->post) {
                echo '<pre>';
                wp_debug_backtrace_summary();
	            new \WP_Error('404', 'Post not found');
                die();
            }
            $this->ID = $this->post->ID;

        }

        $matrixTitle = substr(preg_replace('/[^0-9a-zA-ZüäößÜÄÖ -]*/i', '', $this->post->post_title), 0, 40);

        $this->group_status = $this->get('rpi_wall_group_status');
        $this->slug = $this->get_matrix_channel(true);
        $this->title = '' . $matrixTitle;
        $this->channel_url = "https://{$this->matrix_server_home}/#/room/#{$this->slug}:rpi-virtuell.de";
        $this->pending_days = get_option('options_rpi_wall_pl_group_pending_days', 7);
        $this->group_member_min = get_option('options_rpi_group_min_required_members', 3);

        $this->start_PLG_link = $this->get_startlink();
        $this->link = '<a href="' . get_permalink($this->post) . '#group">' . $this->post->post_title . '</a>';
        $this->url = get_permalink($this->post);

    }

    /**
     * action init
     *
     * überprüft den Gruppenstatus der Pinwandbeiträge
     */
    static function init_cronjob()

    {

        //TODO: calc likers_amount
        //
        //
        //
        // check alle Gruppen, die keinen status, aber likers haben
        // wenn minimum likers erreicht: Gründungsphase zu starten
        $args = [
            'post_type' => 'wall',
            'numberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'rpi_wall_likers_amount',
                    'value' => get_option('options_rpi_group_min_required_members'),
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'rpi_wall_group_status',
                        'compare' => '=',
                        'value' => ''
                    ],
                    [
                        'key' => 'rpi_wall_group_status',
                        'compare' => 'NOT EXISTS',

                    ]
                ]

            ]
        ];

        $posts = get_posts($args);


        foreach ($posts as $post) {
            $group = new Group($post->ID);
            $group->set_status('ready');
            new Message($group, 'ready');
            do_action('rpi_wall_pl_group_ready', $group);
        }

	    $args = [
		    'post_type' => 'wall',
		    'mumberposts' => -1,
		    'meta_query' => [
			    'relation' => 'AND',
			    [
				    'key' => 'rpi_wall_group_status',
				    'value' => 'ready',
				    'compare' => '='
			    ],
			    [
				    'key' => 'rpi_wall_likers_amount',
				    'value' => get_option('options_rpi_group_min_required_members'),
				    'compare' => '<',
				    'type' => 'NUMERIC'
			    ],
		    ]
	    ];
	    $posts = get_posts($args);


	    /**
	     * check groups with the status ready if they still have enough likers
	     */
	    foreach ($posts as $post) {
		    $group = new Group($post->ID);
		    $group->set_status('');
		    //happens when listeners withdraw their interest in a group that already had the status ready
		    do_action('rpi_wall_pl_group_remove_ready-status', $group);
            //@todo add Message to event retreat
		    new Message($group, 'retreat');

	    }


        // check alle Gruppen, die den status pending haben und die pending time abgelaufen ist
        // wenn genug Mitglieder gejoined: create matrix room
        // wenn nicht genug Mitglieder : reset
        $daySeconds = 86400;
        $pending_add = $daySeconds * floatval(get_option('options_rpi_wall_pl_group_pending_days'));

        $args = [
            'post_type' => 'wall',
            'mumberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'rpi_wall_group_status',
                    'value' => 'pending',
                    'compare' => '='
                ],
                [
                    'key' => 'rpi_wall_group_status_timestamp',
                    'value' => time() - $pending_add,
                    'compare' => '<',
                    'type' => 'NUMERIC'
                ]
            ]
        ];


        $posts = get_posts($args);

        foreach ($posts as $post) {

            $group = new Group($post->ID);
            if ($group->get_members_amount() < get_option('options_rpi_group_min_required_members')) {

                $group->reset_status();
                new Message($group, 'reset');
                do_action('rpi_wall_pl_group_reset', $group);

            } else {
                $group->create_room();
                $group->set_status('founded');
                new Message($group, 'founded');
                do_action('rpi_wall_pl_group_founded', $group);
            }

        }

    }

    /**
     * reagiert auf url url request
     */
    static function init_handle_requests()
    {

        //Member möchte einer Gruppe beitreten
        if (!is_admin() && isset($_REQUEST['action']) && isset($_REQUEST['hash']) && isset($_REQUEST['new_plg_group'])) {
            $group = new Group($_REQUEST['new_plg_group']);
            //wenn der Gruppe Gründen Button gedrückt wurde
            if ('plgstart' == $_REQUEST['action'] && $_REQUEST['hash'] == $group->get_hash('start')) {

                $group->start_pending();
                wp_redirect(get_permalink($_REQUEST['new_plg_group']).'#group');
                die();
            }

            // Wenn der Gründungsprozess vorzeitig beendet werden soll
            if ('plgforcefound' == $_REQUEST['action'] && $_REQUEST['hash'] == $group->get_hash('forcefound'))
            {
                    $group->end_status_time();
                    wp_redirect(get_permalink($_REQUEST['new_plg_group']).'#group');
                    die();
            }
        }
    }


    /**
     * @return string
     */
    public function get_status()
    {
        return $this->get('rpi_wall_group_status');
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->get_status());
    }

    public function is_ready()
    {
        return $this->get_status() === 'ready';
    }

    public function is_pending()
    {
        return $this->get_status() === 'pending';
    }

    public function is_not_founded()
    {
        return !in_array($this->get_status(), ['founded', 'closed']);
    }

    public function is_founded()
    {
        return in_array($this->get_status(), ['founded', 'closed']);
    }

    public function is_closed()
    {
        return $this->get_status() === 'closed';
    }

    /**
     * @param string $status ready|pending|founded|closed or null
     *
     * @return void
     */
    public function set_status(string $status)
    {
        if (empty($status)) {
            delete_post_meta($this->ID, 'rpi_wall_group_status');
        } else {
            update_post_meta($this->ID, 'rpi_wall_group_status', $status);
            if ($status == 'pending') {

                $this->set_status_time();

            } elseif ($status == 'founded') {

                //remove all  likers
                foreach ($this->get_likers_Ids() as $user_id) {
                    $m = new Member($user_id);
                    $m->un_like_group($this->ID);

                };

            }
        }

    }

    /**
     * @return void
     */
    protected function set_status_time()
    {
        update_post_meta($this->ID, 'rpi_wall_group_status_timestamp', time());
    }
	protected function end_status_time()
	{
        $daySeconds = 86400;
		$pending_add = $daySeconds * floatval(get_option('options_rpi_wall_pl_group_pending_days'));
		update_post_meta($this->ID, 'rpi_wall_group_status_timestamp', time()-$pending_add-10);
	}

    /**
     * remove members and status
     * if pending time out and noch enouph user du found a PLG
     *
     * @return void
     */
    public function reset_status()
    {

        $this->remove_members();

        delete_post_meta($this->ID, 'rpi_wall_group_status_timestamp');

        $this->set_status('');
    }

    /**
     * @return Date
     */
    public function get_status_date()
    {

        return date('d.n.Y', $this->get('rpi_wall_group_status_timestamp'));
    }

    public function get_countdown($timestamp){

	    $daySeconds = 86400;
	    $end_time = $timestamp + ($this->pending_days * $daySeconds);
	    $pendingtime = $end_time - time();

	    $days = floor($pendingtime / $daySeconds);
	    $hours = floor(($pendingtime - $days * $daySeconds) / 3600);
	    $minutes = floor(($pendingtime / 60) % 60);

	    $format = '%d Tage, %d Stunden und %d Minuten';
	    $timeformated = sprintf($format, $days, $hours, $minutes);
	    return apply_filters('rpi_wall_pendingtime', $timeformated, $days, $hours, $minutes);
    }

    public function get_pending_time()
    {

        if ($this->group_status = 'pending') {

            return $this->get_countdown(intval($this->get('rpi_wall_group_status_timestamp')));
        }

        return '';

    }

    /**
     * @param string $context email|html|matrix
     *
     * @return string
     */
    public function get_matrix_link(string $context = 'html')
    {

        switch ($context) {
            case 'toolbar':
                $matrix = new Matrix();
                //check room exits otherwise create new
	            $roomid = $matrix->create_Room($this);
                $room_slug = $this->get_matrix_channel();

                return  "https://{$this->matrix_server_home}/#/room/{$room_slug}";
                break;
            case 'matrix':
                return "#{$this->slug}:rpi-virtuell.de";
                break;
            case 'html':
	            return KONTO_SERVER.'?action=mredirect&url='.$this->channel_url;
	            break;
            case 'client':
                $room_slug = $this->get_matrix_channel();
                return 'https://matrix.to/#/'.$room_slug;
                break;
            case 'email':
                return $this->channel_url;
                break;
        }
    }

    /**
     * @return array
     */
    public function get_toolbar_buttons()
    {
        return get_field('rpi_wall_group_toolbar_buttons', $this->ID );
    }

    /**
     * @param array $buttons
     * @return void
     */
    public function set_toolbar_buttons(array $buttons){
        update_post_meta($this->ID, 'rpi_wall_group_toolbar_buttons', $buttons);
    }

    /**
     * @return mixed
     */
    public function get_toolbar_status(){
        return get_post_meta($this->ID, 'rpi-wall_group_toolbar_status',true);
    }

    /**
     * @param $status
     * @return void
     */
    public function set_toolbar_status($status)
    {
       update_post_meta($this->ID,'rpi-wall_group_toolbar_status', $status);
    }


    /**
     * @return array WP_User[]
     */
    public function get_likers()
    {


        return get_users(array(
	        "include" =>  $this->get_likers_Ids()

        ));
    }

    /**
     * @return int
     */
    public function get_likers_amount()
    {
        return count($this->get_likers_Ids());
        //return get_post_meta($this->ID, 'like_amount', true);
    }


    /**
     * @return array user Ids
     */
    public function get_likers_Ids()
    {
        $likers = get_post_meta($this->ID, 'rpi_wall_liker_id');
        if ($likers) {
            return $likers;
        }

        return [];

    }

    public function get_liker_and_member_Ids()
    {

        $likers = $this->get_likers_Ids();
        $members = $this->get_memberIds();
        $rest_likers = [];
        foreach ($likers as $liker) {
            if (!in_array($liker, $members)) {
                $rest_likers[] = $liker;
            }
        }

        return (object)[
            'likers' => $rest_likers,
            'members' => $members
        ];

    }

    /**
     * @return array WP_User[]
     */
    public function all_get_comments_liker()
    {

        $likers = get_users(['include', $this->get_comment_liker_Ids()]);

        return $likers;
    }

    /**
     * @return array $user_id[]
     */
    public function all_get_comments_likerIds()
    {

        $comments = get_comments(['post_id' => $this->id]);

        $likers = [];
        foreach ($comments as $comment) {
            $ids = $this->get_comment_liker_Ids($comment->comment_ID);
            foreach ($ids as $user_id) {
                $likers[] = $user_id;
            }
        }

        return array_unique($likers);

    }

    /**
     * @return array $user_id[]
     */
    public function get_comment_liker_Ids($comment_id)
    {
        if(function_exists('wp_ulike_get_likers_list_per_post')){
	        return wp_ulike_get_likers_list_per_post('ulike_comments', 'likers_list', $comment_id, 100);
        }

    }

    /**
     * @return array $user_id[]
     */
    public function get_comment_likes_amount()
    {
        $likes = 0;
	    if(function_exists('wp_ulike_get_comment_likes')) {
		    foreach ( get_comments( [ 'post_id' => $this->ID ] ) as $comment ) {
			    $likes += intval( wp_ulike_get_comment_likes( $comment->comment_ID ) );
		    }
	    }

        return $likes;
    }

    /**
     * @return string comma separated matrix user_ids
     */
    public function get_members_matrix_ids()
    {
        $ids = $this->get_members_matrix_ids();
        $return = [];
        foreach ($ids as $id) {
            if ($matrixId = get_user_meta($id, 'matrixId', true)) {
                $return[] = $matrixId;
            } else {
                $return[] = '@' . get_user_by('ID', $id)->user_login . ':' . $this->matrix_server_base . '(?)';

            }

        }
    }

    /**
     * @return array Member[]
     *
     */
    public function get_members()
    {
        $members = [];
        foreach ($this->get_memberIds() as $member_id) {
            $members[] = new Member($member_id);
        }

        return $members;
    }

    /**
     * @param \WP_User $user_id
     *
     * @return Member
     */
    public function get_member($user_id)
    {
        return new Member($user_id);
    }

    /**
     * @return int
     */
    public function get_members_amount()
    {
//		if(!$this->get_memberIds()){
//			return 0;
//		}
        return count($this->get_memberIds());
    }

    /**
     * @return array
     */
    public function get_memberIds()
    {
        $members = get_post_meta($this->ID, 'rpi_wall_member_id');
        if ($members) {
            return $members;
        }

        return [];
    }

    /**
     * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
     * @return void
     */
    protected function remove_members()
    {
        foreach ($this->get_memberIds() as $user_id) {
            delete_post_meta($this->ID, 'rpi_wall_member_id', $user_id);
            delete_user_meta($user_id, 'rpi_wall_group_id', $this->ID);
        };
    }

    /**
     * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
     * @return void
     */
    protected function remove_likers()
    {
        foreach ($this->get_likers_Ids() as $user_id) {
            delete_post_meta($this->ID, 'rpi_wall_liker_id', $user_id);
            delete_user_meta($user_id, 'rpi_wall_liked_group_id', $this->ID);
        };
    }

    /**
     * @return array $user_id[]
     */
    public function get_watcher_Ids()
    {
        if (!$ids = get_post_meta($this->ID, 'rpi_wall_watcher_id')) {
            $ids = [];
        }

        return $ids;
    }
	/**
	 * @return int
	 */
	public function get_watcher_amount()
	{
		return count($this->get_watcher_Ids());
	}




    /**
     * get metakey value
     *
     * @param $key
     *
     * @return mixed
     */
    protected function get($key)
    {
        return get_post_meta($this->ID, $key, true);
    }

    /**
     * @return string <embed>widgetcontent</embed>
     *
     * Todo
     */
    public function get_toolbar()
    {
        return '';
    }


    protected function get_matrix_channel_id()
    {
        return $this->get('rpi_wall_group_room_id');
    }

    protected function get_matrix_channel($local_slug = false)
    {

        $prefix = get_option('options_matrix_channel_prefix', 'dibes');
        $group_slug = get_option('options_matrix_group_slug', 'plg');
        $ch  = $this->get('rpi_wall_group_channel');
        if(!$ch){
            $ch = '#'.$prefix.'_'.$group_slug.'_' . $this->ID.':'.get_option('options_matrix_server_base','rpi-virtuell.de');
            if($this->get_matrix_room_id()){
	            $this->set_matrix_channel($ch);
            }
            return $prefix.'_'.$group_slug.'_' . $this->ID;
        }
        if($local_slug){
	        $ch = substr($ch, 1, strpos($ch, ':')-1);
        }
        return $ch;
    }

	/**
	 * @param bool $set
	 * @return bool
	 */
	public function has_matrix_toolbar(bool $set = false){

		if($set){
			update_post_meta($this->ID,'matrix_room_has_toolbar',1);
			$return = true;
		}else {
			$return = empty( get_post_meta( $this->ID, 'matrix_room_has_toolbar', true ) ) ? false : true;
		}
		return $return;

	}
    protected function get_joined_member_matrixId($member)
    {
        $matrix = new Matrix();
        return $matrix->get_MatrixRoom_Members($this);
    }

    protected function start_pending()
    {
        $this->set_status('pending');
        new Message($this, 'pending');
	    $founder = new Member();
	    $founder->join_group($this->ID);
        $this->set_founder($founder);
        do_action('rpi_wall_pl_group_pending', $this);

    }

    protected function set_founder($member){

        update_post_meta($this->ID, 'rpi_wall_group_founder', $member->ID );
	    new Message($this, 'founder', array($member->ID));

    }
	protected function get_founder_id(){

		return get_post_meta($this->ID, 'rpi_wall_group_founder', true );


	}

    protected function create_room()
    {

        $matrix = new Matrix();

        $room_id = $matrix->create_room($this);
        if(is_wp_error($room_id)){
            echo $room_id->get_error_message();
        }
        /**
         * Message to orga channel
         * E-Mails to likers
         */
        do_action('rpi_wall_pl_group_room_created', $room_id);
    }

    /**
     * @param string $room_alias
     *
     * @return void
     */

    public function set_matrix_channel($room_alias)
    {
        update_post_meta($this->ID, 'rpi_wall_group_channel', $room_alias);
    }

    /**
     * @param string $room_id
     *
     * @return void
     */

    public function set_matrix_room_id($room_id)
    {
        update_post_meta($this->ID, 'rpi_wall_group_room_id', $room_id);
    }

    public function get_matrix_room_id(){

        return get_post_meta($this->ID, 'rpi_wall_group_room_id', true);;
    }

    public function get_blocksy_login_button($label)
    {

        return '<a href="account-modal" data-id="account" data-state="out" class="ct-account-item button">' . __('Log In') . ', dann<br>' . $label . '</a>';
    }

    public function get_startlink($label = 'Gruppe gründen'): string
    {
        if (!is_user_logged_in()) {
            return $this->get_blocksy_login_button($label);
        }

        return '<a class="button" href="' . get_home_url() . '?action=plgstart&hash=' . $this->get_hash('start') . '&new_plg_group=' . $this->ID . '">' . $label . '</a>';
    }

    public function get_force_end_link($label = 'Beitrittsphase beenden'){

        return '<a class="button" href="' . get_home_url() . '?action=plgforcefound&hash=' . $this->get_hash('forcefound') . '&new_plg_group=' . $this->ID . '">' . $label . '</a>';

    }

    public function get_current_users_joinlink($label = 'Gruppe beitreten')
    {
        if (!is_user_logged_in()) {
            return '<a href="account-modal" data-id="account" data-state="out" class="ct-account-item button">Anmelden</a>';
        }

        $member = new Member();
        if (!$this->has_member($member)) {

            return $member->get_joinlink($this->ID,$label);

        }


    }


    public function get_current_users_requestlink($label = 'Beitritt anfragen')
    {
        if (!is_user_logged_in()) {
            return $this->get_blocksy_login_button($label);
        }
        if(empty($this->get_toolbar_status())){
	        $label = 'Beitritt noch möglich';
        }
        $member = new Member();
        if (!$this->has_member($member)) {
            if ($this->has_liker($member)) {
                return 'Anfrage gestellt';
            } else {
                $hash = $member->get_join_hash($this->ID);
                return '<a class="button" href="' . get_home_url() . '?action=plgrequest&hash=' . $hash . '&new_group_member=' . $member->ID . '">' . $label . '</a>';
            }
        } else {
	        return $this->get_current_users_rejectlink();
        }

    }

    protected function get_current_users_rejectlink($label = 'Anfrage ablehnen')
    {
        $out = '';

        $member_requests = get_post_meta($this->ID,'rpi_wall_member_requests') ;

        if (is_array($member_requests)) {
            foreach ($member_requests as $member_id) {
                $member = new Member($member_id);
	            $props = $member->get_serialized('rpi_wall_group_request',$this->ID);
                $pendingtime = $this->get_countdown($props['timestamp']);
                $out .= $member->get_rejectlink($props['hash'], $pendingtime);
            }

        }
        return $out;


    }

    public function has_member($member)
    {
        if (is_a($member, 'rpi\Wall\Member')) {
            $user_id = $member->ID;
        } else {
            $user_id = $member;
        }
        return in_array($user_id, $this->get_memberIds());
    }

    public function has_liker($member)
    {
        if (is_a($member, 'rpi\Wall\Member')) {
            $user_id = $member->ID;
        } else {
            $user_id = $member;
        }
        return in_array($user_id, $this->get_likers_Ids());
    }

    /**
     * @param string $type start|join
     *
     * @return array|string|string[]
     */
    protected function get_hash($type = 'start')
    {

        $hash = md5($this->slug . '_start_founding_plg');

        if ($type === 'join') {
            $hash = md5($this->slug . '_join_plg');
        }

        return $hash;

    }

    public function get_founding_date(){

	    $prefix = 'Konstitutierendes Treffen in Planung.';
	    $date = get_post_meta($this->ID,'constitution_date_of_meeting', true);

	    if(!empty($date)) {
		    $date = get_post_meta($this->ID,'constitution_date_of_meeting', true);
		    $prefix = 'Gegründet am ';
	    }
        if(!empty($date)){
            $date = date('j.m.Y',strtotime($date));
        }
        return $prefix.$date;

    }
	static function display_watcher_area(){
		if(is_user_logged_in() && 'wall' === get_post_type()){
            $member = new Member();
			$is_watcher = $member->is_watched_group(get_the_ID());
            $group = new Group(get_the_ID());
			$amount = $group->get_watcher_amount();
			$amount = $amount>0?$amount:'';
            $class  = $is_watcher?'watching':'';

            ?>
            <div class="watch-btn-wrapper">
                <button title="beobachten" class="rpi-wall-watch-button <?php echo $class;?>" id="btn-watch-group-<?php the_ID(); ?>">
                    <?php echo ($is_watcher) ? Shortcodes::$watch_icon : Shortcodes::$unwatch_icon; ?>
                </button>
                <span title="Anzahl der Beobachtenden" id="rpi-wall-counter-<?php the_ID();?>"><?php echo $amount; ?></span>
            </div>
            <?php
		}
    }

    //outputs

    public function display_liker_button()
    {
        $amount = $this->is_founded() || $this->is_pending() ? $this->get_members_amount() : $this->get_likers_amount();
        //!$this->has_member(get_current_user_id())
        ?>

            <?php if (is_user_logged_in() && $this->is_not_founded()  ): ?>
                <div class="like-btn-wrapper <?php echo $this->get_status(); ?>">
                    <button class="rpi-wall-like-button" id="btn-like-group-<?php the_ID(); ?>">
                        <?php echo (in_array(get_current_user_id(), $this->get_likers_Ids())||in_array(get_current_user_id(), $this->get_memberIds())) ? Shortcodes::$group_sub_icon : Shortcodes::$group_add_icon; ?>
                        <span class="rpi-wall-counter"><?php echo $amount; ?></span>
                    </button>
                </div>
            <?php endif; ?>

        <?php
    }

    public function display_member($size = 96)
    {
        if ($this->is_pending() || $this->is_founded()) {
            return $this->display_user('member', $size);
        }
    }

    public function display_liker($size = 96)
    {
        return $this->display_user('liker', $size);
    }

    public function display_user($type = 'member', $size = 96)
    {
        $out = '';
        if ($type === 'member') {
            if (!$ids = $this->get_memberIds()) {
                $ids = [];
            }
        } elseif ($type === 'liker') {
            if (!$ids = $this->get_likers_Ids()) {
                $ids = [];
            }
        } else {
            return '';
        }

        if (!wp_doing_ajax()) {
            $out .= '<ul class="rpi-wall-group-' . $type . 's">';
        }
        $zIndex = count($ids);
	    if(true || is_user_logged_in()) { //falls der Datenschützer die User Bilder untersagt, true aus der Bedingung entfernen
            foreach ($ids as $user_id) {

		        $class  = '';
	            if(is_user_logged_in()) {
		            $member = new Member( $user_id );
		            if ( $user_id == get_current_user_id() ) {
			            $class = ' my_avatar';
		            }
	            }else{
		            $member = new \stdClass();
		            $member->name='Mitglied';
	            }

		        $out .= '<li class="group-member' . $class . '" title="' . $member->name . '" style="z-index:' . $zIndex . '">';
                if(is_user_logged_in()){
	                $out .= '<a href ="' . $member->get_member_profile_permalink() . '">';
                }

		        $out .= get_avatar( $user_id, $size );
	            if(is_user_logged_in()) {
		            $out .= '</a>';
	            }

		        $out .= '</li>';
	        }
            $zIndex++;
        }else{
		    $out = 'Gruppe: '.$zIndex .' Mitglieder';
        }
        if (!wp_doing_ajax()) {
            $out .= '</ul>';
        }

        return $out;
    }

    public function display_group_box($size = 96)
    {
        ob_start();
        if(is_user_logged_in()){
        ?>
        <div id="like-group-<?php the_ID(); ?>" class="like-group-box">
            <?php $this->display_liker_button() ?>
            <?php echo $this->display_member($size); ?>
            <?php echo $this->display_liker($size); ?>
        </div>
        <?php
        }else{
            ?>
            <div>
                <a href="account-modal" style="display: inline-block; text-decoration: underline;" data-id="account" data-state="out" class="ct-account-item">Melde dich an</a>, um die Mitglieder dieser Gruppe zu sehen.
            </div>
            <?php
        }
        $out = ob_get_clean();
        return $out;
    }

    public function display()
    {
        $headline = '';
	    $stats = '';
        $notice='';

        switch ($status = $this->get_status()) {
            case'ready':
                $headline = get_option('options_rpi_label_group_rpi_wall_ready_header', 'Die Gründung einer Gruppe ist jetzt möglich.');
                $notice = get_option('options_rpi_label_group_rpi_wall_ready_notice', 'Mit Klick auf "Gruppe Gründen" werden alle Interessierten aufgefordert, der Professionellen Lerngemeinschaft (PLG) beizutreten.');
                $button = $this->get_startlink();
                $stats = $this->get_likers_amount() . ' Interessierte.';
                break;
            case'pending':
                if (intval($this->get_founder_id()) === get_current_user_id() && $this->get_members_amount() >= intval(get_option('options_rpi_group_min_required_members')))
                {
                    $headline =get_option('options_rpi_label_group_rpi_wall_pending_header', 'Wir suchen noch Leute für eine Professionellen Lerngemeinschaft (PLG)');
                    $notice=get_option('options_rpi_label_group_rpi_wall_pending_notice', 'Als Gruppengründer:in kannst du die Beitrittsphase beenden und die Gruppe sofort einrichten.');
                    $button = $this->get_force_end_link();
                }
                else{
                    $headline = get_option('options_rpi_wall_pending_header', 'Wir suchen noch Leute für eine Professionellen Lerngemeinschaft (PLG)');
                    if (!$this->has_member(get_current_user_id())) {
                        $notice = get_option('options_rpi_wall_pending_notice', 'Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?');
                    }
                    $button = $this->get_current_users_joinlink();
                    $stats = 'Noch ' . $this->get_pending_time() . ' um beizutreten.';
                }

                break;
            case'founded':
                $headline = get_option('options_rpi_label_group_rpi_wall_founded_header', 'Professionelle Lerngemeinschaft (PLG)').'. ' . $this->get_founding_date();
                //$notice = get_option('options_rpi_label_group_rpi_wall_founded_notice', '');
                $button = $this->get_current_users_requestlink('Beitritt anfragen');
                //$stats = $this->get_members_amount() . ' Mitglieder.';
                break;
            case'closed':
                $headline = get_option('options_rpi_label_group_rpi_wall_closed_header', 'Professionelle Lerngemeinschaft (PLG) - Arbeitsphase abgeschlossen');
                $notice = get_option('options_rpi_label_group_rpi_wall_closed_notice', '');
                $stats = '';
                break;
            default:
                $headline = get_option('options_rpi_label_group_rpi_wall_not_founded_header', 'Interessiert an einer Professionellen Lerngemeinschaft (PLG)?');
                $notice = get_option('options_rpi_label_group_rpi_wall_not_founded_notice', 'Klicke auf (+) und du wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.');
                $stats = $this->get_likers_amount() . ' von mindestens ' . $this->group_member_min . ' sind interessiert';

        }


        echo '<div class="gruppe ' . strval($status) . '">';
        echo '<div class="gruppe-wrapper">';

        echo '<div class="gruppe-header">' . $headline . '</div>';

        if (!in_array($this->get_status(), ['founded', 'closed'])) {
            echo '<div class="gruppe-liker">';
            echo $this->display_group_box(96);
            echo '</div>';
        } else {
            echo '<div class="gruppe-liker">';
            echo $this->display_group_box(96);
            echo '</div>';
        }

        echo '<div class="gruppe-footer">';
        echo '<div class="notice">' . $notice . ' ' . $stats . '</div>';

        if ($button) {
            echo '<div class="gruppe-button">' . $button . '</div>';
        }
        echo '</div>'; //end footer

        echo '</div>'; //end wrapper
        echo '</div>'; //end gruppe
    }

    public function display_short_info()
    {

        $min_required = get_option('options_rpi_group_min_required_members');
        $max_likes = get_option('options_rpi_wall_max_stars_per_comment', 3);

        switch ($status = $this->get_status()) {
            case'ready':
                $notice = get_option('options_rpi_wall_ready_card_notice', 'Gruppe gründen: ');
                $stats = '<br>'.$this->get_likers_amount() . ' Interessierte';
                break;
            case'pending':
                $notice = get_option('options_rpi_wall_pending_card_notice', 'Beitrittsphase.');
                $stats = $this->get_members_amount() . ' sind beigetreten, ' ;
                break;
            case'founded':
                $notice = get_option('options_rpi_wall_founded_card_notice');
                $notice = $this->display_member(48);
                $stats = $this->get_members_amount() . ' Mitglieder';
                $stats = '';

                break;
            case'closed':
                break;
            default:
	            $i = $this->get_likers_amount();
                if($i>0) {
	                $stats = 'Gruppe: ' . $i . ' Interessierte';
                }
                $notice = '';

        }


        $likes = 0;
	    if(function_exists('wp_ulike_get_comment_likes')) {
		    foreach ( get_comments( [ 'post_id' => $this->ID ] ) as $comment ) {
			    $likes += intval( wp_ulike_get_comment_likes( $comment->comment_ID ) );
		    }
	    }
        if ($likes > 0) {
            $n = $likes;
            if ($likes > $max_likes) {
                $n = $max_likes;
                $addlikes = $likes - $max_likes;
                $style = '<style>#more-likes-' . get_the_ID() . '::after{ content: "+' . $addlikes . '";}</style>';
            }
        }

        echo '<div class="ct-ghost"></div>';
        echo '<div class="card_plg_info ' . strval($status) . '">';
        echo '<div class="plg-wrapper">';
        echo '<div class="plg plg-' . $status . '">
						<a href="' . get_the_permalink() . '#group">' . $notice . '</a>
						<span plg-stats>' . $stats . '</span>';
        echo '</div>';
        echo '<div class="hot-comments">';
        for ($i = 0; $i < $n; $i++) {
            echo '<i id="more-likes-' . get_the_ID() . '" class="wp_ulike_star_icon ulp-icon-star"></i>';

        }
        if ($addlikes) echo "<i class='addlikes'>+$addlikes</i>";
        echo '</div>';
        echo '</div>';
        echo '</div>';

    }

    public function get_serialized($meta_key, $key = false){

		$val = get_post_meta($this->ID,$meta_key,true);
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
		update_post_meta($this->ID,$meta_key,serialize($values));
	}

	public function set_serialized($meta_key,$key,$value = true){
		$values = $this->get_serialized($meta_key);
		$values[$key] = $value;
		update_post_meta($this->ID,$meta_key,serialize($values));
	}

	/**
     * @uses action_hook trashed_post
     *
	 * @return void
	 */
    static function on_group_delete(int $post_id ){

        $group = new Group($post_id);
        if(is_a($group, 'rpi\Wall\Group') && is_a( $group->post, 'WP_Post' ) && 'wall' === $group->post->post_type ) {
	        $user_arr = [];

            //find all users associated with this group
            $users = $group->get_likers_Ids();
	        $user_arr = array_merge( $user_arr, $users );

            $users = $group->get_memberIds();
	        $user_arr = array_merge( $user_arr, $users );

            $users = $group->get_watcher_Ids();
	        $user_arr = array_merge( $user_arr, $users );

            $user_id = $group->get_founder_id();
            if ( intval( $user_id ) > 0 ) {
                $user_arr[] = $user_id;
            }

            //remove duplicates
	        $user_arr = array_unique( $user_arr );

            foreach ( $user_arr as $user_id ) {

                //remove from watchlist
                delete_user_meta( $user_id, 'rpi_wall_watched_group_id', $post_id );
                //remove from likes
                delete_user_meta( $user_id, 'rpi_wall_liked_group_id', $post_id );
                //remove membership
                delete_user_meta( $user_id, 'rpi_wall_group_id', $post_id );

            }

            //delete all Messages associsated with this group
            global $wpdb;
            $wpdb->query( $wpdb->prepare( "DELETE FROM wp_posts WHERE post_type = 'message' AND post_content LIKE %s", '%?p=' . $post_id . '#group%' ) );

        }
    }
}


