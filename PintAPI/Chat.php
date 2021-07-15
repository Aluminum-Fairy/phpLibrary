<?php
class Chat
{
	protected $dbh;
	protected $userId;
	protected $msgId;
	protected $pinId;
	protected $lastInsertID;
	protected $lastUpTime;
	protected $rcvMessage;
	protected $chatArray;

	use Verify;


	function __construct($dsn, $db_user, $db_pass)
	{																		//初期化時にデータベースへの接続
		try {
			$this->dbh = new PDO($dsn, $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
		} catch (PDOException $e) {
			echo json_encode(array("error" => "DB接続エラー"));
			exit();
		}
	}

	public function inputPinid($input)
	{
		if (is_numeric($input)) {																						//PinIDはint型で管理しているので、英字記号を入力されたら弾く
			$this->pinId = $input;
		}
	}

	public function inputMsgid($input)
	{
		if (is_numeric($input)) {																						//MessageIDはint型で管理しているので、英字記号を入力されたら弾く
			$this->msgId = $input;
		}
	}

	public function inputRcvMsg($input)
	{
		if (mb_strlen($rcvMsg = str_replace(array(" ", "　"), "", $input)) != 0) {										//スペース入れまくる荒らし対策。
			$this->rcvMessage = htmlspecialchars($rcvMsg, ENT_QUOTES, 'UTF-8');											//ここでHTMLファイルを崩す原因となある文字を置き換える
		}
	}

	public function inputUserId($input)
	{
		if (is_numeric($input)) {																						//UserIdはint型で管理しているため、英数字を入力されたらはじく
			$this->userId = $input;
		}
	}

	public function getLastInsertID()
	{
		return $this->lastInsertID;
	}

	public function getChatArray()
	{
		return $this->chatArray;
	}

	public function regMessage2db()
	{																													//サーバーへ送られてきたメッセージをDBに格納する関数
		if (is_null($this->userId) || (is_null($this->rcvMessage) || is_null($this->pinId))) {							//必要な要素が変数に格納されているかどうかを確認。
			return false;
		}

		if (!$this->checkPinExst()) {
			return false;
		}

		$regMsgSql = "INSERT INTO chatMsg (pinId,msg,userId) VALUES (:PinID,:Message,:UserID)";							//DBにPinIDとメッセージとユーザーIDをDBに登録

		if (!is_null($this->msgId)) {																					//返信用SQLに切り替える
			if (!$this->checkMsgExst()) {																				//DBに存在するmsgIdで有るかどうかを確認
				return false;
			}
			$regMsgSql = "INSERT INTO chatMsg (pinId,msg,userId,msgGroup) VALUES (:PinID,:Message,:UserID,:MessageGroup)";
		}

		$regMsgPre = $this->dbh->prepare($regMsgSql);
		$regMsgPre->bindValue(":PinID", $this->pinId, PDO::PARAM_INT);
		$regMsgPre->bindValue(":Message", $this->rcvMessage, PDO::PARAM_STR);
		$regMsgPre->bindValue(":UserID", $this->userId, PDO::PARAM_INT);
		if (!is_null($this->msgId)) {
			$regMsgPre->bindValue(":MessageGroup", $this->msgId, PDO::PARAM_INT);
		} else {
			$updateMsgGroupSql = "UPDATE chatMsg SET msgGroup = :MsgGroup WHERE msgId = :MsgGroup";						//返信に使う要素msgGroupをまず返信の親にセット
			$updateMsgGroupPre = $this->dbh->prepare($updateMsgGroupSql);
		}


		if ($regMsgPre->execute()) {																					//DB処理の成功失敗
			$this->lastInsertID = $this->dbh->lastInsertId();
			if (is_null($this->msgId)) {
				$updateMsgGroupPre->bindValue(":MsgGroup", $this->lastInsertID, PDO::PARAM_INT);
				return $updateMsgGroupPre->execute();
			}
			return true;
		}
		return false;
	}

	public function loadMessage()
	{																													//指定されたPinIDのメッセージ、メッセージグループ、チャット送信時間、送信者、リアクション数をロードする関数
		if (is_null($this->pinId)) {
			return false;
		}

		if (!$this->checkPinExst()) {																					//存在するPinIDであることを確認
			return false;
		}

		$allMsgSql =
			"SELECT chatMsg.msgId,chatMsg.msgId,chatMsg.msgGroup,chatMsg.msgTime,chatMsg.msg,userList.userName,COUNT( reactionList.reactType) AS reactNum
			FROM chatMsg natural join userList LEFT JOIN reactionList ON chatMsg.msgId = reactionList.msgId
			WHERE chatMsg.pinId = :PinID GROUP BY chatMsg.msgId";
		if (!is_null($this->msgId)) {																					//メッセージIDが入力されていたら入力されたメッセージID以降のメッセージをDBから読み出す
			$allMsgSql =
				"SELECT chatMsg.msgId,chatMsg.msgId,chatMsg.msgGroup,chatMsg.msgTime,chatMsg.msg,userList.userName,COUNT( reactionList.reactType) AS reactNum
				FROM chatMsg natural join userList LEFT JOIN reactionList ON chatMsg.msgId = reactionList.msgId
				WHERE chatMsg.pinId = :PinID AND chatMsg.msgId > :MessageID GROUP BY chatMsg.msgId";
		}

		$allMsgPre = $this->dbh->prepare($allMsgSql);
		$allMsgPre->bindValue(":PinID", $this->pinId, PDO::PARAM_INT);
		if (!is_null($this->msgId)) {
			$allMsgPre->bindValue(":MessageID", $this->msgId, PDO::PARAM_INT);
		}

		if ($allMsgPre->execute()) {																						//DB処理の成功失敗
			$this->chatArray = $allMsgPre->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
			return true;
		}
		return false;
	}
}
