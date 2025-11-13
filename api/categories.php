<?php
ini_set('max_execution_time', 300);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Max-Age: 3600');

// Handle OPTIONS requests for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204); // No Content
	exit();
}

include_once '../config/database.php';
include_once '../class/category.php';

$db_table = 'categories';

try {
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$queryParam = [];
		$queryParam['page'] = isset($_GET['page']) && !empty($_GET['page']) ? $_GET['page'] : 1;
		$queryParam['limit'] = isset($_GET['limit']) && !empty($_GET['limit']) ? $_GET['limit'] : 20;
		$queryParam['search'] = isset($_GET['search']) && !empty($_GET['search']) ? $_GET['search'] : '';

		$database = new Database();
		$db = $database->getConnection();

		$categories = new Category($db);
		$data = $categories->getCategories($queryParam);
		$result = $data['stmt']->fetchAll(PDO::FETCH_ASSOC);
		$totalData = intval($data['total']);

		$response = [];
		$response['message'] = 'Data ' . $db_table . ' berhasil diambil';
		$response['result'] = [];
		$response['pagination'] = [
			'total_data' => 0,
			'total_pages' => 0,
		];

		if ($totalData > 0) {
			foreach ($result as $row) {
				$item = [
					'id' => intval($row['id']),
					'name' => $row['name'],
					'image' => $row['image'],
				];
				array_push($response['result'], $item);
			}

			$response['pagination']['total_data'] = $totalData;
			$response['pagination']['total_pages'] = ceil($totalData / $queryParam['limit']);
		}

		header('Content-Type: application/json; charset=UTF-8');
		http_response_code(200);
		echo json_encode($response);
	} else {
		throw new Exception("Method not allowed", 405);
	}
} catch (Exception $e) {
	$errorResponse = [
		'message' => $e->getMessage(),
	];
	$errorCode = $e->getCode();
	if (!is_int($errorCode)) {
		$errorCode = 500;
	}
	http_response_code($errorCode);
	echo json_encode($errorResponse);
}
