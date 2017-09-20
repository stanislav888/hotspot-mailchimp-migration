<?php
$debug = true;
define('settings_file', './migration-settings.ini');
define('last_imported_file', './last-imported-time.ini');
define('SEPARATOR', '_');

if ($debug) {
	error_reporting(E_ALL); // Please comment if not want see PHP errors
}

require_once "./mailchimp-api/src/MailChimp.php";
require_once "./mailchimp-api/src/Batch.php";
use \DrewM\MailChimp\MailChimp;

$settings = parse_ini_file(settings_file, true);
$last_imported_dates = parse_ini_file(last_imported_file);
// print_r($settings);

define('url', 'https://api.hotspotsystem.com/v2.0/locations/$hotspotsystem_location_id/customers');

function endsWith($haystack, $needle) {
	$length = strlen($needle);

	return $length === 0 ||
		(substr($haystack, -$length) === $needle);
}

foreach ($settings as $key => $val) {

	$debug = isset($val['debug_mode']) ? $val['debug_mode'] : false;

	if ($debug) {
		echo PHP_EOL . 'Handle: ' . $key . PHP_EOL;
	}

	$hotspotsystem_api_key = $val['hotspotsystem_api_key'];
	$hotspotsystem_location_id = $val["hs_location_id"];

	$headers = array
		(
		'sn-apikey: ' . $hotspotsystem_api_key,
	);

	$total_count = 0; // Any infinity value for
	$emails_added = 0;

	$list_id = $val["mc_list_id"];
	$last_imported_key = $hotspotsystem_location_id . "--->" . $list_id;
	$last_date = isset($last_imported_dates[$last_imported_key]) ?
	$last_imported_dates[$last_imported_key] : "0" . SEPARATOR . "0";
	$last_updated = explode(SEPARATOR, $last_date);
	$offset = $last_updated[0];
	$last_record_registered_at = $last_updated[1];
	// echo "offset " . $offset . PHP_EOL;
	// echo "last_record_registered_at " . $last_record_registered_at . PHP_EOL;

	$MailChimp = new MailChimp($val["mc_api_key"]);
	$batch_id = $list_id . "_batch_id";
	$batch = $MailChimp->new_batch($batch_id);

	do {
		// Fetch records in any case for $total_count

		$url2 = url . "?limit=100&offset=" . strval($offset);

		if ($debug) {
			echo "Fetching: " . $url2 . PHP_EOL;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url2);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);

		$response_http_code = $info['http_code'];
		$error_string = null;
		$json = null;

		if ($response_http_code === 200) {
			$json = json_decode($response, true);
			// print_r($json );
			// file_put_contents( "./answer_content".$key.".txt", $json );
		} else {
			$error_string = 'Response code: ' . $response_http_code . ' response not handled';
		}

		curl_close($ch);

		if (strlen($error_string) > 0 and $debug) {
			echo "Error in hotspotsystem: " . $error_string . PHP_EOL;
		}

		if ($json != null) {
			// Mailchimp logic
			$total_count = $json["metadata"]["total_count"];

			foreach ($json["items"] as $value) {

				$email = $value["email"];
				$current_user_registered_at = $value["registered_at"];

				if (strlen($email) > 3 and $current_user_registered_at > $last_record_registered_at
					and endsWith($value["user_name"], '_' . strval($hotspotsystem_location_id))) // Hack for additional group number checking
				{
					$batch->post("op" . strval(++$emails_added), "lists/$list_id/members"
						, [
							'email_address' => $email,
							'status' => 'subscribed',
						]);

					if ($debug) {
						echo 'Added new email:' . $email . PHP_EOL;
					}
				}

				if ($last_record_registered_at < $current_user_registered_at) {
					$last_updated = $offset . SEPARATOR . $current_user_registered_at;
				}
			}

		} // if ($json != null)
		$offset = ($offset + 100);
	} while ($offset < $total_count);

	if ($emails_added > 0) {
		$res = $batch->execute(10);
		$status = $res["status"];

		if ($status == "pending");
		{
			$last_imported_dates[$last_imported_key] = $last_updated;
		}

		if ($debug) {
			echo "Added " . strval($emails_added) . " records" . PHP_EOL;
			echo 'MailChimp status: ' . $status . PHP_EOL . PHP_EOL;

			sleep(11);
			// var_dump($batch->check_status($batch_id));
			echo PHP_EOL . "MailChimp->getLastError()" . $MailChimp->getLastError() . PHP_EOL;

		}

	} elseif ($debug) {
		echo 'No new emails' . PHP_EOL . PHP_EOL;
	}
} // foreach

$content = null;

foreach ($last_imported_dates as $key => $value) {
	$content = $content . $key . ' = ' . $value . PHP_EOL;
}

file_put_contents(last_imported_file, $content);

?>