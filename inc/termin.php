<?php

namespace rpi\Wall;

class termin {

	public function __construct(){

		add_action('blocksy:single:content:bottom', [$this,'display_participants'] , 10 );

	}

	public function display_participants(){

		if(is_singular('termin')){
			$termin = get_post();

			$members = array();
			$guests = array();




			$participants = get_post_meta($termin->ID, 'rpi_wall_termin_participant');
			foreach ($participants as $participant){
				$members[]=intval($participant);
			}
			$participants = get_post_meta($termin->ID, 'rpi_wall_termin_guest');
			$amount_guests = count($participants);

			if(in_array(get_current_user_id(), $members ) || current_user_can('edit_other_posts')){

				echo '<h3>Teilnehmer:innen Liste</h3>';


				foreach ($members as $mId ){

					$member = new Member($mId);
					$member->display(96);


				}

				if($amount_guests==1){
					echo '<hr>Eine nicht angemeldete Person hat teilgenommen';
				}elseif($amount_guests>1){
					echo '<hr>'.$amount_guests.'  nicht angemeldete Personen haben teilgenommen';
				}


			}


		}

	}
}
new termin();
