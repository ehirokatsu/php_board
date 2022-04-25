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

//セッション関数を使用する
require_once( dirname(__FILE__). '/SessionLib.php');
$sessionLib = new SessionLib();

//現在日時を取得する
date_default_timezone_set('Asia/Tokyo');
$today = date("Y-m-d H:i:s");

//セッションを開始
$sessionLib->mySession_start();

//投稿内容の表示用バッファ
$showPosts = [];

//メッセージ出力用初期化
$outputMessage = ERROR_POST;

//POSTメソッド以外から遷移した場合
if (isset($_SERVER['REQUEST_METHOD'])
 && $_SERVER['REQUEST_METHOD'] !== "POST"
) {

    $outputMessage = ERROR_POST;
    header("Location: http://{$_SERVER["SERVER_NAME"]}/board/logout.php?outputMessage=$outputMessage");

}

//ワンタイムチケットが一致しない場合
if (isset($_SESSION['ticket'])
 && $_SESSION['ticket'] !== $_POST['ticket']
) {

    $outputMessage = ERROR_SESSION;
    header("Location: http://{$_SERVER["SERVER_NAME"]}/board/logout.php?outputMessage=$outputMessage");

} 

//ログインフォームから遷移した場合、ユーザ名・パスワードが一致するか検査する
if (isset($_SERVER['REQUEST_METHOD'])
 && $_SERVER['REQUEST_METHOD'] === "POST"
 && !isset($_SESSION['user_id'])
 && !isset($_SESSION['user_name'])
) {

    //入力されたメールアドレスとパスワードがNULLや空白
    if (empty($_POST['user_mail'])
     || empty($_POST['user_pass'])
     || !is_string($_POST['user_mail'])
     || !is_string($_POST['user_pass'])
     ) {

        $outputMessage = ERROR_ILLEGAL;
        header("Location: http://{$_SERVER["SERVER_NAME"]}/board/logout.php?outputMessage=$outputMessage");
        
    }

    //メールアドレスからusersテーブルを検索した結果を取得する
    $users = $dbLib->getUsersFromMail($_POST['user_mail']);

    //入力されたメールアドレスに一致する行が存在しない
    if (empty($users)) {
    
        $outputMessage = ERROR_MAIL;
        header("Location: http://{$_SERVER["SERVER_NAME"]}/board/logout.php?outputMessage=$outputMessage");
        
    }

    //入力されたパスワードが一致しない
    if (!password_verify($_POST['user_pass'], $users['user_pass'])) {
    
        $outputMessage = ERROR_PASS;
        header("Location: http://{$_SERVER["SERVER_NAME"]}/board/logout.php?outputMessage=$outputMessage");
        
    }
    
    //セッションIDを再生成する
    $sessionLib->mySession_regenerate_id();
    
    //ワンタイムチケットを発行して投稿フォームにhiddenでセット
    $ticket = md5(uniqid(rand(), true));
    output_add_rewrite_var('ticket', $ticket);
    
    //セッション変数に格納する
    $_SESSION['user_id'] = $users['user_id'];
    $_SESSION['user_name'] = $users['user_name'];
    $_SESSION['ticket'] = $ticket;


//投稿フォームから遷移した場合
} elseif (isset($_SERVER['REQUEST_METHOD'])
       && $_SERVER['REQUEST_METHOD'] === "POST"
       && isset($_SESSION['user_id'])
       && isset($_SESSION['user_name'])
) {

    //ワンタイムチケット以外のセッション変数を一時退避する
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'];
    
    //セッションIDを再生成する
    $sessionLib->mySession_regenerate_id();

    //ワンタイムチケットを発行して投稿フォームにhiddenでセット
    $ticket = md5(uniqid(rand(), true));
    output_add_rewrite_var('ticket', $ticket);

    //新セッションに一時退避した値を格納する
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['ticket'] = $ticket;
}

//ユーザー名を取得
$loginUserName = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');

