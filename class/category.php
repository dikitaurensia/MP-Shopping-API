<?php
class Category
{
	private $conn;
	private $db_table = 'product_categories';

	public $id;
	public $name;
	public $image;

	public function __construct($db)
	{
		$this->conn = $db;
	}

	public function getCategories($request)
	{
		$sqlQuery =
			'SELECT id, name, image FROM ' .
			$this->db_table .
			' ';

		if (isset($request['search']) && !empty($request['search'])) {
			$sqlQuery .= 'WHERE name LIKE :search ';
		}

		$sqlQuery .= 'LIMIT :limit OFFSET :offset';

		$stmt = $this->conn->prepare($sqlQuery);

		$search = '';
		if (isset($request['search']) && !empty($request['search'])) {
			$search = '%' . $request['search'] . '%';
			$stmt->bindParam(':search', $search);
		}

		$stmt->bindParam(':limit', $request['limit'], PDO::PARAM_INT);
		$offset = ($request['page'] - 1) * $request['limit'];
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

		$stmt->execute();

		$countQuery = 'SELECT COUNT(*) as total FROM ' . $this->db_table . ' ';

		if (isset($request['search']) && !empty($request['search'])) {
			$countQuery .= 'WHERE name LIKE :search';
		}

		$countStmt = $this->conn->prepare($countQuery);

		if (isset($request['search']) && !empty($request['search'])) {
			$countStmt->bindParam(':search', $search);
		}

		$countStmt->execute();

		$totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
		$totalData = $totalRow['total'];

		return [
			'stmt' => $stmt,
			'total' => $totalData
		];
	}
}
