<?php
include('../header.php');
$tsQuery="SELECT * FROM testing_status";
$tsResult = $db->rawQuery($tsQuery);
$userQuery="SELECT * FROM user_details where status='active'";
$userResult = $db->rawQuery($userQuery);

//global config
$cSampleQuery="SELECT * FROM global_config";
$cSampleResult=$db->query($cSampleQuery);
$arr = array();
// now we create an associative array so that we can easily create view variables
for ($i = 0; $i < sizeof($cSampleResult); $i++) {
  $arr[$cSampleResult[$i]['name']] = $cSampleResult[$i]['value'];
}
?>
<style>
    .dataTables_wrapper{
      position: relative;
    clear: both;
    overflow-x: visible !important;
    overflow-y: visible !important;
    padding: 15px 0 !important;
    }
</style>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Imported Results</h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Test Request</li>
      </ol>
    </section>

     <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header with-border">
			  <div class="box-header with-border">
			  <!--<div class="col-md-4 col-sm-4">
				<select style="" class="form-control" id="status" name="status" title="Please select test status" >
				  <option value="">-- Select --</option>
				  <option value="7">Accepted</option>
				  <option value="1">Hold</option>
				  <option value="4">Rejected</option>
				</select>
				</div>
			  <div class="col-md-2 col-sm-2"><input type="button" onclick="submitTestStatus();" value="Update" class="btn btn-success btn-sm"></div>-->
			  <ul style="list-style: none;float: right;">
	    <li><i class="fa fa-square" aria-hidden="true" style="color: #e8000b"></i> - Unknown Sample</li>
	    <li><i class="fa fa-square" aria-hidden="true" style="color: #86c0c8"></i> - Existing Result</li>
	    <li><i class="fa fa-square" aria-hidden="true" style="color: #337ab7"></i> - Result for Sample</li>
	    <li><i class="fa fa-square" aria-hidden="true" style="color: #7d8388"></i> - Control</li>
	    </ul>
            </div>
	      <span><b style="color: #f03033;">Note:-</b>When you leave from this page, these temporary records will be deleted from the system.</span>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <table id="vlRequestDataTable" class="table table-bordered table-striped">
                <thead>
                <tr>
		  <!--<th style="width: 1%;"><input type="checkbox" id="checkTestsData" onclick="toggleAllVisible()"/></th>-->
		  <th style="width: 13%;">Form Serial No.</th>
		  <th style="width: 11%;">Sample Collection Date</th>
                  <th style="width: 18%;">Sample Test Date</th>
                  <th style="width: 18%;">Clinic Name</th>
                  <th style="width: 18%;">Reason</th>
                  <th style="width: 11%;">Sample Type</th>
                  <th style="width: 9%;">Result</th>
                  <th style="width: 9%;">Status</th>
                </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="5" class="dataTables_empty">Loading data from server</td>
		  </tr>
                </tbody>
              </table>
            </div>
	    <table class="table" cellpadding="1" cellspacing="3" style="margin-left:1%;margin-top:30px;width: 75%;">
	    <tr>
		  <input type="hidden" name="checkedTests" id="checkedTests"/>
		  <input type="hidden" name="checkedTestsIdValue" id="checkedTestsIdValue"/>
		  <td>
		    <b>Comments&nbsp;</b>
		    <textarea style="height: 34px;" class="form-control" id="comments" name="comments" placeholder="Comments"></textarea>
		  </td>
		<td>
		  <b>Reviewed By&nbsp;</b>
		  <!--<input type="text" name="reviewedBy" id="reviewedBy" class="form-control" title="Please enter Reviewed By" placeholder ="Reviewed By"/>-->
		  <select name="reviewedBy" id="reviewedBy" class="form-control" title="Please choose reviewed by">
		      <option value="">-- Select --</option>
		      <?php
		      foreach($userResult as $uName){
			?>
			<option value="<?php echo $uName['user_id'];?>" <?php echo ($uName['user_id']==$_SESSION['userId'])?"selected=selected":""; ?>><?php echo ucwords($uName['user_name']);?></option>
			<?php
		      }
		      ?>
                  </select>
		</td>
		<td>
		  <b>Approved By&nbsp;</b>
		  <!--<input type="text" name="approvedBy" id="approvedBy" class="form-control" title="Please enter Approved By" placeholder ="Approved By"/>-->
		  <select name="approvedBy" id="approvedBy" class="form-control" title="Please choose approved by">
		    <option value="">-- Select --</option>
		    <?php
		    foreach($userResult as $uName){
		      ?>
		      <option value="<?php echo $uName['user_id'];?>" <?php echo ($uName['user_id']==$_SESSION['userId'])?"selected=selected":""; ?>><?php echo ucwords($uName['user_name']);?></option>
		      <?php
		    }
		    ?>
		  </select>
		</td>
		  <td><br/><input type="button" onclick="submitTestStatus();" value="Save" class="btn btn-success btn-sm"></td>
	    </tr>
	    
	  </table>
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
  
  <script type="text/javascript">
   var startDate = "";
   var endDate = "";
   var selectedTests=[];
   var selectedTestsIdValue=[];
  $(document).ready(function() {
     loadVlRequestData();
  } );
  
  var oTable = null;
  function loadVlRequestData(){
    $.blockUI();
     oTable = $('#vlRequestDataTable').dataTable({
            "oLanguage": {
                "sLengthMenu": "_MENU_ records per page"
            },
            "bJQueryUI": false,
            "bAutoWidth": false,
            "bInfo": true,
            "bScrollCollapse": true,
            "bRetrieve": true,                        
            "aoColumns": [
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
                {"sClass":"center","bSortable":false},
            ],
	    "iDisplayLength": 100,
            //"aaSorting": [[ 1, "desc" ]],
	    "fnDrawCallback": function() {
//		var checkBoxes=document.getElementsByName("chk[]");
//                len = checkBoxes.length;
//                for(c=0;c<len;c++){
//                    if (jQuery.inArray(checkBoxes[c].id, selectedTestsId) != -1 ){
//			checkBoxes[c].setAttribute("checked",true);
//                    }
//                }
			  var oSettings = this.fnSettings();
              var iTotalRecords = oSettings.fnRecordsTotal();
			  if(iTotalRecords==0){
				  window.location.href="vlPrintResult.php";
			  }
	    },
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "getVlResultsForUnApproval.php",
            "fnServerData": function ( sSource, aoData, fnCallback ) {
              $.ajax({
                  "dataType": 'json',
                  "type": "POST",
                  "url": sSource,
                  "data": aoData,
                  "success": fnCallback,
              });
            },
        });
     $.unblockUI();
  }
  function toggleTest(obj){
    var dValue = obj.value;
    var dId = obj.id;
    if($.inArray(obj.id, selectedTests) == -1){
    selectedTests.push(obj.id);
    selectedTestsIdValue.push(obj.value);
    }else{
      var indexValue = selectedTests.indexOf(obj.id);
      selectedTestsIdValue[indexValue]=obj.value;  
    }
    $("#checkedTests").val(selectedTests.join());
    $("#checkedTestsIdValue").val(selectedTestsIdValue.join());
  }
   function submitTestStatus(){
    id = $("#checkedTests").val();
    status = $("#checkedTestsIdValue").val();
    comments = $("#comments").val();
    appBy = $("#approvedBy").val();
    reviewedBy = $("#reviewedBy").val();
    globalValue = '<?php echo $arr["user_review_approve"];?>';
    if(appBy==reviewedBy && (reviewedBy!='' && appBy!='') && globalValue=='yes'){
      conf = confirm("Same person is reviewing and approving result!");
      if(conf){}else{
	return false;
      }
    }else if(appBy==reviewedBy && (reviewedBy!='' && appBy!='') && globalValue=='no'){
      alert("Same person is reviewing and approving result!");
      return false;
    }
    
    if(appBy!=''){
		conf=confirm("Are you sure you want to continue ?");
		if(conf){
			$.blockUI();
			$.post("updateUnApprovalResultStatus.php", { value : id,status:status,comments:comments,appBy:appBy, reviewedBy : reviewedBy, format: "html"},
			function(data){
				if(data=='vlPrintResult.php'){
				  window.location.href="vlPrintResult.php";
				}
				oTable.fnDraw();
				selectedTests = [];
				selectedTestsIdValue = [];
				$("#checkedTests").val('');
				$("#checkedTestsIdValue").val('');
				$("#comments").val('');
			});
			$.unblockUI();
		}else{
			oTable.fnDraw();
		}
    }else{
      alert("Please choose approved by field");
    }
   }
   
  function updateStatus(value,status){
    if(status!=''){
      conf=confirm("Do you wish to change the status ?");
      if(conf){
		$.blockUI();
		$.post("updateUnApprovalResultStatus.php", { value : value,status:status, format: "html"},
	       function(data){
			oTable.fnDraw();
			selectedTests = [];
			selectedTestsId = [];
			$("#checkedTests").val('');
			$(".countChecksPending").html(0);
	    });
		$.unblockUI();
      }else{
      oTable.fnDraw();
      }
    }
   else{
      alert("Please select the status.");
    }
   }
   
  
 
</script>
 <?php
 include('../footer.php');
 ?>