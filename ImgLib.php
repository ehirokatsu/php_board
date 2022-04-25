<?php

class ImgLib
{
    /**
     * 指定した画像IDからDBを探索し、存在すれば画像情報を得る
     *
     * @param int $image_id 表示する画像ID
     * @return $imgShowInfos 画像のフルパス、サムネイルパス、名称を格納した配列
     */
    public function getImgShowInfos($image_id)
    {
        //共通変数を使用する
        require( dirname(__FILE__). '/env.inc');

        //戻り値用の変数宣言
        $imgShowInfos = [
                  'imgPath'=>'',   //画像のパス
                  'imgThumbnailPath'=>'',   //サムネイル画像のパス
                  'imgName'=>''    //画像のファイル名
                  ];
          
        //データベース関数を使用する
        require_once( dirname(__FILE__). '/DbLib.php');
        $dbLib = new DbLib();

        
        //ファイル情報をデータベースから取得
        try {
        
            //データベース接続処理
            $dbh = $dbLib->connectDb();
        
            //一覧表示するSQL文
            $sql = "SELECT image_id, image_ext, image_type, image_name, image_date
                    FROM images
                    WHERE image_id = :image_id";
            
            $stmt = $dbh->prepare($sql);
            
            $stmt->bindValue(':image_id', $image_id, PDO::PARAM_INT);

            $stmt->execute();

            $imgInfos = $stmt->fetch(PDO::FETCH_ASSOC);

            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
        } catch (PDOException $e) {
            echo 'Connection failed:'.$e -> getMessage();
            die();
        }

        //DBの検索結果が存在する場合
        if (!empty($imgInfos)) {
            //画像ID

            $imgId = $imgInfos['image_id'];
            
            //画像の拡張子
            $imgExt = $imgInfos['image_ext'];
            
            //画像データのMIMEタイプ
            $imgType = $imgInfos['image_type'];

            //ファイル名を定義する（ID＋拡張子にする）
            $imgName = "$imgId.$imgExt";
            
            //ファイルのURLを決定
            $furl = "/board/imgShow.php?imgName=$imgName&imgType=$imgType";
            $turl = "/board/imgShow.php?imgName=$imgName&imgType=$imgType&th=y";
            //戻り値用の画像情報
            $imgShowInfos['imgPath'] = $furl;
            $imgShowInfos['imgThumbnailPath'] = $turl;
            $imgShowInfos['imgName'] = $imgName;


        }
        return $imgShowInfos;

    }

    /**
     * アップロードした画像を保存してDBに登録する
     *
     * @param int $imageFiles アップロードした画像情報
     * @return int $imgId DBに登録した画像のID。失敗したら0を返却する
     */
    public function registerImg($imageFiles)
    {

        //共通変数を使用する
        require( dirname(__FILE__). '/env.inc');

        //データベース関数を使用する
        require_once( dirname(__FILE__). '/DbLib.php');
        $dbLib = new DbLib();
        
        //戻り値初期化
        $returnImgId = 0;

        //DBで採番する画像ID
        $imgId = 0;
        
        //一時ディレクトリ格納時のパス名
        $imgTemp = $imageFiles['yourfile']['tmp_name'];
        
        //元ファイル名
        $imgName = $imageFiles['yourfile']['name'];
        
        //ファイルサイズ
        $imgSize = $imageFiles['yourfile']['size'];
            
        //MIMEタイプ(小文字に揃える)
        $imgType = strtolower($imageFiles['yourfile']['type']);

        //エラーコード（成功：０、エラー：正の整数）
        $imgError = $imageFiles['yourfile']['error'];

        //保管用ディレクトリを確保
        if (!is_dir($folder_files) && !mkdir($folder_files)) {
        
            return $returnImgId;
                
        }
        
        //ファイルがPOSTで受信していない、サイズオーバー、エラー発生時
        if (!is_uploaded_file($imgTemp)
         || $imgSize > $file_maxsize
         || $imgError > 0
        ) {
            return $returnImgId;
            
        }
        
        //MIMEタイプを確認
        if ($imgType !== 'image/jpeg' && $imgType !== 'image/pjpeg') {
         
            return $returnImgId;
            
        }
        
        //ファイル名と拡張子を取得
        $finfo = pathinfo($imgName);
        $imgName = $finfo['basename'];
        $fext = strtolower($finfo['extension']);
        
        //ファイル情報をデータベースに登録
        try {
        
            //データベース接続処理
            $dbh = $dbLib->connectDb();

            //ファイル情報をINSERTするSQLを設定
            $sql = 'INSERT INTO images
                    (image_name, image_ext, image_type, image_date)
                     VALUES (:image_name, :image_ext, :image_type, :image_date)';
            $stmt = $dbh->prepare($sql);
            
            //拡張子
            $stmt->bindValue(':image_ext', $fext, PDO::PARAM_STR);
            
            //MIMEタイプ
            $stmt->bindValue(':image_type', $imgType, PDO::PARAM_STR);
            
            //元ファイル名
            $stmt->bindValue(':image_name', $imgName, PDO::PARAM_STR);
            
            //現在時刻を取得して使用する
            date_default_timezone_set('Asia/Tokyo');
            $today = date("Y-m-d H:i:s"); 
            $stmt->bindValue(':image_date', $today, PDO::PARAM_STR);
            
            //SQL文を実行する
            $stmt->execute();
            
            //保存用ファイル名としてIDを使用する
            $imgId = $dbh->lastInsertId();
            
            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
        } catch (PDOException $e) {
            echo 'Connection failed:'.$e -> getMessage();
            exit();
        }
        
        //画像データを名前変更してファイル保管用ディレクトリに移動させる
        $fpath = "$folder_files/$imgId.$fext";
        if (!move_uploaded_file($imgTemp, $fpath)) {
        
            return $returnImgId;

        }
        
        //ファイルが画像なら読み取る
        $img = false;
        if ($imgType === 'image/jpeg' || $imgType === 'image/pjpeg') {
        
            $img = imagecreatefromjpeg($fpath);
            
        }
        
        //画像の読み取りが成功し、かつサムネイル用ディレクトリが確保された場合
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
            if ($imgType === 'image/jpeg' || $imgType === 'image/pjpeg') {
            
                imagejpeg($thm, "$folder_thumbs/$imgId.$fext");
                
                //全ての処理が完了したら戻り値に画像IDを格納する
                $returnImgId = $imgId;
            }
            
        }
        
        return $returnImgId;
       
    }
}

