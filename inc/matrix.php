<?php

namespace rpi\Wall;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

use Aryess\PhpMatrixSdk\Cache;
use Aryess\PhpMatrixSdk\Exceptions\MatrixException;
use Aryess\PhpMatrixSdk\Exceptions\MatrixHttpLibException;
use Aryess\PhpMatrixSdk\Exceptions\MatrixRequestException;
use Aryess\PhpMatrixSdk\MatrixClient;
use Aryess\PhpMatrixSdk\Room;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use rpi\Wall;

class Matrix {

	public \Aryess\PhpMatrixSdk\MatrixClient $client;
	protected $orga_room;
	protected $domain;
	protected $homeserver;
	protected $token;

	function __construct() {



		$this->token = get_option('options_matrix_bot_token');
		$this->token = "syt_cnBpLXdhbGwtYm90_meZpGbTJUOxoVTQEkEYL_1LWFkV";

		$this->homeserver = get_option('options_matrix_server_home');

		$this->orga_room = get_option('options_matrix_orga_room_id','!mOiolXhqWYcTREcWYK:rpi-virtuell.de ');
		$this->domain = get_option('options_matrix_server_base','rpi-virtuell.de');


		$this->client = new MatrixCustomClient('https://'.$this->homeserver, $this->token);
	}

	function set_topic( Wall\Group $group, string $topic ){
		$room_id = $this->getRoomId($group);
		$room = new Room($this->client,$room_id);
		$room->setRoomTopic($topic);
	}

	function send_msg( Wall\Group $group, string $msg ){
		$room_id = $this->getRoomId($group);
		$room = new Room($this->client,$room_id);
		$room->sendHtml($msg);
	}

	function send_msg_obj( Wall\Group $group, \stdClass $msg ){
		$room_id = $this->getRoomId($group);
		$room = new Room($this->client,$room_id);
		$room->sendHtml('<strong>'.$msg->subject.'</strong><br>'.$msg->body);
	}

	/**
	 * Checks wether User has joined a Matrix group
	 *
	 * @param Group $group
	 * @param Member $member
	 *
	 * @return bool
	 */
	function get_MatrixRoom_Members( Wall\Group $group){

		$room_id = $this->getRoomId($group);
		$members =[];
		$response = $this->client->api()->getRoomMembers($room_id);
		foreach ($response['chunk'] as $event) {
			if (array_get($event, 'content.membership') == 'join') {
				$userId = $event['state_key'];
				$members[$userId] = array_get($event, 'content.displayname');
			}
		}

		return $members;
	}



	function create_Room( Wall\Group $group){

		if(!empty($room_id = $group->get_matrix_room_id())){
			return $room_id;
		}

		$room_alias = '#'.$group->slug.':rpi-virtuell.de';
		try {

			$room_id = $this->client->api()->getRoomId($room_alias);

		}catch (\Exception $e ){

			$room_id = ($e->getCode());

		}
		if(is_int($room_id) && $room_id === 404) {

			try {

				$room = (object) $this->client->api()->createRoom( $group->slug, $group->title, true );

				if ( $room && isset( $room->room_id ) ) {

					$group->set_matrix_channel( $room->room_alias );
					$group->set_matrix_room_id( $room->room_id );
					$group->set_status( 'founded' );

                    $widget_ID = $this->addToolbar($group);

				}

				return $room->room_id;

			} catch ( \Exception $e ) {

				wp_die( $e->getPrevious()->getMessage() );

			}


		}else{
			return new \WP_Error('ROOM_IN_USE', 'Der Raum '. $room_alias . ' existiert bereits');
		}

	}

	function getRoomId(Group $group){

		$room_id = get_post_meta($this->ID, 'rpi_wall_group_room_id', true);
		if(!$room_id){
			$room_alias = '#'.$group->slug.':rpi-virtuell.de';
			try {

				$room_id = $this->client->api()->getRoomId($room_alias);

			}catch (\Exception $e ){

				$room_id = false;

			}
		}
		return $room_id;
	}

	protected function stateKey($roomId){
		return $roomId.'_'.$this->client->userId().'_'.time();
		//return str_replace([':','@'],['%3A','%40'],$roomId.$this->client->userId().'_'.time());
	}

	function addWidget($room_id, $name,$url, $type = 'm.custom'){

			$room = new Room($this->client,$room_id);
			$content = array(
				'type'  =>  $type,
				'url'   =>  $url,
				'name'  =>  $name,
				'data'  =>  array('m'=>'n')
			);

			return $room->sendStateEvent('im.vector.modular.widgets', $content, $this->stateKey($room_id));


	}

	function addToolbar(Group $group){

		$room_id = $this->getRoomId($group);

		$messages = $this->client->api()->filterRoomEvents($room_id,['im.vector.modular.widgets']);
		$toolbar_exists = false;
		$deleted =[];

		foreach ($messages['chunk'] as  $msg){
			$message = (object) $msg;
			if(isset($message->unsigned)){
				$deleted[]= $message->unsigned["replaces_state"];
			}
			if($message->content['type'] == 'm.custom' && $message->content['name']=='Toolbar' && !in_array($message->event_id, $deleted)){
				$toolbar_exists = true;
			}
		}
		if(!$toolbar_exists) {

			return $this->addWidget( $room_id, 'Toolbar', home_url(  )."?p=".$group->post->ID ."&roomId=".$this->getRoomId($group) );
		}

	}

