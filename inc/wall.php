<?php

class wall
{


    public function __construct($post_id)
    {
        define('UNLIMITED', 18446744073709551610);
    }

    /**
     * @param int|WP_Post|null $post
     *
     * @return void
     */
    public function get_wallEntry($post)
    {

        $post = get_post($post, ARRAY_A);

        get_post_meta($post->Id, 'pl_group_status');


    }

    /**
     * @param int $limit
     * @param int $user_id
     *
     * @return false|int|int[]|WP_Comment[]
     */
    public function get_most_liked_comments(int $limit = 10, int $user_id = 0)
    {


        $args = [
            'limit' => 20,
            'user' => $user_id

        ];
        $this->get_liked_comments($args);
    }

    /**
     *
     * @param $args  example: $this->get_liked_comments(['user_id' => 1,'past_days_num'=>7]);
     *
     * @return false|int|int[]|WP_Comment[]
     */
    public function get_liked_comments($atts)
    {

        $args = shortcode_atts(array(
            'past_days_num' => 0,  //der letzten 100 Tage
            "user" => get_current_user_id(),
            "is_popular" => false,     //if true, oder_by most likes comments
            "offset" => 1,
            "limit" => UNLIMITED
        ), $atts);


        if ($args['past_days_num'] === 0) {
            $period = 'all';
        } else {
            $period = array(
                'interval_value' => $args['past_days_num'],
                'interval_unit' => 'DAY'
            );
        }
        $args = array(
            "type" => 'comment',
            'rel_type' => 'wall',
            "status" => 'like',
            "order" => 'DESC',
            'period' => $period,
            "user_id" => $args['user'],
            "offset" => $args['offset'],
            "limit" => $args['limit'],
            "is_popular" => $args['is_popular'],
        );
        if ($args['user'] == 'all') {
            unset($args['user_id']);
        }


        // Get popular comments
        $comment__in = wp_ulike_get_popular_items_ids($args);

        if (empty($comment__in)) {
            return false;
        }

        return get_comments(apply_filters('wp_ulike_get_top_comments_query', array(
            'comment__in' => $comment__in,
            'orderby' => 'comment__in',
            'post_type' => $args['rel_type']
        )));
    }



}
