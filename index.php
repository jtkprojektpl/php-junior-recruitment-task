<?php
/**
 * Reads content of csv file and returns arrays with
 * data from every single line
 * $file_address is the address on the server pointing to the required files
 * $starting_position is the pointer position in array created from base csv file, 
 * it tells you where to start reading columns from csv
 * $slice_lenght is desired lenght of an array created from csv
 * Both arguments refer to array_slice function.
 */
class DataReader
{

	public function getDataFromCSV($file_address, $starting_position, $slice_length) {
		
		$rows = array_map('str_getcsv', file($file_address));
	    $header = array_shift($rows);
	    $slice_of_header = array_slice($header, $starting_position, $slice_length, true);
	    $csv = array();
	    
	    foreach($rows as $row) {
	    	$slice_of_row = array_slice($row, $starting_position, $slice_length, true);
	    	$csv[] = array_combine($slice_of_header, $slice_of_row);
	    }

	    return $csv;
	}

}

/**
 * Takes care of new courier packages
 * Creates labels in png format in /labels/DmY/ folder
 * Saves packages ids in root folder in the format: PACKAGE_ID:LABEL_NAME
 */
class CourierRouting extends DataReader
{

	private $urlprefix = 'https://api.swiatprzesylek.pl/V1/';
	private $login = 'Rtest10';
	private $pass = 'b50ef55e997d6280956c4e362be7eba658f09b87ffda8b1b15c37ec7adf5086c';

	private function createLabel($label, $package_id) {
		
		$label = base64_decode($label);
		$image = imagecreatefromstring($label);
		$rotated_image = imagerotate($image, 270, 0);
			 					
		// Labels should be saved in folder /labels/<Today_date_in_DmY_format>/
		$directory_name = date("DmY");
		$dirPath = __DIR__ . '\labels\\' . $directory_name;

		// Create directory if it doesn't exist
		if (is_dir($dirPath) === FALSE) {
			mkdir($dirPath, 0744, true);
		}

		// Save current label in previously created folder 
		// as png image rotated 90 degrees clockwise
		//file_put_contents($dirPath . '\\' . $package_id . '.png', $label);
		imagepng($rotated_image, $dirPath . '\\' . $package_id . '.png');
	}

	private function savePackageId($package_id) {
		
		$file = fopen("package_ids.txt", "a+") or die("Unable to open file!");

		// String format in txt file:
		// PACKAGE_ID:LABEL_NAME
		// In this situation label and package id are the same
		$formatted_string = $package_id . ':' . $package_id;
		fwrite($file, $formatted_string . "\n");
		fclose($file);
	}

	public function createCouriers($input) {

		if ($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $this->urlprefix . 'courier/create-pre-routing');
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $this->login . ':' . $this->pass);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/json"));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			 
			$response = curl_exec($ch);
			$result = json_decode($response);
			if ($result->result === "OK") {
			 	$i = 0;
			 	foreach ($result->response->packages AS $package) {
			 		
			 		if ($package->result === "OK" OR $options2['problem_label']) {
			 			$labels_no = $package->labels_no;
			 			$package_id = $package->package_id;
			 			
			 			$j = 0;
			 			if ($labels_no)
			 				foreach ($package->labels AS $label) {
			 					
			 					// Save every label in destination folder
			 					$this->createLabel($label, $package_id);

			 					// Now save all packages ids in package_ids.txt file in the root folder
								$this->savePackageId($package_id);

			 					$j++;
			 				}

			 				$i++;
			 		}
			 	}
			} else {
				var_dump($result);
			}
		}
	}

	public function generateOutput() {

		// Data from CSV file
		$sender_data = $this->getDataFromCSV(__DIR__ . "/source/address.csv", 0, 8);
		$receiver_data = $this->getDataFromCSV(__DIR__ . "/source/address.csv", 8, 8);
		$package_data = $this->getDataFromCSV(__DIR__ . "/source/dimensions.csv", 0, 10);

		if(count($sender_data) == count($receiver_data) && 
			count($sender_data) == count($package_data) && 
			count($receiver_data) == count($package_data)) {

			for ($i=0; $i < count($sender_data) ; $i++) {

				$current_sender = array_values($sender_data[$i]);
				$current_receiver = array_values($receiver_data[$i]);

				$sender = [];
				$sender['name'] = $current_sender[0];
				$sender['company'] = $current_sender[1];
				$sender['address_line_1'] = $current_sender[2];
				$sender['address_line_2'] = '';
				$sender['country'] = $current_sender[5];
				$sender['zip_code'] = $current_sender[4];
				$sender['city'] = $current_sender[3];
				$sender['tel'] = $current_sender[6];
				$sender['email'] = $current_sender[7];

				$receiver = [];
				$receiver['name'] = $current_receiver[0];
				$receiver['company'] = $current_receiver[1];
				$receiver['address_line_1'] = $current_receiver[2];
				$receiver['address_line_2'] = '';
				$receiver['country'] = $current_receiver[5];
				$receiver['zip_code'] = $current_receiver[4];
				$receiver['city'] = $current_receiver[3];
				$receiver['tel'] = $current_receiver[6];
				$receiver['email'] = $current_receiver[7];

				
				$package = [];
				$package = $package_data[$i];

				$options = [];

				// API Input
				$DATA = [
				 'package' => $package,
				 'sender' => $sender,
				 'receiver' => $receiver,
				 'options' => $options,
				];

				$apiCall = new CourierRouting();
				$apiCall->createCouriers($DATA);
			}
		}
	}
}

// Execute the whole process
$action = new CourierRouting();
$action->generateOutput();

/*	Some comments

	Country input data should be in ISO 3166-1 alfa-2
	format (basically two letter codes e.g 'PL') so I changed the input
	in the CSV file because API was telling me that country string cannot be empty,
	even though it wansn't. 
	I've spent a lot of time figuring out what was wrong.
	I could write a function to convert csv country input into country codes, but 
	I guess it wasn't the main purpose of this task. :)
*/

?>