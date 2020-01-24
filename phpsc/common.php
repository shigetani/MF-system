<?php 
/*=================
  MakeAcessToken
 ==================*/
class MakeAcessToken {
  //プロパティ
  public $client_id;
  public $client_secret;
  public $scope;
  public $redirect_uri;
  public $code;
  public $erFlag;

  //コンストラクタ
  public function __construct($code) {
    $this->client_id = "6ee65e97f2f144488452f61c5406869041708752050288dcb86d273e8aef0127";
    $this->client_secret = "02f827b737d1fb2aa1440a72f661885d375e6c27521874ce5a428ae16cd63bed";
    $this->scope = "read";
    $this->redirect_uri = "https://mf-system-sc.net/mf-api.php";
    $this->code = $code;
    $this->erFlag = false;   
  }
 
  /*---------------------------------------
  getToken：トークンを取得・DB登録メソッド
  -----------------------------------------*/
  public function getToken() {
    
    $data = array(
      'client_id'=> $this->client_id,
      'client_secret'=> $this->client_secret,
      'redirect_uri'=> $this->redirect_uri,
      'grant_type'=> 'authorization_code',
      'code'=> $this->code
    );
    $data_json = json_encode($data);
        
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'https://invoice.moneyforward.com/oauth/token');
    $result = curl_exec($ch);
        
    if (curl_errno($ch)) {
      echo 'Error:' . curl_error($ch);
      $erFlag = true;
    }
    curl_close($ch); 
        
    if($erFlag == false){
      echo 'success:' . $result;
      $result = json_decode( $result );
          
      //取得したアクセストークン情報をDBへ登録
      try {
        // PDOインスタンスを生成
        $dbh = new PDO('mysql:host=localhost;dbname=MF-system-sc;charset=utf8','root','root');
 
        // INSERT文を変数に格納
        $sql = "INSERT INTO accessInfo (access_token, token_type, expires_in, refresh_token, access_scope, created_at, registration_date) VALUES (:access_token, :token_type, :expires_in, :refresh_token, :access_scope, :created_at, :registration_date)";
        // 挿入する値は空のまま、SQL実行の準備をする
        $stmt = $dbh->prepare($sql);
            
        $access_token = $result->access_token;
        $token_type = $result->token_type;
        $expires_in = $result->expires_in;
        $refresh_token = $result->refresh_token;
        $access_scope = $result->scope;
        $created_at = $result->created_at;
        $registration_date = date("Y/m/d");
            
        // 挿入する値が入った変数をexecuteにセットしてSQLを実行            
        $stmt->bindParam(':access_token', $access_token, PDO:: PARAM_STR);
        $stmt->bindParam(':token_type', $token_type, PDO:: PARAM_STR);
        $stmt->bindParam(':expires_in', $expires_in, PDO:: PARAM_STR);
        $stmt->bindParam(':refresh_token', $refresh_token, PDO:: PARAM_STR);
        $stmt->bindParam(':access_scope', $access_scope, PDO:: PARAM_STR);
        $stmt->bindParam(':created_at', $created_at, PDO:: PARAM_STR);
        $stmt->bindParam(':registration_date', $registration_date, PDO:: PARAM_STR);
        
        $stmt->execute();
        
        //var_dump($stmt->errorInfo());
        //echo '登録完了';
        
        //DB接続を解除
        $dbh = null;
      } catch (PDOException $e) {
        // エラー（例外）が発生した時の処理を記述
        echo 'データベースにアクセスできません' . $e->getMessage();
        exit;
      }
    }    
  }
}

/*=================
  dataAcess
 ==================*/
class dataAcess {
  //プロパティ
  public $date;

