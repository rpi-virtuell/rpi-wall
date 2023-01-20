<?php

use rpi\Wall;

class RpiWallFormHandler
{
    public function __construct()
    {

        add_action('acfe/form/submit/form=constitution', function ($form, $post_id) {
            Wall\Toolbar::update_toolbar_status($form, $post_id, 'constituted');
            $matrix = new Wall\Matrix();
            $matrix->send_msg(new Wall\Group($post_id), get_option('options_matrix_bot_protocol_tutorial'));
        }, 10, 2);

        add_action('acfe/form/submit/form=constitution_date', function ($form, $post_id) {
            do_action('new_meeting_date', get_post_meta($post_id, 'date_of_meeting', true), $post_id);
            Wall\Toolbar::update_toolbar_status($form, $post_id, 'meeting_planned');
            $matrix = new Wall\Matrix();
            $matrix->send_msg(new Wall\Group($post_id), get_option('options_matrix_bot_planung_tutorial'));

        }, 10, 2);

        add_action('acfe/form/submit/form=review', function ($form, $post_id) {
            Wall\Toolbar::update_toolbar_status($form, $post_id, 'closed');
            $group = new Wall\Group($post_id);
            $group->set_status('closed');
            $matrix = new Wall\Matrix();
            $matrix->send_msg(new Wall\Group($post_id), get_option('options_matrix_bot_review'));
        }, 10, 2);

    }
}