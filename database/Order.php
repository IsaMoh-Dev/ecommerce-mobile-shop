<?php
class Order
{
    public $db = null;

    public function __construct(DBController $db) {
        $this->db = $db;
    }

    // Place a new order
    public function placeOrder($user_id, $data, $cart_items) {
        $user_id  = (int)$user_id;
        $name     = mysqli_real_escape_string($this->db->con, $data['full_name']);
        $email    = mysqli_real_escape_string($this->db->con, $data['email']);
        $phone    = mysqli_real_escape_string($this->db->con, $data['phone']);
        $address  = mysqli_real_escape_string($this->db->con, $data['address']);
        $city     = mysqli_real_escape_string($this->db->con, $data['city']);
        $total    = (float)$data['total'];
        $payment  = mysqli_real_escape_string($this->db->con, $data['payment_method']);

        $sql = "INSERT INTO orders (user_id, full_name, email, phone, address, city, total_amount, payment_method)
                VALUES ({$user_id}, '{$name}', '{$email}', '{$phone}', '{$address}', '{$city}', {$total}, '{$payment}')";

        if ($this->db->con->query($sql)) {
            $order_id = $this->db->con->insert_id;

            // Insert order items + deduct stock
            foreach ($cart_items as $item) {
                $item_id    = (int)$item['item_id'];
                $item_name  = mysqli_real_escape_string($this->db->con, $item['item_name']);
                $item_price = (float)$item['item_price'];
                $qty        = (int)($item['qty'] ?? 1);

                $this->db->con->query(
                    "INSERT INTO order_items (order_id, item_id, item_name, item_price, quantity)
                     VALUES ({$order_id}, {$item_id}, '{$item_name}', {$item_price}, {$qty})"
                );

                // Deduct stock (never go below 0)
                $this->db->con->query(
                    "UPDATE product SET item_stock = GREATEST(0, item_stock - {$qty}) WHERE item_id = {$item_id}"
                );
            }

            // Clear cart
            $this->db->con->query("DELETE FROM cart WHERE user_id={$user_id}");

            // Send order confirmation email to customer
            require_once __DIR__ . '/../mailer.php';
            $items_text = '';
            foreach ($cart_items as $item) {
                $items_text .= "  - " . $item['item_name'] . " x" . ($item['qty'] ?? 1) . " @ $" . number_format($item['item_price'], 2) . "\n";
            }
            $payment_labels = [
                'cod'      => 'Cash on Delivery',
                'bank'     => 'Bank Transfer',
                'telebirr' => 'Telebirr',
                'cbe'      => 'CBE Birr',
            ];
            $payment_label = $payment_labels[$data['payment_method']] ?? strtoupper($data['payment_method']);

            $confirm_body =
                "Hi " . $data['full_name'] . ",\n\n" .
                "Thank you for your order! We have received it and will contact you shortly to confirm delivery.\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
                "ORDER SUMMARY — #" . $order_id . "\n" .
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
                $items_text .
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
                "Total:          $" . number_format($total, 2) . "\n" .
                "Payment:        " . $payment_label . "\n" .
                "Delivery to:    " . $data['address'] . ", " . $data['city'] . "\n" .
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "We'll call you on " . $data['phone'] . " to confirm your delivery time.\n\n" .
                "Track your order: http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/orders.php\n\n" .
                "Questions? Contact us:\n" .
                "  Phone: +251 929 346 248\n" .
                "  Telegram: @isadagishop\n\n" .
                "— Isa & Dagi Mobile Shop\n" .
                "Merkato, Samson Building, Addis Ababa";

            sendMail($data['email'], 'Order Confirmed #' . $order_id . ' — Isa & Dagi Mobile Shop', $confirm_body);

            return ['success' => true, 'order_id' => $order_id];
        }

        return ['success' => false, 'message' => 'Order failed. Please try again.'];
    }

    // Get all orders (admin)
    public function getAllOrders() {
        $result = $this->db->con->query(
            "SELECT o.*, u.first_name, u.last_name FROM orders o
             LEFT JOIN user u ON o.user_id = u.user_id
             ORDER BY o.order_date DESC"
        );
        $arr = [];
        while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    // Get orders for a user
    public function getUserOrders($user_id) {
        $user_id = (int)$user_id;
        $result  = $this->db->con->query(
            "SELECT * FROM orders WHERE user_id={$user_id} ORDER BY order_date DESC"
        );
        $arr = [];
        while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    // Get order items
    public function getOrderItems($order_id) {
        $order_id = (int)$order_id;
        $result   = $this->db->con->query("SELECT * FROM order_items WHERE order_id={$order_id}");
        $arr      = [];
        while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    // Update order status (admin)
    public function updateStatus($order_id, $status) {
        $order_id = (int)$order_id;
        $status   = mysqli_real_escape_string($this->db->con, $status);
        return $this->db->con->query("UPDATE orders SET status='{$status}' WHERE order_id={$order_id}");
    }

    // Cancel order by customer — only allowed if pending or processing
    public function cancelOrder($order_id, $user_id) {
        $order_id = (int)$order_id;
        $user_id  = (int)$user_id;

        // Verify the order belongs to this user and is still cancellable
        $result = $this->db->con->query(
            "SELECT order_id, status FROM orders
             WHERE order_id={$order_id} AND user_id={$user_id} LIMIT 1"
        );
        if (!$result || $result->num_rows === 0) {
            return ['success' => false, 'message' => 'Order not found.'];
        }
        $order = $result->fetch_assoc();
        if (!in_array($order['status'], ['pending', 'processing'])) {
            return ['success' => false, 'message' => 'This order cannot be cancelled — it has already been ' . $order['status'] . '.'];
        }

        $this->db->con->query(
            "UPDATE orders SET status='cancelled' WHERE order_id={$order_id}"
        );

        // Restore stock for each item in the cancelled order
        $items = $this->db->con->query(
            "SELECT item_id, quantity FROM order_items WHERE order_id={$order_id}"
        );
        if ($items) {
            while ($item = $items->fetch_assoc()) {
                $this->db->con->query(
                    "UPDATE product SET item_stock = item_stock + {$item['quantity']}
                     WHERE item_id = {$item['item_id']}"
                );
            }
        }

        return ['success' => true];
    }

    // Count orders
    public function countOrders() {
        $result = $this->db->con->query("SELECT COUNT(*) as total FROM orders");
        $row    = $result->fetch_assoc();
        return $row['total'];
    }

    // Total revenue — returns raw float so formatPrice() can convert it
    public function totalRevenue() {
        $result = $this->db->con->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'");
        $row    = $result->fetch_assoc();
        return (float)($row['revenue'] ?? 0);
    }
}
