<?php

//共通変数を使用する
require_once( dirname(__FILE__). '/define.inc');
require_once( dirname(__FILE__). '/env.inc');

//セッション関数を使用する
require_once( dirname(__FILE__). '/SessionLib.php');
$sessionLib = new SessionLib();

//メッセージ出力用初期化
$outputMessage = ERROR_POST;

//セッションを開始
$sessionLib->mySession_start();

//ワンタイムチケットを発行してフォームにhiddenでセット
$ticket = md5(uniqid(rand(), true));
output_add_rewrite_var('ticket', $ticket);

//セッション変数に格納する
$_SESSION['ticket'] = $ticket;

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
<br>
<form action="index.php" method="post" name="login">

    <div class="item_signup_left">
        <label for="user_mail">メールアドレス：</label>
    </div>
    <div class="item_signup_left">
        <input type="text" id="user_mail" name="user_mail" required>
    </div>
    <div class="item_signup_left">
        <label for="user_pass">パスワード：</label>
    </div>
    <div class="item_signup_left">
        <input type="text" id="user_pass" name="user_pass" required>
    </div>
    <br>
    <div class="item_signup_big">
        <input type="submit" value="ログイン">
    </div>
</form>

</div>

</body>
</html>

