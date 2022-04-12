<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>PHP 掲示板</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<br>
<br>
<div class="center">

掲示板課題（フルスクラッチ）
<br>
<?php
require('env.inc');
require('imgLib.php');


date_default_timezone_set('Asia/Tokyo');
$today = date("Y-m-d H:i:s");

$dsn = 'mysql:dbname=myboard;host=localhost';
$db_username = 'user1';
$db_pass = 'user1';

session_start();
$username = $_SESSION['user_name'];
if (isset($_SESSION['user_id'])) {//ログインしているとき
    $msg = 'ユーザー：' . htmlspecialchars($username, \ENT_QUOTES, 'UTF-8');
    $link = '<a href="logout.php">ログアウト</a>';
} else {//ログインしていない時
    $msg = 'ログインしていません';
    $link = '<a href="login.php">ログイン</a>';
    exit();
}

//ログインしているユーザIDに対応する画像を表示する
getImg($_SESSION['user_id']);


echo '<br>';
echo $msg;
echo '<br>';
echo $link;
echo '<br>';
echo '<br>';


try {
    //データベースに接続する
    $dbh = new PDO($dsn, $db_username, $db_password);

    //エラーはCatch内で処理する
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //サーバサイドのプリペアドステートメントを有効にする
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


    //ログインしているユーザのuser_idを取得する
    $sql = "SELECT user_id FROM users WHERE user_name = :name";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':name', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user_id = $stmt->fetch(PDO::FETCH_ASSOC);



    //削除ボタンが押下された場合
    if (isset($_POST['delete'])) {

        //投稿内容を削除する
        $sql = 'delete from bulletinboard where post_id = :post_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $_POST['delete'], PDO::PARAM_INT);
        $stmt->execute();

    }
    if (isset($_POST['delete_com'])) {

        //投稿内容を削除する
        $sql = 'delete from replyboard where reply_post_id = :reply_post_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':reply_post_id', $_POST['delete_com'], PDO::PARAM_INT);
        $stmt->execute();

    }

    //返信にチェックが入っていた場合
    $src_post_id = NULL;

    if (!empty($_POST['reply'])) {
        $src_post_id = $_POST['reply'];

        //元投稿のリプライフラグをONにする
        $sql = 'update bulletinboard set reply_flag = true where post_id = :post_id';

        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $src_post_id, PDO::PARAM_INT);
        $stmt->execute();

        //投稿内容をリプライ用テーブルにinsertする
        if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {
            //投稿内容をinsert
            $sql = 'insert into replyboard
             (send_date, post_text, post_image, send_user_id, src_post_id)
             values (:date, :post_text, null, :send_user_id, :src_post_id)';

            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':date', $today);
            $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);
            $stmt->bindValue(':send_user_id', $user_id['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':src_post_id', $src_post_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    } else {

        //画像があれば登録する
        $image_id = 0;
        
        if (isset($_SERVER['REQUEST_METHOD'])) {
        
           var_dump($_SERVER['REQUEST_METHOD']);
            //POST以外受け付けない
            if ($_SERVER['REQUEST_METHOD'] !== "POST") {
                exit('アップロードが失敗しました。');
            }
            //保管用ディレクトリを確保
            if (!is_dir($folder_files) && !mkdir($folder_files)) {
                exit('保管用ディレクトリを作ることができません。');
            }
            
            //画像をDBに登録して画像IDを取得する
            $image_id = registerImg($_FILES);
        }



        if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {

            echo $image_id;
            //投稿内容をinsert
            $sql = 'insert into bulletinboard
                   (send_date, post_text, post_image_id, send_user_id,reply_flag)
             values (:date, :post_text, :post_image_id, :send_user_id, false)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':date', $today, PDO::PARAM_STR);
            $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);            $stmt->bindValue(':post_image_id', $image_id, PDO::PARAM_INT);
            $stmt->bindValue(':send_user_id', $user_id['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        }

    }

    //掲示板表示
    $sql = 'select * from bulletinboard, users
            where bulletinboard.send_user_id = users.user_id
             order by post_id desc';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();



    //$dbh = null;

} catch (PDOException $e) {
    print('Connection failed:'.$e -> getMessage());
    die();
}



?>


<form action="" method="post" name="post_text" enctype="multipart/form-data">
    <label for="post_text">投稿内容</label>
    <br>
    <textarea id="post_text" name="post_text"  cols="40"  rows="8" maxlength=140></textarea >
    <br>
    <br>
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
    新しいファイル：<input type="file" name="yourfile">
    (1M以内)
    <br>
    <input type="submit" value="投稿する">
    <br>


<?php
    while($result = $stmt->fetch(PDO::FETCH_ASSOC)){

        echo '<table border="1">';
        echo '<tr>';
        echo '<th>';
        print('投稿者ID:'.$result['user_id'].'<br>');
        print('投稿者名:'.$result['user_name'].' ');
        echo '</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        print($result['post_id'].' ');
        print('投稿日時:'.$result['send_date'].' ');

        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        print($result['post_text'].' ');
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';

        //ログインしているユーザIDに対応する画像を表示する
        getImg2($result['post_image_id']);
        echo '</td>';
        echo '</tr>';

        $post_id = $result['post_id'];

        echo '<tr>';
        echo '<td>';
        echo "<input type=\"radio\" id=\"reply$post_id\" name=\"reply\" value=\"$post_id\">";

        echo "<label for=\"reply$post_id\">返信　</label>";



        if ($result['send_user_id'] === $user_id['user_id']) {
            echo "<button type=\"submit\" id=\"delete\" name=\"delete\" value=\"$post_id\">削除</button>";

        }

        echo '</td>';
        echo '</tr>';



        if ($result['reply_flag']) {


            $sql = 'select * from replyboard, users
                    where replyboard.send_user_id = users.user_id
                    and src_post_id = :src_post_id';
            $stmt2 = $dbh->prepare($sql);
            $stmt2->bindValue(':src_post_id', $result['post_id'], PDO::PARAM_INT);
            $stmt2->execute();


            while($result2 = $stmt2->fetch(PDO::FETCH_ASSOC)){
                echo '<tr>';
                echo '<td>';

                echo '<table border="1">';
                echo '<tr>';
                echo '<th>';
                print('返信投稿者ID：'.$result2['reply_post_id'].'<br>');
                print('返信投稿者名：'.$result2['user_name'].'<br>');
                echo '</th>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                print('    '.$result2['send_date'].' ');
                echo '</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td>';
                print('    '.$result2['post_text'].' ');
                echo '</td>';
                echo '</tr>';




                echo '<tr>';
                echo '<td>';
                $reply_post_id = $result2['reply_post_id'];
                if ($result['send_user_id'] === $user_id['user_id']) {
                    echo "<button type=\"submit\" id=\"delete_com\" name=\"delete_com\" value=\"$reply_post_id\">削除</button>";
                    echo '<br>';

                }
                echo '</td>';
                echo '</tr>';

                echo '</table>';
                echo '<br>';

                echo '</td>';
                echo '</tr>';
            }

        }
                echo '</table>';
        echo '<br>';

    }
?>



</form>


</div>
</body>
</html>
