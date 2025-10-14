<?php
class ProductVariant
{
	private $conn;
	private $db_table = 'product_variants';



	public function __construct($db)
	{
		$this->conn = $db;
	}

	public function getProductVariant($productIds)
	{
		$placeholders = implode(',', array_fill(0, count($productIds), '?'));

		$sqlQuery =
			'SELECT id, product_id, item_no, size, color, price, special_price, stock, availability FROM ' .
			$this->db_table;

		if (count($productIds) > 1) {
			$sqlQuery .=
				' WHERE product_id IN (' . $placeholders . ') ' .
				' ';

			$stmt = $this->conn->prepare($sqlQuery);
			$stmt->execute($productIds);
			return $stmt;
		} else if (count($productIds) == 1) {
			$sqlQuery .=
				' WHERE product_id = :product_id ' .
				' ';

			$stmt = $this->conn->prepare($sqlQuery);
			$stmt->bindParam(':product_id', $productIds[0]);
			$stmt->execute();
			return $stmt;
		}
	}
}
