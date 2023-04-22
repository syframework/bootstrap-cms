<?php
namespace Sy\Bootstrap\Db;

use Sy\Db\Sql;

class ContentHistory extends Crud {

	public function __construct() {
		parent::__construct('t_content_history');
	}

	/**
	 * Retrieve all versions for a content
	 *
	 * @param  int $contentId
	 * @param  string|null $updatedAt
	 * @return array
	 */
	public function retrieveContentVersions($contentId, $updatedAt = null) {
		$condition = '';
		$param = [':id' => $contentId];
		if (!empty($updatedAt)) {
			$condition = 'AND `updated_at` < :updated_at';
			$param[':updated_at'] = $updatedAt;
		}
		$sql = new Sql("
			SELECT *
			FROM v_content_history
			WHERE `id` = :id $condition
			ORDER BY updated_at DESC
			LIMIT 10
		", $param);
		return $this->db->queryAll($sql, \PDO::FETCH_ASSOC);
	}

	/**
	 * Count all versions for a content
	 *
	 * @param  int $contentId
	 * @param  string|null $updatedAt
	 * @return int
	 */
	public function countContentVersions($contentId, $updatedAt = null) {
		$condition = '';
		$param = [':id' => $contentId];
		if (!empty($updatedAt)) {
			$condition = 'AND `updated_at` < :updated_at';
			$param[':updated_at'] = $updatedAt;
		}
		$sql = new Sql("
			SELECT COUNT(*)
			FROM v_content_history
			WHERE `id` = :id $condition
		", $param);
		$res = $this->db->queryOne($sql);
		return $res[0];
	}

}