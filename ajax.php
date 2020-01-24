<?php
header("Content-type: text/plain; charset=UTF-8");

//class群は以下のファイルに記載
require "phpsc/common.php";

/*=====================
 ajax処理
 =====================*/
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
  // Ajaxリクエストの場合のみ処理
  if(isset($_POST['request'])){
    //DB登録やファイルへの書き込みなど
    if(empty($_POST['request'])){
      echo 'URLを入れてください';
    }else{
      $str = $_POST['request'];
      //URLを連想配列に分解
      $url = parse_url($str);
      //パラメータ部分をパース
      parse_str($url['query'], $parms);
      
      if(empty($parms['code'])){
        echo 'codeパラメータのついたURLではありません';
      }else{
        /*-------------------------------
        アクセストークンを発行・DB登録
        ---------------------------------*/
        $getMyToken = new MakeAcessToken($parms['code']);
        $getMyToken->getToken();
      }
    }
  } else {
    echo '失敗しました';
  }
}
