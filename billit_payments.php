<?php
include("scripts/settings.php");
$msg='';
$tab=1;
$response=0;
$finalmsg='';
date_default_timezone_set('Asia/Calcutta');
$response = 1;
if(isset($_POST['supplier1_sno'])){
	foreach($_POST as $k => $v){
		$_POST[$k] = strtoupper($v);
	}
	if($_POST['account']==""){
		$msg .= '<div class="alert alert-danger mx-3">Please select account name.</div>';
	}
	
	for($i=1;$i<$_POST['id'];$i++){
		if($_POST['amount'.$i]==""){
			$msg .= '<div class="alert alert-danger mx-3">Please enter Amount at Row:'.$i.'</div>';
		}
		else{
			if($_POST['amount'.$i]!="0"){
				if($_POST['supplier'.$i.'_sno']==''){
					$msg .= '<div class="alert alert-danger mx-3">Please select Ledger at Row:'.$i.'</div>';
				}
				if($_POST['supplier'.$i]==""){
					$msg .= '<div class="alert alert-danger mx-3">Please select Customer name at Row:'.$i.'</div>';
				}
			}
		}
	}
	if($_POST['account_parent']==6){
		$_POST['type1'] = 'CASH';
		$_POST['chq_date']='';
		$_POST['chq_number']='';
		$_POST['bank_name']='';
	}
	if(isset($_POST['treat_as_final'])){
		$status = 0;
	}
	else{
		$status = 1;
	}
	$_POST['total_value_hidden'] = 10000;

	if($msg==''){
		
		/*Array ( [treat_as_final] => ON [sale_date] => 2024-04-21 [rojnamcha] => [account] => 5 [account_name] => [account_parent] => [type1] => NEFT [chq_number] => CHQ.NO: 123456. UTR: BARB0DEKAL [remarks1] => SHARE MONEY AND MEMBERSHIP FEES [supplier1] => BETA [supplier1_sno] => 15 [description1] => TEST1 [amount1] => 1000 [supplier2] => ALPHA [supplier2_sno] => 14 [description2] => TEST2 [amount2] => 2000 [supplier3] => VIKRANT [supplier3_sno] => 4 [description3] => TEST3 [amount3] => 3000 [supplier4] => GST ACCOUNT [supplier4_sno] => 12 [description4] => TEST4 [amount4] => 4000 [supplier5] => [supplier5_sno] => [description5] => [amount5] => [saveForm] => SUBMIT [edit_sno] => [id] => 5 [current] => 4 [total_value_hidden] => 10000 ) */
		
		$sql = 'INSERT INTO `billit_invoice_payment` (`amount`, `mop`, `chq_no`, `timestamp`, `remarks`, `account`, `created_by`, `creation_time`, `branch`) VALUES ("'.$_POST['total_value_hidden'].'", "'.$_POST['type1'].'", "'.$_POST['chq_number'].'", "'.$_POST['sale_date'].'",  "'.$_POST['remarks1'].'",  "'.$_POST['account'].'",  "'.$_SESSION['username'].'",  "'.date("Y-m-d H:i:s").'",  "'.$_SESSION['usersno'].'");';
		execute_query($sql);
		if(mysqli_error($db)){
			$msg .= '<div class="alert alert-danger">Error # 1.0987 : '.mysqli_error($db).' >> '.$sql.'</div>';
		}
		$id = mysqli_insert_id($db);
		
		for($i=1;$i<$_POST['id'];$i++){
			$sql = 'INSERT INTO `billit_stock_payment` (`invoice_no`, `customer_id`, `description`, `amount`, `mop`, `chq_no`, `timestamp`, `branch`, `account`, `status`, `created_by`, `creation_time`) VALUES ("'.$id.'", "'.$_POST['supplier'.$i.'_sno'].'", "'.$_POST['description'.$i].'", "'.$_POST['amount'.$i].'", "'.$_POST['type1'].'", "'.$_POST['chq_number'].'", "'.$_POST['sale_date'].'",  "'.$_SESSION['usersno'].'",  "'.$_POST['account'].'",  "0", "'.$_SESSION['username'].'",  "'.date("Y-m-d H:i:s").'");';
			execute_query($sql);
			if(mysqli_error($db)){
				$msg .= '<div class="alert alert-danger">Error # 1.0987 : '.mysqli_error($db).' >> '.$sql.'</div>';
			}

		}
		
		if($msg==''){
			$inv = mysqli_insert_id($db);
			if($_FILES['snapshot']['name']!=''){
				$allowed =  array('gif','png' ,'jpg', 'jpeg');
				$filename = $_FILES['snapshot']['name'];
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if(!in_array($ext,$allowed) ) {
					$msg .= '<div class="alert alert-danger">Invalid Image.</div>';
				}
				else{

					$temp = explode(".", $_FILES["snapshot"]["name"]);
					$newfilename = $inv . '.' . end($temp);	
					if(move_uploaded_file($_FILES["snapshot"]["tmp_name"], "user_data/payments/".$newfilename)){
						$msg.='<div class="alert alert-success mx-3">Uploaded</div>';
					}
					else{
						$msg.='<div class="alert alert-danger mx-3">Upload Failed.</div>';
					}
				}
			}
			
			$msg .= '<div class="alert alert-success mx-3">Successful</div>';
			$payment['sno']='';
		}
		else {
			$msg .= '<h3>There were some errors.</h3>';
			$payment['sno']=$_POST['edit_sno'];
		}
		$date = $_POST['sale_date'];	
		$payment['timestamp'] = $date;
		unset($_POST);
		$_POST['type1'] = 'CASH';
		$_POST['account']='';
		$_POST['supplier1']='';
		$_POST['address1']='';
		$_POST['mob1']='';
		$_POST['tin1']='';
		$_POST['amount1']='';
		$_POST['remarks1']='';
		$_POST['description1']='';
		$_POST['account_name']='';
		$_POST['account_parent']='6';
		$_POST['supplier1_sno']='';
		$_POST['chq_number']='';
		$_POST['bank_name']='';
		$_POST['sale_date'] = $date;
		$_POST['mop'] = '';
	}
	$response=1;
}
else {
	$sql = 'select * from billit_customer_transactions where type="PAYMENT" order by sno desc limit 1';
	$date_res = execute_query($sql);
	if(mysqli_num_rows($date_res)!=0){
		$date_row = mysqli_fetch_array($date_res);
		$date = $date_row['timestamp'];
		$payment['timestamp'] = $date;
	}
	else{
		$date = date("Y-m-d");
	}
	$payment['sno']='';
	$_POST['mop'] = '';
}
if(isset($_GET['id'])){
	$sql = 'select billit_customer.sno as cust_id, cus_name, address, mobile, tin, invoice_no, amount, chq_date, chq_no, bank_name, timestamp, remarks, account, parent, mop from billit_customer_transactions join billit_customer on cust_id = billit_customer.sno where billit_customer_transactions.sno='.$_GET['id'];
	$payment = mysqli_fetch_array(execute_query($sql));
	$_POST['mop'] = $payment['mop'];
	
	$sql = 'select * from billit_customer where sno='.$payment['account'];
	$parent = mysqli_fetch_array(execute_query($sql));
	$payment['parent'] = $parent['parent'];
}
page_header_start();
?>
<script type="text/javascript" language="javascript">
function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}
</script>

