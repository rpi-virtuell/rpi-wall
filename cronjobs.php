<?php

namespace rpi\Wall;

class Cronjobs
{

    function __construct()
    {
        add_action('cron_wall_send_termine_message', array($this, 'cron_send_termine_message'));
        add_action('cron_wall_sync_read_messages', array($this, 'cron_sync_read_messages'));
        add_action('cron_sync_member_data', array($this, 'cron_sync_member_data'));

        //Externe Crons
        add_action('cron_update_pin_status', ['rpi\Wall\Group', 'init_cronjob']);
        add_action('init', ['rpi\Wall\Group', 'init_cronjob']);

        add_action('cron_update_join_request', ['rpi\Wall\Member', 'init_cronjob'], 5);
        add_action('init', ['rpi\Wall\Member', 'init_cronjob'], 5);


    }


    function cron_sync_member_data()
    {
        global $post;
        $installer = new RPIWallInstaller();

        if ($post->post_type == 'wall') {
            $installer->sync_taxonomies_of_pin_members($post->ID, $post, false);
        }
        if ($post->post_type == 'member') {
            $installer->sync_taxonomies_of_members($post->ID, $post, false);
        }

    }


    public function cron_send_termine_message()
    {

        $today = strtotime('12:00:00');
        $args = [
            'post_type' => 'termin',
            'meta_key' => 'termin_date',
            'numberposts' => -1,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' =>
                [

                    'key' => 'termin_date',
                    'compare' => 'BETWEEN',
                    'value' => [date('Y-m-d h:i:s', $today), date('Y-m-d h:i:s', strtotime('+1 day', $today))],


                ]
        ];

        $termine = get_posts($args);
        $msg = new \stdClass();
        foreach ($termine as $termin) {
            $msg->subject = "Heute findet ein Meeting statt: [{$termin->post_title}]";
            $msg->body = 'Heute findet das Meeting (<a href="' . get_home_url() . '">' . $termin->post_title . '</a>) statt. Auf der Hauptseite gibt es mehr Informationen.';
            $args = [
                'post_type' => 'member',
                'numberposts' => -1,
                'order' => 'ASC',
            ];
            $member = get_posts($args);
            $member_ids = array_column($member, 'post_author');
            Message::send_messages($member_ids, $msg,'rpi_user_moderation_message');

        }

    }

    function cron_sync_read_messages()
    {
        $users = get_users();

        global $wpdb;
        foreach ($users as $user) {
            if (is_a($user, 'WP_User')) {

                //echo('<hr>User:' .$user->ID.', '. $user->user_login);
                $read_messages = get_user_meta($user->ID, 'rpi_read_messages', true);

                if (!empty($read_messages)) {

	                $this->log('User', $user->user_login);

                    $ids = implode(',', array_keys(unserialize($read_messages)));

                    //check ob die message noch in der tabelle vrohanden ist
                    $results = $wpdb->get_results("select ID from {$wpdb->posts} where ID in($ids) and post_status = 'publish' and post_type='message'");
                    $checked_read_message_ids = array();
                    foreach ($results as $result) {
                        $checked_read_message_ids[] = $result->ID;
                    }
                    $in_ids = implode(',', $checked_read_message_ids);

                    //allerdings kann es seign dass die Zuordnung rpi_wall_message_recipient von message und user gelöscht wurde.
                    //deshalb besser der post meta checken welche zuordnungen es überhaupt gibt

                    $assigned_messages = $wpdb->get_results("select post_id from {$wpdb->postmeta} where
                              post_id in ($in_ids) and 
                              meta_key = 'rpi_wall_message_recipient' and meta_value = {$user->ID}");

					$this->log('assigned_messages', count($assigned_messages));


                    $read_messages_ids = array();
                    $assigned_messages_ids = array();

                    foreach ($assigned_messages as $message) {
                        $assigned_messages_ids[] = $message->post_id;
                        if (in_array($message->post_id, $checked_read_message_ids)) {
                            //user hat diese Message gelesen
                            $read_messages_ids[] = $message->post_id;
                        }
                    }
					$this->log(array_diff($assigned_messages_ids,$read_messages_ids ));

                    //Zusammenfassung
                    $count_assigned_messages = count($assigned_messages_ids);
                    $count_read_messages = count($read_messages_ids);
                    $counted_unread = $count_assigned_messages - $count_read_messages;

	                $this->log('Zusammenfassung', $count_assigned_messages,$count_read_messages,$counted_unread );

                    update_user_meta($user->ID, 'rpi_wall_unread_messages_count', $counted_unread);

                    $update_read_messages = array();
                    foreach ($read_messages_ids as $id) {
                        $update_read_messages[$id] = true;
                    }
                    $update_rpi_read_messages = serialize($update_read_messages);

                    update_user_meta($user->ID, 'rpi_read_messages', $update_rpi_read_messages);
                }

            }
        }

    }
	function log(){

		if(true || WP_DEBUG){
			ob_start();
			echo date('ymd H:i:s');
			echo ' : ';
			$args = func_get_args();

			foreach ($args as $arg) {

				if(is_object($arg) || is_array($arg)){
					echo json_encode($arg);

				}else{
					echo $arg;
					echo ' : ';
				}


			}
			echo "\n";
			$c = ob_get_clean();
			file_put_contents(dirname(__FILE__).'/cronjobs.log', $c, FILE_APPEND);
		}


	}
}
