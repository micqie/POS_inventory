<?php
// Handle AJAX request for category products - MUST BE AT THE VERY TOP
if (isset($_GET['ajax']) && $_GET['ajax'] === 'category_products' && isset($_GET['category_id'])) {
    // Clear any previous output
    ob_clean();

    $categoryId = (int)$_GET['category_id'];

    // Debug: Check if connection exists
    if (!$conn) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed'
        ]);
        exit;
    }

    try {
        // Get category info
        $stmt = $conn->prepare('SELECT category_name FROM categories WHERE category_id = ?');
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param('i', $categoryId);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $categoryInfo = $result->fetch_assoc();

        if (!$categoryInfo) {
            throw new Exception('Category not found');
        }

        // Get products in this category
        $productsStmt = $conn->prepare('
            SELECT product_id, product_name, price, stock, created_at
            FROM products
            WHERE category_id = ?
            ORDER BY product_name
        ');
        if (!$productsStmt) {
            throw new Exception('Prepare products failed: ' . $conn->error);
        }

        $productsStmt->bind_param('i', $categoryId);
        if (!$productsStmt->execute()) {
            throw new Exception('Execute products failed: ' . $productsStmt->error);
        }

        $productsResult = $productsStmt->get_result();
        $products = $productsResult->fetch_all(MYSQLI_ASSOC);

        // Get statistics
        $statsStmt = $conn->prepare('
            SELECT
                COUNT(*) as product_count,
                COALESCE(SUM(stock), 0) as total_stock,
                COALESCE(AVG(price), 0) as avg_price
            FROM products
            WHERE category_id = ?
        ');
        if (!$statsStmt) {
            throw new Exception('Prepare stats failed: ' . $conn->error);
        }

        $statsStmt->bind_param('i', $categoryId);
        if (!$statsStmt->execute()) {
            throw new Exception('Execute stats failed: ' . $statsStmt->error);
        }

        $statsResult = $statsStmt->get_result();
        $stats = $statsResult->fetch_assoc();

        // Ensure we output only JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');

        echo json_encode([
            'success' => true,
            'category' => $categoryInfo,
            'products' => $products,
            'stats' => $stats
        ]);

    } catch (Exception $e) {
        // Ensure we output only JSON even on error
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'error' => true,
            'message' => $e->getMessage()
        ]);
    }

    exit; // IMPORTANT: Stop script execution here for AJAX requests
}

// Rest of your categories code continues below...
// CRUD for categories
if (is_post()) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') {
        flash('error', 'Category name is required.');
    } else {
        $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE categories SET category_name=? WHERE category_id=?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            flash('success', 'Category updated.');
        } else {
            $stmt = $conn->prepare('INSERT INTO categories (category_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            flash('success', 'Category added.');
        }
    }
    header('Location: index.php?page=categories');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM categories WHERE category_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Category deleted.');
    header('Location: index.php?page=categories');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM categories WHERE category_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

// Get categories with product counts
$categories = $conn->query('
    SELECT
        c.*,
        COUNT(p.product_id) as product_count,
        COALESCE(GROUP_CONCAT(p.product_name ORDER BY p.product_name SEPARATOR ", "), "No products") as product_list
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id, c.category_name
    ORDER BY c.category_id DESC
')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Categories</h1>
        <p class="page-subtitle">Organize products into categories</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit Category' : 'Add New Category'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                    <label class="form-label">Category Name</label>
                    <input class="form-control" type="text" name="category_name" required value="<?php echo sanitize($editItem['category_name'] ?? ''); ?>" placeholder="Enter category name">
                </div>
                <div style="display: flex; gap: var(--spacing-md);">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update' : 'Add Category'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=categories">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Categories
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No categories found. Add your first category above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['category_id']; ?></td>
                                    <td><strong><?php echo sanitize($cat['category_name']); ?></strong></td>
                                    <td>
                                        <a href="javascript:void(0)"
                                           class="category-products-link"
                                           data-category-id="<?php echo $cat['category_id']; ?>"
                                           data-category-name="<?php echo sanitize($cat['category_name']); ?>"
                                           style="color: #4e54c8; font-weight: 600; text-decoration: none;">
                                            <span class="badge bg-primary" style="font-size: 0.85rem;">
                                                <i class="bi bi-box"></i> <?php echo $cat['product_count']; ?> products
                                            </span>
                                        </a>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=categories&edit=<?php echo $cat['category_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a class="btn btn-sm btn-outline-danger" href="index.php?page=categories&delete=<?php echo $cat['category_id']; ?>" onclick="return confirm('Are you sure you want to delete this category? This will also delete all products in this category.');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Category Products Modal -->
<div id="categoryProductsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 12px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <!-- Modal Header -->
        <div style="background: linear-gradient(135deg, #4e54c8, #8f94fb); color: white; padding: 20px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">
                    <i class="bi bi-tags"></i> Products in Category
                </h3>
                <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">
                    <span id="modalCategoryName">Loading...</span>
                </div>
            </div>
            <button onclick="closeCategoryModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 5px;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 25px; max-height: calc(90vh - 150px); overflow-y: auto;">
            <!-- Loading State -->
            <div id="modalCategoryLoading" style="text-align: center; padding: 40px 0;">
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #4e54c8; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <h4 style="color: #666;">Loading products...</h4>
            </div>

            <!-- Content State -->
            <div id="modalCategoryContent" style="display: none;">
                <!-- Summary Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center;">
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Total Products</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: #4e54c8;" id="modalProductCount">0</div>
                    </div>
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center;">
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Total Stock</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: #28a745;" id="modalTotalStock">0</div>
                    </div>
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center;">
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Avg. Price</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: #ff6b6b;" id="modalAvgPrice">$0.00</div>
                    </div>
                </div>

                <!-- Products Table -->
                <div>
                    <h4 style="margin: 0 0 15px 0; font-size: 1.2rem; color: #333;">
                        <i class="bi bi-list-check"></i> Product List
                    </h4>

                    <div style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f5f5f5;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Product Name</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Stock</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Price</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modalCategoryProductsBody">
                                <!-- Products will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.category-products-link {
    transition: all 0.2s;
}

.category-products-link:hover {
    text-decoration: underline !important;
    opacity: 0.8;
}

/* Custom scrollbar */
#categoryProductsModal > div {
    scrollbar-width: thin;
    scrollbar-color: #4e54c8 #f1f1f1;
}

#categoryProductsModal > div::-webkit-scrollbar {
    width: 8px;
}

