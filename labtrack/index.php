<?php
require_once 'db.php';

// Handle Form Submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $tag = trim($_POST['asset_tag']);
            $name = trim($_POST['name']);
            $category = trim($_POST['category']);
            $status = trim($_POST['status']);
            $borrower = ($status === 'Borrowed') ? trim($_POST['borrower']) : null;

            if (!empty($tag) && !empty($name)) {
                $stmt = $pdo->prepare("INSERT INTO assets (asset_tag, name, category, status, borrower) VALUES (?, ?, ?, ?, ?)");
                try {
                    $stmt->execute([$tag, $name, $category, $status, $borrower]);
                    $message = "Asset added successfully!";
                } catch (PDOException $e) {
                    $message = "Error: Asset Tag already exists!";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Asset deleted successfully!";
        } elseif ($_POST['action'] === 'toggle_status') {
            $id = $_POST['id'];
            $new_status = $_POST['status'];
            $borrower = ($new_status === 'Borrowed') ? $_POST['borrower'] : null;
            
            $stmt = $pdo->prepare("UPDATE assets SET status = ?, borrower = ? WHERE id = ?");
            $stmt->execute([$new_status, $borrower, $id]);
            $message = "Asset status updated!";
        }
    }
}

// Fetch Assets
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$query = "SELECT * FROM assets WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR asset_tag LIKE ? OR category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter)) {
    $query .= " AND status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// Counts for Dashboard Cards
$totalAssets = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$availableAssets = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Available'")->fetchColumn();
$borrowedAssets = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Borrowed'")->fetchColumn();
$maintenanceAssets = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Maintenance'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LabTrack - IT Lab Inventory</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i>LabTrack</a>
            <span class="text-white-50 small">IT Equipment & Lab Inventory System</span>
        </div>
    </nav>

    <div class="container py-4">

        <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-muted small">Total Assets</div>
                        <h3 class="fw-bold mb-0"><?= $totalAssets ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-muted small">Available</div>
                        <h3 class="fw-bold mb-0 text-success"><?= $availableAssets ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white border-start border-warning border-4">
                    <div class="card-body">
                        <div class="text-muted small">Borrowed</div>
                        <h3 class="fw-bold mb-0 text-warning"><?= $borrowedAssets ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-muted small">Maintenance</div>
                        <h3 class="fw-bold mb-0 text-danger"><?= $maintenanceAssets ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, tag, category..." value="<?= htmlspecialchars($search) ?>">
                            <?php if(!empty($filter)): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
                            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
                            <?php if(!empty($search) || !empty($filter)): ?>
                                <a href="index.php" class="btn btn-outline-danger"><i class="fa-solid fa-rotate-left"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group w-100" role="group">
                            <a href="index.php<?= !empty($search)?'?search='.urlencode($search):'' ?>" class="btn btn-sm <?= empty($filter)?'btn-dark':'btn-outline-dark' ?>">All</a>
                            <a href="index.php?filter=Available<?= !empty($search)?'&search='.urlencode($search):'' ?>" class="btn btn-sm <?= $filter=='Available'?'btn-success':'btn-outline-success' ?>">Available</a>
                            <a href="index.php?filter=Borrowed<?= !empty($search)?'&search='.urlencode($search):'' ?>" class="btn btn-sm <?= $filter=='Borrowed'?'btn-warning':'btn-outline-warning' ?>">Borrowed</a>
                            <a href="index.php?filter=Maintenance<?= !empty($search)?'&search='.urlencode($search):'' ?>" class="btn btn-sm <?= $filter=='Maintenance'?'btn-danger':'btn-outline-danger' ?>">Maintenance</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                            <i class="fa-solid fa-plus me-1"></i> Add New Asset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">Asset Tag</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Borrower</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assets) > 0): ?>
                                <?php foreach ($assets as $asset): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-secondary"><?= htmlspecialchars($asset['asset_tag']) ?></td>
                                        <td><?= htmlspecialchars($asset['name']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($asset['category']) ?></span></td>
                                        <td>
                                            <?php 
                                                $badgeBg = 'bg-secondary';
                                                if ($asset['status'] === 'Available') $badgeBg = 'bg-success';
                                                if ($asset['status'] === 'Borrowed') $badgeBg = 'bg-warning text-dark';
                                                if ($asset['status'] === 'Maintenance') $badgeBg = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $badgeBg ?>"><?= htmlspecialchars($asset['status']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($asset['borrower'] ?? '—') ?></td>
                                        <td class="text-end pe-3">
                                            <!-- Status Quick Toggle Dropdown -->
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?= $asset['id'] ?>">
                                                            <input type="hidden" name="status" value="Available">
                                                            <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-circle-check me-2"></i>Available</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#borrowModal<?= $asset['id'] ?>">
                                                            <i class="fa-solid fa-handshake me-2"></i>Borrow...
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?= $asset['id'] ?>">
                                                            <input type="hidden" name="status" value="Maintenance">
                                                            <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Maintenance</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Delete Button -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $asset['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Borrow Modal per Asset -->
                                    <div class="modal fade" id="borrowModal<?= $asset['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Checkout Asset: <?= htmlspecialchars($asset['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $asset['id'] ?>">
                                                        <input type="hidden" name="status" value="Borrowed">
                                                        <div class="mb-3">
                                                            <label class="form-label">Borrower Name / Student ID</label>
                                                            <input type="text" class="form-control" name="borrower" required placeholder="e.g. Marwin Roxas (BA-3103)">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Confirm Checkout</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No assets found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-circle-plus text-primary me-2"></i>Add New Asset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Asset Tag / Serial Number</label>
                            <input type="text" class="form-control" name="asset_tag" required placeholder="e.g. AST-1005">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Equipment Name</label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g. Epson Projector">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="Laptops">Laptops</option>
                                <option value="Networking">Networking</option>
                                <option value="Peripherals">Peripherals</option>
                                <option value="Monitors">Monitors</option>
                                <option value="Audio/Visual">Audio/Visual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initial Status</label>
                            <select class="form-select" name="status" id="newAssetStatus" onchange="toggleBorrowerInput()">
                                <option value="Available">Available</option>
                                <option value="Borrowed">Borrowed</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3" id="borrowerField" style="display: none;">
                            <label class="form-label">Borrower Name</label>
                            <input type="text" class="form-control" name="borrower" placeholder="e.g. John Doe">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBorrowerInput() {
            const status = document.getElementById('newAssetStatus').value;
            const borrowerField = document.getElementById('borrowerField');
            borrowerField.style.display = (status === 'Borrowed') ? 'block' : 'none';
        }
    </script>
</body>
</html>