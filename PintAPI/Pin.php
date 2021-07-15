<?php
class Pin
{
    protected $dbh;
    protected $userId;
    protected $pinId;
    protected $pinTitle;
    protected $pinType;
    protected $pinTime;
    protected $lastInsertID;
    protected $mvId;
    protected $pinArray;
    protected $BestReactArray;

    use Verify;

    //失敗した際にエラーを返す関数
    function __construct($dsn, $db_user, $db_pass)
    {
        try {
            $this->dbh = new PDO($dsn, $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            echo json_encode(array("error" => "DB接続エラー"));
            exit();
        }
    }

    public function inputPinID($input)
    {
        $this->pinId = $input;
    }

    public function inputPinType($input)
    {
        $this->pinType = $input;
    }

    public function inputUserID($input)
    {
        $this->userId = $input;
    }

    public function inputPinTime($input)
    {
        $this->pinTime = $input;
    }

    public function inputMvID($input)
    {
        if (is_numeric($input)) {
            $this->mvId = $input;
        }
    }

    public function getPinArray()
    {
        return $this->pinArray;
    }

    public function getLastInsertID()
    {
        return $this->lastInsertID;
    }

    public function getBestReactArray()
    {
        return $this->BestReactArray;
    }

    public function regPin2db()
    {																													//サーバーへ送信されたPinの登録情報をDBに格納する関数。
        if (is_null($this->pinType) || is_null($this->pinTime) || is_null($this->mvId)) {
            return false;
        }

        if (!$this->checkPinTime()) {
            return false;
        }

        //INSERT INTOでテーブルにVALUESの数値を入れる
        $regPinSql = "INSERT INTO pinList (pinType,pinTime,mvId) VALUES (:PinType,:PinTime,:MvID)";
        $regPinPre = $this->dbh->prepare($regPinSql);
        $regPinPre->bindValue(":PinType", $this->pinType, PDO::PARAM_INT);
        $regPinPre->bindValue(":PinTime", $this->pinTime, PDO::PARAM_INT);
        $regPinPre->bindValue(":MvID", $this->mvId, PDO::PARAM_INT);

        //この変数の値で実行できるかexecute()で確認
        //データベースの型を確認
        if ($regPinPre->execute()) {
            $this->lastInsertID = $this->dbh->lastInsertId();
            return true;
        }

        return false;
    }

    public function loadPin()
    {																													//指定されたMovieIDのPin一覧を読み出す関数
        if (is_null($this->mvId)) {
            return false;
        }

        if (!$this->checkMvExst()) {																					//存在するMovieIDであることを確認
            return false;
        }

        $allPinSql =
            "SELECT pinList.pinId,pinList.pinId,pinList.pinTime ,ifnull(SUM(reactSum),0) AS reactSum,COUNT(msgTable.msgId) AS msgSum
            FROM pinList LEFT JOIN (
                SELECT chatMsg.msgId,chatMsg.pinId,COUNT(reactionList.reactId) AS reactSum
                FROM reactionList RIGHT JOIN chatMsg ON reactionList.msgId = chatMsg.msgId GROUP BY chatMsg.msgId) AS msgTable
            ON msgTable.pinId = pinList.pinId WHERE pinList.mvId=:MvID GROUP BY pinList.pinId ";


        $allPinPre = $this->dbh->prepare($allPinSql);
        $allPinPre->bindValue(":MvID", $this->mvId, PDO::PARAM_INT);

        if ($allPinPre->execute()) {                                                                                    //DB処理の成功失敗
            $this->pinArray = $allPinPre->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
            return true;
        }
        return false;
    }

    public function loadBestReact()
    {																													//指定されたPinIDのリアクションの多かったメッセージのメッセージIDとリアクション数を返す関数。リアクションがない場合は最新のメッセージのメッセージIDを返し、メッセージが存在しない場合はFalseを返す
        if (is_null($this->pinId)) {
            return false;
        }

        if (!$this->checkPinExst()) {
            return false;
        }

        $loadReactSql =
            "WITH msgTable AS (SELECT chatMsg.msgId,chatMsg.pinId,COUNT(reactionList.reactId) AS reactSum FROM reactionList RIGHT JOIN chatMsg ON reactionList.msgId = chatMsg.msgId WHERE pinId =:PinID GROUP BY chatMsg.msgId)
        SELECT msgTable.msgId,msgId,reactSum AS reactNum FROM msgTable WHERE msgTable.reactSum = (SELECT MAX(reactSum) FROM msgTable) AND msgTable.pinId=:PinID ORDER BY msgTable.msgId DESC ";

        $loadReactPre = $this->dbh->prepare($loadReactSql);
        $loadReactPre->bindValue(':PinID', $this->pinId);
        if ($loadReactPre->execute()) {
            $this->BestReactArray = $loadReactPre->fetch(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
            return !empty($this->BestReactArray['msgId']);																//メッセージが存在しているかどうかを確認。メッセージIDは1からスタートなので0が入ってFalse扱いになることはない
        }
        return false;
    }
}
