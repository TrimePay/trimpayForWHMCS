<?php
# 异步返回页面
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

if(!class_exists('Trimepay')) {
    include("./class.php");
}

function  log_result($word) {
	$fp = fopen("./alipay_log.txt","a");	
	flock($fp, LOCK_EX) ;
	fwrite($fp,$word."：执行日期：".strftime("%Y%m%d%H%I%S",time())."\t\n");
	flock($fp, LOCK_UN); 
	fclose($fp);
}
// log_result(http_build_query($_REQUEST));
$GATEWAY 					= getGatewayVariables('trimepayForAlipay');
$url						= $GATEWAY['systemurl'];
$companyname 				= $GATEWAY['companyname'];
$currency					= $GATEWAY['currency'];
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$appId						= $GATEWAY['appId'];
$appSecret					= $GATEWAY['appSecret'];
$trimepay                   = new Trimepay($appId, $appSecret);
$cbData						= [
	'payStatus' 				=> $_REQUEST['payStatus'],
	'payFee'					=> $_REQUEST['payFee'],
	'callbackTradeNo' 			=> $_REQUEST['callbackTradeNo'],
	'payType'					=> $_REQUEST['payType'],
	'merchantTradeNo'			=> $_REQUEST['merchantTradeNo']
];
$strToSign = $trimepay->prepareSign($cbData);
$verify_result = $trimepay->verify($strToSign, $_REQUEST['sign']);
if(!$verify_result) { 
	logTransaction($GATEWAY["name"],$_GET,"Unsuccessful");
} else {
	if($cbData['payStatus']) {
		$invoiceId = $cbData['merchantTradeNo'];
		$transid = $cbData['callbackTradeNo'];
		$paymentAmount = $cbData['payFee'] / 100;
		$feeAmount = 0;

		//货币转换开始
		//获取支付货币种类
		$currencytype 	= \Illuminate\Database\Capsule\Manager::table('tblcurrencies')->where('id', $gatewayParams['convertto'])->first();
		
		//获取账单 用户ID
		$userinfo 	= \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoiceId)->first();
		
		//得到用户 货币种类
		$currency = getCurrency( $userinfo->userid );
		
		// 转换货币
		$paymentAmount = convertCurrency( $paymentAmount, $currencytype->id, $currency['id'] );
		// 货币转换结束
		checkCbTransID($transid);
		addInvoicePayment($invoiceId,$transid,$paymentAmount,$feeAmount,'trimepayForAlipay');
		logTransaction($GATEWAY["name"],$_POST,"Successful-A");
		echo 'SUCCESS';exit;
	}
}
echo 'FAIL'
?>