<?php
#require_once('../../startup.php');


$tableName = "batch_details";
$primaryKey = "batch_id";

$general = new \Vlsm\Models\General($db);



if (isset($_POST['type']) && $_POST['type'] == 'vl') {
    $refTable = "vl_request_form";
    $refPrimaryColumn = "vl_sample_id";
    $editFileName = 'editBatch.php';
    $editPositionFileName = 'editBatchControlsPosition.php';
} else if (isset($_POST['type']) && $_POST['type'] == 'eid') {
    $refTable = "eid_form";
    $refPrimaryColumn = "eid_id";
    $editFileName = 'eid-edit-batch.php';
    $editPositionFileName = 'eid-edit-batch-position.php';
} else if (isset($_POST['type']) && $_POST['type'] == 'covid19') {
    $refTable = "form_covid19";
    $refPrimaryColumn = "covid19_id";
    $editFileName = 'covid-19-edit-batch.php';
    $editPositionFileName = 'covid-19-edit-batch-position.php';
}



/* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
        */

$aColumns = array('b.batch_code', "DATE_FORMAT(b.request_created_datetime,'%d-%b-%Y %H:%i:%s')");
$orderColumns = array('b.batch_code', '', '', '', 'b.request_created_datetime');

/* Indexed column (used for fast and accurate table cardinality) */
$sIndexColumn = $primaryKey;

$sTable = $tableName;
/*
         * Paging
         */
$sLimit = "";
if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
    $sOffset = $_POST['iDisplayStart'];
    $sLimit = $_POST['iDisplayLength'];
}

/*
         * Ordering
        */

$sOrder = "";
if (isset($_POST['iSortCol_0'])) {
    $sOrder = "";
    for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
        if ($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {

            $sOrder .= $orderColumns[intval($_POST['iSortCol_' . $i])] . "
				" . ($_POST['sSortDir_' . $i]) . ", ";
        }
    }
    $sOrder = substr_replace($sOrder, "", -2);
}

/*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
        */

$sWhere = "";
if (isset($_POST['sSearch']) && $_POST['sSearch'] != "") {
    $searchArray = explode(" ", $_POST['sSearch']);
    $sWhereSub = "";
    foreach ($searchArray as $search) {
        if ($sWhereSub == "") {
            $sWhereSub .= "(";
        } else {
            $sWhereSub .= " AND (";
        }
        $colSize = count($aColumns);

        for ($i = 0; $i < $colSize; $i++) {
            if ($i < $colSize - 1) {
                $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
            } else {
                $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
            }
        }
        $sWhereSub .= ")";
    }
    $sWhere .= $sWhereSub;
}

/* Individual column filtering */
for ($i = 0; $i < count($aColumns); $i++) {
    if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
        if ($sWhere == "") {
            $sWhere .= $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
        } else {
            $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
        }
    }
}

/*
         * SQL queries
         * Get data to display
        */

$sQuery = "SELECT SUM(CASE WHEN vl.sample_tested_datetime is not null THEN 1 ELSE 0 END) as `testcount`, 
                MAX(vl.sample_tested_datetime) as last_tested_date,
                b.request_created_datetime,
                b.batch_code, 
                b.batch_id, 
                COUNT(vl.sample_code) AS total_samples 
                FROM $refTable vl, batch_details b 
                WHERE vl.sample_batch_id = b.batch_id
                AND b.test_type like '" . $_POST['type'] . "'";

$sQuery = $sQuery . ' ' . $sWhere;
$sQuery = $sQuery . ' group by b.batch_id';
if (isset($sOrder) && $sOrder != "") {
    $sOrder = preg_replace('/(\v|\s)+/', ' ', $sOrder);
    $sQuery = $sQuery . ' order by ' . $sOrder;
}

if (isset($sLimit) && isset($sOffset)) {
    $sQuery = $sQuery . ' LIMIT ' . $sOffset . ',' . $sLimit;
}
//die($sQuery);
//echo $sQuery;die;
$rResult = $db->rawQuery($sQuery);
// print_r($rResult);
/* Data set length after filtering */


