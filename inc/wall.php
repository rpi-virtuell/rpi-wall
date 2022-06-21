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

    public function get_user_posts($args)
    {

        $attributes = shortcode_atts(array(
            'post_type' => 'wall',
            'past_days' => '',
            'per_page' => 10,
            'user_id' => null
        ), $args);

        if (strpos($attributes['post_type'], ',')) {
            $attributes['post_type'] = explode(',', $attributes['post_type']);
        }

        if (is_user_logged_in()) {

            if ($attributes['user_id'] === null) {
                $attributes['user_id'] = get_current_user_id();
            }
        } else {
            return false;
        }


        $getPosts = NULL;

        if (empty($attributes['past_days'])) {
            $pinnedItems = wp_ulike_get_meta_data($attributes['user_id'], 'user', 'post_status', true);
            // Exclude like status
            $pinnedItems = !empty($pinnedItems) ? array_filter($pinnedItems, function ($v, $k) {
                return $v == 'like';
            }, ARRAY_FILTER_USE_BOTH) : NULL;

            if (!empty($pinnedItems)) {
                $getPosts = get_posts(array(
                    'post_type' => $attributes['post_type'],
                    'post_status' => array('publish', 'inherit'),
                    'posts_per_page' => $attributes['per_page'],
                    'post__in' => array_reverse(array_keys($pinnedItems)),
                    'orderby' => 'post__in'
                ));
            }

        } else {
            $getPosts = wp_ulike_get_most_liked_posts($attributes['per_page'], $attributes['post_type'], 'post', array(
                'start' => wp_ulike_pro_get_past_time($attributes['past_days']),
                'end' => current_time('mysql')
            ), array('like'), false, 1, $attributes['user_id']);
        }

        return $getPosts;
    }


}
