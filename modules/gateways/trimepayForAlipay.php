<?php
//$partner         = "";        //合作伙伴ID
//$security_code   = "";        //安全检验码
//$seller_email    = "";        //卖家支付宝帐户

function trimepayForAlipay_config() {
    $configarray = [
    	"FriendlyName" 		=> ["Type" => "System", "Value"=>"支付宝（Trimepay）"],
		"appId" 			=> ["FriendlyName" => "AppID", "Type" => "text", "Size" => "32", ],
		"appSecret" 		=> ["FriendlyName" => "AppSecret", "Type" => "text", "Size" => "32", ],
		"qrcode" 			=> ["FriendlyName" => "启动二维码模式", "Type" => "yesno", "Size" => "50", "Description" => "是否显示二维码",],
    ];
	return $configarray;
}

function trimepayForAlipay_refund($params) {
    if(!class_exists('Trimepay')) {
        include("trimepayForAlipay/class.php");
	}
	
	$trimepay = new Trimepay($params['appId'], $params['appSecret']);
	$response = $trimepay->refund($params['transid']);
	// file_put_contents('./test.log', json_encode($response)."\r\n", FILE_APPEND);
	if($response['code'] !== 0) {
	    return [
	          'status' => 'declined',
	          'rawdata' => json_encode($response)
	    ];
	}
	return [
		'status' => 'success',
		'rawdata' => json_encode($response),
		'transid' => $response['data'],
		'fees' => $params['amount'],
	];

}

function trimepayForAlipay_link($params) {
    if(!class_exists('Trimepay')) {
        include("trimepayForAlipay/class.php");
    }
	$trimepay = new Trimepay($params['appId'], $params['appSecret']);
	$systemurl = $params['systemurl'];
	$payData = [
		'appId' => $params['appId'],
		'appSecret' => $params['appSecret'],
		'merchantTradeNo' => $params['invoiceid'],
		'totalFee' => $params['amount'] * 100,
		'notifyUrl' => $systemurl."/modules/gateways/trimepayForAlipay/notify.php",
		'returnUrl' => $systemurl."viewinvoice.php?id=".$params['invoiceid'],
	];

	if($params['qrcode']) {
		$payData['payType'] = 'ALIPAY_QR';
		$signData = $trimepay->prepareSign($payData);
		$payData['sign'] = $trimepay->sign($signData);
		$response = $trimepay->post($payData);
		$qcodelink = $response['data'];
	}else{
		$payData['payType'] = stristr($_SERVER['HTTP_USER_AGENT'], 'mobile')?'ALIPAY_WAP':'ALIPAY_WEB';
		$signData = $trimepay->prepareSign($payData);
		$payData['sign'] = $trimepay->sign($signData);
		$response = $trimepay->post($payData);
		$webpaylink = $response['data'];
	}
	
	$code = '<div class="alipay" style="max-width: 230px;margin: 0 auto">';
	if ($params['qrcode']) {
    	$code = $code . '<div id="alipayimg" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;margin-bottom: 5px;"><img src="https://www.zhihu.com/qrcode?url='.$qcodelink.'" style="transform: scale(.9);width: 100%;height: 100%;"></img></div><!--微信支付ajax跳转-->
        	<a href="javascript:;" id="alipayDiv" class="btn btn-info btn-block">使用支付宝扫码付款</a></div>';
	}else{
    	$code_ajax = '<a href="'.$webpaylink.'" target="_blank" id="alipayDiv" class="btn btn-info btn-block">前往支付宝进行支付</a></div>';
    	$code = $code.$code_ajax;
	}
	if (stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
		return $code.'<script>
		//设置每隔 5000 毫秒执行一次 load() 方法
		setInterval(function(){load()}, 5000);
		function load(){
			var xmlhttp;
			if (window.XMLHttpRequest){
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
			}else{
				// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function(){
				if (xmlhttp.readyState==4 && xmlhttp.status==200){
					trade_state=xmlhttp.responseText;
					if(trade_state=="SUCCESS"){
						document.getElementById("alipayimg").style.display="none";
						document.getElementById("alipayDiv").innerHTML="支付成功";
						window.location.reload()
					}
				}
			}
			//invoice_status.php 文件返回订单状态，通过订单状态确定支付状态
			xmlhttp.open("get","'.$systemurl.'/modules/gateways/trimepayForAlipay/query.php?invoiceid='.$params['invoiceid'].'",true);
			//下面这句话必须有
			//把标签/值对添加到要发送的头文件。
			//xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			//xmlhttp.send("out_trade_no=002111");
			xmlhttp.send();
		}
	</script>';
	} else {
		return '<img style="width: 150px" src="'.$systemurl.'/modules/gateways/trimepayForAlipay/alipay.png" alt="支付宝支付" />';
	}
}
?>
