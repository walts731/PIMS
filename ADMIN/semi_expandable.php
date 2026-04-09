<?php
// This file is included in property_card.php or accessed directly
if (!isset($conn)) {
    session_start();
    require_once '../config.php';
    require_once '../includes/system_functions.php';
}

// Get filter parameters (if not already set from property_card.php)
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get asset items with ICS ID and filters
$semi_expandable_items = [];
if ($conn && !$conn->connect_error) {
    try {
        // Optimized query with JOINs
        $query = "SELECT 
                    ai.id,
                    ai.created_at,
                    ai.property_no,
                    ai.description,
                    ai.value,
                    ai.ics_id,
                    ai.employee_id,
                    ai.office_id,
                    COALESCE(ac.category_name, 'Uncategorized') as asset_category,
                    COALESCE(ac.category_code, 'UNCAT') as asset_category_code,
                    COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                    COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code,
                    CONCAT(COALESCE(e.firstname, ''), ' ', COALESCE(e.lastname, '')) as employee_name,
                    e.employee_no,
                    if.ics_no,
                    if.received_by as received_by_name,
                    ai.ics_par_no
                  FROM asset_items ai
                  LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                  LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id
                  LEFT JOIN ics_forms `if` ON ai.ics_id = `if`.id
                  WHERE (ai.ics_id IS NOT NULL AND ai.ics_id != '') 
                  OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value < 50000)";
        
        // Add category filter
        if (!empty($selected_category)) {
            $query .= " AND ac.category_name = '" . $conn->real_escape_string($selected_category) . "'";
        }
        
        // Add office filter
        if (!empty($selected_office)) {
            $query .= " AND (o1.office_name = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_name = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        $query .= " ORDER BY ai.created_at ASC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Clean up employee name if empty
                if (empty(trim($row['employee_name']))) {
                    $row['employee_name'] = '';
                    $row['employee_no'] = '';
                }
                
                // Clean up received_by_name if empty
                if (empty($row['received_by_name'])) {
                    $row['received_by'] = '';
                } else {
                    $row['received_by'] = $row['received_by_name'];
                }
                
                $semi_expandable_items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Semi-Expandable Query Error: " . $e->getMessage());
    }
}
?>

<div class="table-container">
    <div class="row mb-3">
        <div class="col-md-6">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Semi-Expandable Items (Below 50k)</h5>
        </div>
    </div>
    
    <?php if (empty($semi_expandable_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #adb5bd;"></i>
            <h4 class="mt-3 text-muted">No Semi-Expandable Items Found</h4>
            <p class="text-muted">There are no asset items with ICS references in the system.</p>
            <a href="ics_form.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create ICS Form
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="semiExpandableTable" style="width: 100%">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>ICS No.</th>
                        <th>Property No.</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Office</th>
                        <th>Employee</th>
                        <th>Value</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semi_expandable_items as $item): ?>
                        <tr>
                            <td>
                                <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($item['ics_no'] ?: $item['ics_par_no'] ?: 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="property-no"><?php echo htmlspecialchars($item['property_no']); ?></span>
                            </td>
                            <td>
                                <span class="category-badge"><?php echo htmlspecialchars($item['asset_category']); ?></span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['description']); ?>
                            </td>
                            <td>
                                <span class="office-name"><?php echo htmlspecialchars($item['office_name']); ?></span>
                            </td>
                            <td>
                                <?php if ($item['employee_name']): ?>
                                    <?php echo htmlspecialchars($item['employee_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>₱<?php echo number_format($item['value'], 2); ?></strong>
                            </td>
                            <td class="no-print">
                                <div class="btn-group" role="group">
                                    <a href="view_asset_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Asset Item">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    // Initialize DataTable for Semi-Expandable items
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#semiExpandableTable')) {
            $('#semiExpandableTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                },
                dom: '<"row"<"col-md-2"l><"col-md-3 category-filter-container-semi"><"col-md-3 office-filter-container-semi"><"col-md-4"f>>rtip',
                initComplete: function(settings, json) {
                    // Add category filter to DataTables
                    $('.category-filter-container-semi').html(`
                        <select id="categoryFilter" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_name']; ?>" <?php echo $selected_category === $category['category_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Add office filter to DataTables
                    $('.office-filter-container-semi').html(`
                        <select id="officeFilter" class="form-select form-select-sm">
                            <option value="">All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo $office['office_name']; ?>" <?php echo $selected_office === $office['office_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['office_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Apply filter events
                    $('#categoryFilter, #officeFilter').on('change', function() {
                        applySemiExpandableFilters();
                    });
                    
                    // Initial filter application
                    applySemiExpandableFilters();
                },
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-primary btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    }
                ]
            });
        }
    });

    // Custom filtering function for Semi-Expandable table
    function applySemiExpandableFilters() {
        if (!$('#semiExpandableTable').length) return;
        const table = $('#semiExpandableTable').DataTable();
        
        // Use a persistent key to identify our filter
        const filterKey = 'semiExpandableFilter';
        
        // Remove existing filter with this key if any (by clearing and re-registering or using a check)
        // For simplicity in this structure, we'll clear and re-push but with a table ID check
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
            return false; // Clear all for now as this is the only one
        });
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'semiExpandableTable') return true;
            
            const category = $('#categoryFilter').val();
            const office = $('#officeFilter').val();
            
            // Get data and strip HTML just in case
            const stripHtml = (html) => {
                const tmp = document.createElement("DIV");
                tmp.innerHTML = html;
                return tmp.textContent || tmp.innerText || "";
            };
            
            const categoryValue = stripHtml(data[3] || ''); 
            const officeValue = stripHtml(data[5] || '');   
            
            // Category filter
            if (category && categoryValue.trim() !== category) {
                return false;
            }
            
            // Office filter
            if (office && officeValue.trim() !== office) {
                return false;
            }
            
            return true;
        });
        
        table.draw();
    }
</script>
