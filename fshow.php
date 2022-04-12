<?php

//共通設定を取得する
require('env.inc');

//パラメータを受け取る
$fid = $_REQUEST['fid'];
if ($fid == "" || !is_numeric($fid) || $fid <= 0) {
    exit();
}

$th = "";//初期化しないと画像表示できない
if (!empty($_REQUEST['th'])) {
    $th = $_REQUEST['th'];
}

//ファイル情報をデータベースから取得
try {
    //データベースに接続する
    $dbh = new PDO($dsn, $db_username, $db_password);
    
    //エラーはCatch内で処理する
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    //サーバサイドのプリペアドステートメントを有効にする
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    //選択した画像データのファイル名から拡張子とMIMEを取得する
    $sql = "select image_ext, image_type from images where image_id=:fid";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':fid', $fid, PDO::PARAM_INT);
    $stmt->execute();
    
    //データベース接続を解除する
    $dbh = null;
    
} catch (PDOException $e) {
    print('Connection failed:'.$e -> getMessage());
    die();
}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    exit();
}

//画像データの拡張子
$image_ext = $row['image_ext'];

//画像データのMIMEタイプ
$image_type = $row['image_type'];

//画像データのパス名
$fpath = "$folder_files/$fid.$image_ext";

//サムネイル画像のパス名
$tpath = "$folder_thumbs/$fid.$image_ext";

//画像データのパス名が存在する
if (is_file($fpath)) {
    header("Content-Type: $image_type");
    
    if ($th != "" && is_file($tpath)) {
    
        //サムネイル画像表示
        @readfile($tpath);
        
    } else {
    
        //画像データ表示
        @readfile($fpath);
    }
}

?>
