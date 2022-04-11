<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>新規登録</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<br>
<br>
<div class="center">
<H1>
<font color="blue">新規登録</font>
</H1>


<?php

$msg = "";
$link = "";

//入力されたメールアドレスとパスワードがNULL
if (!isset($_POST['user_name'])
 || !isset($_POST['user_mail'])
 || !isset($_POST['user_pass'])) {

    $msg = '名前、メールアドレスまたはパスワードがNULLです。';
    
//入力されたメールアドレスとパスワードが空白
} elseif (empty($_POST['user_name'])
       || empty($_POST['user_mail'])
       || empty($_POST['user_pass'])) {

    $msg = '名前、メールアドレスまたはパスワードが空白です。';

//入力されたメールアドレスとパスワードが文字列以外    
} elseif (!is_string($_POST['user_name'])
       || !is_string($_POST['user_mail'])
       || !is_string($_POST['user_pass'])) { 

    $msg = '名前、メールアドレスまたはパスワードの値が不正です。';
        
} else {

    
    //入力パスワードをハッシュ化
    $user_pass = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);

    $dsn = "mysql:host=localhost; dbname=myboard";
    $db_username = "user1";
    $db_pass = "user1";

    try {

        /データベースに接続する
        $dbh = new PDO($dsn, $db_username, $db_pass);
        
        //エラーはCatch内で処理する
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        //サーバサイドのプリペアドステートメントを有効にする
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        //フォームに入力されたmailがすでに登録されていないかチェック
        $sql = "SELECT * FROM users WHERE user_mail = :mail";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':mail', $_POST['user_mail'], PDO::PARAM_STR);
        $stmt->execute();
        
        //入力されたメールアドレスに一致する行が存在する場合
        if ($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
            $msg = '同じメールアドレスが存在します。';
            $link = '<a href="signup.php">戻る</a>';
            
        } else {
            //登録されていなければinsert 
            $sql = "INSERT INTO users(user_name, user_pass, user_mail)
                    VALUES (:name, :pass, :mail)";
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':name', $_POST['user_name'], PDO::PARAM_STR);
            $stmt->bindValue(':pass', $user_pass, PDO::PARAM_STR);
            $stmt->bindValue(':mail', $_POST['user_mail'], PDO::PARAM_STR);
            $stmt->execute();
            
            $msg = '会員登録が完了しました';
            $link = '<a href="login_form.php">ログインページ</a>';

        }
    } catch (PDOException $e) {
        $msg = $e->getMessage();
    }
}

?>

<h1><?php echo $msg; ?></h1><!--メッセージの出力-->
<?php echo $link; ?>


</div>
</body>
</html>
