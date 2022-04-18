<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>サインアップ</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1>
サインアップ
</h1>

<form action="register.php" method="post" name="register" enctype="multipart/form-data">
<div class="container_signup">
    <div class="item_signup_left">
        <label for="user_name">名前：</label>
    </div>
    <div class="item_signup_left">
        <input type="text" id="user_name" name="user_name" required>
    </div>
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
    <div class="item_signup_left">
        <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
        プロフィール画像(1Mbyte以内)：
    </div>
    <div class="item_signup">
        <input type="file" name="yourfile">
    </div>
    <div class="item_signup">
        <input type="submit" value="新規登録">
    </div>

</form>
<div class="item_signup">
<p>すでに登録済みの方は<a href="login_form.php?">こちら</a></p>
</div>

</body>
</html>

