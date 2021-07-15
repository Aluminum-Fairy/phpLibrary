<?php
//ini_set('display_errors', "On");
class Reaction
{
    protected $reactId;                      //いいねの数
    protected $dbh;                         //データベースハンドラ
    protected $msgId;                   //いいねをするメッセージのID
    protected $reactType;               //いいねの種類

    use Verify;

    function __construct($dsn, $db_user, $db_pass)
    {
        try {
            $this->dbh = new PDO($dsn, $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            echo json_encode(array("error" => "DB接続エラー"));
            exit();
        }
    }

    public function inputMsgid($input)
    {
        $this->msgId = $input;
    }

    public function inputReactType($input)
    {
        $this->reactType = $input;
    }

    public function regRea2db()
    {																													//サーバーへ送信されたリアクションをメッセージIDを元に関連付けて登録する関数
        if (is_null($this->msgId) || is_null($this->reactType)) {            											//空だったときにfalseを返す
            return false;
        }

        if (!$this->checkMsgExst()) {
            return false;
        }

        $regReaSql = "INSERT INTO reactionList(msgId,reactType) VALUES(:msgId,:reactType)";
        $regReaPre = $this->dbh->prepare($regReaSql);
        $regReaPre->bindValue(":msgId", $this->msgId, PDO::PARAM_INT);
        $regReaPre->bindValue(":reactType", $this->reactType, PDO::PARAM_INT);

        if ($regReaPre->execute()) {
            return true;
        }
        return false;
    }
}
