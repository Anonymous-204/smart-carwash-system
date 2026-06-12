<?php
// customer/api_get_statuses.php
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
// Đảm bảo các hàm require_customer_login() hoặc tương tự đã định nghĩa các hàm helper như status_badge()
// Nếu hàm status_badge() nằm ở file khác (như functions.php), hãy require thêm file đó vào đây nhé.

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_ids']) || !is_array($input['order_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$orderIds = array_map('intval', $input['order_ids']);

if (empty($orderIds)) {
    echo json_encode(['success' => true, 'orders' => []]);
    exit;
}

$idsString = implode(',', $orderIds);

// Truy vấn trạng thái mới nhất từ Database
$query = "
    SELECT o.id, o.status, p.status AS payment_status
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.id IN ($idsString)
";

$result = $conn->query($query);
$ordersData = [];

while ($row = $result->fetch_assoc()) {
    $ordersData[] = [
        'id' => (int)$row['id'],
        'status' => $row['status'],
        // Trả về HTML đã sinh qua hàm status_badge sẵn có của bạn để đồng bộ giao diện
        'status_html' => status_badge($row['status']),
        'payment_html' => status_badge($row['payment_status'] ?? 'UNPAID')
    ];
}

echo json_encode([
    'success' => true,
    'orders' => $ordersData
]);
exit;