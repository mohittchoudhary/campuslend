<?php
/**
 * CampusLend Item Listings & Catalog Controller
 */

namespace App\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\User;

class ItemController extends Controller
{
    /**
     * Browse Catalog of Available Lendable Items
     */
    public function index(): void
    {
        $filters = [
            'category' => $_GET['category'] ?? 'all',
            'condition' => $_GET['condition'] ?? 'all',
            'search' => $_GET['search'] ?? '',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 16;
        $offset = ($page - 1) * $limit;

        $catalog = Item::getAvailable($filters, $limit, $offset);
        $categories = Category::allOrdered();

        $this->render('pages/items/index', [
            'title' => 'Browse Lend Catalog - CampusLend',
            'items' => $catalog['items'],
            'total' => $catalog['total'],
            'page' => $page,
            'pages' => max(1, ceil($catalog['total'] / $limit)),
            'categories' => $categories,
            'filters' => $filters,
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
        ]);
    }

    /**
     * Show Create Listing Form
     */
    public function showCreate(): void
    {
        $categories = Category::allOrdered();
        $conditions = ['Like New', 'Good', 'Fair', 'Usable'];

        $this->render('pages/items/create', [
            'title' => 'List an Item for Lending - CampusLend',
            'categories' => $categories,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Process Listing Creation with Secure Image Upload
     */
    public function handleCreate(): void
    {
        $user = auth_user();
        if (!$user || (int)$user['is_verified'] !== 1 || (int)$user['is_banned'] === 1) {
            redirect('/login', ['error' => 'Only verified, active students can list items.']);
        }

        $data = $_POST;
        $errors = $this->validate($data, [
            'title' => 'required|min:3|max:150',
            'category_id' => 'required',
            'pickup_location' => 'required|max:150',
            'item_condition' => 'required',
        ]);

        $photoFileName = null;

        // Secure Image Upload Handling
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $maxSize = 2 * 1024 * 1024; // 2 MB strict limit

            if ($file['size'] > $maxSize) {
                $errors['photo'] = "Image size cannot exceed 2 MB.";
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowedExtensions)) {
                    $errors['photo'] = "Only JPG, PNG, and WebP image formats are allowed.";
                } else {
                    // Real MIME type check via finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($mime, $allowedMimes)) {
                        $errors['photo'] = "Invalid image file type ({$mime}).";
                    } else {
                        // Generate random cryptographic filename
                        $photoFileName = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                        $uploadDir = __DIR__ . '/../../public/uploads/items';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $dest = $uploadDir . '/' . $photoFileName;
                        if (!move_uploaded_file($file['tmp_name'], $dest)) {
                            $errors['photo'] = "Failed to upload image. Please try again.";
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            redirect('/items/create', ['error' => 'Please correct the errors below.']);
        }

        $duration = max(1, (int)($data['max_duration_hours'] ?? 24));

        $itemId = Item::createListing([
            'user_id' => $user['id'],
            'category_id' => (int)$data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'photo' => $photoFileName,
            'item_condition' => $data['item_condition'],
            'pickup_location' => $data['pickup_location'],
            'max_duration_hours' => $duration,
        ]);

        redirect('/items/view?id=' . $itemId, [
            'success' => "📦 Your item has been listed and is now visible to campus peers!"
        ]);
    }

    /**
     * View Listing Details
     */
    public function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = Item::getWithDetails($id);

        if (!$item) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Item Not Found']);
            return;
        }

        $viewerId = auth_id();
        $isOwner = $viewerId && (int)$item['user_id'] === $viewerId;

        $this->render('pages/items/view', [
            'title' => e($item['title']) . ' - CampusLend Catalog',
            'item' => $item,
            'isOwner' => $isOwner,
            'viewerId' => $viewerId,
        ]);
    }

    /**
     * Pause or resume listing
     */
    public function togglePause(): void
    {
        $user = auth_user();
        $id = (int)($_POST['item_id'] ?? 0);

        if (!Item::togglePause($id, (int)$user['id'])) {
            redirect('/items/view?id=' . $id, ['error' => 'Could not update listing status.']);
        }

        redirect('/items/view?id=' . $id, ['success' => 'Listing visibility status updated.']);
    }
}
