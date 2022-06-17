<?php
namespace \rpiWall;
class member {

	public function join_group($groupId){

		add_post_meta($groupId, 'rpi_wall_member_id', $this->ID);

	}
	public function leave_group($groupId){

		delete_post_meta($groupId, 'rpi_wall_member_id', $this->ID);

	}
	public function watch_group($post_id){}
	public function get_groups(){}
	public function get_group($post_id){}
	public function set_message_read($post_id){}
	public function get_messages(){}
	public function current_user_is_member(){}
	public function current_member_can($cability){}

}