<script language="javascript" type="text/javascript">
function load_wind(id){
	window.location = id;
}
$(function () {
    // For dropdown change
    $(document).on('change', "[id^='supplier'][id$='_type']", function () {
        var id = $(this).attr('id').match(/\d+/)[0]; // Extract the number (e.g., 1, 2, etc.)
        var ledger_type = $(this).val();

        console.log("Changed: supplier" + id + "_type →", ledger_type);

        $("[name='supplier" + id + "']").autocomplete("option", "source", "scripts/billit_ajax.php?id=cust_name&type=" + ledger_type);
    });

    // For autocomplete initialization on input
    $(document).on('keydown.autocomplete', "[name^='supplier']", function () {
        var nameAttr = $(this).attr("name");
        var id = nameAttr.match(/\d+/)[0]; // Extract number from name

        var ledger_type = $('#supplier' + id + '_type').val();
        var sourceUrl = "scripts/billit_ajax.php?id=cust_name&type=" + ledger_type;

        $(this).autocomplete({
            source: sourceUrl,
            minLength: 1,
            select: function (event, ui) {
                $(this).val(ui.item.label);
                $('#supplier' + id + '_sno').val(ui.item.id);
                get_pending_invoices(ui.item.id);
                return false;
            }
        });
    });
});


function change_account(val){
	$.ajax({
		url: "scripts/billit_ajax.php?id=contra&term="+val,
		dataType: "json",
		async: false
	})
	.done(function( data ) {
		$('#account_parent').val(data[0].category);
		$('#account_name').val(data[0].cust_name);
		if($("#account_parent").val()=='6'){
			$(".type1").attr("disabled", "disabled");
			document.getElementById("chq_number").disabled = 'true';
		}
		else{
			$(".type1").removeAttr('disabled');
			$("#chq_number").removeAttr('disabled');
		}
	});
}	

function get_pending_invoices(val){
	$.ajax({
		url: "scripts/billit_ajax.php?id=pending_purchase&term="+val,
		dataType: "json"
	})
	.done(function( data ) {
		var table = '<div class="alert alert-info">Pending Invoice</div><table class=" table table-striped table-hover table-bordered"><tr><th>S.No.</th><th>Party Name</th><th>Invoice Date</th><th>Due Date</th><th>Overdue Days</th><th>Grand Total</th><th>Amount Paid</th><th>Balance</th><th colspan="2">&nbsp;</th></tr>';
		var i=1;
		var tot_balance = 0;
		$.each( data, function( index, value ) {
			table += '<tr><td>'+(i++)+'</td><td>'+value.cust_name+'</td><td>'+value.dateofdispatch+'</td><td>'+value.overdue_date+'</td><td>'+value.overdue_days+'<td>'+value.grand_total+'</td><td>'+value.amount_paid+'</td><td>'+value.amount_due+'</td><td><a href="scripts/printing_sale.php?id='+value.id+'" target="_blank">View</a></td><td><input type="checkbox" name="settle_'+value.id+'" id="settle_'+value.id+'"></td></tr>';	
			tot_balance += parseFloat(value.amount_due);
		});
		table += '<tr><th colspan="6"></th><th>Total:</th><th>'+tot_balance+'</th><th colspan="2">&nbsp;</th></table>';
		//console.log(table);
		$("#insert_data").html(table);
	});
}
	
