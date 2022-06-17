<?php

namespace rpi\matrix;

Class helper {
	/**
	 * @param $login  WP_User::user_login
	 *
	 * @return string Matrix_User_Id|false
	 */
	static function getUser($login){

		$token = get_option( 'matrix_bot_token' );
		$homeserver = get_option( 'matrix_server_home' );
		$domain = get_option( 'matrix_server_base' );

		$request = new HttpRequest();
		$request->setUrl( 'https://' . $homeserver . '/_matrix/client/v3/user_directory/search' );
		$request->setMethod( HTTP_METH_POST );

		$request->setHeaders( [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token
		] );

		$request->setBody('{
		        "limit": 1,
		        "search_term": "'.$login.':'.substr($domain,0,3).'"
			}');

		try {
			$response = $request->send();

			$respond = $response->getBody();

			if($respond && isset($respond->results)){
				$matrix_user = $respond->results[0];
				if(isset($matrix_user->user_id)){
					return $matrix_user->user_id;
				}

			}

		} catch (HttpException $ex) {
			echo $ex;
		}
		return false;
	}


	static function create_room($group){

		$token = get_option( 'matrix_bot_token' );
		$homeserver = get_option( 'matrix_server_home' );
		$domain = get_option( 'matrix_server_base' );


		$request = new HttpRequest();
		$request->setUrl('https://'.$homeserver.'/_matrix/client/v3/user_directory/search');
		$request->setMethod(HTTP_METH_POST);

		$request->setHeaders([
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer '.$token
		]);


		/**
		 * Channel erstellen
		 */
		$toolbar = $group->get_toolbar();


		$response = $request->setBody('{"name":"'.$group->title.'","visibility":"private","preset":"public_chat","room_alias_name":"'.$group->slug.'","topic":"'.$toolbar.'","initial_state":[]}');

		$room = json_decode($response->getBody());
		$room->room_id;
		$room->room_alias;


		$group->set_matrix_channel_id($room->room_id);
		$group->set_room_id($room->room_id);
		$group->set_status('founded');



		/**
		 * Action Hook for
		 * Message to orga channel
		 * E-Mails to likers
		 */
		do_action('rpi_wall_pl_group_after_channel_created', $group);
	}
}