#categoryProductsModal > div::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#categoryProductsModal > div::-webkit-scrollbar-thumb {
    background: #4e54c8;
    border-radius: 4px;
}

#categoryProductsModal > div::-webkit-scrollbar-thumb:hover {
    background: #3a3f9c;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}
</style>

<script>
// Category Modal Functions
function showCategoryModal() {
    document.getElementById('categoryProductsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCategoryModal() {
    document.getElementById('categoryProductsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    resetCategoryModal();
}

function resetCategoryModal() {
    document.getElementById('modalCategoryContent').style.display = 'none';
    document.getElementById('modalCategoryLoading').style.display = 'block';
    document.getElementById('modalCategoryName').textContent = 'Loading...';
}

// Handle click on category products link
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to category product links
    document.querySelectorAll('.category-products-link').forEach(link => {
        link.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.categoryName;
            loadCategoryProducts(categoryId, categoryName);
        });
    });

    // Close modal when clicking outside
    document.getElementById('categoryProductsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCategoryModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCategoryModal();
        }
    });
});

function loadCategoryProducts(categoryId, categoryName) {
    // Show modal with loading state
    resetCategoryModal();
    showCategoryModal();

    // Set category name
    document.getElementById('modalCategoryName').textContent = categoryName;

    // Fetch category products via AJAX
    fetch(`index.php?page=categories&ajax=category_products&category_id=${categoryId}`)
        .then(response => {
            // First check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned non-JSON response. Check the URL and server configuration.');
                });
            }
            return response.json();
        })
        .then(data => {
            // Check for error response
            if (data.error || !data.success) {
                throw new Error(data.message || 'Failed to load category products');
            }

            // Hide loading, show content
            document.getElementById('modalCategoryLoading').style.display = 'none';
            document.getElementById('modalCategoryContent').style.display = 'block';

            // Update summary stats
            document.getElementById('modalProductCount').textContent = data.stats.product_count || 0;
            document.getElementById('modalTotalStock').textContent = data.stats.total_stock || 0;
            document.getElementById('modalAvgPrice').textContent = '$' + (parseFloat(data.stats.avg_price) || 0).toFixed(2);

            // Update products table
            let productsHTML = '';

            if (data.products && data.products.length > 0) {
                data.products.forEach(product => {
                    productsHTML += `
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">
                                <div style="font-weight: 500; color: #333;">
                                    ${escapeHtml(product.product_name || 'Unknown Product')}
                                </div>
                            </td>
                            <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e0e0e0;">
                                <span style="background: ${product.stock > 10 ? '#28a745' : product.stock > 0 ? '#ffc107' : '#dc3545'}; color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.85rem;">
                                    ${product.stock} units
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: right; border-bottom: 1px solid #e0e0e0; color: #333; font-weight: 500;">
                                $${parseFloat(product.price || 0).toFixed(2)}
                            </td>
                            <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e0e0e0;">
                                <a href="index.php?page=products&edit=${product.product_id}" class="btn btn-sm btn-outline-primary" style="padding: 3px 10px; font-size: 0.85rem;">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                productsHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                            <i class="bi bi-exclamation-circle"></i>
                            No products found in this category
                        </td>
                    </tr>
                `;
            }

            document.getElementById('modalCategoryProductsBody').innerHTML = productsHTML;

        })
        .catch(error => {
            console.error('Error loading category products:', error);

            // Show error message
            document.getElementById('modalCategoryLoading').style.display = 'block';
            document.getElementById('modalCategoryLoading').innerHTML = `
                <div style="text-align: center; padding: 40px 0;">
                    <div style="background: #dc3545; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h4 style="color: #dc3545; margin-bottom: 10px;">Failed to load products</h4>
                    <p style="color: #666; margin-bottom: 10px;">${error.message || 'Please try again or check your connection'}</p>
                    <p style="color: #999; font-size: 0.85rem; margin-bottom: 20px;">Category: ${categoryName} (ID: ${categoryId})</p>
                    <p style="color: #999; font-size: 0.85rem; margin-bottom: 20px;">URL: index.php?page=categories&ajax=category_products&category_id=${categoryId}</p>
                    <button onclick="loadCategoryProducts(${categoryId}, '${categoryName}')" style="background: #4e54c8; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                </div>
            `;
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