$( document ).ready(function() {
	change_account($("#account option:selected").text());
	if($("#account_parent").val()=='6'){
		$(".type1").attr("disabled", "disabled");
		document.getElementById("chq_number").disabled = 'true';
	}
	else{
		$(".type1").removeAttr('disabled');
		$("#chq_number").removeAttr('disabled');
	}
});
</script>
<style>
.legend .row:nth-of-type(odd) div {
background-color:#e5fad7;
}
.legend .row:nth-of-type(even) div {
background:#F4DDDD;
}
</style>
<?php
page_header_end();
page_sidebar();
?>


	<div class="row">
		<div class="col-md-12">
			<form id="purchase_form" name="purchase_form" enctype="multipart/form-data" method="post" novalidate action="<?php echo $_SERVER['PHP_SELF']; ?>" onSubmit="return confirm('Are you sure?');">
			<?php
			switch($response){
				case 1:{
			?>
		       		<div class="card">
						<div class="row my-3">
							<div class="col-md-12">
								<?php echo $msg; ?>	
							</div>
							
						</div>
						<div class="row my-3">
							<div class="col-lg-4 col-md-6 col-sm-12">
								<div class="row">
									<div class="col-md-3">
										<div class="form-check">
											<label class="form-check-label">
												<input class="form-check-input" type="checkbox" name="treat_as_final" <?php if(isset($_GET['id'])){if($old_data['status']==0){echo "checked='checked'";}}else{ echo 'checked="checked"';}?>>
												<span class="form-check-sign"></span>Draft Entry
											</label>
										</div>       					
									</div>	
									<div class="col-md-7 mx-4">
										<label for="exampleInputEmail1" class="form-label">Entry Date</label>
										<script type="text/javascript" language="javascript">
										document.writeln(DateInput('sale_date', 'purchase_form', false, 'YYYY-MM-DD', '<?php if(isset($_POST['sale_date'])){echo $_POST['sale_date'];}else{if(isset($_GET['id'])){echo $payment['timestamp'];}else{echo date("Y-m-d");}} ?>', <?php echo $tab; $tab+=7; ?>));
										</script>
									</div>	
									
								</div>					     					
							</div>
							<div class="col-md-4">
<div class="form-group mx-4">
    <label>TRANSFER VOUCHER</label>
<select name="transfer_voucher" id="transfer_voucher">
    <option value="">Select</option>
    <?php
    $res = execute_query("SELECT id, total_rcv_amt FROM project_note_sheet");
    while($row = mysqli_fetch_assoc($res)) {
        echo "<option value='{$row['id']}'>{$row['total_rcv_amt']}</option>";
    }
    ?>
</select>
</div>
</div>					     					
							</div>
							<div class="col-md-4">
								<div class="form-group mx-4">
									<label>Voucher No.</label>
									<input name="rojnamcha" type="text" value=""  class="form-control" tabindex="<?php echo $tab++;?>" id="challan" />
								</div>								     					
							</div>
							<div class="col-md-4">
								<div class="form-group mx-4">
									<label>Upload Image</label>
									<input accept="image/png, image/jpeg, image/gif" name="snapshot" id="snapshot" type="file" class="btn btn-info form-control" tabindex="<?php echo $tab++;?>">
								</div>								     					
							</div>
						</div>
						<div class="row my-3">
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Account Name</label>
									<select name="account" class="form-control" tabindex="<?php echo $tab++;?>" id="account" onChange="change_account($('#account option:selected').text())">
									<?php
									$sql = 'select * from billit_customer where parent in ("BANK","CASH", 6, 1) limit 20'; 
									//echo $sql;
									$res = execute_query($sql);
									while($row = mysqli_fetch_array($res)) {
										echo '<option value="'.$row['sno'].'" ';
										if(isset($_POST['sale_date'])){
											if($_POST['account']==$row['sno']){
												echo ' selected="selected" ';	
											}
										}
										if(isset($_GET['id'])){
											if($payment['account']==$row['sno']){
												echo ' selected="selected" ';
											}
										}
										echo '>'.$row['cus_name'].'</option>';	
									}
									?>            		
									</select>
									<input type="hidden" name="account_name" id="account_name" value="<?php if(isset($_POST['sale_date'])){echo $_POST['account_name'];}else{if(isset($_GET['id'])){echo $payment['account'];}} ?>" >
									<input type="hidden" name="account_parent" id="account_parent" value="<?php if(isset($_POST['sale_date'])){echo $_POST['account_parent'];}else{if(isset($_GET['id'])){echo $payment['parent'];}} ?>" >
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Mode of Payment</label>
									<input type="text" list="type1" name="type1" class="type1 form-control" tabindex="<?php echo $tab++; ?>" value="<?php echo ($_POST['mop']!=''?$_POST['mop']:''); ?>"/>
									<datalist id="type1">
									<?php
									$sql = 'select * from billit_customer_transactions where type in ("PAYMENT", "RECEIPT", "RECIEPT") group by mop';
									$result_mop = execute_query($sql);
									while($row_mop = mysqli_fetch_array($result_mop)){
										echo '<option ';
										if($row_mop['mop']==$_POST['mop']){
											echo ' selected="selected"';
										}
										echo '>'.$row_mop['mop'].'</option>';
									}
									?>
									</datalist>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Chq/UTR/Ref Number</label>
									<input type="text" name="chq_number" class="form-control" id="chq_number" tabindex="<?php echo $tab++; ?>" value="<?php if(isset($_POST['sale_date'])){echo $_POST['chq_number'];}else{if(isset($_GET['id'])){echo $payment['chq_no'];}} ?>" />
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Remarks</label>
									<input type="text" name="remarks1" class="form-control" id="remarks1" tabindex="<?php echo $tab++; ?>" value="<?php if(isset($_POST['sale_date'])){echo $_POST['remarks1'];}else{if(isset($_GET['id'])){echo $payment['remarks'];}} ?>" />
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="alert alert-primary">Particulars</div>
							</div>
						</div>
						<div class="legend">
							<div class="row px-3">
								<!--<div class="col-3" style="align-content: center;">
									1. <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal">New Ledger <i class="far fa-plus-square"></i></button>
									<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editModal" onclick="edit_client();">Edit Ledger <i class="far fa-edit"></i></button>
								</div>-->
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Party/Leder Type</label>
										<select class="form-control" name="supplier1_type" id="supplier1_type"  tabindex="<?php echo $tab++; ?>" >
											<option value="">--Select--</option>
											
											<!--<option value="uprnss_department_name"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_department_name'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'project' ? ' selected' : '');}} ?>>DEPARTMENT</option>
										
											<option value="uprnss_project_temp"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_project_temp'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'project' ? ' selected' : '');}} ?>>PROJECT</option>
											
											<option value="emp"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='emp'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'emp' ? ' selected' : '');}} ?>>EMPLOYEE</option>--->
											
											<option value="vendor"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='contractor'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'vendor' ? ' selected' : '');}} ?>>CONTRACTOR</option>
											
											<option value="uprnss_architect"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_architect'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'uprnss_architect' ? ' selected' : '');}} ?>>ARCHITECT/STRUCTURAL ARCHITECT</option>
											
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Party/Leder Name</label>
										<input name="supplier1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['supplier1'];}else{if(isset($_GET['id'])){echo get_ledger($payment['cust_id']);}} ?>"  class="form-control" tabindex="<?php echo $tab++;?>" id="supplier" onFocus="set_current(1);" onBlur="add_payment_row(1)" /> &nbsp;
										<input type="text" id="supplier1_sno" name="supplier1_sno" value="<?php if(isset($_POST['sale_date'])){echo $_POST['supplier1_sno'];}else{if(isset($_GET['id'])){echo $payment['cust_id'];}} ?>">
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Description</label>
										<input name="description1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['description1'];}else{if(isset($_GET['id'])){echo $payment['description'];}} ?>" class="form-control" id="description1" tabindex="<?php echo $tab++;?>" onFocus="set_current(1); calc_total();" onBlur="add_payment_row(1)"/>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Amount</label>
										<input name="amount1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['amount1'];}else{if(isset($_GET['id'])){echo $payment['amount'];}} ?>" class="form-control" id="amount1" tabindex="<?php echo $tab++;?>" onFocus="set_current(1);" onBlur="add_payment_row(1); calc_total();"/>
									</div>
								</div>

							</div>
						</div>
						<div class="row">
							<div class="col-12 ">
								<div class="float-right col-3 alert alert-primary my-3">Total : <span id="total_value"></span></div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4 mx-4">
								<input id="saveForm" name="saveForm" class="btn btn-success" type="submit" value="Submit" tabindex="<?php echo $tab++; ?>">
        						<input type="hidden" name="edit_sno" value="<?php if(isset($_GET['id'])){echo $_GET['id'];}?>" />
        						<input type="text" name="id" id="id" value="1">
        						<input type="hidden" name="current" id="current" value="1">
        						<input type="hidden" name="total_value_hidden" id="total_value_hidden" value="">
							</div>
						</div>
						<div class="row my-3 px-4" id="insert_data">
							
						</div>
					</div>
			<?php
					break;
				}
			}
			?>
			</form> 
		</div>
	</div>



