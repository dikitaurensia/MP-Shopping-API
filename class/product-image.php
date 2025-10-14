<?php
class ProductImage
{
	private $conn;
	private $db_table = 'product_images';

	public $id;
	public $image_url;

	public function __construct($db)
	{
		$this->conn = $db;
	}

	public function getProductImages($productIds)
	{
		$placeholders = implode(',', array_fill(0, count($productIds), '?'));

		$sqlQuery =
			'SELECT id, image_url, is_thumbnail, product_id FROM ' .
			$this->db_table;

		if (count($productIds) > 1) {
			$sqlQuery .=
				' WHERE product_id IN (' . $placeholders . ') ' .
				' ORDER BY is_thumbnail DESC ' .
				' ';

			$stmt = $this->conn->prepare($sqlQuery);
			$stmt->execute($productIds);
			return $stmt;
		} else if (count($productIds) == 1) {
			$sqlQuery .=
				' WHERE product_id = :product_id ' .
				' ORDER BY is_thumbnail DESC ' .
				' ';

			$stmt = $this->conn->prepare($sqlQuery);
			$stmt->bindParam(':product_id', $productIds[0]);
			$stmt->execute();
			return $stmt;
		}
	}
}
