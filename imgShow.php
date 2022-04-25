<?php

//共通設定を取得する
require_once( dirname(__FILE__). '/env.inc');

//画像IDパラメータのチェック
if (empty($_REQUEST['imgName'])
 || empty($_REQUEST['imgType'])
 || !is_string($_REQUEST['imgName'])
 || !is_string($_REQUEST['imgType'])
) {
 
    exit();
    
} else {

    //パラメータを受け取る
    $imgName = $_REQUEST['imgName'];
    $imgType = $_REQUEST['imgType'];
    
}

if (empty($_REQUEST['th']) || !is_string($_REQUEST['th'])) {
 
    //このパラメータが無い場合は使用しないので初期化する
    $th = "";
    
} else {

    //パラメータを受け取る
    $th = $_REQUEST['th'];
    
}

//画像データのパス名
$fpath = "$folder_files/$imgName";

//サムネイル画像のパス名
$tpath = "$folder_thumbs/$imgName";

//画像データのパス名が存在する
if (is_file($fpath)) {
    header("Content-Type: $imgType");
    
    if ($th !== "" && is_file($tpath)) {
    
        //サムネイル画像表示
        readfile($tpath);
        
    } else {
    
        //画像データ表示
        readfile($fpath);
    }
}

?>
