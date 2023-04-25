<?php

use rpi\Wall;
use rpi\Wall\Message;

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


        add_filter('acfe/form/prepare/rpi-redaktion-message', function ($prepare, $form, $post_id, $action) {
            if (!current_user_can('write_redaktion_message')) {
                $prepare = false;
            } else {
                $all_users = get_users(array('fields'=> 'ID'));

                $msg = new \stdClass();
                $msg->subject = get_field('betreff');
                $msg->body = get_field('nachricht');
                Message::send_messages($all_users, $msg , 'rpi_user_moderation_message');
            }

            return $prepare;
        }, 10, 4);

    }
}