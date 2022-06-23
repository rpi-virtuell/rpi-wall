<?php
namespace rpi\Wall;

class Shortcodes{
	public function __construct() {

		add_shortcode( 'user_pinned_posts', [$this,'get_users_pinwall_posts'] );
        add_shortcode('rpi-userprofile', array($this, 'get_user_profile_tags'));
        add_shortcode('my_messages', array($this, 'get_user_messages'));
        add_shortcode('my_groups', array($this, 'get_user_groups'));
        add_shortcode('my_likes', array($this, 'get_user_likes'));

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
        global $wp_ulike_pro_current_user;


        if (isset($atts['content']) && is_a($wp_ulike_pro_current_user, 'WP_User')) {
            echo '<ul>';
            $member = get_page_by_title($wp_ulike_pro_current_user->display_name, 'OBJECT', 'Member');
            if (post_type_exists($atts['content'])) {
                //TODO: Gruppen Link einfügen (Link auf Pinns mit gruppen)
            } elseif (taxonomy_exists($atts['content'])) {
                $terms = wp_get_post_terms($member->ID, $atts['content']);
                foreach ($terms as $term) {
                    if (is_a($term, 'WP_Term')) {
                        echo '<a href="' . site_url() . '/' . $atts['content'] . '/' . $term->slug . '">' . $term->name . '</a>';
                        echo '<br>';
                    }
                }
            }
            echo '</ul>';
        }
    }

	public function get_user_messages($atts){

		$user = wp_ulike_pro_get_current_user();

		//var_dump($user->ID);

		$args = [
			'post_type' => 'message',
			'numberposts' => -1,
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
		$messages = get_posts($args);

		ob_start();
		foreach ( $messages as $message ):
			setup_postdata( $message );
			?>
				<div class="message">
					<details class="message-content">
						<summary class="entry-title">
							<?php echo date('d.n.Y',strtotime($message->post_date));?>: <?php echo $message->post_title;?>
						</summary>
						<?php echo $message->post_content;?>
					</details>
				</div>
			<?php
		endforeach;
		wp_reset_postdata();
		return ob_get_clean();
	}


	public function get_user_groups($atts){

		$user = wp_ulike_pro_get_current_user();

		//var_dump($user->ID);

		$args = [
			'post_type' => 'wall',
			'numberposts' => -1,
			'meta_query' => [
				[
					'key' => 'rpi_wall_member_id',
					'value' => $user->ID,
					'compare' => '>=',
					'type' => 'NUMERIC'
				]
			]
		];
		$groups = get_posts($args);
        if(!$groups){
            return 'Noch keine Mitgliedschaft in professionellen Lerngruppen';
        }

		ob_start();
		foreach ( $groups as $group ):
			setup_postdata( $group );
            $plg  = new Group($group->ID);
			?>
			<div class="mygroup">
                <div class="mygroup-wrapper">
                    <div class="entry-title"><h3><?php echo $plg->title;?></h3></div>
                    <div class="content">
                        <?php echo wp_trim_words(get_the_content(),50,'...');?>
                    </div>
                    <div><?php echo $plg->get_members_amount();?> Mitglied(er) <?php echo $plg->get_status()==='pending'?', Status: Gründungsphase':'';?></div>
                    <div>
                        <a href="<?php the_permalink()?>">Pinwandeintrag</a>
                        <?php if('pending' !== $plg->get_status()):
                            ?>| Matrix Raum: <?php echo $plg->get_matrix_link();?>
                        <?php endif;?>
                    </div>
			    </div>
			</div>
		<?php
		endforeach;
		wp_reset_postdata();
		return ob_get_clean();
	}
    public function get_user_likes($atts){

        ob_start();
        $user = wp_ulike_pro_get_current_user();

        $args = self::get_query_args(['user_id'=>$user->ID],true);
	    $query = new \WP_Query($args);
        if($query->have_posts()) {
	        while ( $query->have_posts() ) {
		        self::display_post( $query->the_post()  );

	        }
        }
        wp_reset_query();



        return ob_get_clean();
    }

    static function get_query_args($args,$not_self = false){

	    $not_in = [];
        if($not_self && $args['user_id']){
            $pins = get_posts(['post_type'=>'wall', 'author'=>$args['user_id']]);
	        foreach ($pins as $pin) {
		        $not_in[] = $pin->ID;
	        }
        }

        $defaults = array(
		    "type"       => 'post',
		    "rel_type"   => 'wall',
		    "is_popular" => true,
		    "status"     => 'like',
		    "user_id"    =>  '',
		    "order"      => 'DESC',
		    "period"     => 'all',
		    "offset"     => 1,
		    "limit"      => 10
	    );
	    $parsed_args = wp_parse_args( $args, $defaults );
	    $item_info   = wp_ulike_get_popular_items_info( $parsed_args );
	    $ids_stack   = array();
	    if( ! empty( $item_info ) ){
		    foreach ($item_info as $key => $info) {
                if(!in_array($info->item_ID,$not_in)){
                    $ids_stack[] = $info->item_ID;
                }
		    }
	    }
        $args =[
            'post__in'=>$ids_stack,
            'post_type'=>$parsed_args['rel_type']
        ];

        return $args;

    }

	static function get_comments($args){

		$defaults = array(
			"type"       => 'comment',
			"rel_type"   => 'wall',
			"is_popular" => true,
			"status"     => 'like',
			"user_id"    =>  '',
			"order"      => 'DESC',
			"period"     => 'all',
			"offset"     => 1,
			"limit"      => 10
		);
		$parsed_args = wp_parse_args( $args, $defaults );
		$item_info   = wp_ulike_get_popular_items_info( $parsed_args );
		$ids_stack   = array();
		if( ! empty( $item_info ) ){
			foreach ($item_info as $key => $info) {
				$ids_stack[] = $info->item_ID;
			}
		}
		return get_comments(['comment__in'=>$ids_stack]);
	}

	static function display_user($user_id){
        $user  = get_userdata($user_id);
        ?>
        <div class="user-grid">
            <div class="user-avatar"><?php echo get_avatar($user_id);?></div>
            <div class="user-name"><a href="<?php echo wp_ulike_pro_get_user_profile_permalink($user_id) ?>"><?php echo $user->display_name;?></a></div>
        </div>
        <?php
	}
	static function display_likers(Group $group){
        if($group->get_members_amount()>0){
            foreach ($group->get_likers_Ids() as $user_id){
                self::display_user($user_id);
            }
        }
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
                <div class="entry-meta"><?php echo $post->post_author;?></div>
                <div class="entry-title"><h3><?php echo $post->post_title;?></h3></div>
                <div class="content">
				    <?php echo wp_trim_words($post->post_content,50,'...');?>
                </div>
                <div><?php echo $plg->get_status()==='pending'?', Status: Gründungsphase':'';?></div>
                <div>
                    <div class="user-members"  style="display: flex"><?php self::display_members($plg);?><div class="user-text"><?php echo $plg->get_members_amount();?> Mitglied(er)</div></div>
                    <div class="user-likers" style="display: flex"><?php self::display_likers($plg);?></div><div class="user-text"><?php echo $plg->get_likers_amount();?> sind interssiert</div></div>
                </div>
                <div>
                    <a href="<?php the_permalink()?>">Pinwandeintrag</a>
				    <?php if(is_user_logged_in() && 'pending' !== $plg->get_status()  && $plg->has_member(get_current_user_id())):
					    ?>| Matrix Raum: <?php echo $plg->get_matrix_link();?>
				    <?php endif;?>
                </div>
            </div>
        </div>
        <?php
    }

}
