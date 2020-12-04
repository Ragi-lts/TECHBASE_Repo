<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>Mission_5-1</title>
</head>

<body>
    <?php

/* データベース接続情報 */
// 本番

$dbsn = 'データベース';
$user = "ユーザー";
$password ="パスワード";
$pdo = new PDO($dbsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));


/* データベース新規作成 */
/*  $dropsql = "DROP TABLE Dashboard";
$stmt = $pdo->query($dropsql);  */

$init_sql = "CREATE TABLE IF NOT EXISTS Dashboard"
."("
." id INT AUTO_INCREMENT PRIMARY KEY,"
."name char(32),"
."comment TEXT,"
."postdate datetime,"
."password char(32)"
.");";
$stmt = $pdo->query($init_sql);

$form_idx = null;
$form_nickname="";
$form_comment="";

/************************************************** */


/**
 * ユーザ共通．
 */
class USER /* 投稿ユーザクラス */
{
    public $nickname;       /* 名前 */
    public $comment;        /* コメント */
    public $password;       /* パスワード */
    public $edit_num;       /* 編集番号 */
    public $edit_pass;      /* 編集パスワード */
    public $delete_num;     /* 削除番号 */
    public $delete_pass;    /*削除パスワード  */

    /**
     * 初期化関数
     *
     * @param string    $nickname       名前
     * @param string    $comment        コメント
     * @param string    $password       パスワード
     * @param int       $edit_num       編集番号
     * @param string    $edit_pass      編集パスワード
     * @param int       $delete_num     削除番号
     * @param string    $delete_pass    削除パスワード
     */
    public function __construct(/* 初期値はすべてnull */
        $nickname       = null,
        $comment        = null,
        $password       = null,
        $edit_num       = null,
        $edit_pass      = null,
        $delete_num     = null,
        $delete_pass    = null
    ) /* 初期化 */
        {
        ///echo "Create Constructor <br>";
        $this->index       = "";
        $this->nickname    = $nickname;
        $this->comment     = $comment;
        $this->password    = $password;
        $this->edit_num    = $edit_num;
        $this->edit_pass   = $edit_pass;
        $this->delete_num  = $delete_num;
        $this->delete_pass = $delete_pass;
        $this->valid       = "";

        //echo var_dump(debug_backtrace());
    }

    /**
     * データベースに投稿する関数
     *
     * @param PDO $pdo  データベース操作変数
     * @return bool 投稿完了?
     */
    public function Reg_comment($pdo=null, $pid=null)
    {
        //echo "INSERT INTO Dashboard (name,comment,postdate,password) VALUES (".$this->nickname,", ",$this->comment,",",date('Y/m/d H:i:s'),",",$this->password,");<br>";
        if ($pdo == null) {
            echo("Database is null");
            return null;
        }
       

        /* データの挿入． */
        $post_sql = ($pid != null) ?
        $pdo->prepare("UPDATE Dashboard SET name=:name, comment=:comment, postdate=:postdate, password=:password WHERE id=:id"):
        $pdo->prepare("INSERT INTO Dashboard (name, comment, postdate, password) VALUES (:name, :comment, :postdate, :password)");
        if ($pid != null) {
            $post_sql->bindParam(':id', $pid, PDO::PARAM_INT);
        }
        /* 変数割り当て */
        $now = date('Y/m/d H:i:s');
        $post_sql->bindParam(':name', $this->nickname, PDO::PARAM_STR);
        $post_sql->bindParam(':comment', $this->comment, PDO::PARAM_STR);
        $post_sql->bindParam(':postdate', $now, PDO::PARAM_STR);
        $post_sql->bindParam(':password', $this->password, PDO::PARAM_STR);
        /* 登録に成功すると，trueを返す． */
        if ($post_sql->execute()) {
            echo "投稿しました！";
            return true;
        } else {
            echo "NG";
            return false;
        }
    }

    /**
     * 投稿番号を取得する関数
     *
     * @return int  投稿番号
     */
    public function get_pid()
    {
        if (empty($this->edit_num) != true|| empty($this->delete_num) != true) {  /* 編集番号または削除番号が存在したら */
            $pid = ($this->edit_num) ? $this->edit_num : $this->delete_num; /* 投稿番号を決める */
            return (int)$pid;
        }
        echo("Edit Number or Delete Number is not defined. <br>");
        return null;
    }

    /**
     * 指定した投稿を読み込む．
     *
     * @param PDO $pdo データベース操作変数
     * @param int $pid  投稿番号(指定なしなら全部表示)
     * @return bool 書き換え可能?
     */
    public function Load_comment($pdo=null, $pid=null, $pass=null)
    {
        //echo "SELECT * FROM Dashboard WHERE id=".$pid."<br>";
        if ($pdo != null) {
            $sel_id = ($pid != null) ? " WHERE id=:id":"";
            $match_sql = "SELECT * FROM Dashboard".$sel_id;/* 選択した番号を検索する */
            //echo $match_sql;
            $stmt = $pdo->prepare($match_sql);
            if ($pid != null) {
                $stmt->bindParam(':id', $pid, PDO::PARAM_INT);
            }
            if ($stmt->execute()) { /* もし成功したら */
                $results = $stmt->fetchAll();   /* SQL文で出力した結果を格納． */
                $amount =  count($results);   /* 1であれば抽出成功． */
                if ($amount == 0) {
                    echo "表示できる投稿がありません。<br>";
                    return -7;
                }
                if ($pid == null) {
                    foreach ($results as $row) {    //* 抽出結果について，出力． */
                        echo "No.", $row['id']."\t";
                        echo $row['name']."さん"."\t";
                        echo "(".$row['postdate'].") <br>";
                        echo "\t",$row['comment']."<br>";
                    }
                    return 0;
                } elseif ($results[0]['password'] == null) {
                    return -1;
                } elseif ($pass != null) {
                    if ($pass == $results[0]['password']) {
                        $this->index        = $results[0]['id'];
                        $this->nickname     = $results[0]['name'];
                        $this->comment      = $results[0]['comment'];
                        $this->valid        = $results[0]['password'];
                        return 1;   /* 抽出成功 */
                    }
                } else {
                    echo "パスワードが間違っています．<br>";
                    return -2;  /* パスコード不一致 */
                }
            } else {
                echo "SQLが実行できませんでした．<br>";
                return -8;
            }
        } else {
            echo "データベース接続先がありません．<br>";
            return -9;
        }
    }

