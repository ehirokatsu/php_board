<?php

//共通設定を取得する
require_once( dirname(__FILE__). '/env.inc');

//データベース関数を使用する
require_once( dirname(__FILE__). '/DbLib.php');
$dbLib = new DbLib();

//画像IDパラメータのチェック
if (empty($_REQUEST['image_id'])
 || !is_numeric($_REQUEST['image_id'])
 || $_REQUEST['image_id'] <= 0) {
 
    exit('不正なパラメータを受信しました。');
    
} else {

    //パラメータを受け取る
    $image_id = $_REQUEST['image_id'];

}

if (empty($_REQUEST['th']) || !is_string($_REQUEST['th'])) {
 
    //このパラメータが無い場合は使用しないので初期化する
    $th = "";
    
} else {

    //パラメータを受け取る
    $th = $_REQUEST['th'];
    
}

//ファイル情報をデータベースから取得
try {

    //データベース接続処理
    $dbh = $dbLib->connectDb();

    //選択した画像データのファイル名から拡張子とMIMEを取得する
    $sql = "select image_ext, image_type from images where image_id=:image_id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':image_id', $image_id, PDO::PARAM_INT);
    $stmt->execute();
    $imgInfos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    //データベース切断処理
    $dbLib->disconnectDb($stmt, $dbh);
    
} catch (PDOException $e) {

    print('Connection failed:'.$e -> getMessage());
    die();
    
}

if (!$imgInfos) {

    //画像データがDBに無ければ終了する
    exit('選択された画像がDBにありません');
    
}

//画像データの拡張子
$image_ext = $imgInfos['image_ext'];

//画像データのMIMEタイプ
$image_type = $imgInfos['image_type'];

//画像データのパス名
$fpath = "$folder_files/$image_id.$image_ext";

//サムネイル画像のパス名
$tpath = "$folder_thumbs/$image_id.$image_ext";

//画像データのパス名が存在する
if (is_file($fpath)) {
    header("Content-Type: $image_type");
    
    if ($th !== "" && is_file($tpath)) {
    
        //サムネイル画像表示
        @readfile($tpath);
        
    } else {
    
        //画像データ表示
        @readfile($fpath);
    }
}

?>