<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Create New Ledger</h5>
				<button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form name="create_ledger" id="create_ledger" action="scripts/billit_ajax.php?id=create_ledger" method="get"><p>Enter Details</p>
					<div class="row">
						<div class="col-md-6">
							<label>Company Name</label>
							<input id="cus_name" name="cus_name" tabindex="21" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>State</label>
							<select id="state" name="state" tabindex="22" class="form-control">
							<?php
							$sql = 'select * from general_settings where `desc`="state"';
							$default_state = mysqli_fetch_assoc(execute_query($sql));

							$sql = 'select * from billit_state_name';
							$res_state = execute_query($sql);
							while($row_state = mysqli_fetch_array($res_state)){
								echo '<option value="'.$row_state['state_code'].'" ';
								if(isset($_GET['id'])){
									if(strtoupper(trim($row_state['state_code']))==strtoupper(trim($ledger['state']))){
										echo ' selected="selected" ';
									}
								}
								else{
									if(strtoupper(trim($row_state['state_code']))==$default_state['rate']){
										echo ' selected="selected"';
									}
								}
								echo '>'.$row_state['indian_states'].'</option>';
							}
							?>
							</select></td>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label>Mobile</label>
							<input id="mobile" name="mobile" tabindex="23" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>GSTIN</label>
							<input id="tin" name="tin" tabindex="24" value="" type="text" class="form-control">
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label>Address</label>
							<input id="address" name="address" tabindex="25" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>Address 2</label>
							<input id="add_2" name="add_2" tabindex="26" value="" type="text" class="form-control">
						</div>
					</div>
				</form>				
			</div>
			<div class="modal-footer">
				<div class="col-md-12 text-center" id="ajax_loader" style="display:none;"><img src="images/loading_transparent.gif"></div>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="create_new();">Save changes</button>
			</div>
		</div>
	</div>
