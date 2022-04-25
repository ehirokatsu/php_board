
<?php

//共通変数を使用する
require_once( dirname(__FILE__). '/define.inc');
require_once( dirname(__FILE__). '/env.inc');

//画像関数を使用する
require_once( dirname(__FILE__). '/ImgLib.php');
$imgLib = new ImgLib();

//データベース関数を使用する
require_once( dirname(__FILE__). '/DbLib.php');
$dbLib = new DbLib();

//プロフィール画像をアップロードしていない場合の初期化用
$imgId = 0;

//メッセージ出力用初期化
$outputMessage = ERROR_POST;

//メソッドがPOST以外であればNG
if (!isset($_SERVER['REQUEST_METHOD'])
 || $_SERVER['REQUEST_METHOD'] !== "POST"
) {

    $outputMessage = ERROR_POST;
    
//入力項目が空白はNG(NULLを含む)
} elseif (empty($_POST['user_name'])
       || empty($_POST['user_mail'])
       || empty($_POST['user_pass'])
       || empty($_FILES['yourfile']['tmp_name'])
) {

    $outputMessage = ERROR_NULL;

//入力されたメールアドレスとパスワードが文字列以外はNG
} elseif (!is_string($_POST['user_name'])
       || !is_string($_POST['user_mail'])
       || !is_string($_POST['user_pass'])
) { 

    $outputMessage = ERROR_ILLEGA;
        
//アップロード画像の保管用ディレクトリ作成に失敗すればNG     
} elseif (!is_dir($folder_files) && !mkdir($folder_files)) {

    $outputMessage = ERROR_DIR;
    
} else {

    //アップロードされた画像をDBに登録して画像IDを取得する
    $imgId = $imgLib->registerImg($_FILES);

    //入力パスワードをハッシュ化する
    $user_pass = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);

    try {

        //メールアドレスからusersテーブルを検索した結果を取得する
        $users = $dbLib->getUsersFromMail($_POST['user_mail']);
        
        //入力されたメールアドレスに一致する行が存在する場合
        if (!empty($users)) {
        
            $outputMessage = ERROR_MAIL;
            
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
            $stmt->bindValue(':user_image_id', $imgId, PDO::PARAM_INT);
            $stmt->execute();
            
            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);

            $outputMessage = CORRECT;

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
<title>新規登録</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="center">
<H1>
<font color="blue">新規登録</font>
</H1>

<?php


//正常に新規登録完了した場合
if ($outputMessage === CORRECT) {

    echo '会員登録が完了しました。<br>';
    echo '<a href="loginForm.php">ログインページ</a>';

//新規登録に失敗した場合
} else {

    //エラー内容に応じてメッセージを表示する。
    if ($outputMessage === ERROR_POST) {

        echo 'アップロードが失敗しました。<br>';
        
    } elseif ($outputMessage === ERROR_NULL) {

        echo '名前、メールアドレスまたはパスワードが空白です。<br>';
        
    } elseif ($outputMessage === ERROR_ILLEGAL) {

        echo '名前、メールアドレスまたはパスワードの値が不正です。<br>';
        
    } elseif ($outputMessage === ERROR_DIR) {

        echo '保管用ディレクトリを作ることができません。<br>';

    } elseif ($outputMessage === ERROR_MAIL) {

        echo '同じメールアドレスが存在します。<br>';
    } else {

        echo 'エラーが発生しました<br>';
    } 
    
    echo '<a href="signup.php">戻る</a>';
}
?>


</div>
</body>
</html>

