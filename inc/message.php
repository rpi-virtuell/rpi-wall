<?php

namespace rpi\Wall;


class Message
{

    protected $group;
    protected $template;
    protected $subject;
    protected $body;
    protected Member $member;
    protected $actor;

    static $templates = [


        'ready' =>
            [
                'subject' => '[%grouptitle%] Gründung möglich',
                'body' => 'Für den Pinwandeintrag %postlink% ' .
                    'haben sich einige Interessierte gefunden. Die Gründung einer Professionellen Lerngemeinschaft ist jetzt möglich. ' .
                    'Klicke auf dem Beitrag ganz unten auf den Button "Gruppe gründen"'

            ],
        'pending' =>
            [
                'subject' => '[%grouptitle%] Jetzt beitreten!',
                'body' => '%actorname% (%actorlink%) hat die Beitrittsphase zur Gründung einer Professionellen Lerngemeinschaft eröffnet.  ' .
                    'Klicke innerhalb der nächsten %countdown% auf dem Pinwandeintrag %postlink% ' .
                    'ganz unten auf den Button "Beitreten", um Mitglied der Gruppe zu werden'

            ],
        'founded' =>
            [
                'subject' => '[%grouptitle%] Gruppe erfolgreich eingerichtet ',
                'body' => 'Auf der Seite %postlink%  wurde eine Professionellen Lerngemeinschaft eingerichtet. ' .
                    '%memberamount%  Mitglieder sind beigetreten. ' .
                    'Mitglieder der Gruppe finden unten auf der Seite  den Link zu einer geschützten Raum ' .
                    'und zu weiteren hilfreichen Kooperationswerkzeugen.'
            ],
        'create' =>
            [
                'subject' => '[Pinwandeintrag]: %posttitle%',
                'body' => '%actorlink% hat folgendes an die Pinnwand gepostet: <br>Titel:  %posttitle%<br>%content%'

            ],
        'creator' =>
	        [
		        'subject' => '[Pinwandeintrag]: %posttitle%',
		        'body' => 'Hallo %actorlink%!<br><br>Vielen Dank für deinen Beitrag an der Pinnwand: %postlink%.<br>'.
		                  'Wie geht es nun weiter? Mitglieder des Netzwerkes können deinen Beitrag lesen und kommentieren. '.
		                  'Wenn du dir darüberhinaus im Kontext deines Beitrages auch eine professionelle Lerngemeinschaft (PLG) wünscht, '.
		                  'öffne den Tab "Gruppe" und klicke auf "interessiert". Sobald sich eine Mindestzahl an Interessierten gefunden haben, '.
		                  'bekommst du eine Nachricht, und du kannst die PLG gründen. Wenn alle Interessierten beigetreten sind, bekommst du Zugang zu einem'.
		                  'geschützten digitalen Raum, in dem speziell für deine PLG Unterstützungsinstrumente und auf Wunsch auch Begleitung bereit stehen.<br><br>Viel Erfolg!'

	        ],
        'liked' =>
            [
                'subject' => '[%grouptitle%] Interesse bekundet',
                'body' => '%actorlink% hat Interesse an einer Professionellen Lerngemeinschaft im Kontext von %postlink%'


            ],
        'joined' =>
            [
                'subject' => '[%grouptitle%] neues Mitglied',
                'body' => '%actorlink% der Professionellen Lerngemeinschaft unter %postlink% beigetreten.'


            ],
        'requested' =>
            [
                'subject' => '[%grouptitle%] Beitrittsanfrage',
                'body' => '%actorlink% möchte der Professionellen Lerngemeinschaft beitreten. ' .
                    'Mitglieder haben unter %postlink%  kurze Zeit, das Beitrittsgesuche abzulehnen.'


            ],
        'comment' =>
            [
                'subject' => '[%posttitle%] neuer Kommentar',
                'body' => '%actorname% schreibt unter %commentlink%:<br>%commentcontent% '


            ],
        'reset' =>
            [
                'subject' => '[%grouptitle%] zu wenig Intresse',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) haben sich leider nicht genug Interessierte gefunden, um eine Professionelle Lerngemeinschaft zu etablieren. ' .
                    'Der Gründungsvorgang wurde zurückgesetzt. Wenn sich mehr Interessierte finden, kann der Gründungsprozess erneut gestartet werden.'
            ],

    ];
    protected $events = ['create','creator', 'ready', 'liked', 'joined', 'pending', 'founded', 'requested', 'comment', 'reset'];

    /**
     * @param Group|Int $group
     * @param string $event ['create','pending','founded','liked','minimum_likers_met','comment','reset']
     * @param array $to :   ['orga','watch','group'] welche Zielgruppe soll benachrichtigt werden
     * @param int $actor_id handelnder User z.B. Kommentarschreiber
     */

    public function __construct($group, $event = 'pending', $user_ids = null, $actor = 0, $search_replace = array('search' => [], 'replace' => []))
    {
		if(!$group instanceof Group){
			$group = new Group($group);
			if(!$group instanceof Group){
				return false;
			}
		}

        $this->group = $group;

        $this->templates = Message::$templates;

        //ceck if actor is _logged in or anonymous commentor
        if (is_int($actor)) {
            $this->actor = new Member($actor);
        } else {
            $this->actor = new \stdClass();
            $this->actor->name = $actor;
            $this->actor->link = $actor;
        }

        //ceck for additional replacements
        if (
            isset($search_replace['search']) &&
            isset($search_replace['replace']) &&
            count($search_replace['search']) == count($search_replace['replace'])
        ) {
            $replace_data = $search_replace;
        } else {
            $replace_data = ['search' => [], 'replace' => []];
        }

        if ($user_ids === null) {
            //message to all watches
            $user_ids = $group->get_watcher_Ids();
        }



        if (in_array($event, $this->events) && $msg = $this->prepare_message($event, $replace_data)) {
            if ($msg !== false) {
                $this->create($msg, $user_ids);
                $this->increase_message_counters($user_ids);

                $user_ids = $this->get_watchers_with_mail_permission($event, $user_ids);

	            $this->send($msg, $user_ids);

            }
        }
    }

    public function get_watchers_with_mail_permission($event, $watchers)
    {

        $args = [
            'post_type' => 'member',
            'posts_per_page' => -1,
            'author__in' => (array) $watchers,
            'meta_query' => array(
                array(
                    'key' => 'rpi_user_message_' . $event,
                    'value' => 1,
                    'compare' => '='
                )
            )

        ];
	    $member_ids = array();
		$members = get_posts($args);
		foreach ($members as $member){
			$member_ids[]=$member->post_author;
		}
		return $member_ids;
    }


    /**
     * @param string $slug : $this->$events
     * @param string $part 'subject'|'body'
     *
     * @return false|mixed|void
     */
    public function get_template(string $slug, string $part)
    {

        return get_option('option_rpi_message_' . $slug . '_template_' . $part, Message::$templates[$slug][$part]);
    }


    /**
     * ToDo set in Optin page
     * @return int[]
     */
    static public function get_orga_ids()
    {
        return get_option('options_rpi_wall_orga_team_ids', [2, 3]);
    }

    /**
     * //Todo: Tempates in einer Optionspage verwalten, Variablen als Hilfestellung definieren.
     *
     * @param string $template_key
     *
     * @return object
     */
    protected function prepare_message($template_key, $search_replace)
    {
        if (!isset($this->templates[$template_key])) {

            return false;
        }

        $search_array = [
            '%grouptitle%',
            '%posttitle%',
            '%postlink%',
            '%actorname%',
            '%actorlink%',
            '%memberamount%',
            '%channellink%',
            '%likeramount%',
            '%countdown%',


        ];
        $replace_array = [
            $this->group->title,
            $this->group->post->post_title,
            $this->group->link,
            $this->actor->name,
            $this->actor->link,
            $this->group->get_members_amount(),
            $this->group->get_matrix_link(),
            $this->group->get_likers_amount(),
            $this->group->get_pending_time(),
        ];
        $search_array = array_merge($search_array, $search_replace['search']);
        $replace_array = array_merge($replace_array, $search_replace['replace']);


        $body = str_replace($search_array, $replace_array, $this->get_template($template_key, 'body'));
        $subject = str_replace($search_array, $replace_array, $this->get_template($template_key, 'subject'));

        return (object)['body' => $body, 'subject' => $subject];

    }

    /**
     * create a Message CPT
     *          title:      subject,
     *          content:    body,
     *          recipients: meta_key rpi_wall_message_recipient,
     *          actor_id:   post_author
     *
     * @return void
     */
    protected function create($msg, $recipient_ids)
    {

        $message_id = wp_insert_post(array(
            'post_title' => $msg->subject,
            'post_status' => 'publish',
            'post_author' => $this->actor->ID,
            'post_type' => 'message',
            'post_content' => $msg->body

        ));
        foreach ($recipient_ids as $user_id) {
            if ($user_id instanceof \WP_User) {
                $user_id = $user_id->ID;
            }
            add_post_meta($message_id, "rpi_wall_message_recipient", $user_id);
        }
    }


	/**
	 * @param array $recipient_ids |WP_User->IDs
	 *
	 * @return void
	 */
    protected function increase_message_counters($recipient_ids){

		foreach ($recipient_ids as $recipient_id){

			self::change_message_counter($recipient_id);
		}

    }

	/**
	 * @param int $recipient_id |WP_User->ID
	 * @param bool $decrease
	 *
	 * @return void
	 */
	static function change_message_counter($recipient_id, $decrease = false){
		if(get_userdata($recipient_id)){
			$counter  = get_user_meta($recipient_id, 'rpi_wall_unread_messages_count',true);
			if($decrease){
				if($counter>0){
					$counter --;
				}
			}else{
				$counter ++;
			}
			update_user_meta($recipient_id, 'rpi_wall_unread_messages_count', $counter);
		}
	}

	/**
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	static function get_unread_messages_count($user_id){
		return get_user_meta($user_id,'rpi_wall_unread_messages_count', true);
	}


	/**
	 * @param \stdClass $msg
	 * @param array $recipient_ids
	 *
	 * @return void
	 */
	public function send($msg, $recipient_ids)
    {

	    $headers = array('Content-Type: text/html; charset=UTF-8');

        if (is_array($recipient_ids) && count($recipient_ids) > 0) {

            $to = [];

            foreach ($recipient_ids as $user_id) {
                $user = get_userdata($user_id);
                $to[] = $user->user_email;
            }

            $headers[] = 'From: Dibes Netzwerk <technik@rpi-virtuell.de>' . "\r\n";
			foreach ($to as $bcc){
				$headers[] = 'Bcc: ' . $bcc ;
			}

			wp_mail('', $msg->subject, $msg->body, $headers);


            $room_id = false;
            if ($room_id){
				$matrix = new Matrix();
	            $matrix->send_msg_obj($msg, $room_id);
            }

        } elseif (is_string($recipient_ids)) {
            //user einzeln anschreiben
            $user = get_userdata($recipient_ids);
            wp_mail($user->user_email, $msg->subject, $msg->body, $headers);
        }


    }

    static function get($post_id)
    {

    }

    /**
     * @param array|int $member_ids
     * @param object $msg ->subject ->body
     *
     * @return void
     */
    static function send_messages($member, \stdClass $msg)
    {

        $message_id = wp_insert_post(array(
            'post_title' => $msg->subject,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'post_type' => 'message',
            'post_content' => $msg->body
        ));
        if (is_array($member)) {
            foreach ($member as $user_id) {
                add_post_meta($message_id, "rpi_wall_message_recipient", $user_id);
            }
        } else {
            add_post_meta($message_id, "rpi_wall_message_recipient", $member);
        }


        //Matrix\Helper::send($msg->subject, $msg->body, $msg->room_id);
    }



}
