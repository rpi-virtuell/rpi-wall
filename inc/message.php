<?php

namespace rpi\Wall;


class Message
{

    protected $group;
    protected $template;
    protected $subject;
    protected $body;
    protected Member $member;
    protected $recipient_ids;
    //protected $recipient_groups=['orga','watch','group'];
//Hallo zusammen,\n\nIch bin <a href='{$this->get_member_profile_permalink()}'>{$this->name}</a> und würde gerne der Arbeitsgruppe beitreten.".
//				             "Wenn etwas dagegen spricht, bitte meine Anfrage auf dem Pinnwandeintrag ".$plg->link." ablehnen"

    static $templates = [


        'orga_ready' =>
	        [
		        'subject' => '[%grouptitle%] Gründung möglich',
		        'body' => 'Für den Pinwandeintrag %postlink% '.
		                  'haben sich einige Interessierte gefunden. Die Gründung einer Professionellen Lerngruppe ist jetzt möglich. '.
		                  'Klicke auf dem Beitrag ganz unten auf den Button "Gruppe gründen"'

	        ],
        'pending' =>
	        [
		        'subject' => '[%grouptitle%] Jetzt beitreten!',
		        'body' => '%actorname% (%actorlink%) hat die Beitrittsphase zur Gründung einer Professionellen Lerngruppe eröffnet.  ' .
			              'Klicke innerhalb der nächsten %countdown% auf dem Pinwandeintrag %postlink% '.
		                  'ganz unten auf den Button "Beitreten", um Mitglied der Gruppe zu werden'

	        ],
        'founded' =>
            [
                'subject' => '[%grouptitle%] Gruppe erfolgreich eingerichtet ',
                'body' => 'Auf der Seite %postlink%  wurde eine Professionellen Lerngruppe eingerichtet. ' .
                          '%memberamount%  Mitglieder sind beigetreten. '.
                          'Mitglieder der Gruppe finden unten auf der Seite  den Link zu einer geschützten Raum '.
                          'und zu weiteren hilfreichen Kooperationswerkzeugen.'
            ],
        'create' =>
            [
                'subject' => '[Pinwandeintrag]: %posttitle%',
                'body' => '%actorlink% hat folgendes an die Pinnwand gepostet: <br>Titel:  %posttitle%<br>%content%'

            ],
        'liked' =>
            [
                'subject' => '[%grouptitle%] Intresse bekundet',
                'body' => '%actorlink% hat Interesse an einer Professionellen Lerngruppe im Kontext von %postlink%'


            ],
        'joined' =>
	        [
		        'subject' => '[%grouptitle%] neues Mitglied',
		        'body' => '%actorlink% der Professionellen Lerngruppe unter %postlink% beigetreten.'


	        ],
        'requested' =>
	        [
		        'subject' => '[%grouptitle%] Beitrittsanfrage',
		        'body' => '%actorlink% möchte der Professionellen Lerngruppe beitreten. '.
		                  'Mitglieder haben unter %postlink%  kurze Zeit, das Beitrittsgesuche abzulehnen.'


	        ],
        'comment' =>
            [
                'subject' => '[%posttitle%] neuer Kommentar',
                'body' => '%actorlink% hat unter %commentlink% einen neuen Kommentar  verfasst:<br>%commentcontent% '


            ],
        'reset' =>
            [
                'subject' => '[%grouptitle%] zu wenig Intresse',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) haben sich leider nicht genug Interessierte gefunden, um eine Professionelle Lerngruppe zu etablieren. ' .
                    'Der Gründungsvorgang wurde zurückgesetzt. Wenn sich mehr Interessierte finden, kann der Gründungsprozess erneut gestartet werden.'
            ],

    ];
    protected $events = ['create','ready','liked','joined', 'pending', 'founded', 'requested', 'comment','reset'];

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
     * @param Group $group
     * @param string $event ['create','pending','founded','liked','minimum_likers_met','comment','reset']
     * @param array $to :   ['orga','watch','group'] welche Zielgruppe soll benachrichtigt werden
     * @param int $actor_id handelnder User z.B. Kommentarschreiber
     */
    public function __construct(Group $group, $event = 'pending', $user_ids = null, $actor_id = 0)
    {
        $this->group = $group;

        $this->templates = Message::$templates;

        $this->actor = new Member($actor_id);

        if ($user_ids === null) {
	        //message to all watches
	        $user_ids = $group->get_watcher();
        }

        if (in_array($event,$this->events) && $msg = $this->prepare_message($event)) {
            if ($msg !== false) {
                $this->create($msg, $user_ids);
                $this->send($msg, $user_ids);
            }
        }
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
    protected function prepare_message($template_key)
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
	        '%commentlink%',
	        '%commentcontent%',

        ];
        $replace_array = [
            $this->group->title,
            $this->group->post->post_title,
            $this->group->link,
            $this->actor->name,
            $this->actor->get_link(),
            $this->group->get_members_amount(),
            $this->group->get_matrix_link(),
            $this->group->get_likers_amount(),
            $this->group->get_pending_time(),
        ];

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
			if($user_id instanceof \WP_User){
				$user_id = $user_id->ID;
			}
            add_post_meta($message_id, "rpi_wall_message_recipient", $user_id);
        }
    }

	static function send_messages($member, $msg){

		$message_id = wp_insert_post(array(
			'post_title' => $msg->subject,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_type' => 'message',
			'post_content' => $msg->body
		));
		if(is_array($member)){
			foreach ($member as $user_id) {
				add_post_meta($message_id, "rpi_wall_message_recipient", $user_id);
			}
		}else{
			add_post_meta($message_id, "rpi_wall_message_recipient", $member);
		}


		//Matrix\Helper::send($msg->subject, $msg->body, $msg->room_id);
	}

    static function get_messages($member_id)
    {

        return get_posts([
            'post_type' => 'massage',
            'numberposts' => -1,
            'meta_query' => [
                'key' => 'rpi_wall_message_recipient',
                'value' => $member_id,
                'compare' => '=',
                'type' => 'NUMERIC'
            ]
        ]);

    }

    public function send($msg, $recipient_ids)
    {

        if (is_array($recipient_ids) && count($recipient_ids) > 0) {

            $to = [];

            foreach ($recipient_ids as $user_id) {
                $user = get_userdata($user_id);
                $to[] = $user->user_email;
            }

            $headers = 'From: Dibes Netzwerk <happel@comeniuse.de>' . "\r\n";
            $headers .= 'BCC: ' . implode(",", $to) . "\r\n";

            wp_mail(get_option('options_rpi_wall_email_dummy', 'technik@rpi-virtuell.de'), $msg->subject, $msg->body, $headers);


            $room_id = false;
            if ($room_id)
                Matrix\Helper::send($msg->subject, $msg->body, $room_id);

        } elseif (is_string($recipient_ids)) {
            //user einzeln anschreiben
            $user = get_userdata($recipient_ids);
            wp_mail($user->user_email, $msg->subject, $msg->body);
        }


    }

    static function get($post_id)
    {

    }


}
