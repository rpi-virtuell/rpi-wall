<?php

namespace rpi\Wall\Matrix;

use rpi\Wall\Group;
use http\Client;

class Helper
{
    /**
     * @param $login  WP_User::user_login
     *
     * @return string Matrix_User_Id|false
     */
    static function getUser($login)
    {
        return;
        $token = get_option('options_matrix_bot_token');
        $homeserver = get_option('options_matrix_server_home');
        $domain = get_option('options_matrix_server_base');

        $request = new \HttpRequest();
        $request->setUrl('https://' . $homeserver . '/_matrix/client/v3/user_directory/search');
        $request->setMethod(HTTP_METH_POST);

        $request->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]);

        $request->setBody('{
		        "limit": 1,
		        "search_term": "' . $login . ':' . substr($domain, 0, 3) . '"
			}');

        try {
            $response = $request->send();

            $respond = $response->getBody();

            if ($respond && isset($respond->results)) {
                $matrix_user = $respond->results[0];
                if (isset($matrix_user->user_id)) {
                    return $matrix_user->user_id;
                }

            }

        } catch (HttpException $ex) {
            echo $ex;
        }
        return false;
    }


    static function create_room(Group $group)
    {

        $token = get_option('options_matrix_bot_token');
        $homeserver = get_option('options_matrix_server_home');
        $domain = get_option('options_matrix_server_base');

        $toolbar = $group->get_toolbar();

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://$homeserver/_matrix/client/v3/createRoom",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"name":"' . $group->title . '","visibility":"private","preset":"public_chat","room_alias_name":"' . $group->slug . '","topic":"' . $toolbar . '","initial_state":[]}',
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token",
                "Content-Type: application/json"
            ],
        ]);


        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            wp_die("cURL Error #:" . $err);
        }

        $room = json_decode($response);

        if (isset($room->errcode)) {
            file_put_contents(ABSPATH . '/matris-error.log', date('Y-m-d H:i:s') . ': ' . $room->error . "\n", FILE_APPEND);
        }

        if ($room && isset($room->room_id)) {
            $group->set_matrix_channel($room->room_alias);
            $group->set_matrix_room_id($room->room_id);
            $group->set_status('founded');
        }
        return $room->room_id;

    }

    /**
     * @param string $subject
     * @param string $body
     * @param string $to_room Matrix room_id
     *
     * @return void
     */
    static function send($subject, $body, $to_room)
    {

    }
}
