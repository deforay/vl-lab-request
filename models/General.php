<?php

/**
 * General functions
 *
 * @author Amit
 */

class General
{

    protected $db = null;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    public static function generateRandomString($length = 8, $seeds = 'alphanum')
    {
        // Possible seeds
        $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
        $seedings['numeric'] = '0123456789';
        $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
        $seedings['hexidec'] = '0123456789abcdef';

        // Choose seed
        if (isset($seedings[$seeds])) {
            $seeds = $seedings[$seeds];
        }

        // Seed generator
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float) $sec + ((float) $usec * 100000);
        mt_srand($seed);

        // Generate
        $str = '';
        $seeds_count = strlen($seeds);

        for ($i = 0; $length > $i; $i++) {
            $str .= $seeds{
                mt_rand(0, $seeds_count - 1)};
        }

        return $str;
    }

    public function generateUserID()
    {
        $idOne = $this->generateRandomString(8);
        $idTwo = $this->generateRandomString(4);
        $idThree = $this->generateRandomString(4);
        $idFour = $this->generateRandomString(4);
        $idFive = $this->generateRandomString(12);
        return $idOne . "-" . $idTwo . "-" . $idThree . "-" . $idFour . "-" . $idFive;
    }

    /**
     * Used to format date from dd-mmm-yyyy to yyyy-mm-dd for storing in database
     *
     */
    public function dateFormat($date)
    {
        $date = trim($date);
        if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
            return "0000-00-00";
        } else {
            $dateArray = explode('-', $date);
            if (sizeof($dateArray) == 0) {
                return;
            }
            $newDate = $dateArray[2] . "-";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = 1;
            $mon += array_search(ucfirst($dateArray[1]), $monthsArray);

            if (strlen($mon) == 1) {
                $mon = "0" . $mon;
            }
            return $newDate .= $mon . "-" . $dateArray[0];
        }
    }

    public function humanDateFormat($date)
    {
        $date = trim($date);
        if ($date == null || $date == "" || $date == "0000-00-00" || substr($date, 0, strlen("0000-00-00")) === "0000-00-00") {
            return "";
        } else {

            $dateTimeArray = explode(' ', $date);

            $dateArray = explode('-', $dateTimeArray[0]);
            $newDate = $dateArray[2] . "-";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = $monthsArray[$dateArray[1] - 1];

            $newDate .= $mon . "-" . $dateArray[0];

            if (isset($dateTimeArray[1]) && $dateTimeArray[1] != '') {
                $newDate .= " " . $dateTimeArray[1];
            }

            return $newDate;
        }
    }

    public function getDbDateFormat($date)
    {

        if ($date == null || $date == "" || $date == "0000-00-00" || substr($date, 0, strlen("0000-00-00")) === "0000-00-00") {
            return "";
        } else {

            $dateTimeArray = explode(' ', $date);

            $dateArray = explode('-', $dateTimeArray[0]);

            $newDate = new Zend_date(array('year' => $dateArray[0], 'month' => $dateArray[1], 'day' => $dateArray[2]));

            if (isset($dateTimeArray[1]) && $dateTimeArray[1] != '') {
                $newDate .= " " . $dateTimeArray[1];
            }

            return $newDate;
        }
    }

    public static function getDateTime()
    {
        $date = new DateTime(date('Y-m-d H:i:s'));
        return $date->format('Y-m-d H:i:s');
    }

    public function removeDirectory($dirname)
    {
        // Sanity check
        if (!file_exists($dirname)) {
            return false;
        }

        // Simple delete for a file
        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        // Loop through the folder
        $dir = dir($dirname);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse
            $this->removeDirectory($dirname . DIRECTORY_SEPARATOR . $entry);
        }

        // Clean up
        $dir->close();
        return rmdir($dirname);
    }

    // get data from the system_config table from database
    public function getSystemConfig($name = null)
    {
        if ($this->db == null) {
            return false;
        }

        if ($name == null) {
            $systemConfigQuery = "SELECT * from system_config";
        } else {
            $systemConfigQuery = "SELECT * from system_config WHERE `name` = '$name'";
        }

        $systemConfigResult = $this->db->query($systemConfigQuery);
        $sarr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
            $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
        }

        if ($name == null) {
            return $sarr;
        } else {
            if (isset($sarr[$name])) {
                return $sarr[$name];
            } else {
                return null;
            }
        }
    }

    // get data from the global_config table from database
    public function getGlobalConfig($name = null)
    {

        if ($this->db == null) {
            return false;
        }

        if ($name == null) {
            $globalConfigQuery = "SELECT * from global_config";
        } else {
            $globalConfigQuery = "SELECT * from global_config WHERE `name` = '$name'";
        }

        $globalConfigResult = $this->db->query($globalConfigQuery);
        $garr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < sizeof($globalConfigResult); $i++) {
            $garr[$globalConfigResult[$i]['name']] = $globalConfigResult[$i]['value'];
        }

        if ($name == null) {
            return $garr;
        } else {
            if (isset($garr[$name])) {
                return $garr[$name];
            } else {
                return null;
            }
        }
    }

    public function fetchDataFromTable($tableName = null, $condition = null, $fieldName = null)
    {
        if ($this->db == null || $tableName == null) {
            return false;
        }

        $fieldName = ($fieldName != null) ? $fieldName : '*';
        if ($condition == null) {
            $configQuery = "SELECT $fieldName from $tableName";
        } else {
            $configQuery = "SELECT $fieldName from $tableName WHERE $condition";
        }
        $configResult = $this->db->query($configQuery);
        return $configResult;
    }

    // checking if the provided field list has any empty or null values
    public function checkMandatoryFields($field)
    {
        foreach ($field as $chkField) {
            if (empty(trim($chkField))) {
                return true;
            }
        }

        return false;
    }

    public function crypto($action, $inputString, $secretIv)
    {

        return $inputString;

        if (empty($inputString)) {
            return "";
        }

        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'rXBCNkAzkHXGBKEReqrTfPhGDqhzxgDRQ7Q0XqN6BVvuJjh1OBVvuHXGBKEReqrTfPhGDqhzxgDJjh1OB4QcIGAGaml';

        // hash
        $key = hash('sha256', $secret_key);

        if (empty($secretIv)) {
            $secretIv = 'sd893urijsdf8w9eurj';
        }
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secretIv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($inputString, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($inputString), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }

    public function activityLog($eventType, $action, $resource)
    {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        $data = array(
            'event_type' => $eventType,
            'action' => $action,
            'resource' => $resource,
            'date_time' => $this->getDateTime(),
            'ip_address' => $ipaddress,
        );

        $this->db->insert('activity_log', $data);
    }

    public function resultImportStats($numberOfResults, $importMode, $importedBy)
    {

        $data = array(
            'no_of_results_imported' => $numberOfResults,
            'imported_on' => $this->getDateTime(),
            'import_mode' => $importMode,
            'imported_by' => $importedBy,
        );

        $this->db->insert('result_import_stats', $data);
    }

    public function getLowVLResultTextFromImportConfigs($machineFile = null)
    {
        if ($this->db == null) {
            return false;
        }

        if ($machineFile == null) {
            $importConfigQuery = "SELECT low_vl_result_text from import_config";
        } else {
            $importConfigQuery = "SELECT low_vl_result_text from import_config WHERE `import_machine_file_name` = '$machineFile'";
        }

        $importConfigResult = $this->db->query($importConfigQuery);
        $lowVlResults = array();
        foreach ($importConfigResult as $row) {
            if ($row['low_vl_result_text'] != "") {
                $lowVlResults[] = $row['low_vl_result_text'];
            }
        }

        return implode(", ", $lowVlResults);
    }

    public function getFacilitiesByUser($userId = null)
    {

        $fQuery = "SELECT * FROM facility_details where status='active'";

        $facilityWhereCondition = '';

        if (!empty($userId)) {
            $userfacilityMapQuery = "SELECT GROUP_CONCAT(DISTINCT `facility_id` ORDER BY `facility_id` SEPARATOR ',') as `facility_id` FROM vl_user_facility_map WHERE user_id='" . $userId . "'";
            $userfacilityMapresult = $this->db->rawQuery($userfacilityMapQuery);
            if ($userfacilityMapresult[0]['facility_id'] != null && $userfacilityMapresult[0]['facility_id'] != '') {
                $facilityWhereCondition = " AND facility_id IN (" . $userfacilityMapresult[0]['facility_id'] . ") ";
            }
        }

        return $this->db->rawQuery($fQuery . $facilityWhereCondition . " ORDER BY facility_name ASC");
    }

    public function generateEIDSampleCode($province,$sampleCollectionDate, $sampleFrom = null, $autoTyp = null, $provinceId = '', $provinceCode = '')
    {
        //global config
        $configQuery = "SELECT * from global_config";
        $configResult = $this->db->query($configQuery);
        $arr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < sizeof($configResult); $i++) {
            $arr[$configResult[$i]['name']] = $configResult[$i]['value'];
        }
        //system config
        $systemConfigQuery = "SELECT * from system_config";
        $systemConfigResult = $this->db->query($systemConfigQuery);
        $sarr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
            $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
        }
        $rKey = '';
        $sampleCodeKey = 'sample_code_key';
        $sampleCode = 'sample_code';
        if ($sarr['user_type'] == 'remoteuser') {
            $rKey = 'R';
            $sampleCodeKey = 'remote_sample_code_key';
            $sampleCode = 'remote_sample_code';
        }
        $sampleColDateTimeArray = explode(" ", $sampleCollectionDate);
        $sampleCollectionDate = $this->dateFormat($sampleColDateTimeArray[0]);
        $sampleColDateArray = explode("-", $sampleCollectionDate);
        $samColDate = substr($sampleColDateArray[0], -2);
        $start_date = $sampleColDateArray[0] . '-01-01';
        $end_date = $sampleColDateArray[0] . '-12-31';
        $mnthYr = $samColDate[0];

        if ($arr['eid_sample_code'] == 'MMYY') {
            $mnthYr = $sampleColDateArray[1] . $samColDate;
        } else if ($arr['eid_sample_code'] == 'YY') {
            $mnthYr = $samColDate;
        }

        $auto = $samColDate . $sampleColDateArray[1] . $sampleColDateArray[2];
        if (isset($sampleFrom) && $sampleFrom != null) {
            $svlQuery = 'SELECT ' . $sampleCodeKey . ' FROM eid_form as vl WHERE DATE(vl.sample_collection_date) >= "' . $start_date . '" AND DATE(vl.sample_collection_date) <= "' . $end_date . '" AND province_id=' . $provinceId . ' AND ' . $sampleCode . ' IS NOT NULL AND ' . $sampleCode . '!= "" ORDER BY ' . $sampleCodeKey . ' DESC LIMIT 1';

            $svlResult = $this->db->query($svlQuery);

            if (isset($svlResult[0][$sampleCodeKey]) && $svlResult[0][$sampleCodeKey] != '' && $svlResult[0][$sampleCodeKey] != null) {
                $maxId = $svlResult[0][$sampleCodeKey] + 1;
                $strparam = strlen($maxId);
                $zeros = (isset($autoTyp) && trim($autoTyp) == 'auto2') ? substr("0000", $strparam) : substr("000", $strparam);
                $maxId = $zeros . $maxId;
            } else {
                $maxId = (isset($autoTyp) && trim($autoTyp) == 'auto2') ? '0001' : '001';
            }
            $sCode = $rKey . "R" . date('y') . $provinceCode . "VL" . $maxId;
            $j = 1;
            do {
                $sQuery = "select sample_code from eid_form as vl where sample_code='" . $sCode . "'";
                $svlResult = $this->db->query($sQuery);
                if (!$svlResult) {
                    $maxId;
                    break;
                } else {
                    $x = $maxId + 1;
                    $strparam = strlen($x);
                    $zeros = (isset($autoTyp) && trim($autoTyp) == 'auto2') ? substr("0000", $strparam) : substr("000", $strparam);
                    $maxId = $zeros . $x;
                    $sCode = $rKey . "R" . date('y') . $provinceCode . "VL" . $maxId;
                }
            } while ($sCode);
        } else {
            $svlQuery = 'SELECT ' . $sampleCodeKey . ' FROM eid_form as vl WHERE DATE(vl.sample_collection_date) >= "' . $start_date . '" AND DATE(vl.sample_collection_date) <= "' . $end_date . '" AND ' . $sampleCode . '!="" ORDER BY ' . $sampleCodeKey . ' DESC LIMIT 1';

            $svlResult = $this->db->query($svlQuery);
            if (isset($svlResult[0][$sampleCodeKey]) && $svlResult[0][$sampleCodeKey] != '' && $svlResult[0][$sampleCodeKey] != null) {
                $maxId = $svlResult[0][$sampleCodeKey] + 1;
                $strparam = strlen($maxId);
                $zeros = (isset($autoTyp) && trim($autoTyp) == 'auto2') ? substr("0000", $strparam) : substr("000", $strparam);
                $maxId = $zeros . $maxId;
            } else {
                $maxId = (isset($autoTyp) && trim($autoTyp) == 'auto2') ? '0001' : '001';
            }
        }

        $sCodeKey = (array('maxId' => $maxId, 'mnthYr' => $mnthYr, 'auto' => $auto));

        if ($arr['eid_sample_code'] == 'auto') {
            $pNameVal = explode("##",$province);
            $sCode = $sCodeKey['auto'];
            $sCodeKey['sampleCode'] = $sampleCode = ($rKey . $pNameVal[1] . $sCode . $sCodeKey['maxId']);
            $sCodeKey['sampleCodeInText'] = $sampleCodeInText = ($rKey . $pNameVal[1] . $sCode . $sCodeKey['maxId']);
            $sCodeKey['sampleCodeFormat'] = $sampleCodeFormat = ($rKey . $pNameVal[1] . $sCode);
            $sCodeKey['sampleCodeKey'] = $sampleCodeKey = ($sCodeKey['maxId']);
        } else if ($arr['eid_sample_code'] == 'YY' || $arr['eid_sample_code'] == 'MMYY') {
            $sCodeKey['sampleCode'] = $sampleCode = $rKey . $arr['eid_sample_code_prefix'] . $sCodeKey['mnthYr'] . $sCodeKey['maxId'];
            $sCodeKey['sampleCodeInText'] = $sampleCodeInText = $rKey . $arr['eid_sample_code_prefix'] . $sCodeKey['mnthYr'] . $sCodeKey['maxId'];
            $sCodeKey['sampleCodeFormat'] = $sampleCodeFormat = $rKey . $arr['eid_sample_code_prefix'] . $sCodeKey['mnthYr'];
            $sCodeKey['sampleCodeKey'] = $sampleCodeKey = ($sCodeKey['maxId']);
        }        

        return json_encode($sCodeKey);
        
    }

    public function getEidResults()
    {
        $results = $this->db->rawQuery("SELECT * FROM r_eid_results where status='active' ORDER BY result_id DESC");
        $response = array();
        foreach ($results as $row) {
            $response[$row['result_id']] = $row['result'];
        }
        return $response;
    }
}
