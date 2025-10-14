<?php
error_reporting(E_ALL); // Menampilkan semua error, warning, notice
ini_set('display_errors', 1); // Menampilkan error ke browser
ini_set('max_execution_time', 300);

// CORS Headers - Keep these as they are
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

// Ensure the Database connection is established early
include_once '../config/database.php';
include_once '../class/product.php';
include_once '../class/product-image.php';
include_once '../class/product-variant.php';

$db_table = 'products';
$database = new Database(); // Assuming Database is a valid class for connection
$db = $database->getConnection(); // Get PDO connection

try {
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		if (isset($_GET['id']) && !empty($_GET['id'])) {
			// --- Logic for single product retrieval (GET by ID) ---

			$response = [
				'message' => 'Data ' . $db_table . ' berhasil diambil',
				'result' => null
			];

			$products = new Product($db);
			// Assuming getProduct returns a single product statement
			$stmt = $products->getProduct($_GET['id']);
			$item = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($item) {
				$productIds = [$item['id']];

				$productImages = new ProductImage($db);
				// Assuming getProductImages accepts an array of product IDs
				$productImageStmt = $productImages->getProductImages($productIds);
				$productImageItems = $productImageStmt->fetchAll(PDO::FETCH_ASSOC);

				// Map images by product_id
				$mapProductImageByProductId = [];
				foreach ($productImageItems as $image_item) {
					$productId = $image_item['product_id'];
					if (!isset($mapProductImageByProductId[$productId])) {
						$mapProductImageByProductId[$productId] = [];
					}
					// Only include necessary image attributes for the detail view
					$mapProductImageByProductId[$productId][] = [
						'id' => intval($image_item['id']),
						'image_url' => $image_item['image_url'],
					];
				}

				$productVariant = new ProductVariant($db);
				$productVarianStmt = $productVariant->getProductVariant($productIds);
				$productVariantItems = $productVarianStmt->fetchAll(PDO::FETCH_ASSOC);

				// Map images by product_id
				$mapProductVariantByProductId = [];
				foreach ($productVariantItems as $variant_item) {
					$productId = $variant_item['product_id'];
					if (!isset($mapProductVariantByProductId[$productId])) {
						$mapProductVariantByProductId[$productId] = [];
					}


					$mapProductVariantByProductId[$productId][] = [
						'id' => intval($variant_item['id']),
						'product_id' => intval($variant_item['product_id']),
						'item_no' => $variant_item['item_no'],
						'size' => $variant_item['size'],
						'color' => $variant_item['color'],
						'price' => intval($variant_item['price']),
						'special_price' => intval($variant_item['special_price']),
						'stock' => intval($variant_item['stock']),
						'availability' => intval($variant_item['availability']),
					];
				}
				// Correctly structure the single item response
				$response['result'] = array(
					'id' => intval($item['id']),
					'name' => $item['name'],
					'category' => $item['category'],
					'description' => $item['description'],
					'brand' => $item['brand'],
					'tag' => $item['tag'],
					'images' => $mapProductImageByProductId[$item['id']] ?? [],
					'variants' => $mapProductVariantByProductId[$item['id']] ?? [],
				);

				http_response_code(200);
				echo json_encode($response);
			} else {
				// No product found with that ID
				$response['message'] = 'Product not found';
				http_response_code(404);
				echo json_encode($response);
			}
		} else {
			// --- Logic for product list retrieval (GET all/paginated) ---

			$queryParam = [];
			// Use coalesce operator (??) for cleaner defaults and type casting
			$queryParam['page'] = (int) ($_GET['page'] ?? 1);
			$queryParam['limit'] = (int) ($_GET['limit'] ?? 10);
			$queryParam['search'] = $_GET['search'] ?? '';
			$queryParam['category'] = $_GET['category'] ?? '';
			$queryParam['brand'] = $_GET['brand'] ?? '';
			$queryParam['color'] = $_GET['color'] ?? '';
			$queryParam['sort_by'] = $_GET['sort_by'] ?? 'name';
			$queryParam['sort_type'] = strtoupper($_GET['sort_type'] ?? 'ASC');

			$products = new Product($db);
			// Assuming getProducts returns an array with 'stmt' (PDOStatement) and 'total' (int)
			$data = $products->getProducts($queryParam);
			$result = $data['stmt']->fetchAll(PDO::FETCH_ASSOC);
			$totalData = (int) $data['total'];

			$response = [
				'message' => 'success get ' . $db_table,
				'result' => [],
				'pagination' => [
					'total_data' => 0,
					'total_pages' => 0,
				],
			];

			if ($totalData > 0) {
				// 1. Get all product IDs from the current result set
				$productIds = array_column($result, 'id');

				// 2. Fetch all images for these product IDs
				$productImages = new ProductImage($db);
				// Assuming getProductImages accepts an array of product IDs
				$productImageStmt = $productImages->getProductImages($productIds);
				$productImageItems = $productImageStmt->fetchAll(PDO::FETCH_ASSOC);

				// 3. Map images by product_id
				$mapProductImageByProductId = [];
				foreach ($productImageItems as $image_item) {
					$productId = $image_item['product_id'];
					if (!isset($mapProductImageByProductId[$productId])) {
						$mapProductImageByProductId[$productId] = [];
					}
					// Only include necessary image attributes for the list view
					$mapProductImageByProductId[$productId][] = [
						'id' => intval($image_item['id']),
						'image_url' => $image_item['image_url'], // Assuming this field exists
						// Add other necessary image attributes here
					];
				}

				// 4. Map the product data and inject the images
				$response['result'] = array_map(function ($row) use ($mapProductImageByProductId) {
					$productId = intval($row['id']);
					// Map product fields
					$product = [
						'id' => $productId,
						'name' => $row['name'] ?? null, // Use null coalescing if keys might be missing
						'category' => $row['category'] ?? null,
						'brand' => $row['brand'] ?? null,
						'price' => floatval($row['price'] ?? 0),
						'tag' => $row['tag'] ?? null,
						'image_url' => $row['image_url'] ?? null,
						// **INJECT ALL IMAGES HERE**
						'images' => $mapProductImageByProductId[$productId] ?? [],
					];

					// Optional: If you want to replace 'image_url' with the main image from product_images
					// $main_image = array_filter($product['product_images'], fn($img) => !$img['is_detail']); // Example: non-detail image as main
					// if (!empty($main_image)) {
					//     $product['image_url'] = reset($main_image)['image_url'];
					// }

					return $product;
				}, $result);


				$response['pagination']['total_data'] = $totalData;
				// Avoid division by zero
				$response['pagination']['total_pages'] = $queryParam['limit'] > 0 ? ceil($totalData / $queryParam['limit']) : 0;
			}

			http_response_code(200);
			echo json_encode($response);
		}
	} else {
		// Only GET method is allowed for this endpoint
		throw new Exception("Method not allowed", 405);
	}
} catch (Exception $e) {
	// Handle exceptions and return appropriate error response
	$errorCode = $e->getCode();
	if (!is_int($errorCode) || $errorCode < 100 || $errorCode > 599) {
		$errorCode = 500; // Default to 500 if code is not a valid HTTP code
	}

	$errorResponse = [
		'message' => $e->getMessage(),
	];

	http_response_code($errorCode);
	echo json_encode($errorResponse);
}