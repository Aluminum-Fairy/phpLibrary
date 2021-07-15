<?php
class UserInfo
{
	protected $userId;
	protected $userName;
	protected $passwd;
	protected $dbh;

	use Verify;

	function __construct($dsn, $db_user, $db_pass)
	{																													//初期化時にデータベースへの接続
		try {
			$this->dbh = new PDO($dsn, $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
		} catch (PDOException $e) {
			echo json_encode(array("error" => "DB接続エラー"));
			exit();
		}
	}

	public function inputUserId($input)
	{
		$this->userId = $input;
	}

	public function inputName($input)
	{
		$this->userName = $input;
	}

	public function inputPasswd($input)
	{
		$this->passwd = $input;
	}

	public function userAuth()
	{																													//ユーザー認証を行う。成功時はTrueを返し、認証失敗時（エラーを含む）Falseを返す
		if (is_null($this->userId) || is_null($this->passwd)) {															//NULLチェック。ユーザーIDとパスワードが万が一入力されていない場合はFalseを返して終了する
			return false;
		}

		if ($this->checkUserExst()) {																					//ユーザーIDが登録されているものかを確認する。登録されていない場合はFalseを返して終了する。
			return false;
		}
		$authSQL = "SELECT `Passwd` FROM userList WHERE userId = :userId";
		$authPre = $this->dbh->prepare($authSQL);
		$authPre->bindValue(":userId", $this->userId, PDO::PARAM_STR);
		if ($authPre->execute()) {
			return password_verify($this->passwd, ($authPre->fetch())['Passwd']);										//データベースへに格納されているハッシュ値と入力されたデータを比較し、成功時Trueを、失敗時Falseを返す
		}
		echo "AuthFalse";
		return false;
	}

	public function chPasswd($newPasswd)
	{																													//パスワードを変更用。引数として新しいパスワードを入浴し、予め現在のユーザー名とパスワードがセットされている必要がある。現時点で不要である場合でも必要になる可能性があるため残すこと。
		if (!$this->userAuth()) {																						//ユーザー認証を行う。失敗時Falseを返して終了する。
			return false;
		}
		$chPasswdsql = "UPDATE userList SET Passwd = :newPasswd WHERE userId = :userId";
		$chPasswdpre = $this->dbh->prepare($chPasswdsql);
		$chPasswdpre->bindValue(":newPasswd", password_hash($newPasswd, PASSWORD_DEFAULT), PDO::PARAM_STR);
		$chPasswdpre->bindValue(":userId", $this->userId, PDO::PARAM_STR);
		return $chPasswdpre->execute();																					//パスワード変更を完了した場合Trueを返す。失敗時はFalseを返す。
	}
}
