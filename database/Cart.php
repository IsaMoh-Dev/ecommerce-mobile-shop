<?php
class Cart
{
    public $db = null;

    public function __construct(DBController $db) {
        $this->db = $db;
    }

    // Add item — if exists increase qty, else insert
    // Returns true on success, false if out of stock
    public function addToCart($user_id, $item_id) {
        $user_id = (int)$user_id;
        $item_id = (int)$item_id;

        // Check stock first
        $res = $this->db->con->query("SELECT item_stock FROM product WHERE item_id={$item_id} LIMIT 1");
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && (int)$row['item_stock'] <= 0) {
                return false; // Out of stock
            }
        }

        $stmt = $this->db->con->prepare(
            "INSERT INTO cart (user_id, item_id, qty) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE qty = qty + 1"
        );
        $stmt->bind_param("ii", $user_id, $item_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Update qty directly
    public function updateQty($user_id, $item_id, $qty) {
        $user_id = (int)$user_id;
        $item_id = (int)$item_id;
        $qty     = min(20, max(1, (int)$qty));
        $stmt = $this->db->con->prepare(
            "UPDATE cart SET qty=? WHERE user_id=? AND item_id=?"
        );
        $stmt->bind_param("iii", $qty, $user_id, $item_id);
        $stmt->execute();
        $stmt->close();
    }

    // Delete from cart or wishlist — fixed: requires user_id, no redirect
    public function deleteCart($item_id = null, $table = 'cart', $user_id = 0) {
        if ($item_id == null) return false;
        $item_id = (int)$item_id;
        $user_id = (int)$user_id;
        if ($user_id > 0) {
            $this->db->con->query("DELETE FROM `{$table}` WHERE item_id={$item_id} AND user_id={$user_id}");
        } else {
            $this->db->con->query("DELETE FROM `{$table}` WHERE item_id={$item_id}");
        }
        return true;
    }

    // Move cart → wishlist — no redirect, returns bool
    public function saveForLater($item_id = null, $user_id = 0) {
        if ($item_id == null) return false;
        $item_id = (int)$item_id;
        $user_id = (int)$user_id;
        $this->db->con->query(
            "INSERT IGNORE INTO wishlist (user_id, item_id)
             SELECT user_id, item_id FROM cart WHERE item_id={$item_id} AND user_id={$user_id} LIMIT 1"
        );
        $this->db->con->query("DELETE FROM cart WHERE item_id={$item_id} AND user_id={$user_id}");
        return true;
    }

    // Move wishlist → cart — no redirect, returns bool
    public function moveToCart($item_id = null, $user_id = 0) {
        if ($item_id == null) return false;
        $item_id = (int)$item_id;
        $user_id = (int)$user_id;
        $this->db->con->query(
            "INSERT INTO cart (user_id, item_id, qty) VALUES ({$user_id}, {$item_id}, 1)
             ON DUPLICATE KEY UPDATE qty = qty + 1"
        );
        $this->db->con->query("DELETE FROM wishlist WHERE item_id={$item_id} AND user_id={$user_id}");
        return true;
    }

    // Get cart rows for a user (with qty)
    public function getCartByUser($user_id) {
        $user_id = (int)$user_id;
        $result  = $this->db->con->query(
            "SELECT c.*, p.item_name, p.item_price, p.item_image, p.item_brand
             FROM cart c JOIN product p ON c.item_id = p.item_id
             WHERE c.user_id = {$user_id}"
        );
        $arr = [];
        if ($result) while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    // Get wishlist rows for a user
    public function getWishlistByUser($user_id) {
        $user_id = (int)$user_id;
        $result  = $this->db->con->query(
            "SELECT w.*, p.item_name, p.item_price, p.item_image, p.item_brand
             FROM wishlist w JOIN product p ON w.item_id = p.item_id
             WHERE w.user_id = {$user_id}"
        );
        $arr = [];
        if ($result) while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    // Get item_ids array
    public function getCartId($cartArray = null, $key = "item_id") {
        if (!$cartArray) return [];
        return array_map(fn($v) => $v[$key], $cartArray);
    }

    // Count cart items for a user
    public function countCart($user_id) {
        $user_id = (int)$user_id;
        $result  = $this->db->con->query(
            "SELECT COALESCE(SUM(qty),0) as total FROM cart WHERE user_id={$user_id}"
        );
        return (int)$result->fetch_assoc()['total'];
    }

    // Count wishlist items for a user
    public function countWishlist($user_id) {
        $user_id = (int)$user_id;
        $result  = $this->db->con->query(
            "SELECT COUNT(*) as total FROM wishlist WHERE user_id={$user_id}"
        );
        return (int)$result->fetch_assoc()['total'];
    }
}
