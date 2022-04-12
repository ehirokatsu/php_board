<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>サインアップ</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<br>
<br>
<div class="center">
<H1>
<font color="blue">サインアップ</font>
</H1>


<form action="register.php" method="post" name="register" enctype="multipart/form-data">
    <label for="user_name">名前：</label>
    <input type="text" id="user_name" name="user_name" required>
    <br>
    <br>
    <label for="user_mail">メールアドレス：</label>
    <input type="text" id="user_mail" name="user_mail" required>
    <br>
    <br>
    <label for="user_pass">パスワード：</label>
    <input type="text" id="user_pass" name="user_pass" required>
    <br>
    <br>
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
    新しいファイル：<input type="file" name="yourfile">
    (1M以内)<br>
    <input type="submit" value="新規登録">
    <br>
</form>

<p>すでに登録済みの方は<a href="login_form.php">こちら</a></p>


</div>
</body>
</html>