  //コンストラクタ
  public function __construct($date) {
    $this->date = $date;
    $this->tag1 = "billings";//請求書呼び出しタグ
    $this->tag2 = "quotes";//見積書呼び出しタグ
  }
  /*---------------------------------------
  MFの見積書情報を取得・DB登録する
  -----------------------------------------*/
  public function mfEstimateInfo() {
    
      //DBからアクセストークン情報を取得
      try {
        // PDOインスタンスを生成
        $dbh = new PDO('mysql:host=localhost;dbname=MF-system-sc;charset=utf8','root','root');
 
        $sql = 'SHOW TABLES';
        $stmt = $dbh->query($sql);

        while ($result = $stmt->fetch(PDO::FETCH_NUM)){
          $table_names[] = $result[0];
        }

        $table_data = array();
        foreach ($table_names as $key => $val) {
          $sql2 = "SELECT * FROM $val;";
          $stmt2 = $dbh->query($sql2);
          $table_data[$val] = array();
          while ($result2 = $stmt2->fetch(PDO::FETCH_ASSOC)){
              foreach ($result2 as $key2 => $val2) {
                  $table_data[$val][$key2] = $val2;
              }
          }
        }
        
        $access_token = $table_data['accessinfo']['access_token'];
        $token_type = $table_data['accessinfo']['token_type'];
        $expires_in = $table_data['accessinfo']['expires_in'];
        $refresh_token = $table_data['accessinfo']['refresh_token'];
        $access_scope = $table_data['accessinfo']['access_scope'];
        $created_at = $table_data['accessinfo']['created_at'];
        $registration_date = $table_data['accessinfo']['registration_date'];
      
        //30日経ってないかチェックする処理、ダメだったらトークン再発行を促す
        
        //DB接続を解除
        $dbh = null;
        
        //----------------------
        //見積書情報取得のためID取得
        //----------------------
        $base_url = 'https://invoice.moneyforward.com/api/v2/';

        $header = [
          'Authorization: Bearer '.$access_token,
          'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $base_url.$this->tag2);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);

        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $result = json_decode($body, true); 
    
        curl_close($curl);

        $estimateIdArray = array();
        foreach($result["data"] as $key => $value){
          $estimateIdArray[] = $value["id"];
        }
        
        //----------------------
        //各見積書情報取得
        //----------------------
        $estimateArray = array();
        foreach($estimateIdArray as $key2 => $value2){
          $base_url = 'https://invoice.moneyforward.com/api/v2/';

          $header = [
            'Authorization: Bearer '.$access_token,
            'Content-Type: application/json',
          ];
          $curl = curl_init();

          curl_setopt($curl, CURLOPT_URL, $base_url.$this->tag2.'/'.$value2);
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HEADER, true);

          $response = curl_exec($curl);

          $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
          $header = substr($response, 0, $header_size);
          $body = substr($response, $header_size);
          $result = json_decode($body, true);
          
          $estimateArray[] = $result;
          curl_close($curl);
        }
        
        /*echo "★見積書";          
        echo "<pre>";
        var_dump($estimateArray);
        echo "</pre>";*/  
        
        foreach($estimateArray as $key3 => $value3 ){
          foreach($value3 as $key4 => $value4 ){
            if($value4["attributes"]["partner_name"] == null || $value4["attributes"]["title"] == null){
              continue;
            }else{

            //見積書情報をDBに登録
            try {
              // PDOインスタンスを生成
              $dbh = new PDO('mysql:host=localhost;dbname=MF-system-sc;charset=utf8','root','root');

              // INSERT文を変数に格納
              $sql = "INSERT INTO mf_EstimateData (id, quote_number, title, partner_name, quote_date, expired_date, operator_id, partner_id, department_id, member_id, pdf_url) VALUES (:id, :quote_number, :title, :partner_name, :quote_date, :expired_date, :operator_id, :partner_id, :department_id, :member_id, :pdf_url)";
              // 挿入する値は空のまま、SQL実行の準備をする
              $stmt = $dbh->prepare($sql);

              $id = $value4["id"];
              $quote_number = $value4["attributes"]["quote_number"];
              $title = $value4["attributes"]["title"];
              $partner_name = $value4["attributes"]["partner_name"];
              $quote_date = $value4["attributes"]["quote_date"];
              $expired_date = $value4["attributes"]["expired_date"];
              $operator_id = $value4["attributes"]["operator_id"];
              $partner_id = $value4["attributes"]["partner_id"];
              $department_id = $value4["attributes"]["department_id"];
              $member_id = $value4["attributes"]["member_id"];
              $pdf_url = $value4["attributes"]["pdf_url"];

              // 挿入する値が入った変数をexecuteにセットしてSQLを実行            
              $stmt->bindParam(':id', $id, PDO:: PARAM_STR);
              $stmt->bindParam(':quote_number', $quote_number, PDO:: PARAM_STR);
              $stmt->bindParam(':title', $title, PDO:: PARAM_STR);
              $stmt->bindParam(':partner_name', $partner_name, PDO:: PARAM_STR);
              $stmt->bindParam(':quote_date', $quote_date, PDO:: PARAM_STR);
              $stmt->bindParam(':expired_date', $expired_date, PDO:: PARAM_STR);
              $stmt->bindParam(':operator_id', $operator_id, PDO:: PARAM_STR);
              $stmt->bindParam(':partner_id', $partner_id, PDO:: PARAM_STR);
              $stmt->bindParam(':department_id', $department_id, PDO:: PARAM_STR);
              $stmt->bindParam(':member_id', $member_id, PDO:: PARAM_STR);
              $stmt->bindParam(':pdf_url', $pdf_url, PDO:: PARAM_STR);
              $stmt->execute();
              
              //var_dump($stmt->errorInfo());
              //echo '登録完了';
              
              //DB接続を解除
              $dbh = null;
            } catch (PDOException $e) {
              // エラー（例外）が発生した時の処理を記述
              echo 'データベースにアクセスできません' . $e->getMessage();
              exit;
            }              
            }
          }
        }
      } catch (PDOException $e) {
        // エラー（例外）が発生した時の処理を記述
        echo 'データベースにアクセスできません' . $e->getMessage();
        exit;
      }
  }
  
