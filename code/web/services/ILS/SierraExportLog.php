<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';

class ILS_SierraExportLog extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$logEntries = array();
		$logEntry = new SierraExportLogEntry();
		$total = $logEntry->count();
		$logEntry = new SierraExportLogEntry();
		$logEntry->orderBy('startTime DESC');
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : 30; // to adjust number of items listed on a page
		$interface->assign('recordsPerPage', $pageSize);
		$interface->assign('page', $page);
		$logEntry->limit(($page - 1) * $pageSize, $pageSize);
		$logEntry->find();
		while ($logEntry->fetch()){
			$logEntries[] = clone($logEntry);
		}
		$interface->assign('logEntries', $logEntries);

		$options = array('totalItems' => $total,
		                 'fileName'   => '/ILS/SierraExportLog?page=%d'. (empty($_REQUEST['pageSize']) ? '' : '&pageSize=' . $_REQUEST['pageSize']),
		                 'perPage'    => $pageSize,
		);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('sierraExportLog.tpl', 'Sierra Export Log');
	}
    function getModule()
    {
        return "ILS";
    }
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'cataloging');
	}
}
