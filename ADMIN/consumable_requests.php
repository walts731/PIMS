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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Supplies Consolidator - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <!-- Excel Parsing Library -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <style>
        .x-small { font-size: 0.75rem; }
        .cursor-pointer { cursor: pointer; }
        .border-dashed { border-style: dashed !important; }
        .mapping-select { border-radius: 8px; }
        .list-group-item { transition: all 0.2s ease; border-bottom: 1px solid #f0f0f0 !important; }
        .list-group-item:hover { background-color: #f8f9fa; }
        .rounded-4 { border-radius: 1rem !important; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <?php $page_title = 'Office Supplies Consolidator'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-excel"></i> Office Supplies Consolidator
                    </h1>
                    <p class="text-muted mb-0">Consolidate master supply lists into separate, office-specific Excel files</p>
                </div>
                <div class="col-4 text-end">
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Reset Tool
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Consolidator Tool Column -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <form id="excelImportForm">
                            <!-- Step 1: File Selection -->
                            <div id="importStep1">
                                <div class="alert alert-info border-0 shadow-sm mb-4">
                                    <h6 class="alert-heading fw-bold small"><i class="bi bi-info-circle"></i> How it works</h6>
                                    <p class="mb-0 x-small" style="font-size: 0.85rem;">Upload your master list. Match the description/unit columns, then select which office columns to process. The system will generate separate Excel files for each office.</p>
                                </div>
                                
                                <div class="mb-3 text-center py-5 border-2 border-dashed rounded-4 bg-light cursor-pointer" onclick="document.getElementById('excelFile').click()">
                                    <i class="bi bi-cloud-arrow-up display-5 text-primary mb-3 d-inline-block"></i>
                                    <h5 class="fw-bold">Select Master Excel File</h5>
                                    <p class="text-muted small">Drag and drop or click to browse</p>
                                    <input type="file" id="excelFile" class="form-control d-none" accept=".xlsx, .xls" required>
                                </div>
                            </div>

                            <!-- Step 2: Mapping -->
                            <div id="importStep2" style="display: none;">
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">1</span> Map Item Columns
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle border-0 mb-0">
                                            <tbody id="mappingList">
                                                <!-- Dynamic mapping rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2 d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">2</span> Select Office Columns
                                    </h6>
                                    <p class="x-small text-muted mb-3">Checked columns will be treated as separate office exports.</p>
                                    <div id="officeColumnCheckboxes" class="row g-2 p-2 border rounded-3 bg-light overflow-auto" style="max-height: 300px;">
                                        <!-- Dynamic checkboxes -->
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-light" onclick="location.reload()">Back</button>
                                    <button type="submit" id="submitImport" class="btn btn-success px-5 rounded-pill shadow-sm">
                                        <i class="bi bi-lightning-fill"></i> Consolidate & Download All
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="excel_json" id="excelJson">
                            <input type="hidden" name="mapping_conf" id="mappingConf">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Results Column -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h5 class="mb-0 fw-bold">Consolidated Results</h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div id="emptyResults" class="text-center py-5">
                            <i class="bi bi-file-earmark-spreadsheet fs-1 text-muted opacity-25"></i>
                            <p class="mt-3 text-muted">No files generated yet.<br>Upload a file to start consolidation.</p>
                        </div>
                        
                        <div id="downloadList" class="list-group list-group-flush" style="display: none;">
                            <!-- Dynamically populated downloads -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Float Next Button -->
        <div id="floatingAction" class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050; display: none;">
            <button type="button" id="nextStep" class="btn btn-primary btn-lg rounded-pill shadow-lg px-4">
                Continue to Mapping <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global storage for re-downloads
        window.generatedFiles = {};

        $(document).ready(function() {
            let rawExcelData = null;
            let excelHeaders = [];

            $('#excelFile').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const dropZone = $(this).closest('.cursor-pointer');
                const originalContent = dropZone.html();
                dropZone.html('<div class="py-3"><div class="spinner-border text-primary mb-3"></div><h5 class="fw-bold">Analyzing Workbook...</h5></div>');

                const reader = new FileReader();
                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    
                    // Smarter Header Detection: try rows 0-5 and pick the one with most keyword matches
                    const itemKeywords = ['desc', 'item', 'particulars', 'article', 'description', 'stock', 'unit'];
                    let bestRange = 0;
                    let maxMatches = -1;
                    let bestData = null;

                    for (let r = 0; r <= 5; r++) {
                        try {
                            let testData = XLSX.utils.sheet_to_json(firstSheet, { range: r, defval: null });
                            if (testData.length > 0) {
                                let headers = Object.keys(testData[0]);
                                let matches = headers.filter(h => 
                                    itemKeywords.some(k => String(h).toLowerCase().includes(k))
                                ).length;
                                
                                if (matches > maxMatches) {
                                    maxMatches = matches;
                                    bestRange = r;
                                    bestData = testData;
                                }
                            }
                        } catch(err) { continue; }
                    }

                    rawExcelData = bestData || [];
                    
                    if (rawExcelData.length > 0) {
                        excelHeaders = Object.keys(rawExcelData[0]);
                        $('#floatingAction').fadeIn();
                        dropZone.html('<i class="bi bi-check-circle-fill display-5 text-success mb-3 d-inline-block"></i><h5 class="fw-bold">File Identified</h5><p class="text-muted small">' + file.name + ' (' + rawExcelData.length + ' rows found)</p>');
                    } else {
                        alert('Could not find data in the first sheet. Please ensure your Excel file contains a table starting in Row 1 or Row 3.');
                        location.reload();
                    }
                };
                reader.readAsArrayBuffer(file);
            });

            $('#nextStep').on('click', function() {
                $('#importStep1').fadeOut(200, function() {
                    $('#importStep2').fadeIn(200);
                    $('#floatingAction').hide();
                    
                    const systemFields = [
                        { name: 'description', label: 'Item Description', required: true, keywords: ['desc', 'item', 'particulars', 'article', 'description', 'stock'] },
                        { name: 'unit', label: 'Unit of Measure', required: false, keywords: ['unit', 'measure', 'uom', 'basis', 'measurement'] }
                    ];

                    let html = '';
                    systemFields.forEach(field => {
                        let options = excelHeaders.map(h => {
                            const isMatch = field.keywords.some(k => h.toLowerCase().trim() === k.toLowerCase() || h.toLowerCase().includes(k.toLowerCase()));
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
                        const hLower = String(h).toLowerCase().trim();
                        // Is this column one of the core fields?
                        const isCore = systemFields.some(f => f.keywords.some(k => hLower === k.toLowerCase() || hLower.includes(k.toLowerCase())));
                        
                        // Refined Ignore logic: ignore only if it's EXACTLY a common non-office keyword
                        const blackList = ['total', 'cost', 'price', 'id', 'no.', 'avg', 'value', 'grand total', 'remarks', 'amount', 'balance', 'stock'];
                        const isIgnored = blackList.includes(hLower);
                        
                        if (!isCore) {
                            // Data analysis for indicator only
                            let hasData = rawExcelData.some(row => {
                                const val = row[h];
                                return val !== undefined && val !== null && !isNaN(parseFloat(val)) && parseFloat(val) > 0;
                            });

                            const isSecondary = h.startsWith('__EMPTY');
                            let isChecked = (!isIgnored || hasData) && !isSecondary;
                            const safeH = String(h).replace(/"/g, '&quot;');
                            const displayH = isSecondary ? '(Secondary Column)' : safeH;
                            officeHtml += `
                                <div class="col-md-6 mb-2">
                                    <div class="form-check text-truncate" title="${safeH}">
                                        <input class="form-check-input office-checkbox" type="checkbox" value="${safeH}" id="chk_${safeH}" ${isChecked ? 'checked' : ''}>
                                        <label class="form-check-label small" for="chk_${safeH}">
                                            ${displayH} ${hasData ? '<i class="bi bi-check-all text-success" title="Contains quantities"></i>' : ''}
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

                const officeColumns = [];
                $('.office-checkbox:checked').each(function() {
                    officeColumns.push($(this).val());
                });

                if (officeColumns.length === 0) {
                    alert('Please select at least one Office column.');
                    return;
                }

                $('#submitImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Consolidating...');

                const officeData = {};
                rawExcelData.forEach(row => {
                    const desc = row[mapping.description] ? String(row[mapping.description]).trim() : '';
                    const unit = mapping.unit && row[mapping.unit] ? String(row[mapping.unit]).trim() : '';
                    if (!desc) return;

                    officeColumns.forEach(officeCol => {
                        const cellValue = row[officeCol];
                        const qty = cellValue !== undefined && cellValue !== null && !isNaN(parseFloat(cellValue)) ? parseFloat(cellValue) : 0;
                        if (qty > 0) {
                            const office = String(officeCol).trim();
                            if (!officeData[office]) officeData[office] = [];
                            
                            const normUnit = (u) => {
                                let val = String(u || 'pcs').trim().toLowerCase();
                                const rules = { 'pcs': 'pc', 'pieces': 'pc', 'piece': 'pc', 'boxes': 'box', 'packs': 'pack' };
                                return rules[val] || val;
                            };
                            
                            const normalizedUnit = normUnit(unit);
                            const normalizedDesc = desc.toLowerCase();

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
                    alert('No quantities found.');
                    $('#submitImport').prop('disabled', false).html('<i class="bi bi-lightning-fill"></i> Consolidate & Download All');
                    return;
                }

                // Clear and Show Results
                $('#emptyResults').hide();
                $('#downloadList').show().empty();
                generatedFiles = {};

                let delay = 0;
                officesToExport.forEach((officeName, index) => {
                    setTimeout(() => {
                        const ws = XLSX.utils.json_to_sheet(officeData[officeName]);
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Supplies");
                        ws['!cols'] = [{ wch: 50 }, { wch: 15 }, { wch: 15 }];

                        const safeOfficeName = officeName.replace(/[^a-zA-Z0-9_-]/g, '_');
                        const fileName = `${safeOfficeName}_Office_Supplies.xlsx`;
                        
                        // Add to list
                        const listItem = $(`
                            <div class="list-group-item border-0 px-0 py-3 d-flex justify-content-between align-items-center animate-fade-in">
                                <div>
                                    <i class="bi bi-file-excel text-success me-2 fs-5"></i>
                                    <span class="fw-bold text-dark">${officeName}</span>
                                    <div class="text-muted x-small">${officeData[officeName].length} unique items consolidated</div>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary border-0 rounded-circle" onclick="window.viewOfficeData('${officeName.replace(/'/g, "\\'")}')" title="View Items">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button class="btn btn-sm btn-outline-success border-0 rounded-circle" onclick="window.downloadFile('${officeName.replace(/'/g, "\\'")}')" title="Download Excel List">
                                        <i class="bi bi-download"></i>
                                    </button>
                                </div>
                            </div>
                        `);
                        $('#downloadList').append(listItem);
                        window.generatedFiles[officeName] = { data: officeData[officeName], name: fileName };

                        if (index === officesToExport.length - 1) {
                            $('#submitImport').prop('disabled', false).html('<i class="bi bi-lightning-fill"></i> Consolidate & Download All');
                        }
                    }, delay);
                    delay += 300;
                });
            });
        });

        // Global functions for interactive results
        window.viewOfficeData = function(officeName) {
            const file = window.generatedFiles[officeName];
            if (!file) return;

            $('#previewOfficeName').text(officeName);
            let html = '';
            file.data.forEach(item => {
                html += `
                    <tr>
                        <td>${item['Item Description']}</td>
                        <td>${item['Unit']}</td>
                        <td class="text-end fw-bold">${item['Quantity']}</td>
                    </tr>
                `;
            });
            $('#previewTableBody').html(html);
            const modal = new bootstrap.Modal(document.getElementById('viewOfficeModal'));
            modal.show();
        };



        window.downloadFile = function(officeName) {
            const file = window.generatedFiles[officeName];
            if (file) {
                const ws = XLSX.utils.json_to_sheet(file.data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Supplies");
                ws['!cols'] = [{ wch: 50 }, { wch: 15 }, { wch: 15 }];
                XLSX.writeFile(wb, file.name);
            }
        };
    </script>

    <!-- View Items Modal -->
    <div class="modal fade" id="viewOfficeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary">
                        <i class="bi bi-building"></i> <span id="previewOfficeName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Item Description</th>
                                    <th>Unit</th>
                                    <th class="text-end">Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
