<?php

//共通変数を使用する
require_once( dirname(__FILE__). '/define.inc');
require_once( dirname(__FILE__). '/env.inc');

//データベース関数を使用する
require_once( dirname(__FILE__). '/DbLib.php');
$dbLib = new DbLib();

//メッセージ出力用初期化
$outputMessage = ERROR_POST;


//入力されたメールアドレスとパスワードがNULLや空白   
if (empty($_POST['user_mail']) || empty($_POST['user_pass'])) {

    $outputMessage === ERROR_NULL;

//入力されたメールアドレスとパスワードが文字列以外    
} elseif (!is_string($_POST['user_mail']) || !is_string($_POST['user_pass'])) { 
    $outputMessage = ERROR_ILLEGA;
        
} else {

    //セッション開始
    session_start();

    try {
    
        //メールアドレスからusersテーブルを検索した結果を取得する
        $result_all = $dbLib->getUsersFromMail($_POST['user_mail']);

        //入力されたメールアドレスに一致する行が存在しない
        if (empty($result_all)) {
        
            $msg = 'メールアドレスが間違っています。';
            $outputMessage = ERROR_MAIL;
        
        } else {
        
            foreach ($result_all as $result) {
            
                //入力されたパスワードが一致しない
                if (!password_verify($_POST['user_pass'], $result['user_pass'])) {

                    $msg = 'パスワードが間違っています。';   
                    $outputMessage = ERROR_PASS;
                    
                } else {
                    
                    //DBのユーザー情報をセッションに保存
                    $_SESSION['user_id'] = $result['user_id'];
                    $_SESSION['user_name'] = $result['user_name'];

                    //掲示板に遷移する
                    header("Location:index.php");
                    exit();
                }
            }
        }
        
    } catch (PDOException $e) {
    
        $msg = $e->getMessage();
    }
    
}

?>


<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>ログイン</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<H1>
ログイン
</H1>
<div class="container_signup">
<div class="item">

<?php

//エラー内容に応じてメッセージを表示する。
if ($outputMessage === ERROR_NULL) {

    echo 'メールアドレスまたはパスワードが空白です。<br>';
    
} elseif ($outputMessage === ERROR_ILLEGAL) {

    echo 'メールアドレスまたはパスワードの値が不正です。<br>';

} elseif ($outputMessage === ERROR_MAIL) {

    echo 'メールアドレスが間違っています。<br>';
    
} elseif ($outputMessage === ERROR_PASS) {

    echo 'パスワードが間違っています。<br>';
    
} else {

}
?>
    
</div>
<br>
<form action="loginForm.php" method="post" name="login">

    <div class="item_signup_left">
        <label for="user_mail">メールアドレス：</label>
    </div>
    <div class="item_signup_left">
        <input type="text" id="user_mail" name="user_mail">
    </div>
    <div class="item_signup_left">
        <label for="user_pass">パスワード：</label>
    </div>
    <div class="item_signup_left">
        <input type="text" id="user_pass" name="user_pass">
    </div>
    <br>
    <div class="item_signup_big">
        <input type="submit" value="ログイン">
    </div>
</form>

</div>

</body>
</html>

