<?php

//共通変数を使用する
require_once( dirname(__FILE__). '/define.inc');
require_once( dirname(__FILE__). '/env.inc');

//セッション関数を使用する
require_once( dirname(__FILE__). '/SessionLib.php');
$sessionLib = new SessionLib();

//エラーメッセージ用
$message = '';

if (isset($_GET['message']) && is_string($_GET['message'])) {

    $message = $_GET['message'];

}

//セッションを開始
$sessionLib->mySession_start();

//セッションの中身をすべて削除
$_SESSION = array();

//セッションを破壊
session_destroy();

?>


<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>ログアウト</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="center">
<H1>
ログアウト
</H1>

<?php
//エラー内容に応じてメッセージを表示する。
if ($message === ERROR_POST) {

    echo '不正にアクセスされました。<br>';

} elseif ($message === ERROR_NULL) {

    echo 'メールアドレスまたはパスワードが空白です。<br>';
    
} elseif ($message === ERROR_ILLEGAL) {

    echo 'メールアドレスまたはパスワードの値が不正です。<br>';

} elseif ($message === ERROR_MAIL) {

    echo 'メールアドレスが間違っています。<br>';
    
} elseif ($message === ERROR_PASS) {

    echo 'パスワードが間違っています。<br>';
    
} elseif ($message === ERROR_SESSION) {

    echo 'セッションエラーです。<br>';
   
} else {

    echo 'ログアウトしました。<br>';

}
?>

<a href="signup.php">ログインへ</a>


</div>
</body>
</html>

