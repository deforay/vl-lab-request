<?php
#require_once('../../startup.php');  


$general = new \Vlsm\Models\General($db);
$artNo = $_GET['artNo'];
//global config
$cQuery = "SELECT * FROM global_config";
$cResult = $db->query($cQuery);
$arr = array();
// now we create an associative array so that we can easily create view variables
for ($i = 0; $i < sizeof($cResult); $i++) {
  $arr[$cResult[$i]['name']] = $cResult[$i]['value'];
}
$pQuery = "SELECT * FROM form_covid19 as vl inner join facility_details as fd ON fd.facility_id=vl.facility_id  Left JOIN province_details as pd ON fd.facility_state=pd.province_name where vlsm_country_id='" . $arr['vl_form'] . "' AND (patient_id like '%" . $artNo . "%' OR patient_name like '%" . $artNo . "%' OR patient_surname like '%" . $artNo . "%' OR patient_phone_number like '%" . $artNo . "%')";
$pResult = $db->rawQuery($pQuery);
// print_r($pResult);die;
?>
<link rel="stylesheet" media="all" type="text/css" href="/assets/css/jquery-ui.1.11.0.css" />
<!-- Bootstrap 3.3.6 -->
<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="/assets/css/font-awesome.min.4.5.0.css">
<!-- DataTables -->
<link rel="stylesheet" href="/assets/plugins/datatables/dataTables.bootstrap.css">
<link href="/assets/css/deforayModal.css" rel="stylesheet" />
<style>
  .content-wrapper {
    padding: 2%;
  }

  .center {
    text-align: center;
  }

  body {
    overflow-x: hidden;
    /*overflow-y: hidden;*/
  }

  td {
    font-size: 13px;
    font-weight: 500;
  }

  th {
    font-size: 15px;
  }
</style>
<script type="text/javascript" src="/assets/js/jquery.min.js"></script>
<script type="text/javascript" src="/assets/js/jquery-ui.1.11.0.js"></script>
<script src="/assets/js/deforayModal.js"></script>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h4 class="pull-left bg-primary" style="width:100%;padding:8px;font-weight:normal;">Results matching your search - <?php echo $artNo; ?></h4>
  </section>
  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <!-- /.box-header -->
          <div class="box-body">
            <table id="patientModalDataTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="width:10%;">Select</th>
                  <th>ART Number</th>
                  <th>Patient Name</th>
                  <th>Age</th>
                  <th>Gender</th>
                  <th>Facility</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $artNoList = array();
                foreach ($pResult as $patient) {
                  $value = $patient['patient_id'] . strtolower($patient['patient_name']) . strtolower($patient['patient_surname']) . $patient['patient_age_in_years'] . strtolower($patient['patient_gender']) . strtolower($patient['facility_name']);
                  if (!in_array($value, $artNoList)) {
                    $artNoList[] = $value;
                    $patientDetails = $patient['patient_name'] . "##" . $patient['patient_surname'] . "##" . $patient['patient_gender'] . "##" . $general->humanDateFormat($patient['patient_dob']) . "##" . $patient['patient_age'] . "##" . $patient['patient_age'] . "##" . $patient['is_patient_pregnant'] . "##" . $patient['is_patient_breastfeeding'] . "##" . $patient['patient_phone_number'] .  "##" . $patient['patient_id'].  "##" . $patient['patient_passport_number'].  "##" . $patient['patient_address'].  "##" . $patient['patient_nationality'].  "##" . $patient['patient_city'].  "##" . $patient['patient_province'].  "##" . $patient['patient_district'].  "##" . $patient['province_code'].  "##" . $patient['province_id'];
                ?>
                    <tr>
                      <td><input type="radio" id="patient<?php echo $patient['vl_sample_id']; ?>" name="patient" value="<?php echo $patientDetails; ?>" onclick="getPatientDetails(this.value);"></td>
                      <td><?php echo $patient['patient_id']; ?></td>
                      <td><?php echo ucfirst($patient['patient_name']) . " " . $patient['patient_surname']; ?></td>
                      <td><?php echo $patient['patient_age']; ?></td>
                      <td><?php echo ucwords(str_replace("_", " ", $patient['patient_gender'])); ?></td>
                      <td><?php echo ucwords($patient['facility_name']); ?></td>
                    </tr>
                <?php
                  }
                }
                ?>
              </tbody>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<div id="dDiv" class="dialog">
  <div style="text-align:center"><span onclick="closeModal();" style="float:right;clear:both;" class="closeModal"></span></div>
  <iframe id="dFrame" src="" style="border:none;" scrolling="yes" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0">some problem</iframe>
</div>
<!-- Bootstrap 3.3.6 -->
<script src="/assets/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="/assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="/assets/plugins/datatables/dataTables.bootstrap.min.js"></script>
<script>
  $(document).ready(function() {
    $('#patientModalDataTable').DataTable({
      "aaSorting": [1, 'asc']
    });
  });

  function getPatientDetails(pDetails) {
    parent.closeModal();
    window.parent.setPatientDetails(pDetails);
  }
</script>