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

//セッション開始
session_start();

//セッションの中身をすべて削除
$_SESSION = array();

//セッションを破壊
session_destroy();

?>

<p>ログアウトしました。</p>
<a href="signup.php">ログインへ</a>


</div>
</body>
</html>

