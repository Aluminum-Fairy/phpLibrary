<?php
class Movie
{
	protected $mvId;
	protected $movieInfoArray;
	protected $dbh;

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

	public function inputMvid($input)
	{
		$this->mvId = $input;
	}

	public function getMovieInfo()
	{
		return $this->movieInfoArray;
	}

	public function loadMvInfo()
	{																													//DBに登録された動画情報を読み出す関数
		if (is_null($this->mvId)) {
			return false;
		}

		if (!$this->checkMvExst()) {
			return false;
		}

		$loadMvSql =
			"SELECT mvId AS movieID,mvUrl AS videoID FROM movieList WHERE mvId = :MovieID";
		$loadMvPre = $this->dbh->prepare($loadMvSql);
		$loadMvPre->bindValue(":MovieID", $this->mvId, PDO::PARAM_INT);
		if ($loadMvPre->execute()) {
			$this->movieInfoArray = $loadMvPre->fetch(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
			return true;
		}
		return false;
	}
}
