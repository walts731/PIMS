<?php
// ICS Form Include for form_details.php
?>

<!-- ICS Form Management -->
<ul class="nav nav-tabs" id="icsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="ics-preview-tab" data-bs-toggle="tab" data-bs-target="#ics-preview" type="button" role="tab">
            <i class="bi bi-eye"></i> ICS Preview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ics-entries-tab" data-bs-toggle="tab" data-bs-target="#ics-entries" type="button" role="tab">
            <i class="bi bi-list"></i> ICS Entries
        </button>
    </li>
</ul>

<div class="tab-content" id="icsTabsContent">
    <!-- ICS Preview Tab -->
    <div class="tab-pane fade show active" id="ics-preview" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-info text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-eye"></i> ICS Form Preview
                </h6>
            </div>
            <div class="card-body">
                <div class="ics-preview-container" style="background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; font-family: 'Times New Roman', serif;">
                    <!-- ICS Form Header -->
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
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div style="flex: 1; text-align: center;">
                                <p style="margin: 0; font-size: 16px; font-weight: bold;">INVENTORY CUSTODIAN SLIP</p>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Entity Name, Fund Cluster, and ICS No -->
                    <div style="margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 40%; padding: 5px; border: 1px solid #000;"><strong>Entity Name:</strong></td>
                                <td style="width: 40%; padding: 5px; border: 1px solid #000;"><strong>Fund Cluster:</strong></td>
                                <td style="width: 20%; padding: 5px; border: 1px solid #000;"><strong>ICS No.:</strong></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Main Items Table -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                            <thead>
                                <tr style="background: #f0f0f0;">
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">Quantity</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">Unit</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 25%;">
                                        <div style="font-weight: normal; font-size: 11px;">Amount</div>
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">Unit Cost</div>
                                            <div style="flex: 1; padding: 2px;">Total Cost</div>
                                        </div>
                                    </th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: left; width: 35%;">Description</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">Item No.</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">Estimated Useful Life</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">
                                        <div style="display: flex;">
                                            <div style="flex: 1; border-right: 1px solid #000; padding: 2px;">&nbsp;</div>
                                            <div style="flex: 1; padding: 2px;">&nbsp;</div>
                                        </div>
                                    </td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Signature Section -->
                    <div style="margin-top: 40px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">Received from:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Position / Office</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">Received by:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Position / Office</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Footer Note -->
                    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                        <p style="margin: 0;">This form is prescribed by the Commission on Audit (COA)</p>
                        <p style="margin: 0;">Inventory Custodian Slip (ICS) - Government Property Management</p>
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
    
    <!-- ICS Entries Tab -->
    <div class="tab-pane fade" id="ics-entries" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i> ICS Entries
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($form_data)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No ICS entries found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="icsTable" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ICS No.</th>
                                    <th>Entity Name</th>
                                    <th>Received From</th>
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
                                    // Get item count for this ICS
                                    $item_count_result = $conn->query("SELECT COUNT(*) as count FROM ics_items WHERE ics_id = " . $entry['id']);
                                    $item_count = $item_count_result->fetch_assoc()['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($entry['ics_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($entry['entity_name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['received_from_name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['received_by_name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['office_name'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($entry['created_at'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo $item_count; ?> items</span></td>
                                        <td>
                                            <div class="form-actions">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewICS(<?php echo $entry['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="manageICSItems(<?php echo $entry['id']; ?>)">
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
