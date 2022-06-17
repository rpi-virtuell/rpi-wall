<?php

namespace rpi\Wall;

class member extends stdClass
{

    public $ID;
    public $name;
    public string $url;
    public WP_Post $post;

    public function __construct($post)
    {


        $this->post = get_post($post, ARRAY_A);
        $this->ID = $post->ID;
        $this->name = $post->post_title;
        $this->url = get_permalink($post->ID);


    }


    public function join_group($groupId)
    {
        if (add_post_meta($groupId, 'rpi_wall_member_id', $this->ID)
            && add_post_meta($this->ID, 'rpi_wall_group_id', $groupId)) {
            return true;
        } else {
            return false;
        }


    }

    public function leave_group($groupId)
    {

        if (delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID)
            && delete_post_meta($this->ID, 'rpi_wall_group_id', $groupId)) {
            return true;
        } else {
            return false;
        }

    }

    public function watch_group($groupid)
    {

    }

    public function get_groups()
    {
        return get_post_meta($this->ID, 'rpi_wall_group_id');
    }

    public function get_group($post_id)
    {

        //TODO : Ist diese funktion notwendig?

    }

    public function set_message_read($post_id)
    {
    }

    public function get_messages()
    {
    }

    public function current_user_is_member()
    {

    }

    public function current_member_can($capability)
    {
    }

}
