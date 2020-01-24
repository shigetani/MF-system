<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>トークン発行 MF見積もり請求システム</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="/js/common.js"></script>
</head>

<body>
  <div class="top-section-01">
    <header>
    </header>
    
    <!---------------------【コンテンツエリア開始】--------------------->  
    <article id="contents-area">
      <section>
        <div class="top-section-01">
          <div class="top-section-01__inner">
            <!-- ★.blocks-01★ -->
            <div class="blocks-01">
              <div class="blocks-01__inner">
                
                <div class="form">
                  <form method="post" onsubmit="return false;">
                    <p>codeが付加されたURLを入力 ※月1回<br>
                    <input type="text" name="codeUrl" id="request"></p>
                    <p><input type="submit" value="アクセストークンを発行" id="send"></p>
                  </form>
                </div> 
                
              </div>
            </div>
            <!-- ★.blocks-02★ -->
            <!--<div class="blocks-02">
              <div class="blocks-02__inner">
                <div class="blocks-02__left">
                </div>
                <div class="blocks-02__right">
                </div>
              </div>
            </div>-->
          </div>
        </div>
      </section>
    </article>
    <!---------------------【コンテンツエリア終了】--------------------->
    <footer>
    </footer>
  </div>
</body>
  
</html>
