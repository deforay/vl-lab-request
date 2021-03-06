<?php
if (session_status() == PHP_SESSION_NONE) {
     session_start();
}
#require_once('../../startup.php');  

$general = new \Vlsm\Models\General($db);
$facilitiesDb = new \Vlsm\Models\Facilities($db);
$facilityMap = $facilitiesDb->getFacilityMap($_SESSION['userId']);

$formId = $general->getGlobalConfig('vl_form');

//system config
$systemConfigQuery = "SELECT * from system_config";
$systemConfigResult = $db->query($systemConfigQuery);
$sarr = array();
// now we create an associative array so that we can easily create view variables
for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
     $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
}
$general = new \Vlsm\Models\General($db);
$tableName = "vl_request_form";
$primaryKey = "vl_sample_id";


$sQuery = "SELECT DATE_FORMAT(DATE(vl.sample_tested_datetime), '%Y-%b') as monthrange, f.facility_id, f.facility_name, vl.is_sample_rejected,vl.sample_tested_datetime,vl.sample_collection_date, vl.vl_result_category, tl.suppressed_monthly_target,
SUM(CASE WHEN (sample_collection_date IS NOT NULL) THEN 1 ELSE 0 END) as totalCollected,
SUM(CASE WHEN (vl_result_category IS NOT NULL AND vl_result_category LIKE 'suppressed%') THEN 1 ELSE 0 END) as totalSuppressed,
SUM(IF(vl_result_category LIKE 'suppressed%', (((IF(vl_result_category LIKE 'suppressed%',1,0))/tl.suppressed_monthly_target) * 100), 0)) as supp_percent
 FROM testing_labs as tl INNER JOIN vl_request_form as vl ON vl.lab_id=tl.facility_id LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id  ";

$sWhere = $sWhere . ' where  vl.vlsm_country_id="' . $formId . '" AND vl.result_status!=9';
if (isset($_POST['facilityName']) && count($_POST['facilityName']) > 0) {
     $fac = $_POST['facilityName'];
     $out = '';
     for($s=0; $s < count($fac); $s++)
     {
          if($out)
          $out = $out.',"'.$fac[$s].'"';
          else
          $out = '("'.$fac[$s].'"';
     }
     $out = $out.')';
     if (isset($sWhere)) {
          $sWhere = $sWhere . ' AND vl.lab_id IN ' . $out . '';
     }//  else {
          //      $setWhr = 'where';
          //      $sWhere = ' where ' . $sWhere;
          //      $sWhere = $sWhere . ' vl.lab_id IN ' . $out . '';
          // }

     
}
if (!empty($facilityMap)) {
    $sWhere .= " AND vl.facility_id IN ($facilityMap) ";
}
$sWhere .= " AND tl.test_type = 'vl' " ;

$sQuery = $sQuery . ' ' . $sWhere. ' GROUP BY f.facility_id, YEAR(vl.sample_tested_datetime), MONTH(vl.sample_tested_datetime)';

// $_SESSION['vlSuppressedTargetReportQuery'] = $sQuery;
// die($sQuery);
$rResult = $db->rawQuery($sQuery);
// print_r($sQuery);die;