    /**
     * 投稿を削除する関数．
     *
     * @param PDO $pdo  データベース接続先
     * @return bool 投稿削除できたか?
     */
    public function Del_comment($pdo=null, $pid)
    {
        if ($pid == null) {
            echo "No Defined Delete Number<br>";
            return false;
        }
        //echo "DELETE * FROM Dashboard WHERE id=".$pid."<br>";
        if ($pdo != null) {
            $proc_no = $this->Load_comment($pdo, $pid, $this->delete_pass);
            switch ($proc_no) {
                case -1:
                    echo "削除できる投稿ではありません<br>";
                break;
                case 1:
                    $match_sql = "DELETE FROM Dashboard WHERE id=:id";/* 選択した番号を検索する */
                    $stmt = $pdo->prepare($match_sql);
                    $stmt->bindParam(':id', $pid, PDO::PARAM_INT);
                    if ($stmt->execute()) { /* もし成功したら */
                        return true;
                    } else {
                        echo "SQLが実行できませんでした．<br>";
                    }
                break;
            }
        } else {
            echo "データベース接続先がありません．<br>";
        }
        return false;
    }

    /**
     * それぞれの値を確認する為の関数(デバッグ)
     *
     * @return void
     */
    public function display_value()
    {
        foreach ($this as $key => $value) {
            echo $key.":",$value."<br>";
        }
    }
}   /**
 *  class USER End;
 */



    //* メイン */
    if (isset($_POST)) {
        try {
            $poster = new USER(
                $nickname       = isset($_POST['nickname'])     ?   $_POST['nickname']      : null,
                $comment        = isset($_POST['comment'])      ?   $_POST['comment']       : null,
                $password       = isset($_POST['post_pass'])    ?   $_POST['post_pass']     : null,
                $edit_num       = isset($_POST['edit_num'])     ?   $_POST['edit_num']      : null,
                $edit_pass      = isset($_POST['edit_pass'])    ?   $_POST['edit_pass']     : null,
                $delete_num     = isset($_POST['delete_num'])   ?   $_POST['delete_num']    : null,
                $delete_pass    = isset($_POST['delete_pass'])  ?   $_POST['delete_pass']   : null
            );

            //    $poster->display_value();
            if (isset($_POST["post_d"])) {  //  投稿ボタンを押したら
                $poster->Reg_comment($pdo, $_POST['index']); //  データベースに名前とコメントを投稿する
            } elseif (isset($_POST["edit_d"]) || isset($_POST["delete_d"])) {
                $pid = $poster->get_pid();
                
                if (isset($_POST["edit_d"])) {    //編集要求があれば
                    $chk = $poster->Load_comment($pdo, $pid, $poster->edit_pass);
                    switch ($chk) {
                        case -1:
                            echo "編集できる投稿ではありません．<br>";
                        break;

                        default:
                        $form_idx       = $poster->index;
                        $form_nickname  = $poster->nickname;
                        $form_comment   = $poster->comment;
                        break;
                    }
                } elseif (isset($_POST["delete_d"])) { // 削除要求があれば
                    $poster->Del_comment($pdo, $pid);
                }
            }
            //$poster->Load_comment($pdo);
        } catch (Exception $e) {
            echo "コンストラクタ作成できませんでした．<br>";
        }
    }

?>

    <!-- 新規投稿 -->
    <form action="" method="post">
        <input type="hidden" name="index" value=<?php echo $form_idx ?>>
        <input type="text" name="nickname" placeholder="名前" value=<?php echo $form_nickname;?>>
        <!-- 名前 -->
        <input type="text" name="comment" placeholder="コメント" value=<?php echo $form_comment;?>>
        <!-- 投稿文 -->
        <input type="password" name="post_pass" placeholder="パスワード"><!-- パスワード -->
        <input type="submit" name="post_d" value="投稿">
    </form>

    <!-- 編集フォーム -->
    <form action="" method="post">
        <input type="number" name="edit_num" placeholder="編集番号" min=0> <!-- 編集番号 -->
        <input type="password" name="edit_pass" placeholder="パスワード"> <!-- 編集のためのパスワード -->
        <input type="submit" name="edit_d" value="編集">
    </form>

    <!-- 削除用フォーム -->
    <form action="" method="post">
        <input type="number" name="delete_num" placeholder="削除番号" min=0> <!-- 削除番号 -->
        <input type="password" name="delete_pass" placeholder="パスワード"> <!-- 削除するためのパスワード -->
        <input type="submit" name="delete_d" value="削除">
    </form>
    <hr>

    <h4>過去に投稿された記事</h4>
    <?php
    $poster->Load_comment($pdo);
    ?>

</body>

</html>