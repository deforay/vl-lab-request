<?php
ob_start();
#require_once('../../startup.php');


$general = new \Vlsm\Models\General($db);
$tableName = "eid_form";
try {
    $lock = $general->getGlobalConfig('lock_approved_eid_samples');
    $id = explode(",", $_POST['id']);
    for ($i = 0; $i < count($id); $i++) {
        $status = array(
            'result_status'             => $_POST['status'],
            'result_approved_by'        =>  $_SESSION['userId'],
            'result_approved_datetime'  =>  $general->getDateTime(),
            'data_sync'                 => 0
        );
        if ($_POST['status'] == '4') {
            $status['result'] = null;
            $status['is_sample_rejected'] = 'yes';
            $status['reason_for_sample_rejection'] = $_POST['rejectedReason'];
        } else {
            $status['is_sample_rejected'] = 'no';
            $status['reason_for_sample_rejection'] = null;
        }
        if($status['result_status'] == 7 && $lock == 'yes'){
            $status['locked'] = 'yes';
        }
        $db = $db->where('eid_id', $id[$i]);
        $db->update($tableName, $status);
        $result = $id[$i];
    }
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
echo $result;
