<?php

class ImgLib
{
    /**
     * 指定した画像IDのサムネイルを表示する。
     *
     * @param int $image_id 表示する画像ID
     * @return void
     */
    public function getImgInfos($image_id)
    {
        //共通変数を使用する
        require( dirname(__FILE__). '/env.inc');

        //戻り値用の変数宣言
        $imgInfos = [
                  'imgFPath'=>'',   //画像のパス
                  'imgTPath'=>'',   //サムネイル画像のパス
                  'imgBName'=>''    //画像のファイル名
                  ];
          
        //データベース関数を使用する
        require_once( dirname(__FILE__). '/DbLib.php');
        $dbLib = new DbLib();

        
        //ファイル情報をデータベースから取得
        try {
            //データベース接続処理
            $dbh = $dbLib->connectDb();
        
            //一覧表示するSQL文
            $sql = "select image_id, image_ext, image_type, image_name, image_date
                    from images
                    where image_id = :image_id";
            
            $stmt = $dbh->prepare($sql);
            
            $stmt->bindValue(':image_id', $image_id, PDO::PARAM_INT);

            $stmt->execute();

            $result_all = $stmt->fetchAll();

            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
        } catch (PDOException $e) {
            print('Connection failed:'.$e -> getMessage());
            die();
        }

        //引数の画像IDが存在する場合
        foreach ($result_all as $result) {
            
            //画像ID
            $image_id = $result['image_id'];
            
            //画像の拡張子
            $image_ext = $result['image_ext'];
            
            //ファイル名を定義する（ID＋拡張子にする）
            $bname = "$image_id.$image_ext";
            
            //画像のフルパス
            $fpath = "$folder_files/$image_id.$image_ext";
            
            //サムネイル画像のフルパス
            $tpath = "$folder_thumbs/$image_id.$image_ext";
            
            //ファイルのURLを決定
            $furl = "/board/imgShow.php?image_id=$image_id";
            $turl = "/board/imgShow.php?image_id=$image_id&th=y";
            
            
            $imgInfos['imgFPath'] = $furl;
            $imgInfos['imgTPath'] = $turl;
            $imgInfos['imgBName'] = $bname;


        }
        return $imgInfos;

    }

    /**
     * アップロードした画像を保存してDBに登録する
     *
     * @param int $imageFile アップロードした画像情報
     * @return int $image_id DBに登録した画像のID。失敗したら0を返却する
     */
    public function registerImg($imageFile)
    {

        //共通変数を使用する
        require( dirname(__FILE__). '/env.inc');

        //データベース関数を使用する
        require_once( dirname(__FILE__). '/DbLib.php');
        $dbLib = new DbLib();
        

        //プロフィール画像をアップロードしていない場合用の初期化
        $image_id = 0;
        
        //一時ディレクトリ格納時のパス名
        $ftemp = $imageFile['yourfile']['tmp_name'];
        
        //元ファイル名
        $fname = $imageFile['yourfile']['name'];
        
        //ファイルサイズ
        $fsize = $imageFile['yourfile']['size'];
            
        //MIMEタイプ(小文字に揃える)
        $ftype = strtolower($imageFile['yourfile']['type']);

        //エラーコード（成功：０、エラー：正の整数）
        $ferror = $imageFile['yourfile']['error'];

        //ファイルがPOSTで受信していない、サイズオーバー、エラー発生時
        if (!is_uploaded_file($ftemp) || $fsize > $file_maxsize || $ferror > 0) {
            return 0;
            
        }
        
        //MIMEタイプを確認
        if ($ftype !== 'image/jpeg' && $ftype !== 'image/pjpeg') {
         
            return 0;
            
        }
        
        //ファイル名と拡張子を取得
        $finfo = pathinfo($fname);
        $fname = $finfo['basename'];
        $fext = strtolower($finfo['extension']);
        
        
        //ファイル情報をデータベースに登録
        try {
        
            //データベース接続処理
            $dbh = $dbLib->connectDb();

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
            
            //データベース切断処理
            $dbLib->disconnectDb($stmt, $dbh);
            
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
}