</div>
<?php page_footer_start(); ?>		
<script>
	function create_new(){
		var cus_name = encodeURIComponent($('#cus_name').val());
		var state = $('#state').val();
		var mobile = $('#mobile').val();
		var tin = $('#tin').val();
		var address = $('#address').val();
		var address2 = $('#add_2').val();
		var finaldata = '';
		finaldata = 'parent=31&term=t&cus_name='+cus_name+'&state='+state+'&mobile='+mobile+'&tin='+tin+'&address='+address+'&add_2='+address2;
		var form = $('#create_ledger');
		document.getElementById('ajax_loader').style.display = 'block';
		$.ajax({
		  type: "GET",
		  url: form.attr('action'),
		  data: finaldata,
		  cache: false,
		  //success: result
		  complete: function(response){
			  document.getElementById('ajax_loader').style.display = 'none';
			  $('#createModal').modal('hide');
			  $("#supplier").val(decodeURIComponent(cus_name));
			  $("#supplier1_sno").val(response.responseText);
			  var details = address+", "+address2+". <br/><b>M:</b> "+mobile+"<br/><b>GSTIN:</b> "+tin;
			
			$('#supplier_data').html(details);
		  }
		});
	}
	
	function edit_client(){
		var id = $("#supplier1_sno").val();
		if(id==''){
			alert('Please select a customer.');
			return;
		}
		else{
			window.open("ledgers.php?id="+id);
		}
	}	
	
	function add_payment_row(id){
		var tot=0;
		
		var max_id = $("#id").val();
		var current = $("#current").val();
		
		if(max_id==current){
			if($("#supplier"+current+"_sno").val()!=''){
				id++;
				var txt = '<div class="row px-3"><div class="col-md-3"><div class="form-group mx-2"><label>Party/Ledger Type</label><select class="form-control" name="supplier'+id+'_type" id="supplier'+id+'_type" tabindex="1"><option value="">--Select--</option><option value="vendor">CONTRACTOR</option><option value="uprnss_architect">ARCHITECT/STRUCTURAL ARCHITECT</option></select></div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Party/Leder Name</label>		<input name="supplier'+id+'" type="text" value=""  class="form-control" tabindex="" id="supplier" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+')"/> &nbsp;		<input type="text" id="supplier'+id+'_sno" name="supplier'+id+'_sno" value="">	</div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Description</label><input name="description'+id+'" type="text" value="" class="form-control" id="description'+id+'" tabindex="" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+')"/>	</div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Amount</label><input name="amount'+id+'" type="text" value="" class="form-control" id="amount'+id+'" tabindex="" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+');  calc_total();"/></div></div></div>';
				$(".legend").append(txt);
				$("#id").val(id);
			}
		}
		
		
	}
	
	function calc_total(){
		var tot=0;
		
		var max_id = $("#id").val();
		for(i=1; i<=max_id;i++){
			
			console.log(max_id+"A: "+i+">>"+$("#amount"+i).val()+" >> "+document.getElementById("amount"+i).value);
			var amt = parseFloat($("#amount"+i).val());
			if(!amt){
				amt = 0;
			}
			tot += amt;
		}
		$("#total_value").html(tot);
		$("#total_value_hidden").val(tot);
		
	}
	
	function set_current(id){
		$("#current").val(id);
	}
		
		

		$("#transfer_voucher").on("change", function() {
    var selectedAmount = $(this).val();
    if (selectedAmount != "") {
        $.ajax({
            url: "scripts/billit_ajax.php",
            type: "GET",
            data: {
                id: "fetch_transfer_voucher_data",
                amount: selectedAmount
            },
            dataType: "json",
            success: function(response) {
                if(response.success) {
                    $("#challan").val(response.data.voucher_no);
                    $("#account").val(response.data.account_id).change();
                    $("#account_name").val(response.data.account_name);
                    $("#account_parent").val(response.data.account_parent);
                    $("#supplier1").val(response.data.party_name);
                    $("#supplier1_sno").val(response.data.party_id);
                    $("#description1").val(response.data.description);
                    $("#amount1").val(response.data.amount);
                    $("#total_value").text(response.data.amount);
                    $("#total_value_hidden").val(response.data.amount);
                } else {
                    alert("No matching data found.");
                }
            }
        });
    }
});
</script>
<?php page_footer_end();?><?php
include("scripts/settings.php");
$msg='';
$tab=1;
$response=0;
$finalmsg='';
date_default_timezone_set('Asia/Calcutta');
$response = 1;
if(isset($_POST['supplier1_sno'])){
	foreach($_POST as $k => $v){
		$_POST[$k] = strtoupper($v);
	}
	if($_POST['account']==""){
		$msg .= '<div class="alert alert-danger mx-3">Please select account name.</div>';
	}
	
	for($i=1;$i<$_POST['id'];$i++){
		if($_POST['amount'.$i]==""){
			$msg .= '<div class="alert alert-danger mx-3">Please enter Amount at Row:'.$i.'</div>';
		}
		else{
			if($_POST['amount'.$i]!="0"){
				if($_POST['supplier'.$i.'_sno']==''){
					$msg .= '<div class="alert alert-danger mx-3">Please select Ledger at Row:'.$i.'</div>';
				}
				if($_POST['supplier'.$i]==""){
					$msg .= '<div class="alert alert-danger mx-3">Please select Customer name at Row:'.$i.'</div>';
				}
			}
		}
	}
	if($_POST['account_parent']==6){
		$_POST['type1'] = 'CASH';
		$_POST['chq_date']='';
		$_POST['chq_number']='';
		$_POST['bank_name']='';
	}
	if(isset($_POST['treat_as_final'])){
		$status = 0;
	}
	else{
		$status = 1;
	}
	$_POST['total_value_hidden'] = 10000;

	if($msg==''){
		
		/*Array ( [treat_as_final] => ON [sale_date] => 2024-04-21 [rojnamcha] => [account] => 5 [account_name] => [account_parent] => [type1] => NEFT [chq_number] => CHQ.NO: 123456. UTR: BARB0DEKAL [remarks1] => SHARE MONEY AND MEMBERSHIP FEES [supplier1] => BETA [supplier1_sno] => 15 [description1] => TEST1 [amount1] => 1000 [supplier2] => ALPHA [supplier2_sno] => 14 [description2] => TEST2 [amount2] => 2000 [supplier3] => VIKRANT [supplier3_sno] => 4 [description3] => TEST3 [amount3] => 3000 [supplier4] => GST ACCOUNT [supplier4_sno] => 12 [description4] => TEST4 [amount4] => 4000 [supplier5] => [supplier5_sno] => [description5] => [amount5] => [saveForm] => SUBMIT [edit_sno] => [id] => 5 [current] => 4 [total_value_hidden] => 10000 ) */
		
		$sql = 'INSERT INTO `billit_invoice_payment` (`amount`, `mop`, `chq_no`, `timestamp`, `remarks`, `account`, `created_by`, `creation_time`, `branch`) VALUES ("'.$_POST['total_value_hidden'].'", "'.$_POST['type1'].'", "'.$_POST['chq_number'].'", "'.$_POST['sale_date'].'",  "'.$_POST['remarks1'].'",  "'.$_POST['account'].'",  "'.$_SESSION['username'].'",  "'.date("Y-m-d H:i:s").'",  "'.$_SESSION['usersno'].'");';
		execute_query($sql);
		if(mysqli_error($db)){
			$msg .= '<div class="alert alert-danger">Error # 1.0987 : '.mysqli_error($db).' >> '.$sql.'</div>';
		}
		$id = mysqli_insert_id($db);
		
		for($i=1;$i<$_POST['id'];$i++){
			$sql = 'INSERT INTO `billit_stock_payment` (`invoice_no`, `customer_id`, `description`, `amount`, `mop`, `chq_no`, `timestamp`, `branch`, `account`, `status`, `created_by`, `creation_time`) VALUES ("'.$id.'", "'.$_POST['supplier'.$i.'_sno'].'", "'.$_POST['description'.$i].'", "'.$_POST['amount'.$i].'", "'.$_POST['type1'].'", "'.$_POST['chq_number'].'", "'.$_POST['sale_date'].'",  "'.$_SESSION['usersno'].'",  "'.$_POST['account'].'",  "0", "'.$_SESSION['username'].'",  "'.date("Y-m-d H:i:s").'");';
			execute_query($sql);
			if(mysqli_error($db)){
				$msg .= '<div class="alert alert-danger">Error # 1.0987 : '.mysqli_error($db).' >> '.$sql.'</div>';
			}

		}
		
		if($msg==''){
			$inv = mysqli_insert_id($db);
			if($_FILES['snapshot']['name']!=''){
				$allowed =  array('gif','png' ,'jpg', 'jpeg');
				$filename = $_FILES['snapshot']['name'];
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if(!in_array($ext,$allowed) ) {
					$msg .= '<div class="alert alert-danger">Invalid Image.</div>';
				}
				else{

					$temp = explode(".", $_FILES["snapshot"]["name"]);
					$newfilename = $inv . '.' . end($temp);	
					if(move_uploaded_file($_FILES["snapshot"]["tmp_name"], "user_data/payments/".$newfilename)){
						$msg.='<div class="alert alert-success mx-3">Uploaded</div>';
					}
					else{
						$msg.='<div class="alert alert-danger mx-3">Upload Failed.</div>';
					}
				}
			}
			
			$msg .= '<div class="alert alert-success mx-3">Successful</div>';
			$payment['sno']='';
		}
		else {
			$msg .= '<h3>There were some errors.</h3>';
			$payment['sno']=$_POST['edit_sno'];
		}
		$date = $_POST['sale_date'];	
		$payment['timestamp'] = $date;
		unset($_POST);
		$_POST['type1'] = 'CASH';
		$_POST['account']='';
		$_POST['supplier1']='';
		$_POST['address1']='';
		$_POST['mob1']='';
		$_POST['tin1']='';
		$_POST['amount1']='';
		$_POST['remarks1']='';
		$_POST['description1']='';
		$_POST['account_name']='';
		$_POST['account_parent']='6';
		$_POST['supplier1_sno']='';
		$_POST['chq_number']='';
		$_POST['bank_name']='';
		$_POST['sale_date'] = $date;
		$_POST['mop'] = '';
	}
	$response=1;
}
else {
	$sql = 'select * from billit_customer_transactions where type="PAYMENT" order by sno desc limit 1';
	$date_res = execute_query($sql);
	if(mysqli_num_rows($date_res)!=0){
		$date_row = mysqli_fetch_array($date_res);
		$date = $date_row['timestamp'];
		$payment['timestamp'] = $date;
	}
	else{
		$date = date("Y-m-d");
	}
	$payment['sno']='';
	$_POST['mop'] = '';
}
if(isset($_GET['id'])){
	$sql = 'select billit_customer.sno as cust_id, cus_name, address, mobile, tin, invoice_no, amount, chq_date, chq_no, bank_name, timestamp, remarks, account, parent, mop from billit_customer_transactions join billit_customer on cust_id = billit_customer.sno where billit_customer_transactions.sno='.$_GET['id'];
	$payment = mysqli_fetch_array(execute_query($sql));
	$_POST['mop'] = $payment['mop'];
	
	$sql = 'select * from billit_customer where sno='.$payment['account'];
	$parent = mysqli_fetch_array(execute_query($sql));
	$payment['parent'] = $parent['parent'];
}
page_header_start();
?>
<script type="text/javascript" language="javascript">
function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}
</script>

