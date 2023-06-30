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

		// Array für IDs der gelesenen Nachrichten initialisieren
	    $read_messages_ids = array();

	    foreach ($users as $user) {
		    if (is_a($user, 'WP_User') && $user->ID === 2) {

			    $user_id = $user->ID;

			    // Nachrichten zuordnen, die dem Benutzer zugeordnet sind
			    $query = $wpdb->prepare(
				    "SELECT post_id
		                FROM {$wpdb->postmeta}
		                WHERE post_id IN (SELECT ID from $wpdb->posts WHERE post_status = 'publish' and post_type='message')
		                AND meta_key = 'rpi_wall_message_recipient'
		                AND meta_value = %d",
				    $user_id
			    );
			    $this->log('User', $user->user_login);
			    // $this->log('$query', $query);

			    $assigned_messages = $wpdb->get_col($query);
			    // $this->log('assigned_messages', count($assigned_messages));

			    $read_messages = get_user_meta($user_id, 'rpi_read_messages', true);
			    // $this->log('read_messages',$read_messages);

			    if (!empty($read_messages)) {

				    $read_message_ids = array_keys(unserialize($read_messages));
				    // $this->log('read_message_ids',$read_message_ids);
				    $read_message_ids = array_intersect($read_message_ids, $assigned_messages);
				    //$this->log('read_message_ids_intersect',$read_message_ids);
				    $update_read_messages = array_fill_keys($read_message_ids, true);
				    //$this->log('update_read_messages',$read_message_ids, $update_read_messages);


				    // $this->log('$read_messages', $read_messages, $read_message_ids);
					// $this->log(array_diff($assigned_messages, $read_messages_ids));

				    // Zusammenfassung
				    $count_assigned_messages = count($assigned_messages);
				    $count_read_messages = count($read_message_ids);
				    $counted_unread = $count_assigned_messages - $count_read_messages;

				    $this->log('Zusammenfassung', $count_assigned_messages, $count_read_messages, $counted_unread);

				    update_user_meta($user_id, 'rpi_wall_unread_messages_count', $counted_unread);

				    // Gelesene Nachrichten aktualisieren
				    $update_rpi_read_messages = serialize($update_read_messages);

				    update_user_meta($user_id, 'rpi_read_messages', $update_rpi_read_messages);
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
