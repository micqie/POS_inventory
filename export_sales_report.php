<?php
// export_sales_report.php
session_start();

// Check if we're being directly accessed (not included)
$directAccess = (basename($_SERVER['SCRIPT_FILENAME']) === 'export_sales_report.php');

if ($directAccess) {
    // We're being accessed directly, so output headers and content
    require_once __DIR__ . '/connect_db.php'; // Adjust path as needed

    // // Check if user is logged in
    // if (!isset($_SESSION['user_id'])) {
    //     header('HTTP/1.0 403 Forbidden');
    //     die("Access denied. Please log in.");
    // }

    // Get parameters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    // Validate dates
    if (empty($startDate) || empty($endDate)) {
        die("Error: Missing date parameters.");
    }

    // Ensure end date is not before start date
    if (strtotime($endDate) < strtotime($startDate)) {
        $endDate = $startDate;
    }

    // Fetch sales data
    $salesStmt = $conn->prepare("
        SELECT DATE(sale_date) AS day, SUM(total_amount) AS total
        FROM sales
        WHERE DATE(sale_date) BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY day ASC
    ");
    $salesStmt->bind_param("ss", $startDate, $endDate);
    $salesStmt->execute();
    $salesResult = $salesStmt->get_result();

    $salesData = [];
    $totalSales = 0;
    while ($row = $salesResult->fetch_assoc()) {
        $salesData[] = $row;
        $totalSales += $row['total'];
    }

    // Get transaction counts
    $countStmt = $conn->prepare("
        SELECT DATE(sale_date) AS day, COUNT(*) as count
        FROM sales
        WHERE DATE(sale_date) BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY day ASC
    ");
    $countStmt->bind_param("ss", $startDate, $endDate);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $counts = [];
    $totalTransactions = 0;
    while ($row = $countResult->fetch_assoc()) {
        $counts[$row['day']] = $row['count'];
        $totalTransactions += $row['count'];
    }

    // Fetch top products
    $topProductsStmt = $conn->prepare("
        SELECT p.product_name, SUM(sd.quantity) AS qty
        FROM sale_details sd
        JOIN products p ON p.product_id = sd.product_id
        JOIN sales s ON s.sale_id = sd.sale_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY p.product_id, p.product_name
        ORDER BY qty DESC
        LIMIT 5
    ");
    $topProductsStmt->bind_param("ss", $startDate, $endDate);
    $topProductsStmt->execute();
    $topProductsResult = $topProductsStmt->get_result();

    $topProducts = [];
    while ($row = $topProductsResult->fetch_assoc()) {
        $topProducts[] = $row;
    }

    // Set headers for Excel download
    $filename = "sales_report_" . date('Y-m-d') . "_" . $startDate . "_to_" . $endDate . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Transfer-Encoding: binary");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    header("Expires: 0");

    // Output Excel content
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Sales Report</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th { background-color: #4CAF50; color: white; padding: 8px; border: 1px solid #ddd; text-align: left; }
            td { padding: 8px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .total-row { font-weight: bold; background-color: #e6f7ff; }
            .header { font-size: 18px; margin-bottom: 10px; }
            .subheader { font-size: 14px; color: #666; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='header'>Sales Report</div>
        <div class='subheader'>
            <strong>Period:</strong> <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?><br>
            <strong>Generated:</strong> <?php echo date('M d, Y H:i:s'); ?>
        </div>
        <hr>

        <h3>Summary</h3>
        <table>
            <tr><th>Total Sales</th><th>Total Transactions</th><th>Days with Sales</th><th>Average Daily Sales</th></tr>
            <tr>
                <td>$<?php echo number_format($totalSales, 2); ?></td>
                <td><?php echo $totalTransactions; ?></td>
                <td><?php echo count($salesData); ?></td>
                <td>$<?php echo number_format(count($salesData) > 0 ? $totalSales / count($salesData) : 0, 2); ?></td>
            </tr>
        </table>

        <br><br>

        <h3>Daily Sales Data</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Total Sales</th>
                <th>Transactions</th>
                <th>Avg. Transaction</th>
            </tr>
            <?php if (!empty($salesData)): ?>
                <?php foreach ($salesData as $sale):
                    $transactionCount = $counts[$sale['day']] ?? 0;
                    $avgTransaction = $transactionCount > 0 ? $sale['total'] / $transactionCount : 0;
                ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($sale['day'])); ?></td>
                        <td>$<?php echo number_format($sale['total'], 2); ?></td>
                        <td><?php echo $transactionCount; ?></td>
                        <td>$<?php echo number_format($avgTransaction, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class='total-row'>
                    <td><strong>Total / Average</strong></td>
                    <td><strong>$<?php echo number_format($totalSales, 2); ?></strong></td>
                    <td><strong><?php echo $totalTransactions; ?></strong></td>
                    <td><strong>$<?php echo number_format($totalTransactions > 0 ? $totalSales / $totalTransactions : 0, 2); ?></strong></td>
                </tr>
            <?php else: ?>
                <tr><td colspan='4' style='text-align:center;'>No sales data in this period</td></tr>
            <?php endif; ?>
        </table>

        <br><br>

        <h3>Top 5 Products</h3>
        <table>
            <tr><th>#</th><th>Product Name</th><th>Quantity Sold</th></tr>
            <?php if (!empty($topProducts)): ?>
                <?php $count = 1; ?>
                <?php foreach ($topProducts as $product): ?>
                    <tr>
                        <td><?php echo $count; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo $product['qty']; ?></td>
                    </tr>
                    <?php $count++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='3' style='text-align:center;'>No product data in this period</td></tr>
            <?php endif; ?>
        </table>
    </body>
    </html>
    <?php
    // Close statements
    $salesStmt->close();
    $countStmt->close();
    $topProductsStmt->close();
    exit;
} else {
    // We're being included as a module, so just return content without headers
    // This is for when you might want to show the report within the dashboard

    // You can leave this empty or put a message
    echo '<div class="alert alert-info">Export functionality should be accessed directly.</div>';
}
