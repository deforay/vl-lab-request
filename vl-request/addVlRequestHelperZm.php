<?php
session_start();
ob_start();
include('../includes/MysqliDb.php');
include('../General.php');
$general=new Deforay_Commons_General();
$tableName="vl_request_form";
$tableName1="activity_log";
try {
     $configQuery ="SELECT value FROM global_config where name='auto_approval'";
     $configResult = $db->rawQuery($configQuery);
     $status = 6;
     if(isset($configResult[0]['value']) && trim($configResult[0]['value']) == 'yes'){
          $status = 7;
     }
     //set lab no
     $start_date = date('Y-m-01');
     $end_date = date('Y-m-31');
     $labvlQuery='select MAX(lab_no) FROM vl_request_form as vl where vl.form_id="2" AND DATE(vl.created_on) >= "'.$start_date.'" AND DATE(vl.created_on) <= "'.$end_date.'"';
     $labvlResult = $db->rawQuery($labvlQuery);
     if($labvlResult[0]['MAX(lab_no)']!='' && $labvlResult[0]['MAX(lab_no)']!=NULL){
     $_POST['labNo'] = $labvlResult[0]['MAX(lab_no)']+1;
    }else{
     $_POST['labNo'] = '1';
    }
     
     //var_dump($_POST);die;
     if(isset($_POST['sampleCollectionDate']) && trim($_POST['sampleCollectionDate'])!=""){
          $sampleDate = explode(" ",$_POST['sampleCollectionDate']);
          $_POST['sampleCollectionDate']=$general->dateFormat($sampleDate[0])." ".$sampleDate[1];
     }
     
     if(isset($_POST['sampleReceivedDate']) && trim($_POST['sampleReceivedDate'])!=""){
          $sampleReceivedDate = explode(" ",$_POST['sampleReceivedDate']);
          $_POST['sampleReceivedDate']=$general->dateFormat($sampleReceivedDate[0])." ".$sampleReceivedDate[1];
     }
     
     if(isset($_POST['dob']) && trim($_POST['dob'])!=""){
          $_POST['dob']=$general->dateFormat($_POST['dob']); 
     }
     
     if(isset($_POST['dateOfArtInitiation']) && trim($_POST['dateOfArtInitiation'])!=""){
          $_POST['dateOfArtInitiation']=$general->dateFormat($_POST['dateOfArtInitiation']);  
     }
     
     if(isset($_POST['lastViralLoadTestDate']) && trim($_POST['lastViralLoadTestDate'])!=""){
          $_POST['lastViralLoadTestDate']=$general->dateFormat($_POST['lastViralLoadTestDate']);  
     }
     if(isset($_POST['sampleTestingDateAtLab']) && trim($_POST['sampleTestingDateAtLab'])!=""){
          $sampleTestingDateLab = explode(" ",$_POST['sampleTestingDateAtLab']);
          $_POST['sampleTestingDateAtLab']=$general->dateFormat($sampleTestingDateLab[0])." ".$sampleTestingDateLab[1];  
     }
    
     if(isset($_POST['newArtRegimen']) && trim($_POST['newArtRegimen'])!=""){
          $data=array(
            'art_code'=>$_POST['newArtRegimen'],
            'nation_identifier'=>'zmb'
          );
          
          $result=$db->insert('r_art_code_details',$data);
          $_POST['artRegimen'] = $_POST['newArtRegimen'];
     }
    
     if(!isset($_POST['patientPregnant']) || trim($_POST['patientPregnant'])==''){
        $_POST['patientPregnant']='';
     }
     if(!isset($_POST['breastfeeding']) || trim($_POST['breastfeeding'])==''){
        $_POST['breastfeeding']='';
     }
     if(!isset($_POST['receiveSms']) || trim($_POST['receiveSms'])==''){
        $_POST['receiveSms']='';
     }
     if(!isset($_POST['gender']) || trim($_POST['gender'])==''){
        $_POST['gender']='';
     }
     if(isset($_POST['gender']) && trim($_POST['gender'])=='male'){
          $_POST['patientPregnant']='';
          $_POST['breastfeeding']='';
     }
     $_POST['result'] = '';
     if($_POST['vlResult']!=''){
          $_POST['result'] = $_POST['vlResult'];
     }else if($_POST['vlLog']!=''){
          $_POST['result'] = $_POST['vlLog'];
     }
     if(!isset($_POST['approvedBy'])){
          $_POST['approvedBy'] = '';
     }
     if(!isset($_POST['noResult'])){
          $_POST['noResult'] = '';
          $_POST['rejectionReason'] = '';
     }
     if(!isset($_POST['patientPhoneNumber'])){
          $_POST['patientPhoneNumber'] = '';
     }
     if(!isset($_POST['sampleCodeFormat'])){
          $_POST['sampleCodeFormat'] = '';
          $_POST['sampleCodeKey'] = '';
     }
     $instanceId = '';
     if(isset($_SESSION['instanceId'])){
          $instanceId = $_SESSION['instanceId'];
     }
     if($_POST['testingPlatform']!=''){
          $platForm = explode("##",$_POST['testingPlatform']);
          $_POST['testingPlatform'] = $platForm[0];
     }
     //Sample type section
    if(!isset($_POST['specimenType']) || trim($_POST['specimenType'])==""){
       $_POST['specimenType'] = NULL;
    }
     $vldata=array(
          'urgency'=>$_POST['urgency'],
          'vl_instance_id'=>$instanceId,
          'sample_code_format'=>$_POST['sampleCodeFormat'],
          'sample_code_key'=>$_POST['sampleCodeKey'],
          'form_id'=>'2',
          'serial_no'=>$_POST['serialNo'],
          'sample_code'=>$_POST['serialNo'],
          'facility_id'=>$_POST['clinicName'],
          //'sample_code'=>$_POST['sampleCode'],
          'lab_contact_person'=>$_POST['clinicianName'],
          'sample_collection_date'=>$_POST['sampleCollectionDate'],
          'collected_by'=>$_POST['collectedBy'],
          'patient_name'=>$_POST['patientFname'],
          'surname'=>$_POST['surName'],
          'gender'=>$_POST['gender'],
          'patient_dob'=>$_POST['dob'],
          'age_in_yrs'=>$_POST['ageInYears'],
          'age_in_mnts'=>$_POST['ageInMonths'],
          'is_patient_pregnant'=>$_POST['patientPregnant'],
          'is_patient_breastfeeding'=>$_POST['breastfeeding'],
          'art_no'=>$_POST['patientArtNo'],
          'current_regimen'=>$_POST['artRegimen'],
          'date_of_initiation_of_current_regimen'=>$_POST['dateOfArtInitiation'],
          'patient_receive_sms'=>$_POST['receiveSms'],
          'patient_phone_number'=>$_POST['patientPhoneNumber'],
          'last_viral_load_date'=>$_POST['lastViralLoadTestDate'],
          'last_viral_load_result'=>$_POST['lastViralLoadResult'],
          'viral_load_log'=>$_POST['viralLoadLog'],
          'vl_test_reason'=>$_POST['vlTestReason'],
          //'drug_substitution'=>$_POST['drugSubstitution'],
          'lab_no'=>$_POST['labNo'],
          'lab_id'=>$_POST['labId'],
          'vl_test_platform'=>$_POST['testingPlatform'],
          'sample_id'=>$_POST['specimenType'],
          'lab_tested_date'=>$_POST['sampleTestingDateAtLab'],
          'sample_rejection_reason'=>$_POST['rejectionReason'],
          'absolute_value'=>$_POST['vlResult'],
          'result'=>$_POST['result'],
          'log_value'=>$_POST['vlLog'],
          'comments'=>$_POST['labComments'],
          'date_sample_received_at_testing_lab'=>$_POST['sampleReceivedDate'],
          'rejection'=>$_POST['noResult'],
          'result_reviewed_by'=>$_POST['reviewedBy'],
          'result_approved_by'=>$_POST['approvedBy'],
          'status'=>$status,
          'created_by'=>$_SESSION['userId'],
          'created_on'=>$general->getDateTime(),
          'modified_by'=>$_SESSION['userId'],
          'modified_on'=>$general->getDateTime(),
          'result_coming_from'=>'manual'
        );
         //print_r($vldata);die;
          $id=$db->insert($tableName,$vldata);
          if($id>0){
          $_SESSION['alertMsg']="VL request added successfully";
          //Add event log
          $eventType = 'add-vl-request-zm';
          $action = ucwords($_SESSION['userName']).' added a new request data with the sample code '.$_POST['serialNo'];
          $resource = 'vl-request-zm';
          $data=array(
          'event_type'=>$eventType,
          'action'=>$action,
          'resource'=>$resource,
          'date_time'=>$general->getDateTime()
          );
          $db->insert($tableName1,$data);
          if(isset($_POST['saveNext']) && $_POST['saveNext']=='next'){
                $_SESSION['treamentId'] = $id;
                $_SESSION['facilityId'] = $_POST['clinicName'];
                header("location:addVlRequest.php");
          }else{
                $_SESSION['treamentId'] = '';
                $_SESSION['facilityId'] = '';
                unset($_SESSION['treamentId']);
                unset($_SESSION['facilityId']);
                header("location:vlRequest.php");
          }
          }else{
               $_SESSION['alertMsg']="Please try again later";
          }
  
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}