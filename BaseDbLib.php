<?php

class BaseDbLib
{

    /**
     * データベースの接続処理を行う。
     *
     * @param void
     * @return $dbh PDOインスタンス
     */
    public function connectDb()
    {
        //データベース接続情報
        $dsn = 'mysql:dbname=myboard;host=localhost';
        $db_username = "user1";
        $db_password = "user1";
        
        try {
            $dbh = null;
            
            //データベースに接続する
            $dbh = new PDO($dsn, $db_username, $db_password);
            
            //エラーはCatch内で処理する
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            //サーバサイドのプリペアドステートメントを有効にする
            $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        
        } catch (PDOException $e) {
            print('Connection failed:'.$e -> getMessage());
            die();
        }
        return $dbh;
    }
    
    /**
     * データベースの切断処理を行う。
     *
     * @param $stmt PDOStatement オブジェクト
     * @param $dbh  PDOインスタンス
     * @return void
     */
    public function disconnectDb($stmt, $dbh)
    {
        //データベースを閉じる
        $stmt = null;
        $dbh = null;
    }
}



