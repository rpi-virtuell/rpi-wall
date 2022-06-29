<?php
namespace rpi\Wall;

class Shortcodes{

    static $user_icon       = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 6c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2m0 10c2.7 0 5.8 1.29 6 2H6c.23-.72 3.31-2 6-2m0-12C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    static $date_icon       = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5z"/></svg>';
    static $group_icon      = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M6.32,13.01c0.96,0.02,1.85,0.5,2.45,1.34C9.5,15.38,10.71,16,12,16c1.29,0,2.5-0.62,3.23-1.66 c0.6-0.84,1.49-1.32,2.45-1.34C16.96,11.78,14.08,11,12,11C9.93,11,7.04,11.78,6.32,13.01z"/><path d="M4,13L4,13c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3s-3,1.34-3,3C1,11.66,2.34,13,4,13z"/><path d="M20,13L20,13c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3s-3,1.34-3,3C17,11.66,18.34,13,20,13z"/><path d="M12,10c1.66,0,3-1.34,3-3c0-1.66-1.34-3-3-3S9,5.34,9,7C9,8.66,10.34,10,12,10z"/><path d="M21,14h-3.27c-0.77,0-1.35,0.45-1.68,0.92C16.01,14.98,14.69,17,12,17c-1.43,0-3.03-0.64-4.05-2.08 C7.56,14.37,6.95,14,6.27,14H3c-1.1,0-2,0.9-2,2v4h7v-2.26c1.15,0.8,2.54,1.26,4,1.26s2.85-0.46,4-1.26V20h7v-4 C23,14.9,22.1,14,21,14z"/></g></g></svg>';
    static $group_add_icon  = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/><rect fill="none" height="24" width="24"/></g><g><g><polygon points="22,9 22,7 20,7 20,9 18,9 18,11 20,11 20,13 22,13 22,11 24,11 24,9"/><path d="M8,12c2.21,0,4-1.79,4-4s-1.79-4-4-4S4,5.79,4,8S5.79,12,8,12z M8,6c1.1,0,2,0.9,2,2s-0.9,2-2,2S6,9.1,6,8S6.9,6,8,6z"/><path d="M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z"/><path d="M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z"/><path d="M16.53,13.83C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></g></g></svg>';
    static $group_sub_icon  = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><rect fill="none" height="24" width="24"/><path d="M24,9v2h-6V9H24z M8,4C5.79,4,4,5.79,4,8s1.79,4,4,4s4-1.79,4-4S10.21,4,8,4z M8,10c-1.1,0-2-0.9-2-2s0.9-2,2-2s2,0.9,2,2 S9.1,10,8,10z M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z M16.53,13.83 C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></svg>';
    static $tag_icon        = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM13 20.01L4 11V4h7v-.01l9 9-7 7.02z"/><circle cx="6.5" cy="6.5" r="1.5"/></svg>';
    static $tag2_icon       = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16zM16 17H5V7h11l3.55 5L16 17z"/></svg>';
    static $taxonomy_icon   = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5v-3h3.56c.69 1.19 1.97 2 3.45 2s2.75-.81 3.45-2H19v3zm0-5h-4.99c0 1.1-.9 2-2 2s-2-.9-2-2H5V5h14v9z"/></svg>';
    static $folder_icon     = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M9.17 6l2 2H20v10H4V6h5.17M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
    static $like_icon       = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/><rect fill="none" height="24" width="24"/></g><g><g><polygon points="22,9 22,7 20,7 20,9 18,9 18,11 20,11 20,13 22,13 22,11 24,11 24,9"/><path d="M8,12c2.21,0,4-1.79,4-4s-1.79-4-4-4S4,5.79,4,8S5.79,12,8,12z M8,6c1.1,0,2,0.9,2,2s-0.9,2-2,2S6,9.1,6,8S6.9,6,8,6z"/><path d="M8,13c-2.67,0-8,1.34-8,4v3h16v-3C16,14.34,10.67,13,8,13z M14,18H2v-0.99C2.2,16.29,5.3,15,8,15s5.8,1.29,6,2V18z"/><path d="M12.51,4.05C13.43,5.11,14,6.49,14,8s-0.57,2.89-1.49,3.95C14.47,11.7,16,10.04,16,8S14.47,4.3,12.51,4.05z"/><path d="M16.53,13.83C17.42,14.66,18,15.7,18,17v3h2v-3C20,15.55,18.41,14.49,16.53,13.83z"/></g></g></svg>';
    static $watch_icon      = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 6c3.79 0 7.17 2.13 8.82 5.5C19.17 14.87 15.79 17 12 17s-7.17-2.13-8.82-5.5C4.83 8.13 8.21 6 12 6m0-2C7 4 2.73 7.11 1 11.5 2.73 15.89 7 19 12 19s9.27-3.11 11-7.5C21.27 7.11 17 4 12 4zm0 5c1.38 0 2.5 1.12 2.5 2.5S13.38 14 12 14s-2.5-1.12-2.5-2.5S10.62 9 12 9m0-2c-2.48 0-4.5 2.02-4.5 4.5S9.52 16 12 16s4.5-2.02 4.5-4.5S14.48 7 12 7z"/></svg>';
    static $mail_icon       = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/></svg>';
    static $pin_icon       =  '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><path d="M14,4v5c0,1.12,0.37,2.16,1,3H9c0.65-0.86,1-1.9,1-3V4H14 M17,2H7C6.45,2,6,2.45,6,3c0,0.55,0.45,1,1,1c0,0,0,0,0,0l1,0v5 c0,1.66-1.34,3-3,3v2h5.97v7l1,1l1-1v-7H19v-2c0,0,0,0,0,0c-1.66,0-3-1.34-3-3V4l1,0c0,0,0,0,0,0c0.55,0,1-0.45,1-1 C18,2.45,17.55,2,17,2L17,2z"/></g></svg>';


