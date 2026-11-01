<?php
/**
 * RuangUMKM - Halaman Utama (Unified Social Commerce Feed)
 * Menggabungkan Produk UMKM, Content Creator, Jasa Editing Video, dan Event & Bazar dalam satu aliran feed.
 */

$pageTitle = "Beranda - Feed Komunitas UMKM";
$activeNav = "home";

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Filter parameters
$currentCategory = $_GET['category'] ?? 'all'; // all, product, creator, editing, event
$searchQuery = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'latest'; // latest, price_asc, price_desc
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$mixedFeedItems = [];
$totalCount = 0;

// User favorites
$userFavorites = [];
if (isLoggedIn()) {
    $uid = currentUser()['id'];
    $favStmt = $db->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $favStmt->execute([$uid]);
    $userFavorites = array_map('intval', $favStmt->fetchAll(PDO::FETCH_COLUMN));
}

// ----------------------------------------------------
// Build Query for each component or union
// ----------------------------------------------------
$searchWildcard = '%' . $searchQuery . '%';

// 1. PRODUCTS
$productItems = [];
if ($currentCategory === 'all' || $currentCategory === 'product') {
    $pSql = "
        SELECT 
            p.id, p.name, p.slug, p.description, p.price, p.stock, p.primary_image, p.created_at,
            'product' AS item_type,
            s.store_name, s.city, s.logo AS store_logo, s.rating,
            c.name AS category_name
        FROM products p
        JOIN stores s ON p.store_id = s.id
        JOIN product_categories c ON p.category_id = c.id
        WHERE p.status = 'aktif'
    ";
    $pParams = [];
    if (!empty($searchQuery)) {
        $pSql .= " AND (p.name LIKE ? OR p.description LIKE ? OR s.store_name LIKE ? OR s.city LIKE ?)";
        $pParams = [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard];
    }
    $pSql .= " ORDER BY p.id DESC";
    $pStmt = $db->prepare($pSql);
    $pStmt->execute($pParams);
    $productItems = $pStmt->fetchAll();
}

// 2. CONTENT CREATORS
$creatorItems = [];
if ($currentCategory === 'all' || $currentCategory === 'creator') {
    $cSql = "
        SELECT 
            cp.id, cp.stage_name, cp.bio, cp.niche, cp.location, cp.primary_platform,
            cp.social_handle, cp.followers_count, cp.starting_price, cp.photo,
            cp.availability_status, cp.created_at,
            'creator' AS item_type
        FROM creator_profiles cp
        WHERE cp.availability_status != 'tidak_aktif'
    ";
    $cParams = [];
    if (!empty($searchQuery)) {
        $cSql .= " AND (cp.stage_name LIKE ? OR cp.bio LIKE ? OR cp.niche LIKE ? OR cp.location LIKE ?)";
        $cParams = [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard];
    }
    $cSql .= " ORDER BY cp.id DESC";
    $cStmt = $db->prepare($cSql);
    $cStmt->execute($cParams);
    $creatorItems = $cStmt->fetchAll();
}

// 3. EDITING SERVICES
$editingItems = [];
if ($currentCategory === 'all' || $currentCategory === 'editing') {
    $eSql = "
        SELECT 
            es.id, es.title, es.category, es.description, es.price, es.delivery_days, es.created_at,
            'editing' AS item_type,
            ep.editor_name, ep.studio_name, ep.location, ep.photo, ep.whatsapp,
            (SELECT thumbnail FROM editor_portfolios WHERE editor_id = ep.id ORDER BY id DESC LIMIT 1) AS thumbnail
        FROM editing_services es
        JOIN editor_profiles ep ON es.editor_id = ep.id
        WHERE ep.availability_status != 'tidak_aktif'
    ";
    $eParams = [];
    if (!empty($searchQuery)) {
        $eSql .= " AND (es.title LIKE ? OR es.description LIKE ? OR ep.editor_name LIKE ? OR ep.location LIKE ?)";
        $eParams = [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard];
    }
    $eSql .= " ORDER BY es.id DESC";
    $eStmt = $db->prepare($eSql);
    $eStmt->execute($eParams);
    $editingItems = $eStmt->fetchAll();
}

// 4. EVENTS & BAZAAR
$eventItems = [];
if ($currentCategory === 'all' || $currentCategory === 'event') {
    $evSql = "
        SELECT 
            ev.id, ev.title, ev.slug, ev.description, ev.organizer_name, ev.category,
            ev.venue_name, ev.city, ev.start_date, ev.end_date, ev.time_info,
            ev.entry_fee, ev.booth_rental_fee, ev.available_booths, ev.poster_image,
            ev.status, ev.created_at,
            'event' AS item_type
        FROM events ev
        WHERE ev.status IN ('dipublikasikan', 'berlangsung')
    ";
    $evParams = [];
    if (!empty($searchQuery)) {
        $evSql .= " AND (ev.title LIKE ? OR ev.description LIKE ? OR ev.organizer_name LIKE ? OR ev.city LIKE ?)";
        $evParams = [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard];
    }
    $evSql .= " ORDER BY ev.start_date ASC";
    $evStmt = $db->prepare($evSql);
    $evStmt->execute($evParams);
    $eventItems = $evStmt->fetchAll();
}

// Interleave / Combine in a balanced social commerce feed
if ($currentCategory === 'all') {
    // Interleave array items so the user gets a rich variety of content in feed
    $maxCount = max(count($productItems), count($creatorItems), count($editingItems), count($eventItems));
    for ($i = 0; $i < $maxCount; $i++) {
        if (isset($productItems[$i])) $mixedFeedItems[] = $productItems[$i];
        if (isset($creatorItems[$i])) $mixedFeedItems[] = $creatorItems[$i];
        if (isset($editingItems[$i])) $mixedFeedItems[] = $editingItems[$i];
        if (isset($eventItems[$i]))   $mixedFeedItems[] = $eventItems[$i];
    }
} else {
    // Single selected category
    $mixedFeedItems = array_merge($productItems, $creatorItems, $editingItems, $eventItems);
}

