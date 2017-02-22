<?php

try {
    
    $db->delete('temp_sample_report');
    //set session for controller track id in hold_sample_record table
    $cQuery  = "select MAX(import_batch_tracking) FROM hold_sample_report";
    $cResult = $db->query($cQuery);
    if ($cResult[0]['MAX(import_batch_tracking)'] != '') {
        $maxId = $cResult[0]['MAX(import_batch_tracking)'] + 1;
    } else {
        $maxId = 1;
    }
    $_SESSION['controllertrack'] = $maxId;
    
    $allowedExtensions = array(
        'xls',
        'xlsx',
        'csv'
    );
    $fileName          = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['resultFile']['name']);
    $fileName          = str_replace(" ", "-", $fileName);
    $ranNumber         = str_pad(rand(0, pow(10, 6) - 1), 6, '0', STR_PAD_LEFT);
    $extension         = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileName          = $ranNumber . "." . $extension;
    
    
    if (!file_exists('../temporary' . DIRECTORY_SEPARATOR . "import-result") && !is_dir('../temporary' . DIRECTORY_SEPARATOR . "import-result")) {
        mkdir('../temporary' . DIRECTORY_SEPARATOR . "import-result");
    }
    if (move_uploaded_file($_FILES['resultFile']['tmp_name'], '../temporary' . DIRECTORY_SEPARATOR . "import-result" . DIRECTORY_SEPARATOR . $fileName)) {
        //$file_info = new finfo(FILEINFO_MIME); // object oriented approach!
        //$mime_type = $file_info->buffer(file_get_contents('../temporary' . DIRECTORY_SEPARATOR . "import-result" . DIRECTORY_SEPARATOR . $fileName)); // e.g. gives "image/jpeg"

        $objPHPExcel = \PHPExcel_IOFactory::load('../temporary' . DIRECTORY_SEPARATOR . "import-result" . DIRECTORY_SEPARATOR . $fileName);
        $sheetData   = $objPHPExcel->getActiveSheet();
        
        $bquery    = "select MAX(batch_code_key) from batch_details";
        $bvlResult = $db->rawQuery($bquery);
        if ($bvlResult[0]['MAX(batch_code_key)'] != '' && $bvlResult[0]['MAX(batch_code_key)'] != NULL) {
            $maxBatchCodeKey = $bvlResult[0]['MAX(batch_code_key)'] + 1;
            $maxBatchCodeKey = "00" . $maxBatchCodeKey;
        } else {
            $maxBatchCodeKey = '001';
        }
        
        $newBatchCode = date('Ymd') . $maxBatchCodeKey;
        
        $sheetData   = $sheetData->toArray(null, true, true, true);
        $m           = 0;
        $skipTillRow = 2;
        
        $sampleIdCol   = "B";
        $resultCol     = "F";
        $txtValCol     = "G";
        $sampleTypeCol = "C";
        $batchCodeVal  = "";
        $flagCol       = "K";
        $testDateCol   = "L";
        
        foreach ($sheetData as $rowIndex => $row) {
            
            if ($rowIndex < $skipTillRow)
                continue;
            
            $sampleCode    = "";
            $batchCode     = "";
            $sampleType    = "";
            $absDecimalVal = "";
            $absVal        = "";
            $logVal        = "";
            $txtVal        = "";
            $resultFlag    = "";
            $testingDate   = "";
            
            
            $sampleCode = $row[$sampleIdCol];
            
            if (strpos($row[$resultCol], 'Log (Copies / mL)') !== false) {
                $logVal = str_replace("Log (Copies / mL)", "", $row[$resultCol]);
                $logVal = str_replace(",", ".", $logVal);
            } else if (strpos($row[$resultCol], 'Copies / mL') !== false) {
                $absVal = str_replace("Copies / mL", "", $row[$resultCol]);
                preg_match_all('!\d+!', $absVal, $absDecimalVal);
                $absVal = $absDecimalVal = implode("", $absDecimalVal[0]);
            } else {
                if ($row[$resultCol] == "" || $row[$resultCol] == null) {
                    $txtVal     = "Failed";
                    $resultFlag = $row[$flagCol];
                } else {
                    $txtVal     = $row[$flagCol];
                    $resultFlag = $row[$flagCol];
                    $absVal     = "";
                    $logVal     = "";
                }
            }
            
            $sampleType = $row[$sampleTypeCol];
            if ($sampleType == 'Patient') {
                $sampleType = 'S';
            }
            
            $batchCode = "";
            
            // Date time in the provided Abbott Sample file is in this format : 11/23/2016 2:22:35 PM
            $testingDate = DateTime::createFromFormat('m/d/Y g:i:s A', $row[$testDateCol])->format('Y-m-d H:i');
            
            if ($sampleCode == "")
                continue;
            
            if (!isset($infoFromFile[$sampleCode])) {
                $infoFromFile[$sampleCode] = array(
                    "sampleCode" => $sampleCode,
                    "logVal" => trim($logVal),
                    "txtVal" => $txtVal,
                    "resultFlag" => $resultFlag,
                    "testingDate" => $testingDate,
                    "sampleType" => $sampleType,
                    "batchCode" => $batchCode
                );
            } else {
                $infoFromFile[$sampleCode]['absVal']        = $absVal;
                $infoFromFile[$sampleCode]['absDecimalVal'] = $absDecimalVal;
            }
            
            $m++;
        }
        
        /*
         * OK, so the reason why we are putting the information into an array ($infoFromFile)
         * is because the Abbott data has same sample ID repeated in two rows, with one row
         * giving log and another giving abs value. So we create the $infoFromFile array to
         * ensure we get both log and abs value for the given sample
         */
        
        
        foreach ($infoFromFile as $sampleCode => $d) {
            $data = array(
                'lab_id' => base64_decode($_POST['labId']),
                'vl_test_platform' => $_POST['vltestPlatform'],
                'result_reviewed_by' => $_SESSION['userId'],
                'sample_code' => $d['sampleCode'],
                'log_value' => $d['logVal'],
                'sample_type' => $d['sampleType'],
                'absolute_value' => $d['absVal'],
                'text_value' => $d['txtVal'],
                'absolute_decimal_value' => $d['absDecimalVal'],
                'lab_tested_date' => $testingDate,
                'status' => '6',
                'file_name' => $fileName,
                'comments' => $d['resultFlag']
            );
            
            
            if ($d['absVal'] != "") {
                $data['result'] = $d['absVal'];
            } else if ($d['logVal'] != "") {
                $data['result'] = $d['logVal'];
            } else if ($d['txtVal'] != "") {
                $data['result'] = $d['txtVal'];
            } else {
                $data['result'] = "";
            }
            
            if ($batchCode == '') {
                $data['batch_code']     = $newBatchCode;
                $data['batch_code_key'] = $maxBatchCodeKey;
            } else {
                $data['batch_code'] = $batchCode;
            }
            
            $query    = "select facility_id,vl_sample_id,result,log_value,absolute_value,text_value,absolute_decimal_value from vl_request_form where sample_code='" . $sampleCode . "'";
            $vlResult = $db->rawQuery($query);
            if ($vlResult && $sampleCode != '') {
                if ($vlResult[0]['log_value'] != '' || $vlResult[0]['absolute_value'] != '' || $vlResult[0]['text_value'] != '' || $vlResult[0]['absolute_decimal_value'] != '') {
                    $data['sample_details'] = 'Result exists already';
                } else {
                    $data['status'] = '7';
                }
                $data['facility_id'] = $vlResult[0]['facility_id'];
            } else {
                $data['sample_details'] = 'New Sample';
            }
            //echo "<pre>";var_dump($data);echo "</pre>";continue;
            if ($sampleCode != '' || $batchCode != '' || $sampleType != '' || $logVal != '' || $absVal != '' || $absDecimalVal != '') {
                $id = $db->insert("temp_sample_report", $data);
            }
        }
    }
    
    $_SESSION['alertMsg'] = "Imported results successfully";
    //Add event log
    $eventType            = 'import';
    $action               = ucwords($_SESSION['userName']) . ' imported a new test result with the sample code ' . $sampleCode;
    $resource             = 'import-result';
    $data                 = array(
        'event_type' => $eventType,
        'action' => $action,
        'resource' => $resource,
        'date_time' => $general->getDateTime()
    );
    $db->insert("activity_log", $data);
    
    //new log for update in result
    $data = array(
        'user_id' => $_SESSION['userId'],
        'vl_sample_id' => $id,
        'updated_on' => $general->getDateTime()
    );
    $db->insert("log_result_updates", $data);
    
    header("location:../vl-print/vlResultUnApproval.php");
    
}
catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}