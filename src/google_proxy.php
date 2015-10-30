<?php
/*
	Proxy class for google calendar data
	Can be used to load data from Google Calendar to the dhtmlxScheduler
	or to save changes in dhtmlxScheduler back to Google Calendar

	Copyright (c) 2013 Dinamenta UAB
	Distributed under the MIT software license
*/


require_once("google-api-php-client/src/Google_Client.php");
require_once("google-api-php-client/src/contrib/Google_CalendarService.php");

class GoogleCalendarProxy {

	private $cal;
	private $cal_name;

	public $timezone = false;
	public $outputAll = false;
	
	public function __construct($email, $id, $key, $calendar = false) {
		$client = new Google_Client();
		$client->setAssertionCredentials(
		  new Google_AssertionCredentials(
		    $email,
		    array('https://www.googleapis.com/auth/calendar'),
		    $key
		));

		$client->setClientId($id);

		$this->cal = new Google_CalendarService($client);
		$this->cal_name = $calendar;
	}


	private function retrieveAllEvents() {
		$cache_events = $this->cal->events->listEvents($this->cal_name);
		return $this->extractEventsFromJSON($cache_events);
	}

	private function retrieveEvent($event_id) {
		return $this->cal->events->get($this->cal_name, $event_id);
	}

	private function extractEventsFromJSON($json) {
		$events = Array();
		if (isset($json["items"])) {
			for ($i = 0; $i < count($json["items"]); $i++) {
				$event = $json["items"][$i];

				if (!$this->outputAll){
					$result = array();
					foreach ($event as $key => $value)
						if (is_string($value))
							$result[$key] = $event[$key];
				} else {
					$result = $event;
				}

				$result["text"] 		= $event["summary"];
				$result["start_date"] 	= $this->parse_gDate($event["start"]);
				$result["end_date"] 	= $this->parse_gDate($event["end"]);
				$events[] = $result;
			}
		}
		return $events;
	}

	private function detect_timezone(){
		if (!$this->timezone){
			$calendar = $this->cal->calendars->get($this->cal_name);
			$this->timezone = $calendar["timeZone"];
		}
	}

	private function parse_gDate($input_date) {
		if (isset($input_date["dateTime"])){
			$date = explode("T", $input_date["dateTime"]);
			$time = $date[1];
			$date = $date[0];
			$time = explode('.', $time);
			$time = $time[0];
		} else if (isset($input_date["date"])){
			$date = $input_date["date"];
			$time = "00:00";
		}
		return $date.' '.$time;
	}


	private function to_gDate($input_date) {
		$input_date = str_replace(" ", "T", $input_date);
		$input_date .= ":00.000";
		return $input_date;
	}


	public function map($googleField, $dhtmlxField) {
		$this->map[$googleField] = $dhtmlxField;
		$this->back_map[$dhtmlxField] = $googleField;
		$this->export_map[$googleField] = $dhtmlxField;
		$this->export_back_map[$dhtmlxField] = $googleField;
	}


	public function connect() {
		if (isset($_POST['!nativeeditor_status'])) {
			$xml = $this->dataProcessor($_POST);
		} else {
			$xml = $this->render();
		}
	}


	private function render() {
		$events = $this->retrieveAllEvents();

		header('Content-type: application/json');
		echo json_encode($events);
	}


	private function dataProcessor($data) {
		$this->detect_timezone();

		$status = $data['!nativeeditor_status'];
		$id = $data["id"];
		switch ($status) {
			case 'updated':
				$result = $this->update($data);
				break;
			case 'inserted':
				$result = $this->insert($data);
				break;
			case 'deleted':
				$result = $this->delete($data);
				break;
		}

		if (!$result)
			$status = "error";

		header("Content-type: text/xml");
		echo "<?xml version='1.0' ?><data><action type='$status' sid='{$id}' tid='{$result}'></action></data>";
	}


	public function update($data) {
		$id = $data['id'];
		// takes event from google server
		$ev = $this->retrieveEvent($id);
		// update and save to server
		$xml = $this->updateEvent($ev, $data);

		return $xml;
	}


	private function updateEvent($ev, $data){
		$data["start"] = array( 
			"dateTime" => $this->to_gDate($data["start_date"]), 
			"timeZone" => "Europe/Minsk"	);
		$data["end"]   = array( 
			"dateTime" => $this->to_gDate($data["end_date"]),
			"timeZone" => "Europe/Minsk"	);
		$data["summary"] = $data["text"];

		try{
			$ev = $this->cal->events->update($this->cal_name, $ev["id"], new Google_Event($data));
		} catch(Exception $error){
			return false;
		}

		// process response
		if ($ev)
			return $ev["id"];
		return false;
	}


	private function insert($data) {
		return $this->insertEvent($data);
	}


	private function insertEvent($data) {
		$timezone = isset($data["timezone"]) ? $data["timezone"]: "00:00";
		$data["start"] = array( 
			"dateTime" => $this->to_gDate($data["start_date"]), 
			"timeZone" => "Europe/Minsk"	);
		$data["end"]   = array( 
			"dateTime" => $this->to_gDate($data["end_date"]),
			"timeZone" => "Europe/Minsk"	);
		$data["summary"] = $data["text"];
		unset($data["id"]);

		try{
			$new_ev = $this->cal->events->insert($this->cal_name, new Google_Event($data));
		} catch(Exception $error){
			return false;
		}

		// process result
		if ($new_ev)
			return $new_ev["id"];
		return false;
	}


	private function delete($data) {
		$xml = $this->deleteEvent($data);
		return $xml;
	}


	private function deleteEvent($data) {
		try{
			$result = $this->cal->events->delete($this->cal_name, $data["id"]);
		} catch(Exception $error){
			return false;
		}

		return $data["id"];
	}
}

?>
