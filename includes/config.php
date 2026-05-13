<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

// Site Configuration
define('SITE_NAME', getenv('SITE_NAME') ?: 'LaptopStore');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/laptopstore');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'laptop_store');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4"); // Hỗ trợ tiếng Việt đầy đủ

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ----------------------------------------------------
// FETCH DATA FROM DATABASE
// ----------------------------------------------------

// 1. Fetch Categories
$categories = [];
$cat_sql = "SELECT slug, name FROM categories";
$cat_result = $conn->query($cat_sql);
if ($cat_result->num_rows > 0) {
    while($row = $cat_result->fetch_assoc()) {
        $categories[$row['slug']] = $row['name'];
    }
}

// 2. Fetch Brands
$brands = [];
$brand_sql = "SELECT name FROM brands";
$brand_result = $conn->query($brand_sql);
if ($brand_result->num_rows > 0) {
    while($row = $brand_result->fetch_assoc()) {
        $brands[] = $row['name'];
    }
}

// 3. Fetch Products & Process JSON
$products = [];
$prod_sql = "SELECT * FROM products ORDER BY id ASC";
$prod_result = $conn->query($prod_sql);

if ($prod_result->num_rows > 0) {
    while ($row = $prod_result->fetch_assoc()) {

        // Giải mã detail_specs (JSON) -> Array
        $detailSpecs = [];
        if (!empty($row['detail_specs'])) {
            $tmp = json_decode($row['detail_specs'], true);
            if (is_array($tmp)) {
                $detailSpecs = $tmp;
            }
        }

        // Giải mã gallery_images (JSON) -> Array
        $galleryImages = [];
        if (!empty($row['gallery_images'])) {
            $tmp = json_decode($row['gallery_images'], true);
            if (is_array($tmp)) {
                $galleryImages = $tmp;
            }
        }

        // Chuẩn hóa dữ liệu để khớp với code cũ
        $item = [
            'id'       => (int)$row['id'],
            'name'     => $row['name'],
            'brand'    => $row['brand'],
            'price'    => (int)$row['price'],
            'old_price'=> (int)$row['old_price'],
            'category' => $row['category_slug'],       // cho phù hợp logic cũ
            'image'    => $row['image'],
            'specs'    => $row['specs'],
            'desc'     => $row['description'],         // description DB -> desc
            'rating'   => (float)$row['rating'],
            'reviews'  => (int)$row['reviews'],
            'stock'    => (int)$row['stock'],
            'detail_specs'   => $detailSpecs,          // đã là array
            'gallery_images' => $galleryImages,        // đã là array
        ];

        $products[] = $item;
    }
}

// ----------------------------------------------------
// HELPER FUNCTIONS
// ----------------------------------------------------

// Price ranges (Giữ nguyên mảng tĩnh này để lọc)
$priceRanges = [
    '0-20' => 'Dưới 20 triệu',
    '20-30' => '20 - 30 triệu',
    '30-40' => '30 - 40 triệu',
    '40-50' => '40 - 50 triệu',
    '50+' => 'Trên 50 triệu'
];

function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

function calculateDiscount($oldPrice, $newPrice) {
    if ($oldPrice <= $newPrice) return 0;
    return round((($oldPrice - $newPrice) / $oldPrice) * 100);
}

// Cập nhật hàm getProductById để lấy từ mảng $products đã load (hoặc query trực tiếp nếu muốn tối ưu sau này)
function getProductById($id) {
    global $products;
    foreach ($products as $product) {
        if ($product['id'] == $id) {
            return $product;
        }
    }
    return null; // Trả về null nếu không tìm thấy
}

// Hàm đóng kết nối (gọi ở footer hoặc cuối trang nếu cần)
function closeConnection() {
    global $conn;
    $conn->close();
}
?>