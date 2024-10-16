<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';

class MaterialsRequest_Graph extends Admin_Admin {
	function launch() {
		global $interface;
		$title = 'Materials Request Usage Graph';
		$status = $_REQUEST['status'];

		$interface->assign('curStatus', $status);

		$dataSeries = [];
		$columnLabels = [];

		$userHomeLibrary = Library::getPatronHomeLibrary();
		if (is_null($userHomeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$userHomeLibrary = $library;
		}
		$libraryId = $userHomeLibrary->libraryId;

		$thisStatus = new MaterialsRequestStatus();
		$thisStatus->id = $status;
		$thisStatus->libraryId = $libraryId;
		$thisStatus->find();
		while ($thisStatus->fetch()) {
			$title = 'Materials Request Usage Graph - ' . $thisStatus->description;
			$materialsRequestUsage = new MaterialsRequestUsage();
			$materialsRequestUsage->groupBy('year, month');
			$materialsRequestUsage->selectAdd();
			$materialsRequestUsage->statusId = $status;
			$materialsRequestUsage->selectAdd('year');
			$materialsRequestUsage->selectAdd('month');
			$materialsRequestUsage->selectAdd('SUM(numUsed) as numUsed');
			$materialsRequestUsage->orderBy('year, month');

			$dataSeries[$thisStatus->description] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];

			//Collect results
			$materialsRequestUsage->find();

			while ($materialsRequestUsage->fetch()) {
				$curPeriod = "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}";
				$columnLabels[] = $curPeriod;
				$dataSeries[$thisStatus->description]['data'][$curPeriod] = $materialsRequestUsage->numUsed;
			}
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('graphTitle', $title);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])) {
			$this->exportToExcel();
		}

		$this->display('graph.tpl', $title);
	}

	public function getAllPeriods() {
		$usage = new MaterialsRequestUsage();
		$usage->selectAdd(null);
		$usage->selectAdd('DISTINCT year, month');
		$usage->find();

		$stats = [];
		while ($usage->fetch()) {
			$stats[$usage->month . '-' . $usage->year]['year'] = $usage->year;
			$stats[$usage->month . '-' . $usage->year]['month'] = $usage->month;
		}
		return $stats;
	}

	function exportToExcel() {
		$status = $_REQUEST['status'];

		$periods = $this->getAllPeriods();

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="MaterialsRequestGraphReport.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');

		$header= ['Date', 'Requests Pending'];
		fputcsv($fp, $header);

		$userHomeLibrary = Library::getPatronHomeLibrary();
		if (is_null($userHomeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$userHomeLibrary = $library;
		}
		$libraryId = $userHomeLibrary->libraryId;

		$thisStatus = new MaterialsRequestStatus();
		$thisStatus->libraryId = $libraryId;
		$thisStatus->find();

		foreach ($periods as $period) {
			$materialsRequestUsage = new MaterialsRequestUsage();
			$materialsRequestUsage->year = $period['year'];
			$materialsRequestUsage->month = $period['month'];
			$materialsRequestUsage->statusId = $status;
			$materialsRequestUsage->find();

			$row = [];
			$date = "{$materialsRequestUsage->month}-{$materialsRequestUsage->year}";
			$row[] = $date;

			$thisStatus = new MaterialsRequestStatus();
			$thisStatus->libraryId = $libraryId;
			$thisStatus->find();

			$materialsRequestUsage = new MaterialsRequestUsage();
			$materialsRequestUsage->year = $period['year'];
			$materialsRequestUsage->month = $period['month'];
			$materialsRequestUsage->statusId = $thisStatus->id;

			if ($materialsRequestUsage->find(true)){ //if we find a match on year, month, and id/statusId
				$row[] = $materialsRequestUsage->numUsed ?? 0;
			}else{
				$row[] = 0;
			}
			fputcsv($fp, $row);
		}
		exit;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#materialsrequest', 'Materials Request');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}
}