$totalCount = count($mixedFeedItems);
// Slice for pagination
$pagedItems = array_slice($mixedFeedItems, $offset, $perPage);
$totalPages = ceil($totalCount / $perPage);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="container hero-inner">
        <div class="hero-content">
            <div class="hero-tag">
                <i class="fa-solid fa-sparkles"></i> Ekosistem Terpadu Ekonomi Kreatif & UMKM
            </div>
            <h1 class="hero-title">
                Satu Ruang Digital untuk <span>Produk, Kreator, Editor & Bazar</span>
            </h1>
            <p class="hero-desc">
                Temukan produk lokal terbaik, sewa jasa endorsement content creator, pesan video promosi dari editor andal, atau ikuti bazar dan pameran UMKM terdekat.
            </p>
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <strong><?= count($productItems) ?>+</strong>
                    <span>Produk UMKM</span>
                </div>
                <div class="hero-stat-item">
                    <strong><?= count($creatorItems) ?>+</strong>
                    <span>Content Creator</span>
                </div>
                <div class="hero-stat-item">
                    <strong><?= count($editingItems) ?>+</strong>
                    <span>Jasa Editing Video</span>
                </div>
                <div class="hero-stat-item">
                    <strong><?= count($eventItems) ?>+</strong>
                    <span>Event & Bazar</span>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; min-width: 260px;">
            <a href="<?= BASE_URL ?>register.php" class="btn btn-accent btn-lg" style="box-shadow: 0 10px 20px rgba(231, 111, 81, 0.35);">
                <i class="fa-solid fa-rocket"></i> Mulai Gabung Gratis
            </a>
            <a href="<?= BASE_URL ?>install.php" class="btn btn-outline" style="background: rgba(255,255,255,0.15); color: #fff; border-color: rgba(255,255,255,0.3);">
                <i class="fa-solid fa-database"></i> Status Database XAMPP
            </a>
        </div>
    </div>
</section>

<!-- Feed Category Filters Bar -->
<section class="feed-filter-section">
    <div class="container filter-bar-wrapper">
        <div class="feed-filter-pills">
            <a href="<?= BASE_URL ?>index.php?category=all<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="filter-pill pill-all <?= $currentCategory === 'all' ? 'active' : '' ?>" 
               data-filter="all">
                <i class="fa-solid fa-shapes"></i> Semua Feed
            </a>
            <a href="<?= BASE_URL ?>index.php?category=product<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="filter-pill pill-product <?= $currentCategory === 'product' ? 'active' : '' ?>" 
               data-filter="product">
                <i class="fa-solid fa-bag-shopping"></i> Produk UMKM
            </a>
            <a href="<?= BASE_URL ?>index.php?category=creator<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="filter-pill pill-creator <?= $currentCategory === 'creator' ? 'active' : '' ?>" 
               data-filter="creator">
                <i class="fa-solid fa-camera"></i> Content Creator
            </a>
            <a href="<?= BASE_URL ?>index.php?category=editing<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="filter-pill pill-editing <?= $currentCategory === 'editing' ? 'active' : '' ?>" 
               data-filter="editing">
                <i class="fa-solid fa-film"></i> Jasa Editing
            </a>
            <a href="<?= BASE_URL ?>index.php?category=event<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="filter-pill pill-event <?= $currentCategory === 'event' ? 'active' : '' ?>" 
               data-filter="event">
                <i class="fa-solid fa-calendar-star"></i> Event & Bazar
            </a>
        </div>

        <div style="font-size: 13px; color: var(--slate-500); font-weight: 600;">
            Menampilkan <strong><?= count($pagedItems) ?></strong> dari <strong><?= $totalCount ?></strong> konten
        </div>
    </div>
</section>

<!-- Main Unified Feed Section -->
<main class="feed-section">
    <div class="container">
        <?php if (!empty($searchQuery)): ?>
            <div style="margin-bottom: 24px; padding: 12px 18px; background: #ffffff; border-radius: 12px; border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    Hasil pencarian untuk kata kunci: <strong>"<?= e($searchQuery) ?>"</strong>
                </div>
                <a href="<?= BASE_URL ?>index.php" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-xmark"></i> Hapus Pencarian
                </a>
            </div>
        <?php endif; ?>

        <div class="feed-grid">
            <?php if (!empty($pagedItems)): ?>
                <?php foreach ($pagedItems as $item): ?>
                    <?php include __DIR__ . '/includes/feed_card.php'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" id="feedEmptyState">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3>Belum Ada Konten di Kategori Ini</h3>
                    <p>Konten yang Anda cari belum tersedia atau filter pencarian tidak menemukan hasil yang sesuai.</p>
                    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Tampilkan Semua Feed
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 40px; display: flex; justify-content: center; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&category=<?= urlencode($currentCategory) ?>&q=<?= urlencode($searchQuery) ?>" class="btn btn-outline">
                        <i class="fa-solid fa-arrow-left"></i> Sebelumnya
                    </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>&category=<?= urlencode($currentCategory) ?>&q=<?= urlencode($searchQuery) ?>" 
                       class="btn <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" style="min-width: 42px;">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&category=<?= urlencode($currentCategory) ?>&q=<?= urlencode($searchQuery) ?>" class="btn btn-outline">
                        Berikutnya <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
