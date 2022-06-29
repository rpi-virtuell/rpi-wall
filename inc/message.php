<?php

namespace rpi\Wall;


class Message
{

    protected $group;
    protected $template;
    protected $subject;
    protected $body;
    protected member $member;
    protected $recipient_ids;
    //protected $recipient_groups=['orga','watch','group'];


    static $templates = [


        'group_ready' =>
            [
                'subject' => '[%grouptitle%] Gründung möglich',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) ist die Gründung einer PLG möglich.  ' .
                    'Klicke auf Gruppe Gründen: %postlink%.'
            ],
        'orga_ready' =>
	        [
		        'subject' => '[%grouptitle%] Gründung möglich',
		        'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) ist die Gründung einer PLG möglich.  '

	        ],
        'group_pending' =>
	        [
		        'subject' => 'Gründungsprozess für %grouptitle% gestartet',
		        'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) hat  %actorname% (%actorlink%) die Gründung einer PLG gestartet.  ' .
		                  'Klicke folgenden Link um der PLG beizutreten: %joinlink%.'
	        ],
        'group_founded' =>
            [
                'subject' => 'Gründung erfolgreich. Gruppe %grouptitle% ',
                'body' => 'Zum Pinwandeintrag "%posttitle%" (%postlink%) wurd eine PLG gegründet.' .
                    ' %memberamount%  Mitglieder sind beigetreten. ' .
                    'Für die Mitglieder der Gruppe wurde eib eigener Matrix Raum %grouptitle% erzeugt, dem  du unter folgendem Link beitreten kannst: %channellink%.'
            ],
        'orga_create' =>
            [
                'subject' => 'Ein neuer Eintrag an der Pinnwand erstellt',
                'body' => 'Für den Pinwandeintrag ""%posttitle%" (%postlink%)" von %actorname% (%actorlink%) erstellt: ' .
                    '%content%'

            ],
        'watch_like' =>
            [
                'subject' => 'PLG Intresse  %posttitle% :',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) hat %actorname% (%actorlink%) Interessent an einer PLG markiert'


            ],
        'watch_minimum_likers_met' =>
            [
                'subject' => 'PLG Gründung möglich: %posttitle%"',
                'body' => 'Zu dem Beitrag "%posttitle%" (%postlink%) haben sich genügend Interessierte für eine PLG gefunden.' .
                    'Wenn du an einer PLG interessiert bist, klicke unter dem Beitrag auf "Gruppe gründen und starte den Gründungsprozess."'
            ],
        'watch_comment' =>
            [
                'subject' => 'Dein Beitrag  %posttitle% an der Pinnwand wurde kommentiert:',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) von %actorname% (%actorlink%) ein Kommentar verfasst '


            ],
        'watch_pending' =>
            [
                'subject' => 'Gründungsprozess für %grouptitle% gestartet',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%)  hat  %actorname% (%actorlink%) die Gründung einer PLG gestartet. ' .
                    'Es haben sie sich %likeramount% Mitglieder dafür interessiert.'

            ],
        'orga_minimum_likers_met' =>
            [
                'subject' => 'Minimum an Interessierten erreicht: %posttitle%',
                'body' => 'Zu dem Beitrag "%posttitle%" (%postlink%) haben sich das eingestellte Minimum an Interessierten gefunden. ' .
                    'Es kann jetzt ein PLG Gründungsprozess gestartet werden. '
            ],
        'orga_founded' =>
            [
                'subject' => 'Gründung erfolgreich. Gruppe %grouptitle% ',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) wurde eine PLG erstellt. %memberamount% Mitglieder sind beigetreten. ' .
                    'Tritt bitte dem Matrix Raum %grouptitle% hier bei: %channellink% '

            ],
        'watch_reset' =>
            [
                'subject' => 'Gründung der Gruppe %grouptitle% leider nicht erfolgreich ',
                'body' => 'Für den Pinwandeintrag "%posttitle%" (%postlink%) haben sich leider nicht genug Interessierte gefunden, um eine PLG zu gründen. ' .
                    'Der Gründungsvorgang wurde zurückgesetzt. Wenn sich mehr Interessierte finden, kann der Prozess erneut gestartet werden.'
            ],

    ];
    protected $events = ['create', 'pending', 'founded', 'liked', 'minimum_likers_met', 'comment', 'reset'];


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
    public function __construct(Group $group, $event = 'pending', $to = ['orga', 'watch', 'group'], $actor_id = 0)
    {
        $this->group = $group;

        $this->templates = Message::$templates;

        $this->actor = new member($actor_id);

        foreach ($to as $reciever) {
            $template_key = $reciever . '_' . $event;
            if ($actor_id === 0) {
                $actor_id = get_current_user_id();
            }
            $this->prepare_message($template_key, $actor_id);
            if ('group' == $reciever) {
                if ('pending' == $event || 'create' == $event || 'minimum_likers_met' == $event) {
                    $user_ids = $group->get_likers();
                }
                if ('founded' == $event || 'comment' == $event || 'liked' == $event) {
                    $user_ids = $group->get_likers();
                }

            }
            if ('watch' == $reciever) {
                $user_ids = $group->get_watcher();
            }
            if ('orga' == $reciever) {
                $user_ids = $this->get_orga_ids();
            }

            if ($msg = $this->prepare_message($template_key)) {
                if ($msg !== false) {
                    if ('group_pending' == $template_key) {
                        //user einzeln anschreiben
                        foreach ($user_ids as $user_id) {
                            $m = new member($user_id);
                            $link = $m->get_joinlink($group->ID);
                            $msg->body = str_replace('%joinlink%', $link, $msg->body);
                            $this->create($msg, [$user_id]);
                            $this->send($msg, $user_id);
                        }
                    } else {
                        $this->create($msg, $user_ids);
                        $this->send($msg, $user_ids);
                    }

                }
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

        switch ($template_key) {
            case 'group_min_likers':
                $template_key = 'watch_min_likers';
                break;
            case 'orga_comment':
            case 'group_comment':
                $template_key = 'watch_comment';
                break;
            case 'orga_reset':
            case 'group_reset':
                $template_key = 'watch_reset';
                break;
            case 'orga_pending':
                $template_key = 'watch_pending';
                break;

        }

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
            get_permalink($this->group->ID),
            $this->actor->name,
            $this->actor->get_member_profile_permalink(),
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
