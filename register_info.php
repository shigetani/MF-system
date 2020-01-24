<?php
header("Content-type: text/plain; charset=UTF-8");

//class群は以下のファイルに記載
require "phpsc/common.php";

/*=====================
 DBに登録実行
 =====================*/
$today = date("Y/m/d H:i:s");

$getMymf = new dataAcess($today);

$getMymf->mfEstimateInfo();//見積情報をDBに登録するメソッド
$getMymf->mfInvoiceInfo();//請求情報をDBに登録するメソッド
