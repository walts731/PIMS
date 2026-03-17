<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Handle filter parameters for additions
$from_office_filter = isset($_GET['from_office']) ? intval($_GET['from_office']) : 0;
$to_office_filter = isset($_GET['to_office']) ? intval($_GET['to_office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Get additions history with filters
$additions_history = [];
try {
    // Query for additions only from consumable_add_history table
    $sql = "SELECT
                'addition' as transaction_type,
                h.id,
                c.description,
                h.quantity_added as quantity,
                h.units,
                h.unit_cost,
                h.total_value,
                h.office_id as from_office_id,
                h.office_id as to_office_id, -- Same office for additions
                h.added_by as released_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                NULL as received_by,
                h.add_date as transaction_date,
                'Consumable added to inventory' as notes,
                h.add_date as created_at,
                fo.office_name as from_office_name,
                fo.office_name as to_office_name, -- Same office for additions
                NULL as expected_return_date,
                NULL as actual_return_date,
                NULL as lend_status
            FROM consumable_add_history h
            LEFT JOIN consumables c ON h.consumable_id = c.id
            LEFT JOIN users u ON h.added_by = u.id
            LEFT JOIN offices fo ON h.office_id = fo.id
            WHERE 1=1";

    $params = [];
    $types = '';

    // Add filters
    if ($from_office_filter > 0) {
        $sql .= " AND h.office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }

    if ($to_office_filter > 0) {
        $sql .= " AND h.office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }

    if (!empty($date_from)) {
        $sql .= " AND DATE(h.add_date) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }

    if (!empty($date_to)) {
        $sql .= " AND DATE(h.add_date) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }

    $sql .= " ORDER BY h.add_date DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Apply search filter
        $include_record = true;

        if (!empty($search_filter)) {
            $search_term = strtolower($search_filter);
            $description_match = strpos(strtolower($row['description']), $search_term) !== false;
            $from_office_match = strpos(strtolower($row['from_office_name'] ?? ''), $search_term) !== false;
            $to_office_match = strpos(strtolower($row['to_office_name'] ?? ''), $search_term) !== false;

            if (!$description_match && !$from_office_match && !$to_office_match) {
                $include_record = false;
            }
        }

        if ($include_record) {
            $additions_history[] = $row;
        }
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Error fetching additions history: " . $e->getMessage());
}

// Get offices for dropdown filters
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching offices: " . $e->getMessage());
}
?>

<!-- Additions Tab Content -->
<div class="tab-pane fade <?php echo ($transaction_type == 'addition') ? 'show active' : ''; ?>" id="additions" role="tabpanel">
    <!-- Filters for Additions -->
    <form method="GET" id="additionsFilterForm" class="mt-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label form-label-sm">From Office</label>
                <select class="form-select form-select-sm" name="from_office">
                    <option value="">All Offices</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo $office['id']; ?>" <?php echo ($from_office_filter == $office['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($office['office_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm">To Office</label>
                <select class="form-select form-select-sm" name="to_office">
                    <option value="">All Offices</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo $office['id']; ?>" <?php echo ($to_office_filter == $office['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($office['office_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label form-label-sm">Date Range</label>
                <div class="input-group input-group-sm">
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From">
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm">&nbsp;</label>
                <div>
                    <a href="release_history.php?transaction_type=addition" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                    </a>
                </div>
            </div>
        </div>
        <input type="hidden" name="transaction_type" value="addition">
    </form>

    <!-- Additions History Table -->
    <div class="table-container mt-4">
        <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Addition History</h5>
            </div>
            <div class="col-md-6">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="additionsTableSearch" placeholder="Search additions..." value="<?php echo htmlspecialchars($search_filter); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="additionsSearchBtn">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-secondary me-2"><?php echo count($additions_history); ?> records</span>
                        <span class="badge bg-success me-2">Additions</span>
                        <?php if ($from_office_filter > 0 || $to_office_filter > 0 || !empty($date_from) || !empty($date_to)): ?>
                            <span class="badge bg-success">Filters Applied</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="additionsHistoryTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Units</th>
                        <th>Unit Cost</th>
                        <th>Total Value</th>
                        <th>From Office</th>
                        <th>To Office</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($additions_history)): ?>
                        <?php foreach ($additions_history as $addition): ?>
                            <tr>
                                <td><small><?php echo date('M j, Y H:i', strtotime($addition['transaction_date'])); ?></small></td>
                                <td><?php echo htmlspecialchars($addition['description']); ?></td>
                                <td><span class="quantity-badge"><?php echo $addition['quantity']; ?></span></td>
                                <td><?php echo htmlspecialchars($addition['units'] ?: 'N/A'); ?></td>
                                <td><?php echo number_format($addition['unit_cost'], 2); ?></td>
                                <td><span class="text-value"><?php echo number_format($addition['total_value'], 2); ?></span></td>
                                <td><?php echo htmlspecialchars($addition['from_office_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($addition['to_office_name'] ?: 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails(<?php echo $addition['id']; ?>, 'addition')">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2">No addition history found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-search functionality for additions filters
document.addEventListener('DOMContentLoaded', function() {
    // Auto-search for additions filter inputs
    const filterSelects = document.querySelectorAll('#additionsFilterForm select');
    const filterDates = document.querySelectorAll('#additionsFilterForm input[type="date"]');

    // Add change event listeners to all select elements
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            console.log('Additions filter changed:', this.name, this.value);
            const form = document.getElementById('additionsFilterForm');
            form.submit();
        });
    });

    // Add change event listeners to all date inputs
    filterDates.forEach(input => {
        input.addEventListener('change', function() {
            console.log('Additions date filter changed:', this.name, this.value);
            const form = document.getElementById('additionsFilterForm');
            form.submit();
        });
    });

    // Search functionality for additions table
    const searchInput = document.getElementById('additionsTableSearch');
    const searchBtn = document.getElementById('additionsSearchBtn');

    function performAdditionsSearch() {
        const searchValue = searchInput.value.trim();
        const currentUrl = new URL(window.location);

        currentUrl.searchParams.set('transaction_type', 'addition');

        if (searchValue) {
            currentUrl.searchParams.set('search', searchValue);
        } else {
            currentUrl.searchParams.delete('search');
        }

        window.location.href = currentUrl.toString();
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', performAdditionsSearch);
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performAdditionsSearch();
            }
        });
    }
});
</script>
