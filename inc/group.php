<?php

namespace rpi\Wall;

use rpi\Wall\Matrix\Helper;

class Group extends \stdClass {

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


	/**
	 * @param int|WP_Post|null $post
	 *
	 * @return Group
	 */

	public function __construct( $post ) {
		if ( is_a( $post, 'WP_POST' ) ) {
			$this->ID   = $post->ID;
			$this->post = $post;
		} else {
			$this->post = get_post( $post );
			if ( ! $this->post ) {
				return new \WP_Error( '404', 'Post not found' );
			}
			$this->ID = $this->post->ID;
		}

		$matrixTitle = substr( preg_replace( '/[^a-zA-ZüäößÜÄÖ -]*/i', '', $this->post->post_title ), 0, 40 );

		$this->group_status     = $this->get( 'pl_group_status' );
		$this->slug             = 'dibes_plg_' . $this->ID;
		$this->title            = 'PLG ' . $matrixTitle;
		$this->channel_url      = "https://{$this->matrix_server_home}/#/room/#{$this->slug}:rpi-virtuell.de";
		$this->pending_days     = get_option( 'options_rpi_wall_pl_group_pending_days', 7 );
		$this->group_member_min = get_option( 'options_pl_group_min_required_members', 3 );

		$this->start_PLG_link = $this->get_starlink();


	}

	/**
	 * action init
	 *
	 * überprüft den Gruppenstatus der Pinwandbeiträge
	 */
	static function init_cronjob() {

		//TODO: calc likers_amount
		//
		//
		//
		// check alle Gruppen, die keinen status, aber likers haben
		// wenn minimum likers erreicht: Gründungsphase zu starten

		$args = [
			'post_type'   => 'wall',
			'numberposts' => - 1,
			'meta_query'  => [
				'relation' => 'AND',
				[
					'key'     => 'rpi_wall_likers_amount',
					'value'   => get_option( 'options_pl_group_min_required_members', 3 ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				],
				[
					'key'     => 'pl_group_status',
					'compare' => 'NOT EXISTS'
				]
			]
		];

		$posts = get_posts( $args );


		foreach ( $posts as $post ) {
			$group = new Group( $post->ID );
			$group->set_status( 'ready' );
			new Message( $group, 'ready', [ 'orga', 'group' ] );
			do_action( 'rpi_wall_pl_group_ready', $group );
		}


		// check alle Gruppen, die den status pending haben und die pending time abgelaufen ist
		// wenn genug Mitglieder gejoined: create matrix room
		// wenn nicht genug Mitglieder : reset
		$daySeconds  = 86400;
		$pending_add = $daySeconds * intval( get_option( 'options_rpi_wall_pl_group_pending_days', 7 ) );

		$args = [
			'post_type'   => 'wall',
			'mumberposts' => - 1,
			'meta_query'  => [
				'relation' => 'AND',
				[
					'key'     => 'pl_group_status',
					'value'   => 'pending',
					'compare' => '='
				],
				[
					'key'     => 'pl_group_status_timestamp',
					'value'   => time() - $pending_add,
					'compare' => '<',
					'type'    => 'NUMERIC'
				]
			]
		];


		$posts = get_posts( $args );
		foreach ( $posts as $post ) {

			$group = new Group( $post->ID );
			if ( $group->get_members_amount() < get_option( 'options_pl_group_min_required_members', 3 ) ) {
				$group->reset_status();
				new Message( $group, 'reset' );
				do_action( 'rpi_wall_pl_group_reset', $group );

			} else {
				$group->create_room();
				$group->set_status( 'founded' );
				new Message( $group, 'founded' );
				do_action( 'rpi_wall_pl_group_founded', $group );
			}

		}

	}

	/**
	 * reagiert auf url url request
	 */
	static function init_handle_requests() {

		//member möchte einer Gruppe beitreten
		if ( ! is_admin() && isset( $_REQUEST['action'] ) && isset( $_REQUEST['hash'] ) && isset( $_REQUEST['new_plg_group'] ) ) {
			$group = new Group( $_REQUEST['new_plg_group'] );
			if ( 'plgstart' == $_REQUEST['action'] && $_REQUEST['hash'] == $group->get_hash( 'start' ) ) {

				$group->start_pending();
			}

		}
	}


	/**
	 * @return string
	 */
	public function get_status() {
		return $this->get( 'pl_group_status' );
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->get_status() );
	}

