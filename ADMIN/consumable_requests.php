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

logSystemAction($_SESSION['user_id'], 'Accessed Consumable Requests', 'consumables', 'consumable_requests.php');

// Get RIS forms that are categorized for consumables or just all entries as a starting point
$ris_forms = [];
$result = $conn->query("
    SELECT 
        f.*,
        COUNT(i.id) as item_count,
        SUM(i.total_amount) as total_value
    FROM ris_forms f 
    LEFT JOIN ris_items i ON f.id = i.ris_form_id 
    GROUP BY f.id 
    ORDER BY f.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ris_forms[] = $row;
    }
}

// Get Segregated items by Office
$office_summaries = [];
$result = $conn->query("
    SELECT 
        f.office,
        i.description,
        SUM(i.quantity) as total_quantity,
        i.unit
    FROM ris_forms f
    JOIN ris_items i ON f.id = i.ris_form_id
    GROUP BY f.office, i.description, i.unit
    ORDER BY f.office ASC, total_quantity DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $office_summaries[$row['office']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumable Requests - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <!-- Excel Parsing Library -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <style>
        .request-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>
    <?php $page_title = 'Consumable Requests'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-plus"></i> Consumable Requests
                    </h1>
                    <p class="text-muted mb-0">View and manage requisition and issue slips specifically for consumables</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a href="ris_form.php" class="dropdown-item"><i class="bi bi-plus-circle"></i> New Request</a></li>
                            <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importSuppliesModal"><i class="bi bi-file-earmark-excel"></i> Consolidate & Download Supplies</button></li>
                            <li><button class="dropdown-item" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation & Filters -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <ul class="nav nav-pills" id="requestTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="entries-tab" data-bs-toggle="pill" data-bs-target="#entries" type="button" role="tab">
                        <i class="bi bi-list-ul"></i> RIS Entries
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="segregation-tab" data-bs-toggle="pill" data-bs-target="#segregation" type="button" role="tab">
                        <i class="bi bi-building"></i> Segregation by Office
                    </button>
                </li>
            </ul>
            
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm shadow-sm border-0" id="sourceFilter" style="width: auto;">
                    <option value="">All Sources</option>
                    <option value="Excel Import">Excel Imports</option>
                    <option value="Manual Entry">Manual Entries</option>
                </select>
            </div>
        </div>

        <div class="tab-content" id="requestTabsContent">
            <!-- RIS Entries Tab -->
            <div class="tab-pane fade show active" id="entries" role="tabpanel">
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover" id="requestsTable">
                            <thead>
                                <tr>
                                    <th>RIS No.</th>
                                    <th>Date</th>
                                    <th>Office</th>
                                    <th>Division</th>
                                    <th>Requested By</th>
                                    <th>Items</th>
                                    <th>Source</th>
                                    <th>Total Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ris_forms as $ris): ?>
                                    <tr>
                                        <td class="fw-bold fs-6 text-primary"><?php echo htmlspecialchars($ris['ris_no']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($ris['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($ris['office']); ?></td>
                                        <td><?php echo htmlspecialchars($ris['division']); ?></td>
                                        <td>
                                            <div class="fw-medium"><?php echo htmlspecialchars($ris['requested_by']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($ris['requested_by_position']); ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo $ris['item_count']; ?> items</span></td>
                                        <td>
                                            <?php if (strpos($ris['purpose'], 'Office Supplies 2026') !== false): ?>
                                                <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-excel"></i> Excel Import</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Manual Entry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold">₱<?php echo number_format($ris['total_value'] ?: 0, 2); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewRIS(<?php echo $ris['id']; ?>)" title="View Detailed RIS">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="printRIS(<?php echo $ris['id']; ?>)" title="Print RIS Form">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Segregation by Office Tab -->
            <div class="tab-pane fade" id="segregation" role="tabpanel">
                <div class="row g-4">
                    <?php if (empty($office_summaries)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2">No items found for office segregation.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($office_summaries as $office_name => $items): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="request-card h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 text-primary fw-bold">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($office_name); ?>
                                        </h5>
                                        <span class="badge bg-primary rounded-pill"><?php echo count($items); ?> Unique Items</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <thead class="text-muted small border-bottom">
                                                <tr>
                                                    <th>Item Description</th>
                                                    <th class="text-end">Total Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                        <td class="text-end fw-bold">
                                                            <?php echo $item['total_quantity']; ?> 
                                                            <small class="text-muted fw-normal"><?php echo htmlspecialchars($item['unit']); ?></small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Office Supplies Modal -->
    <div class="modal fade" id="importSuppliesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel text-success"></i> Office Supplies Consolidator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="excelImportForm">
                    <div class="modal-body p-4">
                        <!-- Step 1: File Select -->
                        <div id="importStep1">
                            <div class="alert alert-info border-0 shadow-sm mb-4">
                                <h6 class="alert-heading fw-bold"><i class="bi bi-info-circle"></i> Offline Consolidation</h6>
                                <p class="small mb-0">Upload your Office Supplies list. The system will group items by office and generate a <strong>New Segregated Excel File</strong>. <br><span class="text-danger fw-bold">No data is saved to the database.</span></p>
                            </div>
                            
                            <div class="mb-3 text-center py-4 border-2 border-dashed rounded-3 bg-light">
                                <i class="bi bi-cloud-arrow-up display-5 text-primary mb-2 d-inline-block"></i>
                                <h6 class="fw-bold">Drag and drop file here</h6>
                                <p class="text-muted small">or click to browse from folder</p>
                                <input type="file" id="excelFile" class="form-control d-none" accept=".xlsx, .xls" required>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="document.getElementById('excelFile').click()">
                                    Select Excel File
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Mapping -->
                        <div id="importStep2" style="display: none;">
                            <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3"></i> Map Core Columns</h6>
                            <p class="small text-muted mb-3">Please match the item description and unit columns from your Excel file.</p>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle border-top-0 mb-0">
                                    <tbody id="mappingList">
                                        <!-- Dynamic mapping rows -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div>
                                <h6 class="fw-bold mb-2"><i class="bi bi-building"></i> Select Office Columns</h6>
                                <p class="small text-muted mb-2">Check the columns that represent departments/offices. The numbers inside these columns will be treated as the requested quantities.</p>
                                <div id="officeColumnCheckboxes" class="row g-2 max-height-200 overflow-auto p-2 border rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                                    <!-- Dynamic checkboxes -->
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="excel_json" id="excelJson">
                        <input type="hidden" name="mapping_conf" id="mappingConf">
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="nextStep" class="btn btn-primary px-4 d-none">
                            Next: Map Columns <i class="bi bi-arrow-right"></i>
                        </button>
                        <button type="submit" id="submitImport" class="btn btn-success px-4 d-none">
                            <i class="bi bi-file-earmark-arrow-down"></i> Segregate & Download
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            var table = $('#requestsTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[1, "desc"]], // Default order by date
                "info": true,
                "responsive": true,
                "dom": "<'row mb-3 align-items-center'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-8 text-end'f>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                "language": {
                    "search": "Filter requests:",
                    "lengthMenu": "_MENU_",
                    "emptyTable": "No consumable requests available"
                }
            });

            // Source Filter Logic
            $('#sourceFilter').on('change', function() {
                table.column(6).search(this.value).draw();
            });

            // Excel Import Logic (Smart Mapping)
            let rawExcelData = null;
            let excelHeaders = [];

            $('#excelFile').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                $('#nextStep').removeClass('d-none').prop('disabled', true).html('Reading File...');

                const reader = new FileReader();
                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    
                    // Treat Row 3 as the header (0-based index 2)
                    rawExcelData = XLSX.utils.sheet_to_json(firstSheet, { range: 2 });
                    
                    if (rawExcelData.length > 0) {
                        excelHeaders = Object.keys(rawExcelData[0]);
                        $('#nextStep').prop('disabled', false).html('Next: Map Columns <i class="bi bi-arrow-right"></i>');
                    } else {
                        alert('Excel file seems empty starting from Row 3.');
                        $('#nextStep').addClass('d-none');
                    }
                };
                reader.readAsArrayBuffer(file);
            });

            $('#nextStep').on('click', function() {
                $('#importStep1').fadeOut(200, function() {
                    $('#importStep2').fadeIn(200);
                    $('#nextStep').addClass('d-none');
                    $('#submitImport').removeClass('d-none');
                    
                    const systemFields = [
                        { name: 'description', label: 'Item Description', required: true, keywords: ['desc', 'item', 'particulars', 'article'] },
                        { name: 'unit', label: 'Unit of Measure', required: false, keywords: ['unit', 'measure', 'uom'] }
                    ];

                    let html = '';
                    systemFields.forEach(field => {
                        let options = excelHeaders.map(h => {
                            // Smart auto-select logic
                            const isMatch = field.keywords.some(k => h.toLowerCase().includes(k.toLowerCase()));
                            return `<option value="${h}" ${isMatch ? 'selected' : ''}>${h}</option>`;
                        }).join('');

                        html += `
                            <tr>
                                <td width="40%">
                                    <span class="fw-bold small">${field.label}</span>
                                    ${field.required ? '<span class="text-danger">*</span>' : ''}
                                </td>
                                <td>
                                    <select class="form-select form-select-sm mapping-select" data-field="${field.name}">
                                        <option value="">-- Select Column --</option>
                                        ${options}
                                    </select>
                                </td>
                            </tr>
                        `;
                    });
                    $('#mappingList').html(html);

                    let officeHtml = '';
                    excelHeaders.forEach(h => {
                        const hLower = h.toLowerCase();
                        const isCore = systemFields.some(f => f.keywords.some(k => hLower.includes(k.toLowerCase())));
                        const isIgnored = hLower.includes('cost') || hLower.includes('total') || hLower.includes('price') || hLower.includes('__empty');
                        if (!isCore) {
                            let isChecked = !isIgnored ? 'checked' : '';
                            const safeH = String(h).replace(/"/g, '&quot;');
                            const displayH = h.startsWith('__EMPTY') ? '(Blank Column)' : safeH;
                            officeHtml += `
                                <div class="col-md-4 col-6 mb-2">
                                    <div class="form-check text-truncate" title="${safeH}">
                                        <input class="form-check-input office-checkbox" type="checkbox" value="${safeH}" id="chk_${safeH}" ${isChecked}>
                                        <label class="form-check-label small" for="chk_${safeH}">
                                            ${displayH}
                                        </label>
                                    </div>
                                </div>
                            `;
                        }
                    });
                    $('#officeColumnCheckboxes').html(officeHtml || '<div class="col-12"><p class="small text-muted">No additional columns found.</p></div>');
                });
            });

            $('#excelImportForm').on('submit', function(e) {
                e.preventDefault();
                
                // Collect mapping
                const mapping = {};
                let missingRequired = false;
                $('.mapping-select').each(function() {
                    const field = $(this).data('field');
                    const value = $(this).val();
                    if (value) mapping[field] = value;
                    else if (field === 'description') missingRequired = true;
                });

                if (missingRequired) {
                    alert('Please map the required field: Item Description.');
                    return;
                }

                // Get selected office columns
                const officeColumns = [];
                $('.office-checkbox:checked').each(function() {
                    officeColumns.push($(this).val());
                });

                if (officeColumns.length === 0) {
                    alert('Please select at least one Office column to import quantities from.');
                    return;
                }

                // SEGREGATION LOGIC (Multiple Files per Office)
                const officeData = {}; // Object to hold arrays of items per office

                rawExcelData.forEach(row => {
                    const desc = row[mapping.description] ? String(row[mapping.description]).trim() : '';
                    const unit = mapping.unit && row[mapping.unit] ? String(row[mapping.unit]).trim() : '';

                    if (!desc) return;

                    // For this item, check every selected office column
                    officeColumns.forEach(officeCol => {
                        const cellValue = row[officeCol];
                        const qty = cellValue !== undefined && cellValue !== null && !isNaN(parseFloat(cellValue)) ? parseFloat(cellValue) : 0;
                        
                        if (qty > 0) {
                            const office = String(officeCol).trim();
                            
                            if (!officeData[office]) {
                                officeData[office] = [];
                            }
                            
                            // Normalization function (simplified for JS)
                            const normUnit = (u) => {
                                let val = String(u || 'pcs').trim().toLowerCase();
                                const rules = { 'pcs': 'pc', 'pieces': 'pc', 'piece': 'pc', 'boxes': 'box', 'packs': 'pack' };
                                return rules[val] || val;
                            };
                            
                            const normalizedUnit = normUnit(unit);
                            const normalizedDesc = desc.toLowerCase();

                            // Check if item already exists for this office using case-insensitive find
                            const existingItem = officeData[office].find(item => 
                                item['Item Description'].toLowerCase() === normalizedDesc && 
                                normUnit(item['Unit']) === normalizedUnit
                            );
                            
                            if (existingItem) {
                                existingItem['Quantity'] += qty;
                            } else {
                                officeData[office].push({
                                    'Item Description': desc,
                                    'Unit': normalizedUnit,
                                    'Quantity': qty
                                });
                            }
                        }
                    });
                });

                const officesToExport = Object.keys(officeData);
                
                if (officesToExport.length === 0) {
                    alert('No quantities > 0 found in the selected office columns.');
                    return;
                }

                $('#submitImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating Files...');

                // Generate & Download Multiple Excels (with a small delay to avoid browser blocking)
                let delay = 0;
                officesToExport.forEach((officeName, index) => {
                    setTimeout(() => {
                        const ws = XLSX.utils.json_to_sheet(officeData[officeName]);
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Supplies");
                        
                        // Styling (Auto-size columns roughly)
                        ws['!cols'] = [{ wch: 50 }, { wch: 15 }, { wch: 15 }];

                        // Clean office name for filename
                        const safeOfficeName = officeName.replace(/[^a-zA-Z0-9_-]/g, '_');
                        XLSX.writeFile(wb, `${safeOfficeName}_Office_Supplies.xlsx`);

                        // On the last file download, reset UI and alert
                        if (index === officesToExport.length - 1) {
                            setTimeout(() => {
                                $('#importSuppliesModal').modal('hide');
                                alert(`Success! Generated and downloaded separate Excel files for ${officesToExport.length} offices.`);
                                $('#submitImport').prop('disabled', false).html('<i class="bi bi-file-earmark-arrow-down"></i> Segregate & Download');
                            }, 500);
                        }
                    }, delay);
                    
                    delay += 500; // 500ms delay between generating files to prevent browser blocking
                });
            });
        });

        function viewRIS(id) {
            window.open('ris_view.php?id=' + id, '_blank');
        }
        
        function printRIS(id) {
            window.open('print_ris.php?id=' + id, '_blank');
        }
    </script>
</body>
</html>
