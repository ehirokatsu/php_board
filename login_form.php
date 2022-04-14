<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>ログイン</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<br>
<br>
<div class="center">
<H1>
<font color="blue">ログイン</font>
</H1>


<?php

require_once( dirname(__FILE__). '/env.inc');

//データベース関数を使用する
require_once( dirname(__FILE__). '/DbLib.php');
$dbLib = new DbLib();



$msg = '';

//入力されたメールアドレスとパスワードがNULL
if (!isset($_POST['user_mail']) || !isset($_POST['user_pass'])) {

    $msg = 'メールアドレスまたはパスワードがNULLです。';
    
//入力されたメールアドレスとパスワードが空白
} elseif (empty($_POST['user_mail']) || empty($_POST['user_pass'])) {

    $msg = 'メールアドレスまたはパスワードが空白です。';

//入力されたメールアドレスとパスワードが文字列以外    
} elseif (!is_string($_POST['user_mail']) || !is_string($_POST['user_pass'])) { 

    $msg = 'メールアドレスまたはパスワードの値が不正です。';
        
} else {

    session_start();
    

    try {
    
        //メールアドレスからusersテーブルを検索した結果を取得する
        $result_all = $dbLib->getUsersFromMail($_POST['user_mail']);
        
        //入力されたメールアドレスに一致する行が存在する場合
        if (!empty($result_all)) {
        
            foreach ($result_all as $result) {
                //入力されたパスワードが正しい場合
                if (password_verify($_POST['user_pass'], $result['user_pass'])) {
                    //DBのユーザー情報をセッションに保存
                    $_SESSION['user_id'] = $result['user_id'];
                    $_SESSION['user_name'] = $result['user_name'];
                    
                    $msg = 'ログインしました。';
                    
                    //掲示板に遷移する
                    header("Location:index.php");
                    exit();
                    
                } else {
                    $msg = 'パスワードが間違っています。';
                }
            }
        } else {
            $msg = 'メールアドレスが間違っています。';
        }
        
    } catch (PDOException $e) {
        $msg = $e->getMessage();
    }


}
echo $msg;

?>


<br>
<br>
<form action="login_form.php" method="post" name="login">
    <label for="user_mail">メールアドレス：</label>
    <input type="text" id="user_mail" name="user_mail">
    <br>
    <br>
    <label for="user_pass">パスワード：</label>
    <input type="text" id="user_pass" name="user_pass">
    <br>
    <br>
    <input type="submit" value="ログイン">
    <br>
</form>



</div>
</body>
</html>

