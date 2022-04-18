<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>新規登録</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="center">
<H1>
<font color="blue">新規登録</font>
</H1>


<?php

//共通変数を使用する
require_once( dirname(__FILE__). '/env.inc');

//画像関数を使用する
require_once( dirname(__FILE__). '/ImgLib.php');
$imgLib = new ImgLib();

//データベース関数を使用する
require_once( dirname(__FILE__). '/DbLib.php');
$dbLib = new DbLib();

//プロフィール画像をアップロードしていない場合の初期化用
$image_id = 0;

//エラーメッセージ用の変数初期化
$msg = "";
$link = "";

//POST以外であればNG
if (!isset($_SERVER['REQUEST_METHOD'])
 || $_SERVER['REQUEST_METHOD'] !== "POST") {
 
    $msg = 'アップロードが失敗しました。';
    
//入力されたメールアドレスとパスワードが空白はNG
} else if (empty($_POST['user_name'])
 || empty($_POST['user_mail'])
 || empty($_POST['user_pass'])) {

    $msg = '名前、メールアドレスまたはパスワードが空白です。';

//入力されたメールアドレスとパスワードが文字列以外はNG
} elseif (!is_string($_POST['user_name'])
       || !is_string($_POST['user_mail'])
       || !is_string($_POST['user_pass'])) { 

    $msg = '名前、メールアドレスまたはパスワードの値が不正です。';
        
//アップロード画像の保管用ディレクトリ作成に失敗すればNG     
} elseif (!is_dir($folder_files) && !mkdir($folder_files)) {

    $msg = '保管用ディレクトリを作ることができません。';
    
} else {

    //アップロードされた画像をDBに登録して画像IDを取得する
    $image_id = $imgLib->registerImg($_FILES);

    //入力パスワードをハッシュ化する
    $user_pass = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);

    try {

        //メールアドレスからusersテーブルを検索した結果を取得する
        $result_all = $dbLib->getUsersFromMail($_POST['user_mail']);
        
        //入力されたメールアドレスに一致する行が存在する場合
        if (!empty($result_all)) {
        
            $msg = '同じメールアドレスが存在します。';
            $link = '<a href="signup.php">戻る</a>';
            
        } else {
        
            //データベース接続処理
            $dbh = $dbLib->connectDb();
            
            //メールアドレスが登録されていなければDBにinsertする 
            $sql = "INSERT INTO users(user_name, user_pass, user_mail, user_image_id)
                    VALUES (:user_name, :user_pass, :user_mail, :user_image_id)";
                    
            $stmt = $dbh->prepare($sql);
            
            $stmt->bindValue(':user_name', $_POST['user_name'], PDO::PARAM_STR);
            $stmt->bindValue(':user_pass', $user_pass, PDO::PARAM_STR);
            $stmt->bindValue(':user_mail', $_POST['user_mail'], PDO::PARAM_STR);
            $stmt->bindValue(':user_image_id', $image_id, PDO::PARAM_INT);

            $stmt->execute();
            
            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
            $msg = '会員登録が完了しました';
            $link = '<a href="login_form.php">ログインページ</a>';

        }
        
    } catch (PDOException $e) {
        $msg = $e->getMessage();
    }
}
?>

<h1><?php echo $msg; ?></h1>
<?php echo $link; ?>
</div>
</body>
</html>

