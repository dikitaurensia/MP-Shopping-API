<?php
error_reporting(E_ALL); // Menampilkan semua error, warning, notice
ini_set('display_errors', 1); // Menampilkan error ke browser
class Product
{
	private $conn;
	private $db_table = 'products';

	// ... other properties (not shown in the original, but good practice)

	public function __construct($db)
	{
		$this->conn = $db;
	}

	/**
	 * Retrieves a list of products with filtering, sorting, and pagination.
	 * @param array $request Query parameters (page, limit, search, etc.)
	 * @return array Contains 'stmt' (PDOStatement) and 'total' (int)
	 */
	public function getProducts($request)
	{
		$limit = $request['limit'];
		// Calculate OFFSET for pagination
		$offset = ($request['page'] - 1) * $limit;

		// Allowed sort columns to prevent SQL injection in ORDER BY clause
		$allowedSortColumns = [
			'name' => 'p.name',
		];

		$sortBy = $allowedSortColumns[$request['sort_by']] ?? $allowedSortColumns['name'];
		$sortType = (strtoupper($request['sort_type']) === 'DESC') ? 'DESC' : 'ASC';

		//SELECT p.id AS id, p.name, p.category, pi.image_url , p.brand, min(pv.price) AS price FROM 


		// Base SELECT query parts
		$select = 'SELECT p.id, p.name AS name, p.category, p.brand, p.tag,
                   pi.image_url, min(pv.price) AS price';

		$from = ' FROM ' . $this->db_table . ' p ';
		$joins = ' LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_thumbnail = 1'
			. ' LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.availability = 1 ';

		$whereClauses = ['pv.id IS NOT NULL']; // Only include products with available variants
		$bindParams = [];

		// 1. Apply Search Filter (e.g., name or description)
		if (!empty($request['search'])) {
			$search = '%' . $request['search'] . '%';
			$whereClauses[] = '(p.name LIKE :search OR p.description LIKE :search)';
			$bindParams[':search'] = $search;
		}

		// 2. Apply Category Filter
		if (!empty($request['category'])) {
			$whereClauses[] = 'p.category = :category';
			$bindParams[':category'] = $request['category'];
		}

		// 3. Apply Brand (merk) Filter
		if (!empty($request['brand'])) {
			$whereClauses[] = 'p.brand = :brand';
			$bindParams[':brand'] = $request['brand'];
		}


		// Build the WHERE clause
		$where = ' WHERE ' . implode(' AND ', $whereClauses);

		// Grouping is necessary due to the MIN(pv.price) and image join
		$group = ' GROUP BY p.id ';

		// --- 1. Count Total Data ---
		$countQuery = 'SELECT COUNT(DISTINCT p.id) as total ' . $from . $joins . $where;
		$countStmt = $this->conn->prepare($countQuery);

		// Bind the same parameters for the count query
		if (!empty($bindParams)) {
			foreach ($bindParams as $key => $value) {
				$countStmt->bindValue($key, $value);
			}
		}

		$countStmt->execute();
		$totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
		$totalData = $totalRow['total'];

		// --- 2. Retrieve Paginated Data ---
		$orderAndLimit = ' ORDER BY ' . $sortBy . ' ' . $sortType
			. ' LIMIT :limit OFFSET :offset';

		$sqlQuery = $select . $from . $joins . $where . $group . $orderAndLimit;

		$stmt = $this->conn->prepare($sqlQuery);

		// Bind all parameters: filters, limit, and offset
		if (!empty($bindParams)) {
			foreach ($bindParams as $key => $value) {
				$stmt->bindValue($key, $value);
			}
		}

		// Bind pagination parameters (always integers)
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

		$stmt->execute();

		return [
			'stmt' => $stmt,
			'total' => $totalData,
		];
	}

	/**
	 * Retrieves a single product by ID.
	 * @param int $id The product ID.
	 * @return PDOStatement
	 */
	public function getProduct($id)
	{
		// Select all required details for the single product view
		// Note: The structure in the API endpoint implies these columns exist in the 'products' table.
		$sqlQuery =
			'SELECT p.id, p.name, p.category, p.description, p.brand, p.tag ' .
			'FROM ' . $this->db_table . ' p ' .
			'WHERE p.id = :id ' .
			'LIMIT 1';

		$stmt = $this->conn->prepare($sqlQuery);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt;
	}
}