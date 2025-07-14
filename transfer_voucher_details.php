<?php
include("scripts/settings.php");

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $pns = mysqli_fetch_assoc(execute_query("SELECT * FROM project_note_sheet WHERE id = '$id'"));
    if (!$pns) {
        echo json_encode(['error' => 'PNS Not Found']);
        exit;
    }

    $project_id = $pns['project_id'];
    $amount = $pns['total_rcv_amt'];

    $vendor = mysqli_fetch_assoc(execute_query("
        SELECT vendor.firm_name, tender_allotment.bank_name 
        FROM tender_allotment 
        LEFT JOIN vendor ON vendor.sno = tender_allotment.project_awarded_to 
        WHERE tender_allotment.project_id = '$project_id' 
        AND tender_allotment.status != 5
    "));

    if (!$vendor) {
        echo json_encode(['error' => 'Vendor Not Found']);
        exit;
    }

    $response = [
        'voucher_no' => 'TV-' . $id,
        'firm_name' => $vendor['firm_name'],
        'bank_id' => $vendor['bank_name'],
        'amount' => $amount,
    ];

    echo json_encode($response);
}
?>