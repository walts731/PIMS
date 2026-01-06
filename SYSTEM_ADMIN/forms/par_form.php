<?php
// PAR Form Include for form_details.php
?>

<!-- PAR Form Management -->
<ul class="nav nav-tabs" id="parTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="par-preview-tab" data-bs-toggle="tab" data-bs-target="#par-preview" type="button" role="tab">
            <i class="bi bi-eye"></i> PAR Preview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="par-entries-tab" data-bs-toggle="tab" data-bs-target="#par-entries" type="button" role="tab">
            <i class="bi bi-list"></i> PAR Entries
        </button>
    </li>
</ul>

<div class="tab-content" id="parTabsContent">
    <!-- PAR Preview Tab -->
    <div class="tab-pane fade show active" id="par-preview" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-info text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-eye"></i> PAR Form Preview
                </h6>
            </div>
            <div class="card-body">
                <div class="par-preview-container" style="background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; font-family: 'Times New Roman', serif;">
                    <!-- PAR Form Header -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <?php 
                        // Debug: Check header image
                        if (!empty($header_image)) {
                            echo "<!-- Debug: header_image found: " . htmlspecialchars($header_image) . " -->";
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain; border: 1px solid red;">';
                            echo '</div>';
                        } else {
                            echo "<!-- Debug: header_image is empty -->";
                        }
                        ?>
                        <div style="text-align: center;">
                            <p style="margin: 0; font-size: 16px; font-weight: bold;">PROPERTY ACKNOWLEDGEMENT RECEIPT</p>
                            <p style="margin: 0; font-size: 12px;">MUNICIPALITY OF PILAR</p>
                            <p style="margin: 0; font-size: 12px;">OMM</p>
                            <p style="margin: 0; font-size: 12px;">OFFICE/LOCATION</p>
                        </div>
                    </div>
                    
                    <!-- Entity Name and Fund Cluster -->
                    <div style="margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 33%; padding: 5px; border: 1px solid #000;"><strong>Entity Name:</strong></td>
                                <td style="width: 33%; padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="width: 34%; padding: 5px; border: 1px solid #000;"><strong>Fund Cluster:</strong></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- PAR No -->
                    <div style="margin-bottom: 15px; text-align: center;">
                        <strong>PAR No:</strong> _________________________
                    </div>
                    
                    <!-- Main Items Table -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                            <thead>
                                <tr style="background: #f0f0f0;">
                                    <th style="padding: 5px; border: 1px solid #000; text-align: left;">Item No.</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: left;">Description</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center;">Quantity</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: left;">Unit</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: right;">Unit Price</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000; text-align: right;" colspan="4"><strong>TOTAL</strong></td>
                                    <td style="padding: 5px; border: 1px solid #000; text-align: right;">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Remarks -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000; vertical-align: top;"><strong>Remarks:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000; height: 60px;">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Signature Section -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 50%; padding: 5px; border: 1px solid #000; vertical-align: bottom;">
                                    <div style="text-align: center;">
                                        <p style="margin: 0; margin-bottom: 30px;"><strong>Received by:</strong></p>
                                        <div style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</div>
                                        <p style="margin: 0; margin-top: 5px;">Signature over Printed Name</p>
                                    </div>
                                </td>
                                <td style="width: 50%; padding: 5px; border: 1px solid #000; vertical-align: bottom;">
                                    <div style="text-align: center;">
                                        <p style="margin: 0; margin-bottom: 30px;"><strong>Issued by:</strong></p>
                                        <div style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</div>
                                        <p style="margin: 0; margin-top: 5px;">Signature over Printed Name</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Footer Note -->
                    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                        <p style="margin: 0;">This form is prescribed by the Commission on Audit (COA)</p>
                        <p style="margin: 0;">Property Acknowledgement Receipt (PAR) - Government Property Management</p>
                    </div>
                </div>
                
                <!-- Preview Controls -->
                <div class="mt-3 text-center">
                    <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Preview
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="zoomPreview()">
                        <i class="bi bi-zoom-in"></i> Zoom
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- PAR Entries Tab -->
    <div class="tab-pane fade" id="par-entries" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i> PAR Entries
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($form_data)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No PAR entries found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="parTable" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>PAR No.</th>
                                    <th>Entity Name</th>
                                    <th>Received By</th>
                                    <th>Office</th>
                                    <th>Date Received</th>
                                    <th>Items Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($form_data as $entry): ?>
                                    <?php
                                    // Get item count for this PAR
                                    $item_count_result = $conn->query("SELECT COUNT(*) as count FROM par_items WHERE form_id = " . $entry['id']);
                                    $item_count = $item_count_result->fetch_assoc()['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($entry['par_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($entry['entity_name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['received_by_name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['office_name'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($entry['date_received_left'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo $item_count; ?> items</span></td>
                                        <td>
                                            <div class="form-actions">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewPAR(<?php echo $entry['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="manageItems(<?php echo $entry['id']; ?>)">
                                                    <i class="bi bi-box"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
