<?php

function getImg($user_id)
{
    require('env.inc');

    
    //ファイル情報をデータベースから取得
    try {
        //データベースに接続する
        $dbh = new PDO($dsn, $db_username, $db_password);
        
        //エラーはCatch内で処理する
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        //サーバサイドのプリペアドステートメントを有効にする
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
        //一覧表示するSQL文
        $sql = "select image_id, image_ext, image_type, image_name, image_date
                from users, images
                where users.user_image_id = images.image_id and user_id = :user_id";
        
        $stmt = $dbh->prepare($sql);
        

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();
        
        //データベースを閉じる
        $dbh = null;
        
    } catch (PDOException $e) {
        print('Connection failed:'.$e -> getMessage());
        die();
    }
    if ($stmt) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            //各列の値を変数に取り出す
            $image_id = $row['image_id'];
            $image_ext = $row['image_ext'];
            $image_type = $row['image_type'];
            $image_name = $row['image_name'];
            $image_date = $row['image_date'];
            //ファイルのパス名を決定
            $bname = "$image_id.$image_ext";
            $fpath = "$folder_files/$image_id.$image_ext";
            $tpath = "$folder_thumbs/$image_id.$image_ext";
            
            //ファイルのURLを決定
            $furl = "/board/fshow.php?fid=$image_id";
            $turl = "/board/fshow.php?fid=$image_id&th=y";
            //表示
            
            if (is_file($fpath)) {
            
                echo "<a href=\"$furl\">";
                if (is_file($tpath)) {
                
                    echo "<img src=\"$turl\" alt=\"$bname\" width=\"100\" border=\"0\">";
                } else {
                    echo "<br>";
                }
                echo "</a>";
            } else {
                echo "(removed)";
            }
        }
    }
}

function getImg2($image_id)
{
    require('env.inc');

    //ファイル情報をデータベースから取得
    try {
        //データベースに接続する
        $dbh = new PDO($dsn, $db_username, $db_password);
        
        //エラーはCatch内で処理する
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        //サーバサイドのプリペアドステートメントを有効にする
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
        //一覧表示するSQL文
        $sql = "select image_id, image_ext, image_type, image_name, image_date
                from images, bulletinboard
                where bulletinboard.post_image_id = images.image_id and images.image_id = :image_id";
        
        $stmt = $dbh->prepare($sql);
        

        $stmt->bindValue(':image_id', $image_id, PDO::PARAM_INT);

        $stmt->execute();
 
        //データベースを閉じる
        $dbh = null;
        
    } catch (PDOException $e) {
        print('Connection failed:'.$e -> getMessage());
        die();
    }
    if ($stmt) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            //各列の値を変数に取り出す
            $image_id = $row['image_id'];
            $image_ext = $row['image_ext'];
            $image_type = $row['image_type'];
            $image_name = $row['image_name'];
            $image_date = $row['image_date'];
            //ファイルのパス名を決定
            $bname = "$image_id.$image_ext";
            $fpath = "$folder_files/$image_id.$image_ext";
            $tpath = "$folder_thumbs/$image_id.$image_ext";
            
            //ファイルのURLを決定
            $furl = "/board/fshow.php?fid=$image_id";
            $turl = "/board/fshow.php?fid=$image_id&th=y";
            //表示
            
            if (is_file($fpath)) {
            
                echo "<a href=\"$furl\">";
                if (is_file($tpath)) {
                
                    echo "<img src=\"$turl\" alt=\"$bname\" width=\"100\" border=\"0\">";
                } else {
                    echo "<br>";
                }
                echo "</a>";
            } else {
                echo "(removed)";
            }
        }
    }
}