	/**
	 * @return bool
	 */
	public function is_ready() {
		return $this->get_status() === 'ready';
	}

	/**
	 * @return bool
	 */
	public function is_pending() {
		return $this->get_status() === 'pending';
	}
	public function is_not_founded() {
		return !in_array($this->get_status(),['founded', 'closed'] );
	}

	/**
	 * @return bool
	 */
	public function is_founded() {
		return in_array($this->get_status(),['founded', 'closed'] );
	}

	/**
	 * @return bool
	 */
	public function is_closed() {
		return $this->get_status() === 'closed';
	}

	/**
	 * @param string $status ready|pending|founded|closed or null
	 *
	 * @return void
	 */
	public function set_status( string $status ) {
		if ( empty( $status ) ) {
			delete_post_meta( $this->ID, 'pl_group_status' );
		} else {
			update_post_meta( $this->ID, 'pl_group_status', $status );
			if ( $status == 'pending' ) {
				$this->set_status_time();
			}
		}

	}

	/**
	 * @return void
	 */
	protected function set_status_time() {
		update_post_meta( $this->ID, 'pl_group_status_timestamp', time() );
	}

	/**
	 * remove members and status
	 * if pending time out and noch enouph user du found a PLG
	 *
	 * @return void
	 */
	public function reset_status() {

		$this->remove_members();

		delete_post_meta( $this->ID, 'pl_group_status_timestamp' );

		$this->set_status( '' );
	}

	/**
	 * @return Date
	 */
	public function get_status_date() {

		return date( 'd.n.Y', $this->get( 'pl_group_status_timestamp' ) );
	}

	public function get_pending_time() {

		if ( $this->group_status = 'pending' ) {
			$daySeconds = 86400;

			$end_time    = $this->get( 'pl_group_status_timestamp' ) + ( $this->pending_days * $daySeconds );
			$pendingtime = $end_time - time();

			$days    = floor( $pendingtime / 86400 );
			$hours   = floor( ( $pendingtime - $days * 86400 ) / 3600 );
			$minutes = floor( ( $pendingtime / 60 ) % 60 );

			$format       = '%d Tage, %d Stunden und %d Minuten';
			$timeformated = sprintf( $format, $days, $hours, $minutes );

			return apply_filters( 'rpi_wall_pendingtime', $timeformated, $days, $hours, $minutes );
		}

		return '';

	}


