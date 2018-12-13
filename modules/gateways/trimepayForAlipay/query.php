<?php
require_once __DIR__ . '/../../../init.php';
use \Illuminate\Database\Capsule\Manager as Capsule;
$invoiceid = $_REQUEST['invoiceid'];
$ca = new WHMCS_ClientArea();
$userid = $ca->getUserID() ;
if($userid == 0){
    exit;
}
//echo $userid;
$query = Capsule::table('tblinvoices')->where('id', $invoiceid)->where('userid', $userid)->first();
if( $query ) {
    $status 		= $query->status;
    $paymentmethod 	= $query->paymentmethod;
}
if($status == "Paid"){
    echo "SUCCESS";
} else {
    echo "FAIL";
}