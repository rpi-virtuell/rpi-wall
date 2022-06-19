<?php
namespace rpi\Wall;


class Message {

	protected Group $group;
	protected $template;
	protected $subject;
	protected $body;
	protected Member $member;
	protected $recipient_ids;

	protected $templates = [

		'group_pending'=>
			[
				'subject'=>'Gründungsprozess für %grouptitle% gestartet',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%) hat  %actorname% (%actorlink%) die Gründung einer PLG gestartet.  '.
				        'Klicke folgenden Link um der PLG beizutreten %s.'
			],
		'group_founded'=>
			[
				'subject'=>'Gründung erfolgreich. Gruppe %grouptitle% ',
				'body'=>'Zum Pinwandeintrag "%posttitle%" (%postlink%) wurd eine PLG gegründet.'.
				        ' %memberamount%  Mitglieder sind beigetreten. '.
				        'Für die Mitglieder der Gruppe wurde eib eigener Matrix Raum %grouptitle% erzeugt, dem  du unter folgendem Link beitreten kannst: %channellink%.'
			],
		'orga_create'=>
			[
				'subject'=>'Ein neuer Eintrag an der Pinnwand erstellt',
				'body'=>'Für den Pinwandeintrag ""%posttitle%" (%postlink%)" von %actorname% (%actorlink%) erstellt: '.
				        '%content%'

			],
		'watch_like'=>
			[
				'subject'=> 'PLG Intresse  %posttitle% :',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%) hat %actorname% (%actorlink%) Interessent an einer PLG markiert'


			],
		'watch_comment'=>
			[
				'subject'=>'Dein Beitrag  %posttitle% an der Pinnwand wurde kommentiert:',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%) von %actorname% (%actorlink%) ein Kommentar verfasst '


			],
		'watch_pending'=>
			[
				'subject'=>'Gründungsprozess für %grouptitle% gestartet',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%)  hat  %actorname% (%actorlink%) die Gründung einer PLG gestartet. '.
				        'Es haben sie sich %likeramount% Mitglieder dafür interessiert.'

			],
		'orga_founded'=>
			[
				'subject'=>'Gründung erfolgreich. Gruppe %grouptitle% ',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%) wurde eine PLG erstellt. %memberamount% Mitglieder sind beigetreten. '.
				        'Tritt bitte dem Matrix Raum %grouptitle% hier bei: %channellink% '

			],
		'watch_reset'=>
			[
				'subject'=>'Gründung der Gruppe %grouptitle% leider nicht erfolgreich ',
				'body'=>'Für den Pinwandeintrag "%posttitle%" (%postlink%) haben sich leider nicht genug Interessierte gefunden, um eine PLG zu gründen. '.
				        'Der Gründungsvorgang wurde zurückgesetzt. Wenn sich mehr Interessierte finden, kann der Prozess erneut gestartet werden.'
			],

	];

	/**
	 * @param Group $group
	 * @param $template_key
	 * @param $user_ids
	 * @param $actor_id    //des handelnden Users: Group creater, Comment creater ...
	 */
	public function __construct(Group $group, $template_key='watch_pending', $user_ids = array(),$actor_id=0) {
		if($actor_id === 0){
			$actor_id = get_current_user_id();
		}
		$this->actor = new Member($actor_id);
		$this->prepare_message($template_key);
		$this->recipient_ids = $user_ids;
	}

	/**
	 * //Todo: Tempates in einer Optionspage verwalten, Variablen als Hilfestellung definieren.
	 *
	 * @param string $template_key
	 *
	 * @return array
	 */
	protected function prepare_message($template_key, $actor_id){

		switch($template_key){
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

		$search_array = [
			'%grouptitle%',
			'%posttitle%',
			'%postlink%',
			'%actorname%',
			'%actorlink%',
			'%memberamount%',
			'%channellink%',
			'%likeramount%',
		];
		$replace_array =[
			$this->group->title,
			$this->group->post->post_title,
			get_permalink($this->group->ID),
			$this->actor->name,
			$this->actor->get_member_profile_permalink(),
			$this->group->get_members_amount(),
			$this->group->get_matrix_link(),
			$this->group->get_likers_amount()
		];

		$body = str_replace($search_array,$replace_array,$this->get_template($template_key['body']));
		$subject = str_replace($search_array,$replace_array,$this->get_template($template_key['subject']));

		$this->subject = $subject;
		$this->body = $body;

	}

	/**
	 * create a Message CPT
	 *          title:      subject,
	 *          content:    body,
	 *          recipients: meta_key message_recipients,
	 *          actor_id:   post_author
	 *
	 * @return void
	 */
	protected function create(){

		$message_id = wp_insert_post(array(
			'post_title' => $this->subject,
			'post_status' => 'publish',
			'post_author' => $this->actor->ID,
			'post_type' => 'Message',
			'post_content' => $this->body,
			'meta_input' => array(
				'message_recipients' => $this->recipient_ids,
			)
		));

	}

	public function send(){

		$to = [];

		//Todo E-Mailsadressen der Orgaleute müssen in der Optionspage eingegeben werden
		$orga = ['happel@comenius','reintanz@comenius'];



		foreach ($this->recipient_ids as $user_id){
			$user = get_userdata($user_id);
			$to[] = $user->user_email;
		}

		$headers = 'From: Dibes Netzwerk <happel@comeniuse.de>' . "\r\n";
		$headers .= 'BCC: '. implode(",", $to) . "\r\n";

		wp_mail( $orga, $this->subject, $this->body, $headers);


		//Todo Id des Matrix Orga Raums in der Optionpage speichern
		$room_id = '';
		Matrix\Helper::send($this->subject, $this->body, $room_id);


	}

	static function get($post_id){

	}



}