/* $res = array();
$totCnt = 0;
foreach ($rResult as $aRow) {
     $row = array();
     if(isset($res[$aRow['monthrange']]))
    {
        if( isset($res[$aRow['monthrange']][$aRow['facility_id']]))
        {
          
            $row['totalTested'] = $res[$aRow['monthrange']][$aRow['facility_id']]['totalTested'] + 1; 
            if(trim($aRow['vl_result_category'])  != NULL  && trim($aRow['vl_result_category']) == 'suppressed')
                $row['totalSuppressed'] = $res[$aRow['monthrange']][$aRow['facility_id']]['totalSuppressed'] + 1;
            $row['facility_name'] = ucwords($aRow['facility_name']);
            $row['monthrange'] = $aRow['monthrange'];
            $row['supp_percent'] = ($row['totalSuppressed']/$aRow['suppressed_monthly_target']) * 100;
            $row['suppressed_monthly_target'] = $aRow['suppressed_monthly_target'];
            // $row['totalCollected'] = $res[$aRow['monthrange']][$aRow['facility_id']]['totalCollected']  + 1;
            $res[$aRow['monthrange']][$aRow['facility_id']] = $row;
          // print_r(($row['totalTested']) * 100);die;
        }
        else
        {
            $row['totalTested'] = 1; 
            if(trim($aRow['vl_result_category'])  != NULL  && trim($aRow['vl_result_category']) == 'suppressed')
                $row['totalSuppressed'] =  1;
            else
                $row['totalSuppressed'] =  0;
        $row['facility_name'] = ucwords($aRow['facility_name']);
        $row['monthrange'] = $aRow['monthrange'];
        $row['supp_percent'] = ($row['totalSuppressed']/$aRow['suppressed_monthly_target']) * 100;
            $row['suppressed_monthly_target'] = $aRow['suppressed_monthly_target'];
                $res[$aRow['monthrange']][$aRow['facility_id']] = $row;
        }
    }
    else
          {
                $row['totalTested'] = 1; 
                if(trim($aRow['vl_result_category'])  != NULL  && trim($aRow['vl_result_category']) == 'suppressed')
                    $row['totalSuppressed'] =  1;
                else
                    $row['totalSuppressed'] =  0;
               $row['facility_name'] = ucwords($aRow['facility_name']);
               $row['monthrange'] = $aRow['monthrange'];
               $row['supp_percent'] = ($row['totalSuppressed']/$aRow['suppressed_monthly_target']) * 100;
                $row['suppressed_monthly_target'] = $aRow['suppressed_monthly_target'];
               // $row['totalCollected'] = $res[$aRow['monthrange']]['totalCollected']  + 1;
               $res[$aRow['monthrange']][$aRow['facility_id']] = $row;
          }
   
} */
// $_SESSION['vlSuppressedTargetReportResult'] = json_encode($res);
// echo json_encode($res);die;

foreach($rResult as $subRow)
{
    $res[$subRow['monthrange']][$subRow['facility_id']]['totalSuppressed']   = $subRow['totalSuppressed'];
    $res[$subRow['monthrange']][$subRow['facility_id']]['totalCollected']    = $subRow['totalCollected'];
    $res[$subRow['monthrange']][$subRow['facility_id']]['facility_name']     = $subRow['facility_name'];
    $res[$subRow['monthrange']][$subRow['facility_id']]['supp_percent']      = $subRow['supp_percent'];
}

ksort($res);
end($res);
if(isset($_POST['monthYear']) && $_POST['monthYear']!='')
{
    $monthYear = '01-'.$_POST['monthYear'];
    $mon = date('Y-M', strtotime($monthYear));
    $resArray = $res[$mon];
    // print_r($mon);
    // print_r($res);die;
}
else
{
    $monthYear = key($res);
    $resArray = end($res);
}

if(isset($_POST['targetType'])  && $_POST['targetType']!='')
{
    $returnVal = 0 ;
    foreach($rResult as $subRow)
    {
        /* foreach($row as $subRow)
        { */
            if($subRow['totalSuppressed'] < $subRow['suppressed_monthly_target'])
            {
                $returnVal = 1;
                echo $returnVal;die;
            }
        // }

    }
    echo $returnVal;die;
}
?>
<div class="col-xs-12 labAverageTatDiv">
    <div class="box">
        <div class="box-body">
            <div id="eidLabAverageTat" style="padding:15px 0px 5px 0px;float:left;width:100%;"></div>
        </div>
    </div>
</div>
<script>
$('#eidLabAverageTat').highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: 'VL Suppressed Testing Target'
            },
            exporting: {
                chartOptions: {
                    subtitle: {
                        text: 'VL Suppressed Testing Target',
                    }
                }
            },
            credits: {
                enabled: false
            },
            xAxis: {
               //  categories: ["21 Mar", "22 Mar", "23 Mar", "24 Mar", "25 Mar", "26 Mar", "27 Mar"]
                categories: [<?php
                                        echo "'" . $monthYear . "',";
                                ?>]
            },
            yAxis: {
                title: {
                    text: 'No of target in month %'
                },
                labels: {
                    formatter: function() {
                        return this.value;
                    }
                },
               
            },
          
        tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y:.1f} %</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                pointPadding: 0.2,
                borderWidth: 0
                }
            },
            series: [
            <?php  foreach ($resArray as $tRow) {  $color = sprintf("#%06x",rand(0,16777215));?>
            {
                name: '<?php echo  $tRow['facility_name']; ?>',
                   // data: [43934, 52503, 57177, 69658, 97031, 119931, 137133, 154175]
                   data: [
                        <?php
                            echo  $tRow['supp_percent'] ;
                        ?>
                   ],
                //    color:' <?php // echo  $color; ?>'
            },

        <?php  } ?>
        ]
        });
</script>