  /*---------------------------------------
  MFの請求書情報を取得・DB登録する
  -----------------------------------------*/
  public function mfInvoiceInfo() {
    
      //DBからアクセストークン情報を取得
      try {
        // PDOインスタンスを生成
        $dbh = new PDO('mysql:host=localhost;dbname=MF-system-sc;charset=utf8','root','root');
 
        $sql = 'SHOW TABLES';
        $stmt = $dbh->query($sql);

        while ($result = $stmt->fetch(PDO::FETCH_NUM)){
          $table_names[] = $result[0];
        }

        $table_data = array();
        foreach ($table_names as $key => $val) {
          $sql2 = "SELECT * FROM $val;";
          $stmt2 = $dbh->query($sql2);
          $table_data[$val] = array();
          while ($result2 = $stmt2->fetch(PDO::FETCH_ASSOC)){
              foreach ($result2 as $key2 => $val2) {
                  $table_data[$val][$key2] = $val2;
              }
          }
        }
        
        $access_token = $table_data['accessinfo']['access_token'];
        $token_type = $table_data['accessinfo']['token_type'];
        $expires_in = $table_data['accessinfo']['expires_in'];
        $refresh_token = $table_data['accessinfo']['refresh_token'];
        $access_scope = $table_data['accessinfo']['access_scope'];
        $created_at = $table_data['accessinfo']['created_at'];
        $registration_date = $table_data['accessinfo']['registration_date'];
      
        //30日経ってないかチェックする処理、ダメだったらトークン再発行を促す
        
        //DB接続を解除
        $dbh = null;
        
        //----------------------
        //請求書情報取得のためID取得
        //----------------------
        $base_url = 'https://invoice.moneyforward.com/api/v2/';

        $header = [
          'Authorization: Bearer '.$access_token,
          'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $base_url.$this->tag1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);

        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $result = json_decode($body, true); 
    
        curl_close($curl);

        $invoiceIdArray = array();
        foreach($result["data"] as $key => $value){
          $invoiceIdArray[] = $value["id"];
        }
        
        //----------------------
        //各請求書情報取得
        //----------------------
        $invoiceArray = array();
        foreach($invoiceIdArray as $key2 => $value2){
          $base_url = 'https://invoice.moneyforward.com/api/v2/';

          $header = [
            'Authorization: Bearer '.$access_token,
            'Content-Type: application/json',
          ];
          $curl = curl_init();

          curl_setopt($curl, CURLOPT_URL, $base_url.$this->tag1.'/'.$value2);
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HEADER, true);

          $response = curl_exec($curl);

          $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
          $header = substr($response, 0, $header_size);
          $body = substr($response, $header_size);
          $result = json_decode($body, true);
          
          $invoiceArray[] = $result;
          curl_close($curl);
          
        }
        
        /*echo "★請求書";        
        echo "<pre>";
        var_dump($invoiceArray);
        echo "</pre>";*/
        
        foreach($invoiceArray as $key3 => $value3 ){
          foreach($value3 as $key4 => $value4 ){
            if($value4["attributes"]["partner_name"] == null || $value4["attributes"]["title"] == null){
              continue;
            }else{

            //見積書情報をDBに登録
            try {
              // PDOインスタンスを生成
              $dbh = new PDO('mysql:host=localhost;dbname=MF-system-sc;charset=utf8','root','root');

              // INSERT文を変数に格納
              $sql = "INSERT INTO mf_InvoiceData (id, billing_number, title, partner_name, billing_date, operator_id, partner_id, department_id, member_id, due_date, sales_date, pdf_url) VALUES (:id, :billing_number, :title, :partner_name, :billing_date, :operator_id, :partner_id, :department_id, :member_id, :due_date, :sales_date, :pdf_url)";
              // 挿入する値は空のまま、SQL実行の準備をする
              $stmt = $dbh->prepare($sql);

              $id = $value4["id"];
              $billing_number = $value4["attributes"]["billing_number"];
              $title = $value4["attributes"]["title"];
              $partner_name = $value4["attributes"]["partner_name"];
              $billing_date = $value4["attributes"]["billing_date"];
              $operator_id = $value4["attributes"]["operator_id"];
              $partner_id = $value4["attributes"]["partner_id"];
              $department_id = $value4["attributes"]["department_id"];
              $member_id = $value4["attributes"]["member_id"];
              $due_date = $value4["attributes"]["due_date"];
              $sales_date = $value4["attributes"]["sales_date"];
              $pdf_url = $value4["attributes"]["pdf_url"];

              // 挿入する値が入った変数をexecuteにセットしてSQLを実行            
              $stmt->bindParam(':id', $id, PDO:: PARAM_STR);
              $stmt->bindParam(':billing_number', $billing_number, PDO:: PARAM_STR);
              $stmt->bindParam(':title', $title, PDO:: PARAM_STR);
              $stmt->bindParam(':partner_name', $partner_name, PDO:: PARAM_STR);
              $stmt->bindParam(':billing_date', $billing_date, PDO:: PARAM_STR);
              $stmt->bindParam(':operator_id', $operator_id, PDO:: PARAM_STR);
              $stmt->bindParam(':partner_id', $partner_id, PDO:: PARAM_STR);
              $stmt->bindParam(':department_id', $department_id, PDO:: PARAM_STR);
              $stmt->bindParam(':member_id', $member_id, PDO:: PARAM_STR);
              $stmt->bindParam(':due_date', $due_date, PDO:: PARAM_STR);
              $stmt->bindParam(':sales_date', $sales_date, PDO:: PARAM_STR);
              $stmt->bindParam(':pdf_url', $pdf_url, PDO:: PARAM_STR);
              $stmt->execute();
              
              //var_dump($stmt->errorInfo());
              //echo '登録完了';
              
              //DB接続を解除
              $dbh = null;
            } catch (PDOException $e) {
              // エラー（例外）が発生した時の処理を記述
              echo 'データベースにアクセスできません' . $e->getMessage();
              exit;
            }              

            }
          }
        }
      } catch (PDOException $e) {
        // エラー（例外）が発生した時の処理を記述
        echo 'データベースにアクセスできません' . $e->getMessage();
        exit;
      }
  }
  
}