	/**
	 * @param string $context email|html|matrix
	 *
	 * @return string
	 */
	public function get_matrix_link( string $context = 'html' ) {
		switch ( $context ) {
			case 'html':
				return '<a href="' . $this->channel_url . '">#' . $this->slug . ':rpi-virtuell.de</a>';
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
	public function get_likers() {

		return get_users( array(
			"include" => wp_ulike_get_likers_list_per_post( 'ulike', 'likers_list', $this->ID, 100 )
		) );
	}

	/**
	 * @return int
	 */
	public function get_likers_amount() {
		return count( $this->get_likers_Ids() );
		//return get_post_meta($this->ID, 'like_amount', true);

	}

	/**
	 * @return array user Ids
	 */
	public function get_likers_Ids() {
		$likers = get_post_meta( $this->ID, 'rpi_wall_liker_id' );
		if ( $likers ) {
			return $likers;
		}

		return [];

		//return wp_ulike_get_likers_list_per_post('ulike', 'likers_list', $this->ID, 100);
	}

	public function get_liker_and_member_Ids() {

		//$likers =  wp_ulike_get_likers_list_per_post('ulike', 'likers_list', $this->ID, 100);
		$likers      = $this->get_likers_Ids();
		$members     = $this->get_memberIds();
		$rest_likers = [];
		foreach ( $likers as $liker ) {
			if ( ! in_array( $liker, $members ) ) {
				$rest_likers[] = $liker;
			}
		}

		return (object) [
			'likers'  => $rest_likers,
			'members' => $members
		];

	}

	/**
	 * @return array WP_User[]
	 */
	public function all_get_comments_liker() {

		$likers = get_users( [ 'include', $this->get_comment_liker_Ids() ] );

		return $likers;
	}

	/**
	 * @return array $user_id[]
	 */
	public function all_get_comments_likerIds() {

		$comments = get_comments( [ 'post_id' => $this->id ] );

		$likers = [];
		foreach ( $comments as $comment ) {
			$ids = $this->get_comment_liker_Ids( $comment->comment_ID );
			foreach ( $ids as $user_id ) {
				$likers[] = $user_id;
			}
		}

		return array_unique( $likers );

	}

	/**
	 * @return array $user_id[]
	 */
	public function get_comment_liker_Ids( $comment_id ) {
		return wp_ulike_get_likers_list_per_post( 'ulike_comments', 'likers_list', $comment_id, 100 );
	}

	/**
	 * @return array $user_id[]
	 */
	public function get_comment_likes_amount() {
		$likes = 0;
		foreach ( get_comments( [ 'post_id' => $this->ID ] ) as $comment ) {
			$likes += intval( wp_ulike_get_comment_likes( $comment->comment_ID ) );
		}

		return $likes;
	}

	/**
	 * @return string comma separated matrix user_ids
	 */
	public function get_members_matrix_ids() {
		$ids    = $this->get_members_matrix_ids();
		$return = [];
		foreach ( $ids as $id ) {
			if ( $matrixId = get_user_meta( $id, 'matrixId', true ) ) {
				$return[] = $matrixId;
			} else {
				$return[] = '@' . get_user_by( 'ID', $id )->user_login . ':' . $this->matrix_server_base . '(?)';

			}

		}
	}

	/**
	 * @return array member[]
	 *
	 */
	public function get_members() {
		$members = [];
		foreach ( $this->get_memberIds() as $member_id ) {
			$members[] = new member( $member_id );
		}

		return $members;
	}

	/**
	 * @param \WP_User $user_id
	 *
	 * @return member
	 */
	public function get_member( $user_id ) {
		return new member( $user_id );
	}

	/**
	 * @return int
	 */
	public function get_members_amount() {
//		if(!$this->get_memberIds()){
//			return 0;
//		}
		return count( $this->get_memberIds() );
	}

	/**
	 * @return array
	 */
	public function get_memberIds() {
		$members = get_post_meta( $this->ID, 'rpi_wall_member_id' );
		if ( $members ) {
			return $members;
		}

		return [];
	}

	/**
	 * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
	 * @return void
	 */
	protected function remove_members() {
		foreach ( $this->get_memberIds() as $user_id ) {
			delete_post_meta( $this->ID, 'rpi_wall_member_id', $user_id );
			delete_user_meta( $user_id, 'rpi_wall_group_id', $this->ID );
		};
	}

	/**
	 * Remove UserIds from Wall Post Meta (Group) und PostIds from User Meta
	 * @return void
	 */
	protected function remove_likers() {
		foreach ( $this->get_likers_Ids() as $user_id ) {
			delete_post_meta( $this->ID, 'rpi_wall_liker_id', $user_id );
			delete_user_meta( $user_id, 'rpi_wall_liked_group_id', $this->ID );
		};
	}

	/**
	 * @return array $user_id[]
	 */
	public function get_watcherIds() {
		return get_post_meta( $this->ID, 'rpi_wall_watcher_id' );
	}

	/**
	 * @return array WP_User[]
	 */
	public function get_watcher() {
		return get_users( [ 'include' => $this->get_watcherIds() ] );
	}

	/**
	 * @return int
	 */
	public function get_watcher_amount() {
		return count( $this->get_watcherIds() );
	}

	/**
	 * get metakey value
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	protected function get( $key ) {
		return get_post_meta( $this->ID, $key, true );
	}

	/**
	 * @return string <embed>widgetcontent</embed>
	 *
	 * Todo
	 */
	public function get_toolbar() {
		return '';
	}


	protected function get_matrix_channel_id() {
		return $this->get( 'rpi_wall_group_room_id' );
	}

	protected function get_matrix_channel() {
		return $this->get( 'rpi_wall_group_channel' );
	}

	protected function get_joined_member_matrixId( $user_login ) {
		return Matrix\Helper::getUser( $user_login );
	}


	protected function start_pending() {
		$this->set_status( 'pending' );
		new Message( $this, 'pending' );
		$starter = new member();
		$starter->join_group( $this->ID );
		do_action( 'rpi_wall_pl_group_pending', $this );

	}

	protected function create_room() {

		$room_id = Matrix\Helper::create_room( $this );


		/**
		 * Message to orga channel
		 * E-Mails to likers
		 */
		do_action( 'rpi_wall_pl_group_room_created', $room_id );
	}

	/**
	 * @param string $room_alias
	 *
	 * @return void
	 */

	public function set_matrix_channel( $room_alias ) {
		update_post_meta( $this->ID, 'rpi_wall_group_channel', $room_alias );
	}

	/**
	 * @param string $room_id
	 *
	 * @return void
	 */

	public function set_matrix_room_id( $room_id ) {
		update_post_meta( $this->ID, 'rpi_wall_group_room_id', $room_id );
	}

	public function get_starlink( $label = 'Gruppe gründen' ) {

		return '<a class="button" href="' . get_home_url() . '?action=plgstart&hash=' . $this->get_hash( 'start' ) . '&new_plg_group=' . $this->ID . '">' . $label . '</a>';
	}


	public function get_current_users_joinlink( $label = 'Gruppe beitreten' ) {
		$member = new member();

		if ( ! $this->has_member( $member ) ) {
			$hash = $member->get_join_hash( $this->ID );

			return '<a class="button" href="' . get_home_url() . '?action=plgjoin&hash=' . $hash . '&new_group_member=' . $member->ID . '">' . $label . '</a>';
		}


	}

	public function has_member( $member ) {
		if ( is_a( $member, 'rpi\Wall\member' ) ) {
			$user_id = $member->ID;
		} else {
			$user_id = $member;
		}
        return in_array( $user_id, $this->get_memberIds() );
	}

	/**
	 * @param string $type start|join
	 *
	 * @return array|string|string[]
	 */
	protected function get_hash( $type = 'start' ) {

		$hash = md5( $this->slug . '_start_founding_plg' );

		if ( $type === 'join' ) {
			$hash = md5( $this->slug . '_join_plg' );
		}

		return $hash;

	}


	//outputs

	public function display_liker_button(){
		$amount = $this->is_founded()||$this->is_pending()?$this->get_members_amount():$this->get_likers_amount();

        ?>
        <div class="like-btn-wrapper <?php echo $this->get_status();?>">
			<?php if(is_user_logged_in()  && $this->is_not_founded() && !$this->has_member(get_current_user_id())): ?>
                <button class="rpi-wall-like-button" id="btn-like-group-<?php the_ID();?>">
					<?php echo (in_array(get_current_user_id(),$this->get_likers_Ids()))? Shortcodes::$group_sub_icon: Shortcodes::$group_add_icon;?>
                </button>
			<?php else:?>
                <button class="rpi-wall-like-button">
					<?php echo Shortcodes::$group_icon;?>
                </button>
			<?php endif;?>
            <span class="rpi-wall-counter"><?php echo $amount;?></span>

        </div>
		<?php
	}

	public function display_member( $size = 96 ) {
        if($this->is_pending() || $this->is_founded()){
	        return $this->display_user('member', $size);
        }
	}

	public function display_liker($size = 96){
        if($this->is_not_founded()){
	        return $this->display_user('liker', $size);
        }
    }

	public function display_user($type='member', $size = 96 ) {
		$out ='';
		if($type==='member'){
			if(!$ids = $this->get_memberIds()){
				$ids =[];
			}
		}elseif($type==='liker'){
			if(!$ids = $this->get_likers_Ids()){
				$ids =[];
			}
		}else{
			return '';
		}

        if (! wp_doing_ajax() ) {
            $out .= '<ul class="rpi-wall-group-' . $type . 's">';
        }
        $zIndex = count($ids);
        foreach ( $ids as $user_id ) {
	        $class ='';
            $member = new member($user_id);
            if($user_id == get_current_user_id()){
                $class = ' my_avatar';
            }

            $out  .= '<li class="group-member'.$class.'" title="' . $member->name . '" style="z-index:'.$zIndex.'">';
	        $out .= '<a href ="'.$member->get_member_profile_permalink().'">';
	        $out  .= get_avatar( $user_id, $size );
            $out .= '</a>';
            $out  .= '</li>';
	        $zIndex --;
        }
        if (! wp_doing_ajax() ) {
            $out .= '</ul>';
        }

		return $out;
	}

    public function display_group_box($size = 96)
    {
        ob_start()
        ?>
        <div id="like-group-<?php the_ID();?>" class="like-group-box">
            <?php $this->display_liker_button() ?>
            <?php echo $this->display_member($size);?>
		    <?php echo $this->display_liker($size);?>
        </div>
        <?php
        $out = ob_get_clean();
        return $out;
    }

	public function display() {


		switch ( $status = $this->get_status() ) {
			case'ready':
				$headline = get_option( 'options_rpi_wall_ready_header', 'Professionellen Lerngruppe (PLG)' );
				$notice   = get_option( 'options_rpi_wall_ready_notice', 'Mit Klick auf "Gruppe Gründen" werden alle interessierten angeschrieben und haben eine Woche Zeit, der PLG beizutreten.' );
				$button   = $this->get_starlink();
				$stats    = $this->get_likers_amount() . ' Interessierte.';
				break;
			case'pending':
				$headline = get_option( 'options_rpi_wall_pending_header', 'Wir suchen noch Leute für eine Professionellen Lerngruppe (PLG) zu diesem Kontext' );
				if ( ! $this->has_member( get_current_user_id() ) ) {
					$notice = get_option( 'options_rpi_wall_pending_notice', 'Die Gruppe befindet sich in der Gründungsphase. Möchtest du dabei sein?' );
				}

				$button = $this->get_current_users_joinlink();
				$stats  = 'Noch '.$this->get_pending_time() .' um beizutreten.';
				break;
			case'founded':
				$headline = get_option( 'options_rpi_wall_founded_header', 'Professionelle Lerngruppe (PLG) zu diesem Kontext' );
				$notice   = get_option( 'options_rpi_wall_founded_notice', 'Zu diesem Pinwandeintrag hat sich eine PLG gegründet.' );
				$button   = $this->get_current_users_joinlink( 'Beitritt anfragen' );
				$stats    = $this->get_members_amount() . ' Mitglieder.';
				break;
			case'closed':
				$headline = get_option( 'options_rpi_wall_closed_header', 'Professionelle Lerngruppe (PLG) zu diesem Kontext' );
				$notice   = get_option( 'options_rpi_wall_closed_notice', '' );
				$stats    = 'Gruppe geschlossen';
				break;
			default:
				$headline = get_option( 'options_rpi_wall_not_founded_header', 'Interessiert an einer Professionellen Lerngruppe (PLG) zu diesem Kontext?' );
				$notice   = get_option( 'options_rpi_wall_not_founded_notice', 'Wenn du zu den Interessierten gehörst, wirst du automatisch benachrichtigt, sobald sich genügend Interessenten gefunden haben.' );
				$stats    = $this->get_likers_amount() . ' von mindestens ' . $this->group_member_min . ' sind interessiert';

		}


		echo '<div class="gruppe ' . strval( $status ) . '">';
		echo '<div class="gruppe-wrapper">';

		echo '<div class="gruppe-header">' . $headline . '</div>';

		if ( ! in_array( $this->get_status(), [ 'founded', 'closed' ] ) ) {
			echo '<div class="gruppe-liker">';
			echo $this->display_group_box( 96 );
			//echo do_shortcode('[wp_ulike  style="wpulike-heart"]');
			echo '</div>';
		} else {
			echo '<div class="gruppe-liker">';
			echo $this->display_group_box( 96 );
			echo '</div>';
		}

		echo '<div class="gruppe-footer">';
		echo '<div class="notice">' . $notice . ' ' . $stats . '</div>';

		if ( $button ) {
			echo '<div class="gruppe-button">' . $button . '</div>';
		}
		echo '</div>'; //end footer

		echo '</div>'; //end wrapper
		echo '</div>'; //end gruppe
	}

    public function display_short_info()
    {

        $min_required = get_option('options_pl_group_min_required_members', 3);
        $max_likes = get_option('options_rpi_wall_max_stars_per_comment', 3);

        switch ($status = $this->get_status()) {
            case'ready':
                $notice = get_option('options_rpi_wall_ready_card_notice', 'PLG gründen: ');
                $stats = $this->get_likers_amount() . ' Interessierte';
                break;
            case'pending':
                $notice = get_option('options_rpi_wall_pending_card_notice', 'Beitrittsphase zu einer PLG läuft.');
                $stats = $this->get_likers_amount() . ' beigetreten, '. $this->get_members_amount().' interessiert';
                break;
            case'founded':
                $notice = get_option('options_rpi_wall_founded_card_notice');
                $notice = $this->display_member(48);
                $stats = $this->get_members_amount() .' Mitglieder';

                break;
            case'closed':
                $notice = get_option('options_rpi_wall_closed_card_notice', 'PLG beendet');
                break;
            default:
                $notice = '';
                $stats = ($i = $this->get_likers_amount()) > 0 ? $i. ' interessiert' : '';
        }


	    $likes = 0;
	    foreach (get_comments(['post_id' => $this->ID]) as $comment) {
		    $likes += intval(wp_ulike_get_comment_likes($comment->comment_ID));
	    }
	    if ($likes > 0) {
		    $n = $likes;
		    if ($likes > $max_likes) {
			    $n = $max_likes;
			    $addlikes = $likes - $max_likes;
			    $style = '<style>#more-likes-' . get_the_ID() . '::after{ content: "+' . $addlikes . '";}</style>';
		    }
	    }

	    echo '<div class="card_plg_info '.strval($status).'">';
        echo '<div class="plg-wrapper">';
        echo '<div class="plg plg-' . $status . '">
						<a href="' . get_the_permalink() . '">' . $notice . '</a>
						<span plg-stats>' . $stats . '</span>';
        echo '</div>';
        echo '<div class="hot-comments">';
        for ($i = 0; $i < $n; $i++) {
            echo '<i id="more-likes-' . get_the_ID() . '" class="wp_ulike_star_icon ulp-icon-star"></i>';

        }
		if($addlikes) echo "<i class='addlikes'>+$addlikes</i>";
	    echo '</div>';
        echo '</div>';
        echo '</div>';

    }
}


