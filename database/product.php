<?php
class product
{
    public $db = null;

    public function __construct(DBController $db) {
        $this->db = $db;
    }

    // Get all rows from any table
    public function getData($table = 'product') {
        $table  = mysqli_real_escape_string($this->db->con, $table);
        $result = $this->db->con->query("SELECT * FROM `{$table}`");
        $arr    = [];
        if ($result) {
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $arr[] = $row;
            }
        }
        return $arr;
    }

    // Get single product by item_id (prepared)
    public function getProduct($item_id = null, $table = 'product') {
        if (!isset($item_id)) return [];
        $stmt = $this->db->con->prepare("SELECT * FROM `{$table}` WHERE item_id = ? LIMIT 1");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $arr    = [];
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }
        $stmt->close();
        return $arr;
    }

    // Search products by name or brand
    public function searchProducts($query) {
        $stmt = $this->db->con->prepare(
            "SELECT * FROM product WHERE item_name LIKE ? OR item_brand LIKE ? ORDER BY item_name ASC"
        );
        $like = '%' . $query . '%';
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $arr    = [];
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }
        $stmt->close();
        return $arr;
    }

    // Get products by brand
    public function getByBrand($brand) {
        $stmt = $this->db->con->prepare("SELECT * FROM product WHERE item_brand = ?");
        $stmt->bind_param("s", $brand);
        $stmt->execute();
        $result = $stmt->get_result();
        $arr    = [];
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }
        $stmt->close();
        return $arr;
    }

    // Get on-sale products
    public function getOnSale() {
        $result = $this->db->con->query("SELECT * FROM product WHERE item_onsale = 1");
        $arr    = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
        return $arr;
    }

    // Get featured products
    public function getFeatured() {
        $result = $this->db->con->query("SELECT * FROM product WHERE item_featured = 1");
        $arr    = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
        return $arr;
    }

    // Admin: add product
    public function addProduct($data) {
        $brand       = mysqli_real_escape_string($this->db->con, $data['brand']);
        $name        = mysqli_real_escape_string($this->db->con, $data['name']);
        $price       = (float)$data['price'];
        $old_price   = (float)$data['old_price'];
        $image       = mysqli_real_escape_string($this->db->con, $data['image']);
        $description = mysqli_real_escape_string($this->db->con, $data['description']);
        $stock       = (int)$data['stock'];
        $featured    = (int)$data['featured'];
        $onsale      = (int)$data['onsale'];

        $sql = "INSERT INTO product (item_brand, item_name, item_price, item_old_price, item_image, item_description, item_stock, item_featured, item_onsale)
                VALUES ('{$brand}', '{$name}', {$price}, {$old_price}, '{$image}', '{$description}', {$stock}, {$featured}, {$onsale})";
        return $this->db->con->query($sql);
    }

    // Admin: update product
    public function updateProduct($id, $data) {
        $brand       = mysqli_real_escape_string($this->db->con, $data['brand']);
        $name        = mysqli_real_escape_string($this->db->con, $data['name']);
        $price       = (float)$data['price'];
        $old_price   = (float)$data['old_price'];
        $image       = mysqli_real_escape_string($this->db->con, $data['image']);
        $description = mysqli_real_escape_string($this->db->con, $data['description']);
        $stock       = (int)$data['stock'];
        $featured    = (int)$data['featured'];
        $onsale      = (int)$data['onsale'];
        $id          = (int)$id;

        $sql = "UPDATE product SET
                    item_brand='{$brand}', item_name='{$name}', item_price={$price},
                    item_old_price={$old_price}, item_image='{$image}', item_description='{$description}',
                    item_stock={$stock}, item_featured={$featured}, item_onsale={$onsale}
                WHERE item_id={$id}";
        return $this->db->con->query($sql);
    }

    // Admin: delete product
    public function deleteProduct($id) {
        $id = (int)$id;
        return $this->db->con->query("DELETE FROM product WHERE item_id={$id}");
    }

    // Count all products
    public function countProducts() {
        $result = $this->db->con->query("SELECT COUNT(*) as total FROM product");
        $row    = $result->fetch_assoc();
        return $row['total'];
    }
}
