<?php

session_unset(); // no need of session in json response

use Aranyasen\HL7\Message;
use Aranyasen\HL7\Segments\PID;
// PURPOSE : Fetch Results using serial_no field which is used to
// store the recency id from third party apps (for eg. in DRC)

// serial_no field in db was unused so we decided to use it to store recency id

ini_set('memory_limit', -1);
header('Content-Type: application/json');

$general = new \Vlsm\Models\General($db);
$userDb = new \Vlsm\Models\Users($db);
$user = null;

try {
    $hl7Msg = file_get_contents("php://input");
    if(isset($data['api_token']) && $data['api_token']!='')
    {
        $auth = $data['api_token'];
        $authToken = str_replace("Bearer ", "", $auth);
        // Check if API token exists
        $user = $userDb->getAuthToken($authToken);
    }

    // If authentication fails then do not proceed
    if (empty($user) || empty($user['user_id'])) {
        $response = array(
            'status' => 'failed',
            'timestamp' => time(),
            'error' => 'Bearer Token Invalid',
            'data' => array()
        );
        http_response_code(401);
        echo json_encode($response);
        exit(0);
    }
    /* Patient Information */
    if ($msg->hasSegment('PID')) {
        $pid = $msg->getSegmentByIndex(1);
        if ($pid->getField(8) == "F") {
            $gender = "female";
        } else if ($pid->getField(8) == "M") {
            $gender = "male";
        } else if ($pid->getField(8) == "O") {
            $gender = "other";
        }
        $name = $pid->getField(6);
        $data['patientId'] = $pid->getField(1);
        $data['firstName'] = $name[0];
        $data['lastName'] = $name[1];
        $data['patientDob'] = $pid->getField(7);
        $data['patientGender'] = $gender;
        $data['patientAddress'] = $pid->getField(11);
        $data['patientDistrict'] = $pid->getField(12);
        $data['patientPhoneNumber'] = $pid->getField(13);
        $data['patientNationality'] = $pid->getField(28);
    }
    /* Sample Information */
    if ($msg->hasSegment('SPM')) {
        $spm = $msg->getSegmentByIndex(2);
        $data['sampleCode'] = $spm->getField(2);
        // $data['sample_name'] = $spm->getField(4);
        $data['isSampleCollected'] = $spm->getField(12);
        $data['sampleCollectionDate'] = $spm->getField(17);
        $data['sampleReceivedDate'] = $spm->getField(18);
        $data['sampleRejectionReason'] = $spm->getField(21);
        $data['sampleCondition'] = $spm->getField(24);
        $data['testNumber'] = $spm->getField(26);
        // die($spm->getField(10));
        $facilityDetails = $facilityDb->getFacilityByName($spm->getField(10));

        if (!empty($facilityDetails[0]) && $facilityDetails[0] != "") {
            $data['facilityId'] = $facilityDetails[0]['facility_id'];
            $data['provinceCode'] = $facilityDetails[0]['province_code'];
        }
        if ($spm->getField(4) != "" && !empty($spm->getField(4))) {
            $c19Details = $c19Db->getCovid19SampleTypesByName($spm->getField(4));
            $data['specimenType'] = $c19Details[0]['sample_id'];
        }
    }
    /* OBR Section */
    if ($msg->hasSegment('OBR')) {
        $obr = $msg->getSegmentByIndex(3);
        $data['priorityStatus'] = $obr->getField(5);
        $data['sample_received_at_hub_datetime'] = $obr->getField(14);
        $data['sourceOfAlertPOE'] = $obr->getField(15);
        $data['result_status'] = $obr->getField(25);
        $data['result'] = $obr->getField(26);
    }

    /* Clinic Custom Fields Information Details */
    if ($msg->hasSegment('ZCI')) {
        $zci = $msg->getSegmentByIndex(4);
        $data['isSamplePostMortem'] = $zci->getField(1);
        $data['numberOfDaysSick'] = $zci->getField(2);
        $data['dateOfSymptomOnset'] = $zci->getField(3);
        $data['dateOfInitialConsultation'] = $zci->getField(4);
        $data['feverTemp'] = $zci->getField(5);
        $data['medicalHistory'] = $zci->getField(6);
        $data['recentHospitalization'] = $zci->getField(7);
        $data['temperatureMeasurementMethod'] = $zci->getField(8);
        $data['respiratoryRate'] = $zci->getField(9);
        $data['oxygenSaturation'] = $zci->getField(10);
        $data['otherDiseases'] = $zci->getField(11);
    }
    /* Patient Custom Fields Information Details */
    if ($msg->hasSegment('ZPI')) {
        $zpi = $msg->getSegmentByIndex(5);
        $data['patientOccupation'] = $zpi->getField(1);
        $data['patientCity'] = $zpi->getField(2);
        $data['patientProvince'] = $zpi->getField(3);
        $data['patientAge'] = $zpi->getField(4);
        $data['isPatientPregnant'] = $zpi->getField(5);
        $data['doesPatientSmoke'] = $zpi->getField(6);
        $data['patientLivesWithChildren'] = $zpi->getField(7);
        $data['patientCaresForChildren'] = $zpi->getField(8);
        $data['closeContacts'] = $zpi->getField(9);
        $data['contactWithConfirmedCase'] = $zpi->getField(10);
    }
    /* Airline Information Details */
    if ($msg->hasSegment('ZAI')) {
        $zai = $msg->getSegmentByIndex(6);
        $data['patientPassportNumber'] = $zai->getField(1);
        $data['airline'] = $zai->getField(2);
        $data['seatNo'] = $zai->getField(3);
        $data['arrivalDateTime'] = $zai->getField(4);
        $data['airportOfDeparture'] = $zai->getField(5);
        $data['transit'] = $zai->getField(6);
        $data['reasonOfVisit'] = $zai->getField(7);
        $data['hasRecentTravelHistory'] = $zai->getField(8);
        $data['countryName'] = $zai->getField(9);
        $data['returnDate'] = $zai->getField(10);
    }
    // echo $msg->toString(true);
    $data['api'] = "yes";
    $_POST = $data;
    // print_r(APPLICATION_PATH . '/vl/requests/addVlRequestHelper.php');die;
    include_once(APPLICATION_PATH . '/vl/requests/insertNewSample.php');
    include_once(APPLICATION_PATH . '/vl/requests/addVlRequestHelper.php');
} catch (Exception $exc) {

    http_response_code(500);
    $payload = array(
        'status' => 'failed',
        'timestamp' => time(),
        'error' => $exc->getMessage(),
        'data' => array()
    );


    echo json_encode($payload);

    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
    exit(0);
}
