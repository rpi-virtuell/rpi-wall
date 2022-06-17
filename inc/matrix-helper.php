<?php

namespace \matrix;

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
}
