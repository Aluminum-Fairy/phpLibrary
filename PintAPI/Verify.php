<?php

trait Verify
{																														//入力データの検証用各データ処理用クラスファイルよりも先に読み込ませる必要があるため注意
	public function checkMvExst()
	{																													//DBに指定された動画IDが存在しているかを確認する関数
		if (is_null($this->mvId)) {
			return false;
		}

		$checkSql = "SELECT COUNT(mvId) FROM movieList WHERE mvId =:MvID";
		$checkPre = $this->dbh->prepare($checkSql);
		$checkPre->bindValue(":MvID", $this->mvId, PDO::PARAM_INT);

		if ($checkPre->execute()) {
			return $checkPre->fetchColumn() != 0;
		}
		return false;
	}

	public function checkPinExst()
	{																													//DBに指定されたPinIDが存在しているかを確認する関数
		if (is_null($this->pinId)) {
			return false;
		}

		$checkSql = "SELECT COUNT(pinId) FROM pinList WHERE pinId = :PinID";
		$checkPre = $this->dbh->prepare($checkSql);
		$checkPre->bindValue(":PinID", $this->pinId, PDO::PARAM_INT);

		if ($checkPre->execute()) {
			return $checkPre->fetchColumn() != 0;
		}
		return false;
	}

	public function checkMsgExst()
	{																													//DBに指定されたメッセージIDが存在しているかを確認する関数
		if (is_null($this->msgId)) {
			return false;
		}

		$checkSql = "SELECT COUNT(msgId) FROM chatMsg WHERE msgId = :MessageID";
		$checkPre = $this->dbh->prepare($checkSql);
		$checkPre->bindValue(":MessageID", $this->msgId, PDO::PARAM_INT);

		if ($checkPre->execute()) {
			return $checkPre->fetchColumn() != 0;
		}
		return false;
	}

	public function checkUserExst()
	{																													//ユーザー名がすでに登録されていないかどうかを確認する関数。すでに登録されている場合"False"を返し、未登録だとTrueを返す。
		if (is_null($this->userId)) {
			return false;
		}
		$checkSQL = "SELECT COUNT(userId) FROM userList WHERE userId = :userId";
		$checkPre = $this->dbh->prepare($checkSQL);
		$checkPre->bindValue(":userId", $this->userId, PDO::PARAM_STR);
		if ($checkPre->execute()) {
			return $checkPre->fetchColumn() == 0;
		}
		return false;
	}

	public function checkPinTime()
	{																													//指定された動画IDに新規で立てるPinの指定する時間にすでにPinが立っていないかどうかを確認する
		if (is_null($this->pinTime) || is_null($this->mvId)) {
			return false;
		}
		$checkSQL = "SELECT COUNT(*) FROM pinList WHERE mvId = :MovieID AND pinTime = :PinTime";
		$checkPre = $this->dbh->prepare($checkSQL);
		$checkPre->bindValue(":MovieID", $this->mvId, PDO::PARAM_INT);
		$checkPre->bindValue(":PinTime", $this->pinTime, PDO::PARAM_INT);
		if ($checkPre->execute()) {
			return $checkPre->fetchColumn() == 0;
		}
		return false;
	}
}
