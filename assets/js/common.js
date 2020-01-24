$(function(){

    /*======================
      送信ボタンクリック
     ======================*/
    $('#send').click(function() {
      // POSTメソッドで送るデータを定義
      var data = {
        'request' : $('#request').val()
      };

      $.ajax({
        type: "POST",
        url: "../ajax.php",
        data: data
      }).done(function (data) {
        // 通信成功
      }).fail(function (data) {
        // 通信失敗
        alert('Error : ' + errorThrown);
      });      

      // サブミット後のページリロード中止
      return false;
    });
  
    /*======================
      見積・請求情DB登録Ajax
     ======================*/
      //var dt = new Date();
//      var y = dt.getFullYear();
//      var m = ("00" + (dt.getMonth()+1)).slice(-2);
//      var d = ("00" + dt.getDate()).slice(-2);
//      var result = y + "/" + m + "/" + d;
//      
//      var data02 = {
//        'date' : result,
//      };
//
//      $.ajax({
//        type: "POST",
//        url: "../ajax_info.php",
//        data: data02,
//        dataType: 'html',
//        beforeSend: function(){
//          $('.loading').removeClass('hide');
//        }
//      }).done(function (html) {
//        $('.loading').addClass('hide'); 
//        //$('#result').before(html);
//      }).fail(function (html) {
//        // 通信失敗
//        alert('Error : ' + errorThrown);
//      });      
//
//      // サブミット後のページリロード中止
//      return false;
//  
  });