<script language="javascript" type="text/javascript">
function load_wind(id){
	window.location = id;
}
$(function () {
    // For dropdown change
    $(document).on('change', "[id^='supplier'][id$='_type']", function () {
        var id = $(this).attr('id').match(/\d+/)[0]; // Extract the number (e.g., 1, 2, etc.)
        var ledger_type = $(this).val();

        console.log("Changed: supplier" + id + "_type →", ledger_type);

        $("[name='supplier" + id + "']").autocomplete("option", "source", "scripts/billit_ajax.php?id=cust_name&type=" + ledger_type);
    });

    // For autocomplete initialization on input
    $(document).on('keydown.autocomplete', "[name^='supplier']", function () {
        var nameAttr = $(this).attr("name");
        var id = nameAttr.match(/\d+/)[0]; // Extract number from name

        var ledger_type = $('#supplier' + id + '_type').val();
        var sourceUrl = "scripts/billit_ajax.php?id=cust_name&type=" + ledger_type;

        $(this).autocomplete({
            source: sourceUrl,
            minLength: 1,
            select: function (event, ui) {
                $(this).val(ui.item.label);
                $('#supplier' + id + '_sno').val(ui.item.id);
                get_pending_invoices(ui.item.id);
                return false;
            }
        });
    });
});


function change_account(val){
	$.ajax({
		url: "scripts/billit_ajax.php?id=contra&term="+val,
		dataType: "json",
		async: false
	})
	.done(function( data ) {
		$('#account_parent').val(data[0].category);
		$('#account_name').val(data[0].cust_name);
		if($("#account_parent").val()=='6'){
			$(".type1").attr("disabled", "disabled");
			document.getElementById("chq_number").disabled = 'true';
		}
		else{
			$(".type1").removeAttr('disabled');
			$("#chq_number").removeAttr('disabled');
		}
	});
}	

function get_pending_invoices(val){
	$.ajax({
		url: "scripts/billit_ajax.php?id=pending_purchase&term="+val,
		dataType: "json"
	})
	.done(function( data ) {
		var table = '<div class="alert alert-info">Pending Invoice</div><table class=" table table-striped table-hover table-bordered"><tr><th>S.No.</th><th>Party Name</th><th>Invoice Date</th><th>Due Date</th><th>Overdue Days</th><th>Grand Total</th><th>Amount Paid</th><th>Balance</th><th colspan="2">&nbsp;</th></tr>';
		var i=1;
		var tot_balance = 0;
		$.each( data, function( index, value ) {
			table += '<tr><td>'+(i++)+'</td><td>'+value.cust_name+'</td><td>'+value.dateofdispatch+'</td><td>'+value.overdue_date+'</td><td>'+value.overdue_days+'<td>'+value.grand_total+'</td><td>'+value.amount_paid+'</td><td>'+value.amount_due+'</td><td><a href="scripts/printing_sale.php?id='+value.id+'" target="_blank">View</a></td><td><input type="checkbox" name="settle_'+value.id+'" id="settle_'+value.id+'"></td></tr>';	
			tot_balance += parseFloat(value.amount_due);
		});
		table += '<tr><th colspan="6"></th><th>Total:</th><th>'+tot_balance+'</th><th colspan="2">&nbsp;</th></table>';
		//console.log(table);
		$("#insert_data").html(table);
	});
}
	
