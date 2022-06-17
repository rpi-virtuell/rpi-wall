<?php
namespace rpi\Wall;


class Message {

	protected \WP_Post $post;
	protected $template;
	protected $subject;
	protected $body;
	protected $templates = [
		'group_pending'=>
			[
				'subject'=>'Gründungsprozess für %s gestartet',
				'body'=>'Für den Pinwandeintrag %s (Url: %s) würde ein Gründungsprozess gestartet.  Klicke folgenden Link um der PLG beizutreten %s.'
			],
		'group_founded'=>
			[
				'subject'=>'Gründung erfolgreich. Gruppe %s ',
				'body'=>'Zum Pinwandeintrag %s (Url: %s) wurde eine PLG erstellt. %d Mitglieder sind beigetreten. '.
				        'Für die Mitglieder der Gruppe wurde eib eigener Matrix Raum %s erzeugt, dem  du unter folgendem Link beitreten kannst: %s.'
			],
		'group_reset'=>
			[
				'subject'=>'Gründung der Gruppe leider nicht erfolgreich ',
				'body'=>'Für den Pinwandeintrag %s (Url: %s) haben sich leider nicht genug Mitgleider gefunden um eine Gruppe zu gründen. '.
				        'Wenn sich mehr Intersseierte finden, kann der Prozess neu gestartet werden.',
			],
		'orga_pending'=>
			[
				'subject'=>'Gründungsprozess für %s gestartet',
				'body'=>'Für den Pinwandeintrag %s (Url: %s) würde ein Gründungsprozess gestartet. Es haben sie sich %d Mitglieder dafür interssiert.'

			],
		'orga_founded'=>
			[
				'subject'=>'Gründung erfolgreich. Gruppe %s ',
				'body'=>'Für den Pinwandeintrag %s (Url: %s) wurde eine PLG erstellt. %d Mitglieder sind beigetreten. Tritt bitte dem Matrix Raum %s bei.'

			],
		'orga_reset'=>
			[
				'subject'=>'Gründung erfolgreich. Gruppe %s ',
				'body'=>'Zum Pinwandeintrag %s (Url: %s) wurde eine PLG erstellt. %d Mitglieder sind beigetreten. '.
				        'Für die Mitglieder der Gruppe wurde eib eigener Matrix Raum %s erzeugt, dem  du unter folgendem Link beitreten kannst: %s.'
			],

	];


	public function __construct($post, $template, $member_ids) {
		$this->template = $template;
	}
	public function send(){

	}
	protected function create(){

	}
	protected function getTemplate(){

	}

	static function get($post_id){

	}



}
