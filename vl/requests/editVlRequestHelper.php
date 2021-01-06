<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ob_start();
#require_once('../../startup.php');

$general = new \Vlsm\Models\General($db);
$tableName = "vl_request_form";
$tableName1 = "activity_log";
$vlTestReasonTable = "r_vl_test_reasons";
$fDetails = "facility_details";
$vl_result_category = NULL;
try {
     $validateField = array($_POST['sampleCode'], $_POST['sampleCollectionDate']);
     $chkValidation = $general->checkMandatoryFields($validateField);
     if ($chkValidation) {
          $_SESSION['alertMsg'] = "Please enter all mandatory fields to save the test request";
          header("location:editVlRequest.php?id=" . base64_encode($_POST['vlSampleId']));
          die;
     }
     //system config
     $systemConfigQuery = "SELECT * from system_config";
     $systemConfigResult = $db->query($systemConfigQuery);
     $sarr = array();
     // now we create an associative array so that we can easily create view variables
     for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
          $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
     }
     if (($sarr['user_type'] == 'remoteuser') && $_POST['oldStatus'] == 9) {
          $_POST['status'] = 9;
     } else if ($_POST['oldStatus'] == 9) {
          $_POST['status'] = 6;
     }
     if ($_POST['status'] == '') {
          $_POST['status']  = $_POST['oldStatus'];
     }
     //add province
     $splitProvince = explode("##", $_POST['province']);
     if (isset($splitProvince[0]) && trim($splitProvince[0]) != '') {
          $provinceQuery = "SELECT * from province_details where province_name='" . $splitProvince[0] . "'";
          $provinceInfo = $db->query($provinceQuery);
          if (!isset($provinceInfo) || count($provinceInfo) == 0) {
               $db->insert('province_details', array('province_name' => $splitProvince[0], 'province_code' => $splitProvince[1]));
          }
     }
     if (isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate']) != "") {
          $sampleDate = explode(" ", $_POST['sampleCollectionDate']);
          $_POST['sampleCollectionDate'] = $general->dateFormat($sampleDate[0]) . " " . $sampleDate[1];
     } else {
          $_POST['sampleCollectionDate'] = NULL;
     }

     if (isset($_POST['dob']) && trim($_POST['dob']) != "") {
          $_POST['dob'] = $general->dateFormat($_POST['dob']);
     } else {
          $_POST['dob'] = NULL;
     }

     if (isset($_POST['dateOfArtInitiation']) && trim($_POST['dateOfArtInitiation']) != "") {
          $_POST['dateOfArtInitiation'] = $general->dateFormat($_POST['dateOfArtInitiation']);
     } else {
          $_POST['dateOfArtInitiation'] = NULL;
     }

     if (isset($_POST['regimenInitiatedOn']) && trim($_POST['regimenInitiatedOn']) != "") {
          $_POST['regimenInitiatedOn'] = $general->dateFormat($_POST['regimenInitiatedOn']);
     } else {
          $_POST['regimenInitiatedOn'] = NULL;
     }

     if (isset($_POST['newArtRegimen']) && trim($_POST['newArtRegimen']) != "") {
          $artQuery = "SELECT art_id,art_code FROM r_vl_art_regimen where (art_code='" . $_POST['newArtRegimen'] . "' OR art_code='" . strtolower($_POST['newArtRegimen']) . "' OR art_code='" . ucfirst(strtolower($_POST['newArtRegimen'])) . "')";
          $artResult = $db->rawQuery($artQuery);
          if (!isset($artResult[0]['art_id'])) {
               $data = array(
                    'art_code' => $_POST['newArtRegimen'],
                    'parent_art' => '1',
                    'updated_datetime' => $general->getDateTime(),
               );
               $result = $db->insert('r_vl_art_regimen', $data);
               $_POST['artRegimen'] = $_POST['newArtRegimen'];
          } else {
               $_POST['artRegimen'] = $artResult[0]['art_code'];
          }
     }

     //update facility code
     if (trim($_POST['fCode']) != '') {
          $fData = array('facility_code' => $_POST['fCode']);
          $db = $db->where('facility_id', $_POST['fName']);
          $id = $db->update($fDetails, $fData);
     }
     //update facility emails
     //if(trim($_POST['emailHf'])!=''){
     //   $fData = array('facility_emails'=>$_POST['emailHf']);
     //   $db=$db->where('facility_id',$_POST['fName']);
     //   $id=$db->update($fDetails,$fData);
     //}
     if (isset($_POST['gender']) && trim($_POST['gender']) == 'male') {
          $_POST['patientPregnant'] = '';
          $_POST['breastfeeding'] = '';
     }
     $instanceId = '';
     if (isset($_SESSION['instanceId'])) {
          $instanceId = $_SESSION['instanceId'];
     }
     $testingPlatform = '';
     if (isset($_POST['testingPlatform']) && trim($_POST['testingPlatform']) != '') {
          $platForm = explode("##", $_POST['testingPlatform']);
          $testingPlatform = $platForm[0];
     }
     if (isset($_POST['sampleReceivedDate']) && trim($_POST['sampleReceivedDate']) != "") {
          $sampleReceivedDateLab = explode(" ", $_POST['sampleReceivedDate']);
          $_POST['sampleReceivedDate'] = $general->dateFormat($sampleReceivedDateLab[0]) . " " . $sampleReceivedDateLab[1];
     } else {
          $_POST['sampleReceivedDate'] = NULL;
     }


     if (isset($_POST['sampleReceivedAtHubOn']) && trim($_POST['sampleReceivedAtHubOn']) != "") {
          $sampleReceivedAtHubOn = explode(" ", $_POST['sampleReceivedAtHubOn']);
          $_POST['sampleReceivedAtHubOn'] = $general->dateFormat($sampleReceivedAtHubOn[0]) . " " . $sampleReceivedAtHubOn[1];
     } else {
          $_POST['sampleReceivedAtHubOn'] = NULL;
     }

     if (isset($_POST['approvedOnDateTime']) && trim($_POST['approvedOnDateTime']) != "") {
          $approvedOnDateTime = explode(" ", $_POST['approvedOnDateTime']);
          $_POST['approvedOnDateTime'] = $general->dateFormat($approvedOnDateTime[0]) . " " . $approvedOnDateTime[1];
     } else {
          $_POST['approvedOnDateTime'] = NULL;
     }

     if (isset($_POST['sampleTestingDateAtLab']) && trim($_POST['sampleTestingDateAtLab']) != "") {
          $sampleTestingDateAtLab = explode(" ", $_POST['sampleTestingDateAtLab']);
          $_POST['sampleTestingDateAtLab'] = $general->dateFormat($sampleTestingDateAtLab[0]) . " " . $sampleTestingDateAtLab[1];
     } else {
          $_POST['sampleTestingDateAtLab'] = NULL;
     }
     if (isset($_POST['resultDispatchedOn']) && trim($_POST['resultDispatchedOn']) != "") {
          $resultDispatchedOn = explode(" ", $_POST['resultDispatchedOn']);
          $_POST['resultDispatchedOn'] = $general->dateFormat($resultDispatchedOn[0]) . " " . $resultDispatchedOn[1];
     } else {
          $_POST['resultDispatchedOn'] = NULL;
     }

     if (isset($_POST['newRejectionReason']) && trim($_POST['newRejectionReason']) != "") {
          $rejectionReasonQuery = "SELECT rejection_reason_id FROM r_vl_sample_rejection_reasons where rejection_reason_name='" . $_POST['newRejectionReason'] . "' OR rejection_reason_name='" . strtolower($_POST['newRejectionReason']) . "' OR rejection_reason_name='" . ucfirst(strtolower($_POST['newRejectionReason'])) . "'";
          $rejectionResult = $db->rawQuery($rejectionReasonQuery);
          if (!isset($rejectionResult[0]['rejection_reason_id'])) {
               $data = array(
                    'rejection_reason_name' => $_POST['newRejectionReason'],
                    'rejection_type' => 'general',
                    'rejection_reason_status' => 'active',
                    'updated_datetime' => $general->getDateTime(),
               );
               $id = $db->insert('r_vl_sample_rejection_reasons', $data);
               $_POST['rejectionReason'] = $id;
          } else {
               $_POST['rejectionReason'] = $rejectionResult[0]['rejection_reason_id'];
          }
     }

     $isRejection = false;
     if (isset($_POST['noResult']) && $_POST['noResult'] == 'yes') {
          $vl_result_category = 'rejected';
          $isRejection = true;
          $_POST['vlResult'] = '';
          $_POST['vlLog'] = '';
     }

     if (isset($_POST['tnd']) && $_POST['tnd'] == 'yes' && $isRejection == false) {
          $_POST['vlResult'] = 'Target Not Detected';
          $_POST['vlLog'] = '';
     }
     if (isset($_POST['bdl']) && $_POST['bdl'] == 'yes' && $isRejection == false) {
          $_POST['vlResult'] = 'Below Detection Level';
          $_POST['vlLog'] = '';
     }

     $_POST['result'] = '';
     if (isset($_POST['vlResult']) && trim($_POST['vlResult']) != '') {
          $_POST['result'] = $_POST['vlResult'];
     } else if ($_POST['vlLog'] != '') {
          $_POST['result'] = $_POST['vlLog'];
     }
     $reasonForChanges = '';
     $allChange = array();
     if (isset($_POST['reasonForResultChangesHistory']) && $_POST['reasonForResultChangesHistory'] != '') {
          $allChange = json_decode(base64_decode($_POST['reasonForResultChangesHistory']), true);
     }
     if (isset($_POST['reasonForResultChanges']) && trim($_POST['reasonForResultChanges']) != '') {
          $allChange[] = array(
               'usr' => $_SESSION['userId'],
               'msg' => $_POST['reasonForResultChanges'],
               'dtime' => $general->getDateTime()
          );
     }
     if (count($allChange) > 0) {
          $reasonForChanges = json_encode($allChange);
     }
     //set vl test reason
     if (isset($_POST['stViralTesting']) && trim($_POST['stViralTesting']) != "") {
          $reasonQuery = "SELECT test_reason_id FROM r_vl_test_reasons where test_reason_name='" . $_POST['stViralTesting'] . "'";
          $reasonResult = $db->rawQuery($reasonQuery);
          if (isset($reasonResult[0]['test_reason_id']) && $reasonResult[0]['test_reason_id'] != '') {
               $_POST['stViralTesting'] = $reasonResult[0]['test_reason_id'];
          } else {
               $data = array(
                    'test_reason_name' => $_POST['stViralTesting'],
                    'test_reason_status' => 'active'
               );
               $id = $db->insert('r_vl_test_reasons', $data);
               $_POST['stViralTesting'] = $id;
          }
     }

     if (isset($_POST['approvedBy']) && trim($_POST['approvedBy']) != '') {
          $vlObj = new \Vlsm\Models\Vl($db);
        $vl_result_category = $vlObj->vlResultCategory($_POST['vlResult']);
      }

     $vldata = array(
          'vlsm_instance_id' => $instanceId,
          'sample_reordered' => (isset($_POST['sampleReordered']) && $_POST['sampleReordered'] != '') ? $_POST['sampleReordered'] :  'no',
          //'sample_code'=>(isset($_POST['sampleCode']) && $_POST['sampleCode']!='') ? $_POST['sampleCode'] :  NULL,
          'facility_id' => (isset($_POST['fName']) && $_POST['fName'] != '') ? $_POST['fName'] :  NULL,
          'sample_collection_date' => $_POST['sampleCollectionDate'],
          'patient_first_name' => (isset($_POST['patientFirstName']) && $_POST['patientFirstName'] != '') ? $_POST['patientFirstName'] :  NULL,
          'patient_gender' => (isset($_POST['gender']) && $_POST['gender'] != '') ? $_POST['gender'] :  NULL,
          'patient_dob' => $_POST['dob'],
          'patient_age_in_years' => (isset($_POST['ageInYears']) && $_POST['ageInYears'] != '') ? $_POST['ageInYears'] :  NULL,
          'patient_age_in_months' => (isset($_POST['ageInMonths']) && $_POST['ageInMonths'] != '') ? $_POST['ageInMonths'] :  NULL,
          'is_patient_pregnant' => (isset($_POST['patientPregnant']) && $_POST['patientPregnant'] != '') ? $_POST['patientPregnant'] :  NULL,
          'is_patient_breastfeeding' => (isset($_POST['breastfeeding']) && $_POST['breastfeeding'] != '') ? $_POST['breastfeeding'] :  NULL,
          'patient_art_no' => (isset($_POST['artNo']) && $_POST['artNo'] != '') ? $_POST['artNo'] :  NULL,
          'treatment_initiated_date' => $_POST['dateOfArtInitiation'],

          'current_regimen' => (isset($_POST['artRegimen']) && $_POST['artRegimen'] != '') ? $_POST['artRegimen'] :  NULL,
          'date_of_initiation_of_current_regimen' => $_POST['regimenInitiatedOn'],
          'patient_mobile_number' => (isset($_POST['patientPhoneNumber']) && $_POST['patientPhoneNumber'] != '') ? $_POST['patientPhoneNumber'] :  NULL,
          'consent_to_receive_sms' => (isset($_POST['receiveSms']) && $_POST['receiveSms'] != '') ? $_POST['receiveSms'] :  NULL,
          'sample_type' => (isset($_POST['specimenType']) && $_POST['specimenType'] != '') ? $_POST['specimenType'] :  NULL,
          'arv_adherance_percentage' => (isset($_POST['arvAdherence']) && $_POST['arvAdherence'] != '') ? $_POST['arvAdherence'] :  NULL,
          'reason_for_vl_testing' => (isset($_POST['stViralTesting'])) ? $_POST['stViralTesting'] : NULL,
          'last_vl_date_routine' => (isset($_POST['rmTestingLastVLDate']) && $_POST['rmTestingLastVLDate'] != '') ? $general->dateFormat($_POST['rmTestingLastVLDate']) :  NULL,
          'last_vl_result_routine' => (isset($_POST['rmTestingVlValue']) && $_POST['rmTestingVlValue'] != '') ? $_POST['rmTestingVlValue'] :  NULL,
          'last_vl_date_failure_ac' => (isset($_POST['repeatTestingLastVLDate']) && $_POST['repeatTestingLastVLDate'] != '') ? $general->dateFormat($_POST['repeatTestingLastVLDate']) :  NULL,
          'last_vl_result_failure_ac' => (isset($_POST['repeatTestingVlValue']) && $_POST['repeatTestingVlValue'] != '') ? $_POST['repeatTestingVlValue'] :  NULL,
          'last_vl_date_failure' => (isset($_POST['suspendTreatmentLastVLDate']) && $_POST['suspendTreatmentLastVLDate'] != '') ? $general->dateFormat($_POST['suspendTreatmentLastVLDate']) :  NULL,
          'last_vl_result_failure' => (isset($_POST['suspendTreatmentVlValue']) && $_POST['suspendTreatmentVlValue'] != '') ? $_POST['suspendTreatmentVlValue'] :  NULL,
          'request_clinician_name' => (isset($_POST['reqClinician']) && $_POST['reqClinician'] != '') ? $_POST['reqClinician'] :  NULL,
          'request_clinician_phone_number' => (isset($_POST['reqClinicianPhoneNumber']) && $_POST['reqClinicianPhoneNumber'] != '') ? $_POST['reqClinicianPhoneNumber'] :  NULL,
          'test_requested_on' => (isset($_POST['requestDate']) && $_POST['requestDate'] != '') ? $general->dateFormat($_POST['requestDate']) :  NULL,
          'vl_focal_person' => (isset($_POST['vlFocalPerson']) && $_POST['vlFocalPerson'] != '') ? $_POST['vlFocalPerson'] :  NULL,
          'vl_focal_person_phone_number' => (isset($_POST['vlFocalPersonPhoneNumber']) && $_POST['vlFocalPersonPhoneNumber'] != '') ? $_POST['vlFocalPersonPhoneNumber'] :  NULL,
          'lab_id' => (isset($_POST['labId']) && $_POST['labId'] != '') ? $_POST['labId'] :  NULL,
          'vl_test_platform' => $testingPlatform,
          //'test_methods'=>(isset($_POST['testMethods']) && $_POST['testMethods']!='') ? $_POST['testMethods'] :  NULL,
          'sample_received_at_hub_datetime' => $_POST['sampleReceivedAtHubOn'],
          'sample_received_at_vl_lab_datetime' => $_POST['sampleReceivedDate'],
          'sample_tested_datetime' => $_POST['sampleTestingDateAtLab'],
          'result_dispatched_datetime' => $_POST['resultDispatchedOn'],
          'is_sample_rejected' => (isset($_POST['noResult']) && $_POST['noResult'] != '') ? $_POST['noResult'] :  NULL,
          'reason_for_sample_rejection' => (isset($_POST['rejectionReason']) && $_POST['rejectionReason'] != '') ? $_POST['rejectionReason'] :  NULL,
          'result_value_absolute' => (isset($_POST['vlResult']) && $_POST['vlResult'] != '' && ($_POST['vlResult'] != 'Target Not Detected' && $_POST['vlResult'] != 'Below Detection Level')) ? $_POST['vlResult'] :  NULL,
          'result_value_text' => NULL,
          'result_value_absolute_decimal' => (isset($_POST['vlResult']) && $_POST['vlResult'] != '' && ($_POST['vlResult'] != 'Target Not Detected' && $_POST['vlResult'] != 'Below Detection Level')) ? number_format((float)$_POST['vlResult'], 2, '.', '') :  NULL,
          'result' => (isset($_POST['result']) && $_POST['result'] != '') ? $_POST['result'] :  NULL,
          'result_value_log' => (isset($_POST['vlLog']) && $_POST['vlLog'] != '') ? $_POST['vlLog'] :  NULL,
          'tested_by' => (isset($_POST['testedBy']) && $_POST['testedBy'] != '') ? $_POST['testedBy'] :  NULL,
          'result_approved_by' => (isset($_POST['approvedBy']) && $_POST['approvedBy'] != '') ? $_POST['approvedBy'] :  NULL,
          'result_approved_datetime' => (isset($_POST['approvedBy']) && $_POST['approvedBy'] != '') ? $_POST['approvedOnDateTime'] :  NULL,
          'approver_comments' => (isset($_POST['labComments']) && trim($_POST['labComments']) != '') ? trim($_POST['labComments']) :  NULL,
          'result_status' => (isset($_POST['status']) && $_POST['status'] != '') ? $_POST['status'] :  NULL,
          'funding_source' => (isset($_POST['fundingSource']) && trim($_POST['fundingSource']) != '') ? base64_decode($_POST['fundingSource']) : NULL,
          'implementing_partner' => (isset($_POST['implementingPartner']) && trim($_POST['implementingPartner']) != '') ? base64_decode($_POST['implementingPartner']) : NULL,
          'reason_for_vl_result_changes' => $reasonForChanges,
          // 'last_modified_by' => $_SESSION['userId'],
          'last_modified_datetime' => $general->getDateTime(),
          'manual_result_entry' => 'yes',
          'data_sync' => 0,
          'vl_result_category' => $vl_result_category
     );
     $lock = $general->getGlobalConfig('lock_approved_vl_samples');
     if($lock == 'yes' && $_POST['status'] == 7){
          $vldata['locked'] = 'yes';
     }
     if(isset($_POST['api']) && $_POST['api'] = "yes")
     {

     }
	else
		$vldata['last_modified_by'] =  $_SESSION['userId'];
     if ($sarr['user_type'] == 'remoteuser') {
          $vldata['remote_sample_code'] = (isset($_POST['sampleCode']) && $_POST['sampleCode'] != '') ? $_POST['sampleCode'] :  NULL;
     } else if ($_POST['sampleCodeCol'] != '') {
          $vldata['sample_code'] = (isset($_POST['sampleCodeCol']) && $_POST['sampleCodeCol'] != '') ? $_POST['sampleCodeCol'] :  NULL;
     }

     $vldata['patient_first_name'] = $general->crypto('encrypt', $_POST['patientFirstName'], $vldata['patient_art_no']);


     $db = $db->where('vl_sample_id', $_POST['vlSampleId']);
     $id = $db->update($tableName, $vldata);
     if(isset($_POST['api']) && $_POST['api'] = "yes")
	{
		$payload = array(
			        'status' => 'success',
			        'timestamp' => time(),
			        'message' => 'Successfully updated.'
			    );
			   
			
			    http_response_code(200);
			    echo json_encode($payload);
			    exit(0);
	}
	else
	{
          if ($id > 0) {
               $_SESSION['alertMsg'] = "VL request updated successfully";
               //Add event log

               $eventType = 'update-vl-request-sudan';
               $action = ucwords($_SESSION['userName']) . ' updated a request data with the sample code ' . $_POST['sampleCode'];
               $resource = 'vl-request-ss';

               $general->activityLog($eventType, $action, $resource);

               //   $data=array(
               //        'event_type'=>$eventType,
               //        'action'=>$action,
               //        'resource'=>$resource,
               //        'date_time'=>$general->getDateTime()
               //   );
               //   $db->insert($tableName1,$data);

          } else {
               $_SESSION['alertMsg'] = "Please try again later";
          }
          header("location:vlRequest.php");
     }
} catch (Exception $exc) {
     error_log($exc->getMessage());
     error_log($exc->getTraceAsString());
}
