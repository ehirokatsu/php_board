<?php
require( dirname(__FILE__). '/BaseDbLib.php');

class DbLib extends BaseDbLib
{
    /**
     * usersテーブルから指定したメールアドレスを持つユーザを検索する
     *
     * @param string $user_mail メールアドレス
     * @return array $result_all　結果行
     */
    public function getUsersFromMail($user_mail)
    {
        //データベース接続処理
        $dbh = $this->connectDb();
        
        //入力されたメールアドレスを検索する
        $sql = "SELECT * FROM users WHERE user_mail = :user_mail";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_mail', $user_mail, PDO::PARAM_STR);
        $stmt->execute();
        
        $result_all = $stmt->fetchAll();
        
        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
        return $result_all;
    }
    
    /**
     *指定したテーブル名から指定した投稿IDの行を削除する
     *
     * @param string $table_name テーブル名称
     * @param int $post_id 投稿ID
     * @return void
     */
    public function deletePost($table_name, $post_id)
    {
        if ($table_name === 'bulletinboard') {
        
            //テーブル名が投稿用テーブル
            $id_name = 'post_id';
        
        } elseif ($table_name === 'replyboard') {
            
            //テーブル名が返信用テーブル
            $id_name = 'reply_post_id';
            
        } else {
            print('指定されたテーブル名が無効です');
            die();
        }
    
        //データベース接続処理
        $dbh = $this->connectDb();
        
        //投稿内容を削除する
        $sql = "delete from $table_name where $id_name = :post_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();

        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
    }
    
    /**
     *投稿IDから画像情報（パス、サムネイルパス、ファイル名）を取得する
     * @param int $post_id 投稿ID
     * @return パス、サムネイルパス、ファイル名
     */
    public function getImgIdFromPostId($table_name, $post_id)
    {
    
        if ($table_name === 'bulletinboard') {
        
            //テーブル名が投稿用テーブル
            $id_name = 'post_id';
        
        } elseif ($table_name === 'replyboard') {
            
            //テーブル名が返信用テーブル
            $id_name = 'reply_post_id';
            
        } else {
            print('指定されたテーブル名が無効です');
            die();
        }
        
        //データベース接続処理
        $dbh = $this->connectDb();
        
        $sql = "SELECT * FROM $table_name, users
                WHERE $table_name.send_user_id = users.user_id
                 and $table_name.$id_name = :post_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $imgIdsAll = $stmt->fetchAll();

        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
        foreach ($imgIdsAll as $imgIds) {

            return $imgIds['user_image_id'];
            
        }
    }
    
}

