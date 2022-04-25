<?php

class SessionLib
{
    /**
     *タイムスタンプを考慮したセッションを開始する
     *
     * @return void
     */
    public function mySession_start()
    {
        //セッションを開始（クッキーの有効期間を１０分にする）
        session_start(['cookie_lifetime' => 600, ]);
        
        // 古過ぎるセッションIDを使用不可にする
        if (!empty($_SESSION['deleted_time'])
         && $_SESSION['deleted_time'] < time() - 180) {
         
            //セッションを破棄する
            session_destroy();
            //セッションを開始（クッキーの有効期間を１０分にする）
            session_start(['cookie_lifetime' => 600, ]);
            
        }
    }

    /**
     *セッションIDを再生成する  
     *
     * @return void
     */
    public function mySession_regenerate_id()
    {
        // セッションがアクティブな間は、
        // 衝突しないことを確実にするため
        // session_create_id() を呼び出す
        if (session_status() != PHP_SESSION_ACTIVE) {
        
            //セッションを開始（クッキーの有効期間を１０分にする）
            session_start(['cookie_lifetime' => 600, ]);
            
        }
        
        // セッションIDを生成する
        $newid = session_create_id('myprefix-');

        // 削除時のタイムスタンプを設定
        // セッションデータは、それなりの理由があるので、すぐに削除しない
        $_SESSION['deleted_time'] = time();
        
        // セッションを終了する
        session_commit();
        
        // ユーザー定義のセッションIDを確実に受け入れるようにする
        // 注意: 通常の操作のためには、use_strict_mode は有効でなければならない
        ini_set('session.use_strict_mode', 0);
        
        // 新しいカスタムのセッションIDを設定
        session_id($newid);
        
        // カスタムのセッションIDで開始（クッキーの有効期間を１０分にする）
        session_start(['cookie_lifetime' => 600, ]);
    }
}