try {

    //データベース接続処理
    $dbh = $dbLib->connectDb();

    //ログインしているユーザーのプロフィール画像を取得する
    $sql = "SELECT image_id, image_ext, image_type, image_name, image_date
            FROM users, images
            WHERE users.user_image_id = images.image_id AND user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $userImgs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($userImgs)) {
    
        //DBからプロフィール画像の名前や拡張子を取得する
        $loginImgShowInfos= $imgLib->getImgShowInfos($userImgs['image_id']);
    }

    //投稿の削除ボタンが押下された場合
    if (isset($_POST['delete']) && is_string($_POST['delete'])) {

        //投稿テーブルから投稿内容を削除する
        $dbLib->deletePost('bulletinboard', (int)$_POST['delete']);

    }
    //返信投稿の削除ボタンが押下された場合
    if (isset($_POST['delete_com']) && is_string($_POST['delete_com'])) {

        //返信テーブルから返信内容を削除する
        $dbLib->deletePost('replyboard', (int)$_POST['delete_com']);

    }

    //返信にチェックが入っていた場合
    if (!empty($_POST['reply'])) {
    
        //投稿に画像が無い場合の初期化用
        $image_id = 0;
        
        //画像をDBに登録して画像IDを取得する
        $image_id = $imgLib->registerImg($_FILES);
        
        //返信テーブルに格納する元投稿の投稿IDを設定する
        $src_post_id = (int)$_POST['reply'];

        //元投稿の返信フラグをONにする
        $sql = 'UPDATE bulletinboard SET reply_flag = true
                WHERE post_id = :post_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $src_post_id, PDO::PARAM_INT);
        $stmt->execute();

        //返信内容を返信テーブルにinsertする
        if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {
        
            $sql = 'INSERT INTO replyboard
             (send_date, post_text, post_image_id, send_user_id, src_post_id)
             VALUES (:date, :post_text, :post_image_id, :send_user_id, :src_post_id)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':date', $today);
            $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);            $stmt->bindValue(':post_image_id', $image_id, PDO::PARAM_INT);
            $stmt->bindValue(':send_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':src_post_id', $src_post_id, PDO::PARAM_INT);
            $stmt->execute();
            
        }
        
    //返信にチェックが入っていない場合（通常投稿）
    } else {

        //投稿に画像が無い場合の初期化用
        $image_id = 0;
        
        if (!empty($_FILES)) {

            //画像をDBに登録して画像IDを取得する
            $image_id = $imgLib->registerImg($_FILES);

        }

        if (isset($_POST['post_text']) && !empty($_POST['post_text'])) {

            //投稿内容を投稿テーブルにinsertする
            $sql = 'INSERT INTO bulletinboard
                   (send_date, post_text, post_image_id, send_user_id,reply_flag)
             VALUES (:date, :post_text, :post_image_id, :send_user_id, false)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':date', $today, PDO::PARAM_STR);
            $stmt->bindValue(':post_text', $_POST['post_text'], PDO::PARAM_STR);            $stmt->bindValue(':post_image_id', $image_id, PDO::PARAM_INT);
            $stmt->bindValue(':send_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    

    //投稿内容を表示するため、投稿内容とユーザを全て取得する
    $sql = 'SELECT * FROM bulletinboard, users
            WHERE bulletinboard.send_user_id = users.user_id
             ORDER BY post_id DESC';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $posts_all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts_all as $posts) {

        //投稿したユーザのプロフィール画像IDを取得する
        $user_image_id = $dbLib->getImgIdFromPost('bulletinboard', $posts['post_id']);

        //投稿ユーザーのプロフィール画像を取得する
        $userImgShowInfos = $imgLib->getImgShowInfos($user_image_id);
        $userImgFPath = $userImgShowInfos['imgFPath'];
        $userImgTPath = $userImgShowInfos['imgTPath'];
        $userImgIName = $userImgShowInfos['imgIName'];


        //投稿内容の画像を取得する
        $postImgShowInfos = $imgLib->getImgShowInfos($posts['post_image_id']);
        $postImgFPath = $postImgShowInfos['imgFPath'];
        $postImgTPath = $postImgShowInfos['imgTPath'];
        $postImgIName = $postImgShowInfos['imgIName'];
      
        //返信ラジオボタンのValueに投稿IDを埋め込む用
        $post_id = (int)$posts['post_id'];

        //表示用配列に格納する
        $showPosts[] = [
            'isReply'           => 'false',             //通常投稿
            'userImgFPath'      => $userImgFPath,       //画像パス
            'userImgTPath'      => $userImgTPath,       //サムネイルパス
            'userImgIName'      => $userImgIName,       //ユーザ画像名
            'userName'          => $posts['user_name'], //投稿者名
            'date'              => $posts['send_date'], //投稿日時
            'post'              => $posts['post_text'], //投稿内容
            'postImgFPath'      => $postImgFPath,       //投稿画像パス
            'postImgTPath'      => $postImgTPath,       //投稿サムネイル
            'postImgIPath'      => $postImgIName,       //投稿画像名
            'replyPostId'       => $post_id,            //返信用
            'sendUserId'        => $posts['send_user_id'],//投稿ユーザＩＤ
            'deleteButtonId'    => $post_id,            //削除用
        ];


        //投稿に返信がある場合
        if ($posts['reply_flag']) {
        
            //返信投稿テーブルとユーザーテーブルを連結して内容を取得する
            $sql = 'SELECT * FROM replyboard, users
                    WHERE replyboard.send_user_id = users.user_id
                    AND src_post_id = :src_post_id
                    ORDER BY reply_post_id DESC';
            $stmtReply = $dbh->prepare($sql);
            $stmtReply->bindValue(':src_post_id', $posts['post_id'], PDO::PARAM_INT);
            $stmtReply->execute();
            $replyPostsAll = $stmtReply->fetchAll(PDO::FETCH_ASSOC);

            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
            //１つの投稿に対する返信全てについて表示処理をする
            foreach ($replyPostsAll as $replyPosts) {
            
                //投稿ユーザーのプロフィール画像を取得する
                $user_image_id
                 = $dbLib->getImgIdFromPost('replyboard', $replyPosts['reply_post_id']);
                
                //画像情報を取得する
                $userImgShowInfos = $imgLib->getImgShowInfos($user_image_id);
                $userImgFPath = $userImgShowInfos['imgFPath'];
                $userImgTPath = $userImgShowInfos['imgTPath'];
                $userImgIName = $userImgShowInfos['imgIName'];

                //投稿内容の画像を取得する
                $replyPostImgShowInfos
                 = $imgLib->getImgShowInfos($replyPosts['post_image_id']);
                $replyPostImgFPath = $replyPostImgShowInfos['imgFPath'];       
                $replyPostImgTPath = $replyPostImgShowInfos['imgTPath'];
                $replyPostImgIName = $replyPostImgShowInfos['imgIName'];

                //削除ボタンのValueに投稿IDを埋め込む用
                $reply_post_id = $replyPosts['reply_post_id'];
                
                //表示用配列に格納する
                $showPosts[] = [
                    'isReply'           => 'true',          //返信投稿
                    'userImgFPath'      => $userImgFPath,
                    'userImgTPath'      => $userImgTPath,
                    'userImgIName'      => $userImgIName,
                    'userName'          => $replyPosts['user_name'],
                    'date'              => $replyPosts['send_date'],
                    'post'              => $replyPosts['post_text'],
                    'postImgFPath'      => $replyPostImgFPath,
                    'postImgTPath'      => $replyPostImgTPath,
                    'postImgIPath'      => $replyPostImgIName,
                    'replyPostId'       => $reply_post_id,
                    'sendUserId'        => $replyPosts['send_user_id'],
                    'deleteButtonId'    => $reply_post_id,
                ];
            }
        }
    }
    //データベース切断処理
    $dbLib->disconnectDb($stmt, $dbh);

} catch (PDOException $e) {
    echo 'Connection failed:'.$e -> getMessage();
    exit();
}


