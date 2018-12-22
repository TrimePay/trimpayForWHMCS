<?php
//$partner         = "";        //合作伙伴ID
//$security_code   = "";        //安全检验码
//$seller_email    = "";        //卖家支付宝帐户

function trimepayForWepay_config() {
    $configarray = [
    	"FriendlyName" 		=> ["Type" => "System", "Value"=>"微信支付（Trimepay）"],
		"appId" 			=> ["FriendlyName" => "AppID", "Type" => "text", "Size" => "32", ],
		"appSecret" 		=> ["FriendlyName" => "AppSecret", "Type" => "text", "Size" => "32", ]
    ];
	return $configarray;
}

function trimepayForWepay_refund($params) {
    if(!class_exists('Trimepay')) {
        include("trimepayForWepay/class.php");
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

function trimepayForWepay_link($params) {
    if(!class_exists('Trimepay')) {
        include("trimepayForWepay/class.php");
    }
	$trimepay = new Trimepay($params['appId'], $params['appSecret']);
	$systemurl = $params['systemurl'];
	$payData = [
		'appId' => $params['appId'],
		'merchantTradeNo' => $params['invoiceid'],
		'totalFee' => $params['amount'] * 100,
		'notifyUrl' => $systemurl."/modules/gateways/trimepayForWepay/notify.php",
		'returnUrl' => $systemurl."viewinvoice.php?id=".$params['invoiceid'],
	];

	
	$payData['payType'] = $payData['payType'] = stristr($_SERVER['HTTP_USER_AGENT'], 'mobile')?'WEPAY_JSAPI':'WEPAY_QR';;
	$signData = $trimepay->prepareSign($payData);
	$payData['sign'] = $trimepay->sign($signData);
	if(stristr($_SERVER['HTTP_USER_AGENT'], 'mobile')){
		$qcodelink = urlencode("http://cashier.hlxpay.com/#/wepay/jsapi?payData=".base64_encode(json_encode($payData)));
	}else{
		$response = $trimepay->post($payData);
		$qcodelink = $response['data'];
	}
	$code = '<div class="alipay" style="max-width: 230px;margin: 0 auto">';
	$code = $code . '<div id="alipayimg" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;margin-bottom: 5px;"><img src="https://www.zhihu.com/qrcode?url='.$qcodelink.'" style="transform: scale(.9);width: 100%;height: 100%;"></img></div><!--微信支付ajax跳转-->
        	<a href="javascript:;" id="alipayDiv" class="btn btn-success btn-block">使用微信扫码付款</a></div>';
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
						//延迟 2 秒执行 tz() 方法
						setTimeout(function(){tz()}, 5000);
						function tz(){
							window.location.href="'.$systemurl.'/viewinvoice.php?id='.$params['invoiceid'].'";
						}
					}
				}
			}
			//invoice_status.php 文件返回订单状态，通过订单状态确定支付状态
			xmlhttp.open("get","'.$systemurl.'/modules/gateways/trimepayForWepay/query.php?invoiceid='.$params['invoiceid'].'",true);
			//下面这句话必须有
			//把标签/值对添加到要发送的头文件。
			//xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			//xmlhttp.send("out_trade_no=002111");
			xmlhttp.send();
		}
	</script>';
	} else {
		return '<img style="width: 150px" src="'.$systemurl.'/modules/gateways/trimepayForWepay/wepay.png" alt="微信支付" />';
	}
}
?>
