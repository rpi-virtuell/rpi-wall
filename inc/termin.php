<?php

namespace rpi\Wall;

class termin {

	public function __construct(){

		add_action('blocksy:single:content:bottom', [$this,'display_participants'] , 10 );

	}

	public function display_participants(){

		$termin = get_post();

		$members = array();
		$guests = array();

		echo '<h3>Teilnehmer:innen Liste</h3>';

		$participants = get_post_meta($termin->ID, 'rpi_wall_termin_participant');
		foreach ($participants as $participant){
			$members[]=intval($participant);
		}
		$participants = get_post_meta($termin->ID, 'rpi_wall_termin_guest');
		$amount_guests = count($participants);

		foreach ($members as $mId ){

			$member = new Member($mId);
			$member->display(96);


		}
		if($amount_guests==1){
			echo '<hr>Eine Person, die nicht angemeldet war, hat außerdem teilgenommen';
		}elseif($amount_guests>1){
			echo '<hr>'.$amount_guests.' Personen, die nicht angemeldet waren, haben außerdem teilgenommen';
		}


	}
}
new termin();