	function tests(int $group_id=0){

		if($group_id>0 && get_current_user_id() == 2 && false){


			$msg =new \stdClass();
			$msg->subject = 'Subject: Testnachricht:';
			$msg->body = 'Body: Dies ist der Body der Textnachhricht.';


			$group = new Group($group_id);


			$check = $this->create_Room($group);
			if($check instanceof \WP_Error){
				echo $check->get_error_message();
			}else{
				echo 'Erfolg. Matrix Raum Id: '.$check;
			}
			$widget_ID = $this -> addToolbar($group);

			$this->send_msg_obj($group,$msg);
			$this->send_msg($group,'Klicke oben rechts auf <div aria-selected="true" role="tab" aria-label="Rauminfo" tabindex="0" class="mx_AccessibleButton mx_RightPanel_headerButton mx_RightPanel_headerButton_highlight mx_RightPanel_roomSummaryButton"></div>');

			$this->set_topic($group,$group->url);

			var_dump($this->get_MatrixRoom_Members($group));
			die();

		}

	}

}

add_action('init', function (){
	$matrix = new Matrix();
	$matrix->tests(7159);
});

use Aryess\PhpMatrixSdk\MatrixHttpApi;

class MatrixCustomApi extends MatrixHttpApi{
	public function __construct(string $baseUrl, ?string $token = null, ?string $identity = null,
		int $default429WaitMs = 5000, bool $useAuthorizationHeader = true){
		parent::__construct($baseUrl, $token ,$identity , $default429WaitMs , $useAuthorizationHeader);

		$this->baseUrl = $baseUrl;
		$this->token = $token;
		$this->identity = $identity;
		$this->txnId = 0;
		$this->validateCert = true;
		$this->client = new Client();
		$this->default429WaitMs = $default429WaitMs;
		$this->useAuthorizationHeader = $useAuthorizationHeader;

	}
	public function filterRoomEvents($room_id,$eventTypes=array()){

		$path = sprintf('/rooms/%s/messages', urlencode( $room_id ));
		$types = '"'.implode('","',$eventTypes).'"';
		$path .= '?dir=b&filter='.urlencode( '{"types":['.$types.']}' );

		return $this->request('GET', $path);
	}

	protected function request(string $method, string $path, $content = null, array $queryParams = [], array $headers = [],
		$apiPath = parent::MATRIX_V2_API_PATH, $returnJson = true) {
		$options = [];
		if (!in_array('User-Agent', $headers)) {
			$headers['User-Agent'] = 'php-matrix-sdk/' . self::VERSION;
		}

		$method = strtoupper($method);
		if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
			throw new MatrixException("Unsupported HTTP method: $method");
		}

		if (!in_array('Content-Type', $headers)) {
			$headers['Content-Type'] = 'application/json';
		}

		if ($this->useAuthorizationHeader) {
			$headers['Authorization'] = sprintf('Bearer %s', $this->token);
		} else {
			$queryParams['access_token'] = $this->token;
		}

		if ($this->identity) {
			$queryParams['user_id'] = $this->identity;
		}

		$options = array_merge($options, [
			'headers' => $headers,
			'query' => $queryParams,
			'verify' => $this->validateCert,
			'method' => $method
		]);

		$endpoint = $this->baseUrl . $apiPath . $path;


		if ($headers['Content-Type'] == "application/json" && $content !== null) {
			$options[RequestOptions::JSON] = $content;
		}
		else {
			$options[RequestOptions::FORM_PARAMS] = $content;
		}

		$responseBody = '';
		$response = (object) wp_remote_request($endpoint,$options);
		$responseBody = $response->body;
		if ($response->response['code'] < 200 ||$response->response['code'] >= 300) {
			throw new MatrixRequestException($response->getStatusCode(), $responseBody);
		}

		return $returnJson ? json_decode($responseBody, true) : $responseBody;
	}
}
class MatrixCustomClient extends MatrixClient{
	protected $api;
	public function __construct(string $baseUrl, ?string $token = null, bool $validCertCheck = true, int $syncFilterLimit = 20,
		int $cacheLevel = Cache::ALL, $encryption = false, $encryptionConf = []) {

		//parent::__construct($baseUrl,$token,$validCertCheck,$syncFilterLimit,$cacheLevel,$encryption,$encryptionConf);

		$this->api = new MatrixCustomApi($baseUrl, $token);
		$this->api->validateCertificate($validCertCheck);
		$this->encryption = $encryption;
		$this->encryptionConf = $encryptionConf;
		if (!in_array($cacheLevel, Cache::$levels)) {
			throw new ValidationException('$cacheLevel must be one of Cache::NONE, Cache::SOME, Cache::ALL');
		}
		$this->cacheLevel = $cacheLevel;
		$this->syncFilter = sprintf('{ "room": { "timeline" : { "limit" : %d } } }', $syncFilterLimit);
		if ($token) {
			$response = $this->api->whoami();
			$this->userId = $response['user_id'];
			$this->sync();
		}
	}
}
