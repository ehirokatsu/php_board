<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>PHP 掲示板</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1>掲示板（フルスクラッチ）</h1>

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

//現在日時を取得する
date_default_timezone_set('Asia/Tokyo');
$today = date("Y-m-d H:i:s");

//セッションを開始
session_start();

//ログインしているとき
if (isset($_SESSION['user_id'])) {

    //ユーザー名を取得
    $loginUserName = $_SESSION['user_name'];
    
    //ユーザー名出力用
    $msg = htmlspecialchars($loginUserName, \ENT_QUOTES, 'UTF-8');
    
    //ログアウト用リンク
    $link = '<a href="logout.php">ログアウト</a>';
    
    //ファイル情報をデータベースから取得
    try {

        //データベース接続処理
        $dbh = $dbLib->connectDb();

        //ログインしているユーザーのプロフィール画像を取得する
        $sql = "select image_id, image_ext, image_type, image_name, image_date
                from users, images
                where users.user_image_id = images.image_id and user_id = :user_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $userImgsAll_all = $stmt->fetchAll();
        
        //ログインしているユーザーのプロフィール画像を取得する
        foreach ($userImgsAll_all as $userImgs) {
        
            $user_image_id = $userImgs['image_id'];
            $userImgInfos= $imgLib->getImgInfos($user_image_id);
            
        }

        $user_id = $_SESSION['user_id'];

        //投稿の削除ボタンが押下された場合
        if (isset($_POST['delete']) && is_string($_POST['delete'])) {

            //投稿テーブルから投稿内容を削除する
            $dbLib->deletePost('bulletinboard', (int)$_POST['delete']);

        }
        //返信投稿の削除ボタンが押下された場合
        if (isset($_POST['delete_com']) && is_string($_POST['delete_com'])) {

            //返信テーブルから返信内容を削除する
            $dbLib->deletePost('replyboard', (int)$_POST['delete_com']);

            //元投稿の返信フラグをオフにする
        }

        //返信にチェックが入っていた場合
        if (!empty($_POST['reply'])) {
        
            //画像があれば登録する
            $image_id = 0;
            
            if (isset($_SERVER['REQUEST_METHOD'])) {

                //POST以外受け付けない
                if ($_SERVER['REQUEST_METHOD'] !== "POST") {
                    exit('アップロードが失敗しました。');
                }
                //保管用ディレクトリを確保
                if (!is_dir($folder_files) && !mkdir($folder_files)) {
                    exit('保管用ディレクトリを作ることができません。');
                }
                
                //画像をDBに登録して画像IDを取得する
                $image_id = $imgLib->registerImg($_FILES);
            }
            
            //$src_post_id = NULL;
            $src_post_id = (int)$_POST['reply'];

        
            //データベース接続処理
            $dbh = $dbLib->connectDb();

            //元投稿のリプライフラグをONにする
            $sql = 'update bulletinboard set reply_flag = true where post_id = :post_id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':post_id', $src_post_id, PDO::PARAM_INT);
            $stmt->execute();


            //投稿内容をリプライ用テーブルにinsertする
            if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {
                //投稿内容をinsert
                $sql = 'insert into replyboard
                 (send_date, post_text, post_image_id, send_user_id, src_post_id)
                 values (:date, :post_text, :post_image_id, :send_user_id, :src_post_id)';
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(':date', $today);
                $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);            $stmt->bindValue(':post_image_id', $image_id, PDO::PARAM_INT);
                $stmt->bindValue(':send_user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':src_post_id', $src_post_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } else {

            //画像があれば登録する
            $image_id = 0;
            
            if (!empty($_FILES)) {
                if (isset($_SERVER['REQUEST_METHOD'])) {

                    //POST以外受け付けない
                    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
                        exit('アップロードが失敗しました。');
                    }
                    //保管用ディレクトリを確保
                    if (!is_dir($folder_files) && !mkdir($folder_files)) {
                        exit('保管用ディレクトリを作ることができません。');
                    }
                    
                    //画像をDBに登録して画像IDを取得する
                    $image_id = $imgLib->registerImg($_FILES);
                }
            }


            if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {

                //投稿内容をinsert
                $sql = 'INSERT INTO bulletinboard
                       (send_date, post_text, post_image_id, send_user_id,reply_flag)
                 VALUES (:date, :post_text, :post_image_id, :send_user_id, false)';
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(':date', $today, PDO::PARAM_STR);
                $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);            $stmt->bindValue(':post_image_id', $image_id, PDO::PARAM_INT);
                $stmt->bindValue(':send_user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        //データベース切断処理
        $dbLib->disconnectDb($stmt, $dbh);

    } catch (PDOException $e) {
        echo 'Connection failed:'.$e -> getMessage();
        die();
    }
    
//ログインしていない時
} else {
    echo 'ログインしていません<br>';
    echo '<a href="login.php">ログイン</a>';
    exit();
}

?>

<div class="center">
    <?php echo $link; ?>
</div>
<div class="container_post">

    <div class="item_post">
        <a href="<?= $userImgInfos['imgFPath']; ?>">
        <img src="<?= $userImgInfos['imgTPath']; ?>" alt="<?= $userImgInfos['imgBName']; ?>" width="50" border="0"></a>
    <?php echo $msg; ?>
    </div>
    <div class="item_post">
        
    </div>
    <div class="item_post">
    </div>
    <div class="item_post">
    </div>
    <div class="item_post_text">
    <form action="" method="post" name="post_text" enctype="multipart/form-data">

            <textarea id="post_text" name="post_text" cols="60" rows="8" maxlength=140></textarea >
        </div>
        <div class="item_post_file">
            <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
            添付ファイル(1M以内)：<input type="file" name="yourfile">
        </div>
          <div class="item_post"></div>
        <div class="item_post">
            <input type="submit" value="投稿する">
        </div>
</div>

<br>
<br>
<br>

<?php

//データベース接続処理
$dbh = $dbLib->connectDb();

//投稿テーブルとユーザーテーブルを連結して内容を全て取得する
$sql = 'SELECT * FROM bulletinboard, users
        WHERE bulletinboard.send_user_id = users.user_id
         order by post_id desc';
$stmt = $dbh->prepare($sql);
$stmt->execute();
$posts_all = $stmt->fetchAll();

foreach ($posts_all as $posts) {

    echo '<div class="container_board">';
    echo '<div class="item_board_user">';

        //投稿したユーザのプロフィール画像IDを取得する
        $user_image_id = $dbLib->getImgIdFromPostId('bulletinboard', $posts['post_id']);

        //画像情報を取得する
        $imgInfos = $imgLib->getImgInfos($user_image_id);
        $imgFPath = $imgInfos['imgFPath'];
        $imgTPath = $imgInfos['imgTPath'];
        $imgBName = $imgInfos['imgBName'];

        //プロフィール画像があれば表示する
        if ($imgFPath !== '') {
            echo "<a href=\"$imgFPath\">";
            echo "<img src=\"$imgTPath\" alt=\"$imgBName\" width=\"50\"
             border=\"0\"></a>";
        }


        echo '投稿者:'.$posts['user_name'];

        
    echo '</div>';
    echo '<div class="item_board_date">';
        echo $posts['send_date'];
    echo '</div>';
    echo '<div class="item_board_text">';
        echo $posts['post_text'];
        echo '<br>';
        //画像IDから画像を表示す
        $imgInfos = $imgLib->getImgInfos($posts['post_image_id']);
        $imgFPath = $imgInfos['imgFPath'];
        $imgTPath = $imgInfos['imgTPath'];
        $imgBName = $imgInfos['imgBName'];
      
        if ($imgFPath !== '') {
            echo "<a href=\"$imgFPath\">";
            echo "<img src=\"$imgTPath\" alt=\"$imgBName\" width=\"100\" border=\"0\"></a>";
        }
  
    echo '</div>';
        

    echo '<div class="item_board">';
    
        //投稿IDを返信ラジオボタンのValueに埋め込む
        $post_id = (int)$posts['post_id'];
        echo "<input type=\"radio\" id=\"reply$post_id\" name=\"reply\" value=\"$post_id\">";
        echo "<label for=\"reply$post_id\">返信　</label>";

    echo '</div>';
    echo '<div class="item_board">';
    echo '</div>';
    echo '<div class="item_board">';
    echo '</div>';
    echo '<div class="item_board">';
    
        //投稿がログインしているユーザなら削除ボタンを表示する
        if ($posts['send_user_id'] === $user_id) {
        
            echo "<button type=\"submit\" id=\"delete\" name=\"delete\"
             value=\"$post_id\">削除</button>";

        }
        
    echo '</div>';

    
    //投稿に返信がある場合
    if ($posts['reply_flag']) {
    
        try {
        
            //データベース接続処理
            $dbh = $dbLib->connectDb();
            
            //返信投稿テーブルとユーザーテーブルを連結して内容を取得する
            $sql = 'SELECT * FROM replyboard, users
                    WHERE replyboard.send_user_id = users.user_id
                    and src_post_id = :src_post_id
                    order by reply_post_id desc';
            $stmtReply = $dbh->prepare($sql);
            $stmtReply->bindValue(':src_post_id', $posts['post_id'], PDO::PARAM_INT);
            $stmtReply->execute();
            $replyPostsAll = $stmtReply->fetchAll();

            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
            foreach ($replyPostsAll as $replyPosts) {
            
                echo '</div>';
                echo '<div class="container_board">';
                echo '<div class="item_board_user">';

                    //投稿したユーザのプロフィール画像IDを取得する
                    $user_image_id = $dbLib->getImgIdFromPostId('replyboard', $replyPosts['reply_post_id']);
                    
                    //画像情報を取得する
                    $imgInfos = $imgLib->getImgInfos($user_image_id);
                    $imgFPath = $imgInfos['imgFPath'];
                    $imgTPath = $imgInfos['imgTPath'];
                    $imgBName = $imgInfos['imgBName'];
                  
                    if ($imgFPath !== '') {
                        echo "<a href=\"$imgFPath\">";
                        echo "<img src=\"$imgTPath\" alt=\"$imgBName\" width=\"50\" border=\"0\"></a>";
                    }
                    
                    echo '返信投稿者名：'.$replyPosts['user_name'].'<br>';
                    
                echo '</div>';

                
                echo '<div class="item_board_date">';
                    echo $replyPosts['send_date'];
                echo '</div>';
                echo '<div class="item_board_text">';
                    echo $replyPosts['post_text'];
                    echo '<br>';
                    //画像IDから画像を表示す
                    $imgInfos = $imgLib->getImgInfos($replyPosts['post_image_id']);
                    $imgFPath = $imgInfos['imgFPath'];
                    $imgTPath = $imgInfos['imgTPath'];
                    $imgBName = $imgInfos['imgBName'];
                      
                    if ($imgFPath !== '') {
                        echo "<a href=\"$imgFPath\">";
                        echo "<img src=\"$imgTPath\" alt=\"$imgBName\"
                         width=\"50\" border=\"0\"></a>";
                    }
                echo '</div>';
                echo '<div class="item_board">';
                echo '</div>';
                echo '<div class="item_board">';
                echo '</div>';
                echo '<div class="item_board">';
                echo '</div>';
                echo '<div class="item_board">';
                    $reply_post_id = $replyPosts['reply_post_id'];
                    if ($replyPosts['send_user_id'] === $user_id) {
                        echo "<button type=\"submit\" id=\"delete_com\"
                         name=\"delete_com\" value=\"$reply_post_id\">
                         削除</button>";

                echo '</div>';

                }
            }
        } catch (PDOException $e) {
            echo 'Connection failed:'.$e -> getMessage();
            die();
        }
    }
    echo '</div>';
    echo '<br>';
  
}
//データベース切断処理
$dbLib->disconnectDb($stmt, $dbh);

?>
</form>
</body>
</html>

