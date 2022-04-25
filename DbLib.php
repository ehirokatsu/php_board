<?php
require( dirname(__FILE__). '/BaseDbLib.php');

class DbLib extends BaseDbLib
{
    /**
     * usersテーブルから指定したメールアドレスを持つユーザを検索する
     *
     * @param string $user_mail メールアドレス
     * @return array $users　結果行
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
        
        $users = $stmt->fetch(PDO::FETCH_ASSOC);
        
        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
        return $users;
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
            echo '指定されたテーブル名が無効です';
            exit();
        }
    
        //データベース接続処理
        $dbh = $this->connectDb();
        
        //投稿内容を削除する
        $sql = "DELETE FROM $table_name WHERE $id_name = :post_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();

        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
    }
    
    /**
     *投稿IDからユーザプロフィール画像情報
     *（パス、サムネイルパス、ファイル名）を取得する
     * @param int $post_id 投稿ID
     * @return 画像ID。DBに画像情報が無ければ0を返却する。
     */
    public function getImgIdFromPost($table_name, $post_id)
    {
        
        //戻り値の初期化
        $imgId = 0;
    
        if ($table_name === 'bulletinboard') {
        
            //テーブル名が投稿用テーブル
            $id_name = 'post_id';
        
        } elseif ($table_name === 'replyboard') {
            
            //テーブル名が返信用テーブル
            $id_name = 'reply_post_id';
            
        } else {
            
            return $imgId;
            
        }
        
        //データベース接続処理
        $dbh = $this->connectDb();
        
        $sql = "SELECT * FROM $table_name, users
                WHERE $table_name.send_user_id = users.user_id
                 AND $table_name.$id_name = :post_id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $imgIds = $stmt->fetch(PDO::FETCH_ASSOC);

        //データベース切断処理
        $this->disconnectDb($stmt, $dbh);
        
        //DBに存在すれば、ユーザーのプロフィール画像IDを戻り値に格納する
        if (!empty($imgIds)) {

            $imgId = $imgIds['user_image_id'];
            
        }
        
        return $imgId;
    }
    
}

