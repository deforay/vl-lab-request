<?php
ob_start();
require_once('../../startup.php');
include_once(APPLICATION_PATH . '/includes/MysqliDb.php');
include_once(APPLICATION_PATH . '/models/General.php');
$general = new General($db);

$tableName = "contact_notes_details";

try {
    $result = '';
    if (isset($_POST['notes']) && trim($_POST['notes']) != "") {
        $data = array(
            'contact_notes' => $_POST['notes'],
            'treament_contact_id' => $_POST['treamentId'],
            'collected_on' => $general->dateFormat($_POST['dateVal']),
            'added_on' => $general->getDateTime()
        );
        //print_r($data);die;
        $result = $db->insert($tableName, $data);
    }
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
echo $result;