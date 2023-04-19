<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/CommunicoEvent.php';

class CommunicoEventRecordDriver extends IndexRecordDriver {
	private $valid;
	/** @var CommunicoEventRecordDriver */
	private $eventObject;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			disableErrorHandler();
			try {
				require_once ROOT_DIR . '/sys/SearchObject/EventsSearcher.php';
				$searchObject = new SearchObject_EventsSearcher();
				$recordData = $searchObject->getRecord($recordData);
				if ($recordData == null) {
					$this->valid = false;
				} else {
					parent::__construct($recordData);
					$this->valid = true;
				}
			} catch (Exception $e) {
				$this->valid = false;
			}
			enableErrorHandler();
		}
	}

	public function isValid() {
		return $this->valid;
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		//Switch template
		return 'RecordDrivers/Events/listEntry.tpl';
	}

	public function getTitle(){
		$title = isset($this->fields['title']) ? $this->fields['title'] : (isset($this->fields['title_display']) ? $this->fields['title_display'] : '');
		if (strpos($title, '|') > 0) {
			$title = substr($title, 0, strpos($title, '|'));
		}
		return trim($title);
	}

	public function getSearchResult($view = 'list') {
		global $interface;

		$interface->assign('id', $this->getId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));
		$interface->assign('eventUrl', $this->getLinkUrl());
		$interface->assign('title', $this->getTitle());
		if (isset($this->fields['description'])) {
			$interface->assign('description', $this->fields['description']);
		} else {
			$interface->assign('description', '');
		}
		if (array_key_exists('reservation_state', $this->fields) && in_array('Cancelled', $this->fields['reservation_state'])) {
			$interface->assign('isCancelled', true);
		} else {
			$interface->assign('isCancelled', false);
		}
		$allDayEvent = false;
		$multiDayEvent = false;
		if ($this->getEventLength() == 0 || $this->getEventLength() == 24){
			$allDayEvent = true;
		} elseif ($this->getEventLength() > 24){
			$multiDayEvent = true;
		}
		$interface->assign('allDayEvent', $allDayEvent);
		$interface->assign('multiDayEvent', $multiDayEvent);
		$interface->assign('start_date', $this->fields['start_date']);
		$interface->assign('end_date', $this->fields['end_date']);
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

		require_once ROOT_DIR . '/sys/Events/EventsUsage.php';
		$eventsUsage = new EventsUsage();
		$eventsUsage->type = $this->getType();
		$eventsUsage->source = $this->getSource();
		$eventsUsage->identifier = $this->getIdentifier();
		$eventsUsage->year = date('Y');
		$eventsUsage->month = date('n');
		if ($eventsUsage->find(true)) {
			$eventsUsage->timesViewedInSearch++;
			$eventsUsage->update();
		} else {
			$eventsUsage->timesViewedInSearch = 1;
			$eventsUsage->timesUsed = 0;
			$eventsUsage->insert();
		}

		return 'RecordDrivers/Events/communico_result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=communico_event";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'CommunicoEvents';
	}

	public function getStaffView() {
		global $interface;
		return $this->getEventObject()->getDecodedData();
	}

	public function getDescription() {
		if (isset($this->fields['description'])) {
			return $this->fields['description'];
		} else {
			return '';
		}
	}

	public function getFullDescription() {
		$description = $this->getEventObject()->getDecodedData();
		return $description->description;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID() {
		return $this->fields['id'];
	}

	public function getLinkUrl($absolutePath = false) {
		return '/Communico/' . $this->getId() . '/Event';
	}

	public function getExternalUrl($absolutePath = false) {
		return $this->fields['url'];
	}

	public function getAudiences() {
		if (array_key_exists('age_group', $this->fields)){
			return $this->fields['age_group'];
		}
	}

	public function getProgramTypes() {
		if (array_key_exists('program_type', $this->fields)){
			return $this->fields['program_type'];
		}
	}

	public function getBranch() {
		return implode(", ", $this->fields['branch']);
	}

	public function getRoom() {
		return implode(", ", $this->fields['room']);
	}

	public function getType() {
		return $this->fields['event_type'];
	}

	private function getSource() {
		return $this->fields['source'];
	}

	function getEventCoverUrl() {
		$decodedData = $this->getEventObject()->getDecodedData();
		if (!empty($decodedData->eventImage)) {
			return $decodedData->eventImage;
		}
		return null;
	}

	function getEventObject() {
		if ($this->eventObject == null) {
			$this->eventObject = new CommunicoEvent();
			$this->eventObject->externalId = $this->getIdentifier();
			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		return $this->eventObject;
	}

	function getStartDateFromDB($id) : ?object {
		if ($this->eventObject == null) {
			$this->eventObject = new CommunicoEvent();
			$this->eventObject->externalId = $id;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject->getDecodedData();

		try {
			$startDate = new DateTime($data->eventStart);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}

	}

	function getTitleFromDB($id) {
		if ($this->eventObject == null) {
			$this->eventObject = new CommunicoEvent();
			$this->eventObject->externalId = $id;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject->getDecodedData();

		return $data->title;
	}

	private function getIdentifier() {
		return $this->fields['identifier'];
	}

	public function getStartDate() {
		try {
			//Need to specify timezone since we start as a timstamp
			$startDate = new DateTime($this->fields['start_date']);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}
	}

	public function getEndDate() {
		try {
			//Need to specify timezone since we start as a timstamp
			$endDate = new DateTime($this->fields['end_date']);
			$endDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $endDate;
		} catch (Exception $e) {
			return null;
		}
	}

	public function getEventLength() {
		try {
			$start = new DateTime($this->fields['start_date']);
			$end = new DateTime($this->fields['end_date']);

			$interval = $start->diff($end);

			if ($interval->i > 0 && $interval->h == 0){ //some events don't last an hour
				return 1;
			}
			return $interval->h;
		} catch (Exception $e) {
			return null;
		}
	}

	public function isRegistrationRequired(): bool {
		if ($this->fields['registration_required'] == "Yes") {
			return true;
		} else {
			return false;
		}
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index) {
		$result = parent::getSpotlightResult($collectionSpotlight, $index);
		if ($collectionSpotlight->style == 'text-list') {
			global $interface;
			$interface->assign('start_date', $this->fields['start_date']);
			$interface->assign('end_date', $this->fields['end_date']);
			$result['formattedTextOnlyTitle'] = $interface->fetch('RecordDrivers/Events/formattedTextOnlyTitle.tpl');
		}

		return $result;
	}
}