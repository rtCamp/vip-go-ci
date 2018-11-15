<?php

/*
 * Queue up a message for IRC API,
 * or alternatively empty the queue and
 * return its contents.
 */
function vipgoci_irc_api_alert_queue(
	$message = null,
	$dump = false
) {
	static $msg_queue = array();

	if ( true === $dump ) {
		$msg_queue_tmp = $msg_queue;

		$msg_queue = array();

		return $msg_queue_tmp;
	}

	$msg_queue[] = $message; 
}

/*
 * Empty IRC message queue and send off
 * to the IRC API.
 */
function vipgoci_irc_api_alerts_send(
	$irc_api_url,
	$irc_api_token,
	$botname,
	$channel
) {
	$msg_queue = vipgoci_irc_api_alert_queue(
		null, true
	);

	vipgoci_log(
		'Sending messages to IRC API',
		array(
			'msg_queue' => $msg_queue,
		)
	);

	foreach( $msg_queue as $message ) {
		$irc_api_postfields = array(
			'message' => $message,
			'botname' => $botname,
			'channel' => $channel,
		);

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $irc_api_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 20
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT, VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch, CURLOPT_POST, 1
		);

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $irc_api_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: Bearer ' . $irc_api_token )
		);

		/*
		 * Execute query, keep record of how long time it
		 * took, and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'irc_api_post' );

		vipgoci_counter_report( 'do', 'irc_api_request_post', 1 );

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'irc_api_post' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		curl_close( $ch );

		/*
		 * Enforce a small wait between requests.
		 */

		time_nanosleep( 0, 500000000 );
	}
}