$( document ).ready(function() {
	change_account($("#account option:selected").text());
	if($("#account_parent").val()=='6'){
		$(".type1").attr("disabled", "disabled");
		document.getElementById("chq_number").disabled = 'true';
	}
	else{
		$(".type1").removeAttr('disabled');
		$("#chq_number").removeAttr('disabled');
	}
});
</script>
<style>
.legend .row:nth-of-type(odd) div {
background-color:#e5fad7;
}
.legend .row:nth-of-type(even) div {
background:#F4DDDD;
}
</style>
<?php
page_header_end();
page_sidebar();
?>


	<div class="row">
		<div class="col-md-12">
			<form id="purchase_form" name="purchase_form" enctype="multipart/form-data" method="post" novalidate action="<?php echo $_SERVER['PHP_SELF']; ?>" onSubmit="return confirm('Are you sure?');">
			<?php
			switch($response){
				case 1:{
			?>
		       		<div class="card">
						<div class="row my-3">
							<div class="col-md-12">
								<?php echo $msg; ?>	
							</div>
							
						</div>
						<div class="row my-3">
							<div class="col-lg-4 col-md-6 col-sm-12">
								<div class="row">
									<div class="col-md-3">
										<div class="form-check">
											<label class="form-check-label">
												<input class="form-check-input" type="checkbox" name="treat_as_final" <?php if(isset($_GET['id'])){if($old_data['status']==0){echo "checked='checked'";}}else{ echo 'checked="checked"';}?>>
												<span class="form-check-sign"></span>Draft Entry
											</label>
										</div>       					
									</div>	
									<div class="col-md-7 mx-4">
										<label for="exampleInputEmail1" class="form-label">Entry Date</label>
										<script type="text/javascript" language="javascript">
										document.writeln(DateInput('sale_date', 'purchase_form', false, 'YYYY-MM-DD', '<?php if(isset($_POST['sale_date'])){echo $_POST['sale_date'];}else{if(isset($_GET['id'])){echo $payment['timestamp'];}else{echo date("Y-m-d");}} ?>', <?php echo $tab; $tab+=7; ?>));
										</script>
									</div>	
									
								</div>					     					
							</div>
							<div class="col-md-4">
<div class="form-group mx-4">
    <label>TRANSFER VOUCHER</label>
    <select name="transfer_voucher" class="form-control" id="transfer_voucher" style="background-color:yellow;">
        <option value="">-- Select Amount --</option>
        <?php
        $sql = "SELECT total_rcv_amt FROM project_note_sheet ORDER BY id DESC";
        $res = execute_query($sql); // Or use mysqli_query($conn, $sql); if not using helper

        if ($res && mysqli_num_rows($res) > 0) {
            while($row = mysqli_fetch_assoc($res)) {
                $amount = number_format($row['total_rcv_amt'], 2);
                echo '<option value="'.$row['total_rcv_amt'].'">₹ '.$amount.'</option>';
            }
        } else {
            echo '<option disabled>No data found</option>';
        }
        ?>
    </select>
</div>
</div>					     					
							</div>
							<div class="col-md-4">
								<div class="form-group mx-4">
									<label>Voucher No.</label>
									<input name="rojnamcha" type="text" value=""  class="form-control" tabindex="<?php echo $tab++;?>" id="challan" />
								</div>								     					
							</div>
							<div class="col-md-4">
								<div class="form-group mx-4">
									<label>Upload Image</label>
									<input accept="image/png, image/jpeg, image/gif" name="snapshot" id="snapshot" type="file" class="btn btn-info form-control" tabindex="<?php echo $tab++;?>">
								</div>								     					
							</div>
						</div>
						<div class="row my-3">
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Account Name</label>
									<select name="account" class="form-control" tabindex="<?php echo $tab++;?>" id="account" onChange="change_account($('#account option:selected').text())">
									<?php
									$sql = 'select * from billit_customer where parent in ("BANK","CASH", 6, 1) limit 20'; 
									//echo $sql;
									$res = execute_query($sql);
									while($row = mysqli_fetch_array($res)) {
										echo '<option value="'.$row['sno'].'" ';
										if(isset($_POST['sale_date'])){
											if($_POST['account']==$row['sno']){
												echo ' selected="selected" ';	
											}
										}
										if(isset($_GET['id'])){
											if($payment['account']==$row['sno']){
												echo ' selected="selected" ';
											}
										}
										echo '>'.$row['cus_name'].'</option>';	
									}
									?>            		
									</select>
									<input type="hidden" name="account_name" id="account_name" value="<?php if(isset($_POST['sale_date'])){echo $_POST['account_name'];}else{if(isset($_GET['id'])){echo $payment['account'];}} ?>" >
									<input type="hidden" name="account_parent" id="account_parent" value="<?php if(isset($_POST['sale_date'])){echo $_POST['account_parent'];}else{if(isset($_GET['id'])){echo $payment['parent'];}} ?>" >
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Mode of Payment</label>
									<input type="text" list="type1" name="type1" class="type1 form-control" tabindex="<?php echo $tab++; ?>" value="<?php echo ($_POST['mop']!=''?$_POST['mop']:''); ?>"/>
									<datalist id="type1">
									<?php
									$sql = 'select * from billit_customer_transactions where type in ("PAYMENT", "RECEIPT", "RECIEPT") group by mop';
									$result_mop = execute_query($sql);
									while($row_mop = mysqli_fetch_array($result_mop)){
										echo '<option ';
										if($row_mop['mop']==$_POST['mop']){
											echo ' selected="selected"';
										}
										echo '>'.$row_mop['mop'].'</option>';
									}
									?>
									</datalist>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Chq/UTR/Ref Number</label>
									<input type="text" name="chq_number" class="form-control" id="chq_number" tabindex="<?php echo $tab++; ?>" value="<?php if(isset($_POST['sale_date'])){echo $_POST['chq_number'];}else{if(isset($_GET['id'])){echo $payment['chq_no'];}} ?>" />
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group mx-4">
									<label>Remarks</label>
									<input type="text" name="remarks1" class="form-control" id="remarks1" tabindex="<?php echo $tab++; ?>" value="<?php if(isset($_POST['sale_date'])){echo $_POST['remarks1'];}else{if(isset($_GET['id'])){echo $payment['remarks'];}} ?>" />
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="alert alert-primary">Particulars</div>
							</div>
						</div>
						<div class="legend">
							<div class="row px-3">
								<!--<div class="col-3" style="align-content: center;">
									1. <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal">New Ledger <i class="far fa-plus-square"></i></button>
									<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editModal" onclick="edit_client();">Edit Ledger <i class="far fa-edit"></i></button>
								</div>-->
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Party/Leder Type</label>
										<select class="form-control" name="supplier1_type" id="supplier1_type"  tabindex="<?php echo $tab++; ?>" >
											<option value="">--Select--</option>
											
											<!--<option value="uprnss_department_name"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_department_name'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'project' ? ' selected' : '');}} ?>>DEPARTMENT</option>
										
											<option value="uprnss_project_temp"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_project_temp'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'project' ? ' selected' : '');}} ?>>PROJECT</option>
											
											<option value="emp"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='emp'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'emp' ? ' selected' : '');}} ?>>EMPLOYEE</option>--->
											
											<option value="vendor"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='contractor'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'vendor' ? ' selected' : '');}} ?>>CONTRACTOR</option>
											
											<option value="uprnss_architect"<?php if(isset($_POST['supplier1_type'])){echo ($_POST['supplier1_type']=='uprnss_architect'?' selected':'');}else{if(isset($_GET['id'])){echo ($payment == 'uprnss_architect' ? ' selected' : '');}} ?>>ARCHITECT/STRUCTURAL ARCHITECT</option>
											
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Party/Leder Name</label>
										<input name="supplier1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['supplier1'];}else{if(isset($_GET['id'])){echo get_ledger($payment['cust_id']);}} ?>"  class="form-control" tabindex="<?php echo $tab++;?>" id="supplier" onFocus="set_current(1);" onBlur="add_payment_row(1)" /> &nbsp;
										<input type="text" id="supplier1_sno" name="supplier1_sno" value="<?php if(isset($_POST['sale_date'])){echo $_POST['supplier1_sno'];}else{if(isset($_GET['id'])){echo $payment['cust_id'];}} ?>">
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Description</label>
										<input name="description1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['description1'];}else{if(isset($_GET['id'])){echo $payment['description'];}} ?>" class="form-control" id="description1" tabindex="<?php echo $tab++;?>" onFocus="set_current(1); calc_total();" onBlur="add_payment_row(1)"/>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group mx-4">
										<label>Amount</label>
										<input name="amount1" type="text" value="<?php if(isset($_POST['sale_date'])){echo $_POST['amount1'];}else{if(isset($_GET['id'])){echo $payment['amount'];}} ?>" class="form-control" id="amount1" tabindex="<?php echo $tab++;?>" onFocus="set_current(1);" onBlur="add_payment_row(1); calc_total();"/>
									</div>
								</div>

							</div>
						</div>
						<div class="row">
							<div class="col-12 ">
								<div class="float-right col-3 alert alert-primary my-3">Total : <span id="total_value"></span></div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4 mx-4">
								<input id="saveForm" name="saveForm" class="btn btn-success" type="submit" value="Submit" tabindex="<?php echo $tab++; ?>">
        						<input type="hidden" name="edit_sno" value="<?php if(isset($_GET['id'])){echo $_GET['id'];}?>" />
        						<input type="text" name="id" id="id" value="1">
        						<input type="hidden" name="current" id="current" value="1">
        						<input type="hidden" name="total_value_hidden" id="total_value_hidden" value="">
							</div>
						</div>
						<div class="row my-3 px-4" id="insert_data">
							
						</div>
					</div>
			<?php
					break;
				}
			}
			?>
			</form> 
		</div>
	</div>