$aResultFilterTotal = $db->rawQueryOne("SELECT COUNT(b.batch_id) AS total FROM $refTable vl, batch_details b WHERE vl.sample_batch_id = b.batch_id AND b.test_type LIKE '" . $_POST['type'] . "' $sWhere GROUP BY b.batch_id");
$iFilteredTotal = !empty($aResultFilterTotal['total']) ? $aResultFilterTotal['total'] : 0;

$aResultTotal = $db->rawQueryOne("SELECT COUNT(b.batch_id) AS total FROM $refTable vl, batch_details b WHERE vl.sample_batch_id = b.batch_id AND b.test_type LIKE '" . $_POST['type'] . "' GROUP BY b.batch_id");
$iTotal = !empty($aResultTotal['total']) ? $aResultTotal['total'] : 0;


/*
         * Output
        */
$output = array(
    "sEcho" => intval($_POST['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);
$batch = false;
if (isset($_SESSION['privileges']) && (in_array($editFileName, $_SESSION['privileges']))) {
    $batch = true;
}

foreach ($rResult as $aRow) {
    $createdDate = "";
    if (trim($aRow['request_created_datetime']) != "" && $aRow['request_created_datetime'] != '0000-00-00 00:00:00') {
        $createdDate =  date("d-M-Y H:i:s", strtotime($aRow['request_created_datetime']));
    }


    $row = array();
    $printBarcode = '<a href="/vl/batch/generateBarcode.php?id=' . base64_encode($aRow['batch_id']) . '&type=' . $_POST['type'] . '" target="_blank" class="btn btn-info btn-xs" style="margin-right: 2px;" title="Print bar code"><i class="fa fa-barcode"> Print Batch</i></a>';
    $printQrcode = '<a href="javascript:void(0);" class="btn btn-info btn-xs" style="margin-right: 2px;" title="Print qr code" onclick="generateQRcode(\'' . base64_encode($aRow['batch_id']) . '\');"><i class="fa fa-qrcode"> Print QR code</i></a>';
    $editPosition = '<a href="' . $editPositionFileName . '?id=' . base64_encode($aRow['batch_id']) . '" class="btn btn-default btn-xs" style="margin-right: 2px;margin-top:6px;" title="Edit Position"><i class="fa fa-sort-numeric-desc"> Edit Position</i></a>';

    $deleteBatch = '';
    if ($aRow['total_samples'] == 0 || $aRow['testcount'] == 0) {
        $deleteBatch = '<a href="javascript:void(0);" class="btn btn-danger btn-xs" style="margin-right: 2px;margin-top:6px;" title="" onclick="deleteBatchCode(\'' . base64_encode($aRow['batch_id']) . '\',\'' . $aRow['batch_code'] . '\');"><i class="fa fa-times"> Delete</i></a>';
    }

    $date = '';
    if ($aRow['last_tested_date'] != '0000-00-00 00:00:00' && $aRow['last_tested_date'] != null) {
        $exp = explode(" ", $aRow['last_tested_date']);
        $lastDate = $general->humanDateFormat($exp[0]);
    }
    $row[] = ucwords($aRow['batch_code']);
    $row[] = $aRow['total_samples'];
    $row[] = $aRow['testcount'];
    $row[] = $lastDate;
    $row[] = $createdDate;
    //    $row[] = '<select class="form-control" name="status" id=' . $aRow['batch_id'] . ' title="Please select status" onchange="updateStatus(this.id,this.value)">
    //		    <option value="pending" ' . ($aRow['batch_status'] == "pending" ? "selected=selected" : "") . '>Pending</option>
    //		    <option value="completed" ' . ($aRow['batch_status'] == "completed" ? "selected=selected" : "") . '>Completed</option>
    //	    </select>';
    if (isset($_POST['fromSource']) && $_POST['fromSource'] == 'qr') {
        $row[] = $printQrcode;
    } else {
        if ($batch) {
            $row[] = '<a href="' . $editFileName . '?id=' . base64_encode($aRow['batch_id']) . '" class="btn btn-primary btn-xs" style="margin-right: 2px;" title="Edit"><i class="fa fa-pencil"> Edit</i></a>&nbsp;' . $printBarcode . '&nbsp;' . $editPosition . '&nbsp;' . $deleteBatch;
        }
    }
    $output['aaData'][] = $row;
}
echo json_encode($output);