function registerImg($_imagefile)
{

    require('env.inc');
    //プロフィール画像をアップロードしていない場合用の初期化
    $image_id = 0;
    
    //一時ディレクトリ格納時のパス名
    $ftemp = $_imagefile['yourfile']['tmp_name'];
    
    //元ファイル名
    $fname = $_imagefile['yourfile']['name'];
    
    //ファイルサイズ
    $fsize = $_imagefile['yourfile']['size'];
        
    //MIMEタイプ(小文字に揃える)
    $ftype = strtolower($_imagefile['yourfile']['type']);

    //エラーコード（成功：０、エラー：正の整数）
    $ferror = $_imagefile['yourfile']['error'];

    //ファイルがPOSTで受信していない、サイズオーバー、エラー発生時
    if (!is_uploaded_file($ftemp) || $fsize > $file_maxsize || $ferror > 0) {
                return 0;
        exit('アップロードが失敗しました。');
    }
    
    //MIMEタイプを確認
    if ($ftype != 'application/msword'
     && $ftype != 'application/pdf'
     && $ftype != 'image/jpeg'
     && $ftype != 'image/pjpeg'
     && $ftype != 'text/html'
     && $ftype != 'text/plain') {
        return 0;
         exit('この種類のファイルは受付ません。');
    }
    
    //ファイル名と拡張子を取得
    $finfo = pathinfo($fname);
    $fname = $finfo['basename'];
    $fext = strtolower($finfo['extension']);
    
    
    //ファイル情報をデータベースに登録
    try {
    
        //データベースに接続する
        $dbh = new PDO($dsn, $db_username, $db_password);
        
        //エラーはCatch内で処理する
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        //サーバサイドのプリペアドステートメントを有効にする
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        //ファイル情報をINSERTするSQLを設定
        $sql = 'insert into images
                (image_name, image_ext, image_type, image_date)
         values (:image_name, :image_ext, :image_type, :image_date)';
        $stmt = $dbh->prepare($sql);
        
        //拡張子
        $stmt->bindValue(':image_ext', $fext, PDO::PARAM_STR);
        
        //MIMEタイプ
        $stmt->bindValue(':image_type', $ftype, PDO::PARAM_STR);
        
        //元ファイル名
        $stmt->bindValue(':image_name', $fname, PDO::PARAM_STR);
        
        //現在時刻を取得して使用する
        date_default_timezone_set('Asia/Tokyo');
        $today = date("Y-m-d H:i:s"); 
        $stmt->bindValue(':image_date', $today, PDO::PARAM_STR);
        
        //SQL文を実行する
        $stmt->execute();
        
        //保存用ファイル名としてIDを使用する
        $image_id = $dbh->lastInsertId();
        
        //データベース接続を解除する
        $dbh = null;
        
    } catch (PDOException $e) {
        print('Connection failed:'.$e -> getMessage());
        die();
    }
    
    //画像データを名前変更してファイル保管用ディレクトリに移動させる
    $fpath = "$folder_files/$image_id.$fext";
    if (!move_uploaded_file($ftemp, $fpath)) {
        return 0;
        exit('保管用ディレクトリへの移動が失敗しました。');
    }
    
    //ファイルが画像なら読み取る
    $img = false;
    if ($ftype === 'image/jpeg' || $ftype === 'image/pjpeg') {
        $img = @imagecreatefromjpeg($fpath);
    }
    
    //画像の読み取りが成功っし、かつサムネイル用ディレクトリが確保されるなら
    if ($img && (is_dir($folder_thumbs) || mkdir($folder_thumbs))) {
    
        //元画像の横幅
        $iw = imagesx($img);
        
        //元画像の縦幅
        $ih = imagesy($img);
        
        //サムネイル画像の横幅
        $tw = $thumb_width;
        
        //サムネイル画像の縦幅（元画像から計算する）
        $th = (int)($thumb_width * $ih / $iw);
        
        
        //サムネイル用の空画像データを作成
        $thm = imagecreatetruecolor($tw, $th);
        
        //元画像データからサムネイル画像データを作成する
        imagecopyresampled($thm, $img, 0, 0, 0, 0, $tw, $th, $iw, $ih);
        
        //サムネイル画像をサムネイル用フォルダに保存する
        if ($ftype === 'image/jpeg' || $ftype === 'image/pjpeg') {
            imagejpeg($thm, "$folder_thumbs/$image_id.$fext");
        }
    }
    
    return $image_id;

}