    public $user;
    public $is_member_page = false;

	public function __construct() {

		//add_shortcode( 'user_pinned_posts', [$this,'get_users_pinwall_posts'] );
		add_shortcode('my_tags', array($this, 'get_user_profile_tags'));
        add_shortcode('my_messages', array($this, 'get_user_messages'));
        add_shortcode('my_groups', array($this, 'get_user_groups'));
        add_shortcode('my_likes', array($this, 'get_user_likes'));
		add_shortcode( 'my_posts', [$this,'get_user_posts'] );
		add_shortcode('my_comments', array($this, 'get_user_comments'));

        add_action('wp_head',array($this, 'init'));

	}

	public function init(){
		//$this->user = \wp_ulike_pro_get_current_user();

		if('member' === get_post_type()){
			$this->user = get_userdata (get_post()->post_author);
			$this->is_member_page = true;
		}
		if(!$this->user->ID && is_user_logged_in()){
			$this->user = wp_get_current_user();
		}



		?>
        <script>
            const wallIcons={
                group       : <?php echo json_encode(self::$group_icon);?>,
                group_add   : <?php echo json_encode(self::$group_add_icon);?>,
                group_sub   : <?php echo json_encode(self::$group_sub_icon);?>,
                user        : <?php echo json_encode(self::$user_icon);?>,
                pin         : <?php echo json_encode(self::$pin_icon);?>,
                tag2        : <?php echo json_encode(self::$tag2_icon);?>,
                tag         : <?php echo json_encode(self::$tag_icon);?>,
                tax         : <?php echo json_encode(self::$taxonomy_icon);?>,
                like        : <?php echo json_encode(self::$like_icon);?>,
                folder      : <?php echo json_encode(self::$folder_icon);?>,
                watch       : <?php echo json_encode(self::$watch_icon);?>,
                mail        : <?php echo json_encode(self::$mail_icon);?>
            }
        </script>
		<?php

	}