?>


<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN"
 "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<title>PHP 掲示板</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1>掲示板</h1>

<!-- ログアウト用リンクを表示する -->
<div class="center">
    <a href="logout.php">ログアウト</a>
</div>

<!-- ログイン情報と投稿フォームを表示する -->
<div class="container_post">
    <!-- ログインユーザ名とプロフィール画像 -->
    <div class="item_post">
        <a href="<?= $loginImgShowInfos['imgFPath']; ?>"
         target="_blank" rel="noopener noreferrer">
        <img src="<?= $loginImgShowInfos['imgTPath']; ?>"
         alt="<?= $loginImgShowInfos['imgIName']; ?>" width="50" border="0"></a>
        <?php echo $loginUserName; ?>
    </div>
    <!-- レイアウト調整用 -->
    <div class="item_post">
    </div>
    <div class="item_post">
    </div>
    <div class="item_post">
    </div>
    <div class="item_post_text">
    <!-- 投稿フォーム -->
    <form action="" method="post" name="post_text" enctype="multipart/form-data">

            <textarea id="post_text" name="post_text" cols="60" rows="8"
             maxlength=140></textarea >
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

<!-- 投稿内容を表示する -->
<?php foreach ($showPosts as $showPost) { ?>

     <div class="container_board">
     
     <!-- プロフィール画像と投稿者名 -->
     <div class="item_board_user">
       
        <?php if ($showPost['userImgFPath'] !== '') { ?>
             <a href="<?php echo $showPost['userImgFPath']; ?>"
              target="_blank" rel="noopener noreferrer">
             <img src="<?php echo $showPost['userImgTPath']; ?>"
              alt="<?php echo $showPost['userImgIName']; ?>"
               width="50" border="0"></a>
        <?php } ?>
        <?php if ($showPost['isReply'] === 'true') { ?>
            返信
         <?php } ?>
         投稿者:<?php echo $showPost['userName']; ?>
     </div>
     
     <!-- 投稿日時 -->
     <div class="item_board_date">
         <?php echo $showPost['date']; ?>
     </div>
     
     <!-- 投稿内容（テキストと画像） -->
     <div class="item_board_text">
         <?php echo htmlspecialchars($showPost['post'], ENT_QUOTES, 'UTF-8'); ?> 
         <br>
        <?php if ($showPost['postImgFPath'] !== '') { ?>
             <a href="<?php echo $showPost['postImgFPath']; ?>"
              target="_blank" rel="noopener noreferrer">
             <img src="<?php echo $showPost['postImgTPath']; ?>"
              alt="<?php echo $showPost['postImgIPath']; ?>"
               width="100" border="0"></a>
        <?php } ?>
     </div>
     
     <!-- 通常投稿なら返信ラジオボタンを表示する -->
     <div class="item_board">
     <?php if ($showPost['isReply'] === 'false') { ?>
        
         <input type="radio" id="reply<?php echo $showPost['replyPostId']; ?>"
          name="reply" value="<?php echo $showPost['replyPostId']; ?>">
         <label for="reply<?php echo $showPost['replyPostId']; ?>">返信　</label>
     <?php } ?>
     </div>
     
     <!-- レイアウト調整用 -->
     <div class="item_board">
     </div>
     <div class="item_board">
     </div>
     
     <!-- ログインしているユーザの投稿なら削除ボタンを表示する -->
     <div class="item_board">
        <?php if ($showPost['sendUserId'] === $_SESSION['user_id']) { ?>
        
             <button type="submit" id="delete" name="delete"
             value="<?php echo $showPost['deleteButtonId']; ?>">削除</button>

        <?php } ?>
        
     </div>
     </div>
     <br>
<?php } ?>
    

</form>
</body>
</html>