<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Create New Ledger</h5>
				<button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form name="create_ledger" id="create_ledger" action="scripts/billit_ajax.php?id=create_ledger" method="get"><p>Enter Details</p>
					<div class="row">
						<div class="col-md-6">
							<label>Company Name</label>
							<input id="cus_name" name="cus_name" tabindex="21" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>State</label>
							<select id="state" name="state" tabindex="22" class="form-control">
							<?php
							$sql = 'select * from general_settings where `desc`="state"';
							$default_state = mysqli_fetch_assoc(execute_query($sql));

							$sql = 'select * from billit_state_name';
							$res_state = execute_query($sql);
							while($row_state = mysqli_fetch_array($res_state)){
								echo '<option value="'.$row_state['state_code'].'" ';
								if(isset($_GET['id'])){
									if(strtoupper(trim($row_state['state_code']))==strtoupper(trim($ledger['state']))){
										echo ' selected="selected" ';
									}
								}
								else{
									if(strtoupper(trim($row_state['state_code']))==$default_state['rate']){
										echo ' selected="selected"';
									}
								}
								echo '>'.$row_state['indian_states'].'</option>';
							}
							?>
							</select></td>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label>Mobile</label>
							<input id="mobile" name="mobile" tabindex="23" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>GSTIN</label>
							<input id="tin" name="tin" tabindex="24" value="" type="text" class="form-control">
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label>Address</label>
							<input id="address" name="address" tabindex="25" value="" type="text" class="form-control">
						</div>
						<div class="col-md-6">
							<label>Address 2</label>
							<input id="add_2" name="add_2" tabindex="26" value="" type="text" class="form-control">
						</div>
					</div>
				</form>				
			</div>
			<div class="modal-footer">
				<div class="col-md-12 text-center" id="ajax_loader" style="display:none;"><img src="images/loading_transparent.gif"></div>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onClick="create_new();">Save changes</button>
			</div>
		</div>
	</div>
</div>
<?php page_footer_start(); ?>		
<script>
	function create_new(){
		var cus_name = encodeURIComponent($('#cus_name').val());
		var state = $('#state').val();
		var mobile = $('#mobile').val();
		var tin = $('#tin').val();
		var address = $('#address').val();
		var address2 = $('#add_2').val();
		var finaldata = '';
		finaldata = 'parent=31&term=t&cus_name='+cus_name+'&state='+state+'&mobile='+mobile+'&tin='+tin+'&address='+address+'&add_2='+address2;
		var form = $('#create_ledger');
		document.getElementById('ajax_loader').style.display = 'block';
		$.ajax({
		  type: "GET",
		  url: form.attr('action'),
		  data: finaldata,
		  cache: false,
		  //success: result
		  complete: function(response){
			  document.getElementById('ajax_loader').style.display = 'none';
			  $('#createModal').modal('hide');
			  $("#supplier").val(decodeURIComponent(cus_name));
			  $("#supplier1_sno").val(response.responseText);
			  var details = address+", "+address2+". <br/><b>M:</b> "+mobile+"<br/><b>GSTIN:</b> "+tin;
			
			$('#supplier_data').html(details);
		  }
		});
	}
	
	function edit_client(){
		var id = $("#supplier1_sno").val();
		if(id==''){
			alert('Please select a customer.');
			return;
		}
		else{
			window.open("ledgers.php?id="+id);
		}
	}	
	
	function add_payment_row(id){
		var tot=0;
		
		var max_id = $("#id").val();
		var current = $("#current").val();
		
		if(max_id==current){
			if($("#supplier"+current+"_sno").val()!=''){
				id++;
				var txt = '<div class="row px-3"><div class="col-md-3"><div class="form-group mx-2"><label>Party/Ledger Type</label><select class="form-control" name="supplier'+id+'_type" id="supplier'+id+'_type" tabindex="1"><option value="">--Select--</option><option value="vendor">CONTRACTOR</option><option value="uprnss_architect">ARCHITECT/STRUCTURAL ARCHITECT</option></select></div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Party/Leder Name</label>		<input name="supplier'+id+'" type="text" value=""  class="form-control" tabindex="" id="supplier" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+')"/> &nbsp;		<input type="text" id="supplier'+id+'_sno" name="supplier'+id+'_sno" value="">	</div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Description</label><input name="description'+id+'" type="text" value="" class="form-control" id="description'+id+'" tabindex="" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+')"/>	</div></div><div class="col-md-3">	<div class="form-group mx-4"><label>Amount</label><input name="amount'+id+'" type="text" value="" class="form-control" id="amount'+id+'" tabindex="" onFocus="set_current('+id+');" onBlur="add_payment_row('+id+');  calc_total();"/></div></div></div>';
				$(".legend").append(txt);
				$("#id").val(id);
			}
		}
		
		
	}
	
	function calc_total(){
		var tot=0;
		
		var max_id = $("#id").val();
		for(i=1; i<=max_id;i++){
			
			console.log(max_id+"A: "+i+">>"+$("#amount"+i).val()+" >> "+document.getElementById("amount"+i).value);
			var amt = parseFloat($("#amount"+i).val());
			if(!amt){
				amt = 0;
			}
			tot += amt;
		}
		$("#total_value").html(tot);
		$("#total_value_hidden").val(tot);
		
	}
	
	function set_current(id){
		$("#current").val(id);
	}
		
		
document.getElementById("transfer_voucher").addEventListener("change", function () {
    let id = this.value;

    if (id !== "") {
        fetch("transfer_voucher_details.php?id=" + id)
        .then(response => response.json())
        .then(data => {
			 console.log(data);
            if (!data.error) {
                document.getElementById("challan").value = data.voucher_no;

                const accountSelect = document.getElementById("account");
                let found = false;
                for (let i = 0; i < accountSelect.options.length; i++) {
                    if (accountSelect.options[i].text.trim() === data.account_name.trim()) {
                        accountSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }

                document.getElementById("supplier").value = data.party_name;
                document.getElementById("amount1").value = data.amount;

            } else {
                alert(data.error);
            }
        });
    } else {
        document.getElementById("challan").value = "";
        document.getElementById("account").selectedIndex = 0;
        document.getElementById("supplier").value = "";
        document.getElementById("amount1").value = "";
    }
});



</script>
<?php page_footer_end();?>