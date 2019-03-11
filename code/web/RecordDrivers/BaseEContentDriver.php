<?php

require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

abstract class BaseEContentDriver  extends MarcRecordDriver {
	abstract function getModuleName();
	abstract function getValidProtectionTypes();

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
     * @param  GroupedWork $groupedWork ;
     * @access  public
	 */
	public function __construct($recordData, $groupedWork = null){
		parent::__construct($recordData, $groupedWork);
	}


	protected function isValidProtectionType($protectionType) {
		return in_array(strtolower($protectionType), $this->getValidProtectionTypes());
	}

	abstract function isEContentHoldable($locationCode, $eContentFieldData);
	abstract function isLocalItem($locationCode, $eContentFieldData);
	abstract function isLibraryItem($locationCode, $eContentFieldData);
	abstract function isItemAvailable($itemId, $totalCopies);
	function getUsageRestrictions($sharing, $libraryLabel, $locationLabel){
		if ($sharing == 'shared'){
			return "Available to Everyone";
		}else if ($sharing == 'library'){
			return 'Available to patrons of ' . $libraryLabel;
		}else if ($sharing == 'location'){
			return 'Available to patrons of ' .  $locationLabel;
		}else{
			return 'Unable to determine usage restrictions';
		}
	}
	abstract function isValidForUser($locationCode, $eContentFieldData);

	abstract function getSharing($locationCode, $eContentFieldData);

	abstract function getActionsForItem($itemId, $fileName, $acsId);

	abstract function getEContentFormat($fileOrUrl, $iType);

	protected function isHoldable(){
		return false;
	}
}
