<?php
header("Content-type:text/html; charset=utf-8");
include_once("../../../database/mysql.config.php");
include_once("../moneyfunc.php");

#获取第三方资料(非必要不更动)
$pay_type = $_REQUEST['pay_type'];
$params = array(':pay_type' => $pay_type);
$sql = "select t.pay_name,t.mer_id,t.mer_key,t.mer_account,t.pay_type,t.pay_domain,t1.wy_returnUrl,t1.wx_returnUrl,t1.zfb_returnUrl,t1.wy_synUrl,t1.wx_synUrl,t1.zfb_synUrl from pay_set t left join pay_list t1 on t1.pay_name=t.pay_name where t.pay_type=:pay_type";
$stmt = $mydata1_db->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch();
$pay_mid = $row['mer_id'];//商户号
$pay_mkey = $row['mer_key'];//商戶私钥
$pay_account = $row['mer_account'];
$return_url = $row['pay_domain'] . $row['wx_returnUrl'];//return跳转地址
$merchant_url = $row['pay_domain'] . $row['wx_synUrl'];//notify回传地址
if ($pay_mid == "" || $pay_mkey == "") {
  echo "非法提交参数";
  exit;
}
#固定参数设置
$top_uid = $_REQUEST['top_uid'];
$order_no = getOrderNo();
$mymoney = number_format($_REQUEST['MOAmount'], 2, '.', '');

#第三方参数设置
$data = array(
  "parter" => $pay_mid, //商户ID
  "type" => '', //银行类型
  "value" => number_format($_REQUEST['MOAmount'], 2, '.', ''), //金额
  "orderid" => $order_no, //商户订单号
  "callbackurl" => $merchant_url, //下行异步通知地址
  //noarr
  "hrefbackurl" => $return_url, //上行同步通知地址
  "payerIp" => getClientIp(), //支付用户IP
  "attach" => '', //备注消息
  "sign" => '', //MD5签名
);

#变更参数设置

$form_url = 'http://pay.363pays.com/paybank.aspx';//提交地址

if (strstr($pay_type, "银联钱包")) {
  $scan = 'yl';
  $data['type'] = '1006';
  $bankname = $pay_type . "->银联钱包在线充值";
  $payType = $pay_type . "_yl";
} elseif (strstr($pay_type, "银联快捷")) {
  $scan = 'ylkj';
  $data['type'] = '1008';
  $bankname = $pay_type . "->银联快捷在线充值";
  $payType = $pay_type . "_ylkj";
} else {
  $scan = 'wy';
  $data['type'] = $_REQUEST['bank_code'];
  $bankname = $pay_type . "->网银在线充值";
  $payType = $pay_type . "_wy";
}

#新增至资料库，確認訂單有無重複， function在 moneyfunc.php裡(非必要不更动)
$result_insert = insert_online_order($_REQUEST['S_Name'], $order_no, $mymoney, $bankname, $payType, $top_uid);
if ($result_insert == -1) {
  echo "会员信息不存在，无法支付，请重新登录网站进行支付！";
  exit;
} else if ($result_insert == -2) {
  echo "订单号已存在，请返回支付页面重新支付";
  exit;
}

#签名排列，可自行组字串或使用http_build_query($array)
// ksort($data);
$noarr = array('hrefbackurl', 'payerIp', 'attach', 'sign');//不加入签名的array key值
$signtext = '';
foreach ($data as $arr_key => $arr_val) {
  if (!in_array($arr_key, $noarr) && (!empty($arr_val) || $arr_val === 0 || $arr_val === '0')) {
    $signtext .= $arr_key . '=' . $arr_val . '&';
  }
}
$signtext = substr($signtext, 0, -1) . $pay_mkey;
$sign = (md5($signtext));
$data['sign'] = $sign;
$data_str = http_build_query($data);

#跳轉方法
?>
<html>
  <head>
    <title>跳转......</title>
    <meta http-equiv="content-Type" content="text/html; charset=utf-8" />
  </head>
  <body>
  <form method="post" id="frm1" action="<?php echo $form_url ?>" target="_self">
      <p>正在为您跳转中，请稍候......</p>
        <?php foreach ($data as $arr_key => $arr_value) { ?>
          <input type="hidden" name="<?php echo $arr_key; ?>" value="<?php echo $arr_value; ?>" />
        <?php 
      } ?>
    </form>
    <script language="javascript">
      document.getElementById("frm1").submit();
    </script>
  </body>
</html>