    public function is_member_page(){
        return $this->is_member_page;
    }


	/**
     * [my_comments]
     * echo self::get_user_comments();
     *
	 * @param $atts
	 *
	 * @return false|string
	 */
	public function get_user_comments($atts){

        ob_start();
        $member  = new member($this->user->ID);
        foreach ($member->get_my_comments_query() as $comment){
            ?>
            <div class="member-coment">
	            <?php echo $member->display(24); ?>
                <div class="entry-title">
                    <?php echo $comment->comment; ?>
                </div>
                <div class="entry-content">
	                <?php echo  $comment->comment_content; ?>
                </div>
                <div class="entry-post-permalink">
                    <div class="pin-icon"><?php echo self::$pin_icon;?></div>
                    <a href="<?php echo  get_comment_link($comment);?>"><?php echo $comment->post->post_title; ?></a>
                </div>

            </div>
            <?php

        }
	    return ob_get_clean();

    }



	/**
	 * [user_pinned_posts]
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function get_users_pinwall_posts($atts){
		global $post;

		$attributes = shortcode_atts( array(
			'post_type' => 'wall',
			'past_days' => '',
			'per_page'  => 10
		), $atts );

		if( strpos( $attributes['post_type'], ',' ) ){
			$attributes['post_type'] = explode(',', $attributes['post_type']);
		}

		$currentUser = wp_ulike_pro_get_current_user();
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



				blocksy_render_archive_card();

			endforeach;
			wp_reset_postdata();

		}
		echo '</div>';
	}

    public function get_user_profile_tags($atts)
    {
	    $out ='';

        if('member' === get_post_type()){

            if (isset($atts['content'])) {

                if (taxonomy_exists(trim($atts['content']))) {

                    $terms = wp_get_post_terms(get_the_ID(), $atts['content']);
	                $out .='<ul class="rpi-wall-term-'.$atts['content'].'">';
                    foreach ($terms as $term) {
                        if (is_a($term, 'WP_Term')) {
                            $out .= '<li><a href="' . site_url() . '/' . $atts['content'] . '/' . $term->slug . '">' . $term->name . '</a></li>';

                        }
                    }
	                $out .='</ul>';


               }
            }
        }
        return $out;
    }

	public function get_user_messages($atts){

		$user = wp_ulike_pro_get_current_user();

		$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

		$args = [
			'post_type' => 'message',
			'posts_per_page' => 10,
			'paged' => $paged,
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => 'message_recipient',
					'value' => $user->ID,
					'compare' => '>=',
					'type' => 'NUMERIC'
				],
				[
					'key' => 'message_read',
					'compare' => 'NOT EXISTS'
				]
			]
		];
        $wp_query = new \WP_Query($args);
		$messages = $wp_query->get_posts();

		ob_start();
        ?>
        <div class="member-message-grid">
        <?php
		foreach ( $messages as $post ):
			setup_postdata( $post );
            ?>
				<div class="message">
					<details class="message-content">
						<summary class="entry-title">
							<?php echo date('d.n.Y',strtotime($post->post_date));?>: <?php echo $post->post_title;?>
						</summary>
						<?php echo $post->post_content;?>
					</details>
				</div>
			<?php
		endforeach;
        ?>
        </div>
        <?php

		echo '<hr>';
		echo paginate_links( array(
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var('paged') ),
			'total' => $wp_query->max_num_pages
		) );
		wp_reset_postdata();
		return ob_get_clean();
	}


	public function get_user_groups($atts){
		ob_start();

		echo '<div class="group-posts">';
		$member = new member($this->user);


        $query = $member->get_query_all_groups();
		if($query && $query->have_posts()) {
			while ( $query->have_posts() ) {
				self::display_post( $query->the_post()  );
			}
		}
		wp_reset_query();
		echo '</div>';
		return ob_get_clean();
	}
    public function get_user_likes($atts){
        ob_start();
	    echo '<div class="group-posts">';
	    $member = new member($this->user);

	    $query = $member->get_query_pending_groups();
	    if($query && $query->have_posts()) {
		    while ( $query->have_posts() ) {
			    self::display_post( $query->the_post()  );
		    }
	    }
	    wp_reset_query();
	    echo '</div>';
	    return ob_get_clean();
    }
    public function get_user_posts($atts){

        ob_start();
	    echo '<div class="group-posts">';
	    $member = new member($this->user);

	    $query = $member->get_query_my_posts();
	    if($query && $query->have_posts()) {
		    while ( $query->have_posts() ) {
			    self::display_post( $query->the_post()  );
		    }
	    }
	    wp_reset_query();
	    echo '</div>';
        return ob_get_clean();
    }



	static function display_user($user_id, $size){
        $user  = get_userdata($user_id);
        ?>
        <div class="user-grid">
            <div class="user-avatar"><?php echo get_avatar($user_id, $size);?></div>
            <div class="user-name"><a href="<?php echo wp_ulike_pro_get_user_profile_permalink($user_id) ?>"><?php echo $user->display_name;?></a></div>
        </div>
        <?php
	}
    static function display_user_name($user_id){
        $user  = get_userdata($user_id);
        ?>
        <span class="user-name"><a href="<?php echo wp_ulike_pro_get_user_profile_permalink($user_id) ?>"><?php echo $user->display_name;?></a></span>
        <?php
	}
	static function display_assignd_user(Group $group, $size=24){
        $u = $group->get_liker_and_member_Ids();
        ?>
        <div class="user-assignd">
            <div class="user-members">
                <?php
                foreach ($u->members as $user_id){
	                self::display_user($user_id,$size);
                }
                ?>
            </div>
            <div class="user-likers">
                <?php
                foreach ($u->likers as $user_id){
                    self::display_user($user_id,$size);
                }
                ?>
            </div>

        </div>
        <?php
	}
	static function display_members(Group $group){
		if($group->get_members_amount()>0){
			foreach ($group->get_memberIds() as $user_id){
				self::display_user($user_id);
			}
		}
	}

	static function display_post($post){
        global $post;

        $plg = new Group($post->ID);
	    $plg->get_comment_likes_amount();

        ?>
        <div class="group-post">
            <div class="group-post-wrapper">
                <div class="entry-title">
                    <h3><?php echo $post->post_title;?></h3>
                </div>
                <div class="entry-meta"><?php echo self::$user_icon;?>
                    <?php echo self::display_user_name( $post->post_author );?>
                    <?php echo self::$date_icon;?><?php echo date('d.m.Y',strtotime($post->post_date));?>
                </div>
                <div class="content">
				    <?php echo wp_trim_words($post->post_content,50,'...');?>
                </div>
                <div class="ghost"></div>
                <div>
                    <a href="<?php the_permalink()?>">Pinwandeintrag</a>
				    <?php if(is_user_logged_in() && 'pending' !== $plg->get_status()  && $plg->has_member(get_current_user_id())):
					    ?>| Matrix Raum: <?php echo $plg->get_matrix_link();?>
				    <?php endif;?>
                </div>
                <div>
                    <?php $mn = $plg->get_members_amount();?> <?php if($mn>0) echo $mn .' Mitglied';?><?php if($mn>1) echo 'er';?>
                    <?php if ( $plg->is_not_founded()): ?>
	                    <?php
                        $in = $plg->get_likers_amount();
                        if($in>0) {
		                    if($in<2){
                                echo '1 Person interessiert';
		                    }else{
			                    echo $in.' Personen interessiert';
		                    }
	                    }
                        echo $plg->is_pending()?', Status: Gründungsphase':'';?>
			        <?php endif; ?>
                </div>
                <?php self::display_assignd_user($plg,24);?>

            </div>
        </div>
        <?php
    }

}
