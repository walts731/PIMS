<?php
// RIS Form Include for form_details.php
?>

<!-- RIS Form Management -->
<ul class="nav nav-tabs" id="risTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="ris-preview-tab" data-bs-toggle="tab" data-bs-target="#ris-preview" type="button" role="tab">
            <i class="bi bi-eye"></i> RIS Preview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ris-entries-tab" data-bs-toggle="tab" data-bs-target="#ris-entries" type="button" role="tab">
            <i class="bi bi-list"></i> RIS Entries
        </button>
    </li>
</ul>

<div class="tab-content" id="risTabsContent">
    <!-- RIS Preview Tab -->
    <div class="tab-pane fade show active" id="ris-preview" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-info text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-eye"></i> RIS Form Preview
                </h6>
            </div>
            <div class="card-body">
                <div class="ris-preview-container" style="background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; font-family: 'Times New Roman', serif;">
                    <!-- RIS Form Header -->
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
                       
                    </div>
                    
                    <!-- Header Information -->
                    <div style="margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 25%; padding: 5px; border: 1px solid #000;"><strong>DIVISION:</strong></td>
                                <td style="width: 25%; padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="width: 25%; padding: 5px; border: 1px solid #000;"><strong>RESPONSIBILITY CENTER:</strong></td>
                                <td style="width: 25%; padding: 5px; border: 1px solid #000;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000;"><strong>RIS NO.:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;"><strong>SAI NO.:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000;"><strong>OFFICE:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;"><strong>CODE:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000;"><strong>DATE:</strong></td>
                                <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                <td style="padding: 5px; border: 1px solid #000;" colspan="2">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Main Items Table -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                            <thead>
                                <tr style="background: #f0f0f0;">
                                    <th rowspan="2" style="padding: 5px; border: 1px solid #000; text-align: center; width: 15%;">STOCK NO.</th>
                                    <th rowspan="2" style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">UNIT</th>
                                    <th rowspan="2" style="padding: 5px; border: 1px solid #000; text-align: left; width: 30%;">DESCRIPTION</th>
                                    <th colspan="2" style="padding: 5px; border: 1px solid #000; text-align: center; width: 20%;">REQUISITION</th>
                                    <th colspan="3" style="padding: 5px; border: 1px solid #000; text-align: center; width: 25%;">ISSUANCE</th>
                                </tr>
                                <tr style="background: #f0f0f0;">
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">QUANTITY</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 10%;">QUANTITY</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 5%;">SIGNATURE</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 5%;">PRICE</th>
                                    <th style="padding: 5px; border: 1px solid #000; text-align: center; width: 5%;">TOTAL AMOUNT</th>
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
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                    <td style="padding: 5px; border: 1px solid #000;">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Purpose Section -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid #000; font-weight: bold; width: 15%;">PURPOSE:</td>
                                <td style="padding: 5px; border: 1px solid #000; min-height: 60px; vertical-align: top;">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Signature Section -->
                    <div style="margin-top: 40px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 25%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">REQUESTED BY:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                                <td style="width: 25%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">APPROVED BY:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                                <td style="width: 25%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">ISSUED BY:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                                <td style="width: 25%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">RECEIVED BY:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Printed Name</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Date</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Footer Note -->
                    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                        <p style="margin: 0;">This form is prescribed by Commission on Audit (COA)</p>
                        <p style="margin: 0;">Requisition and Issue Slip (RIS) - Government Property Management</p>
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
    
    <!-- RIS Entries Tab -->
    <div class="tab-pane fade" id="ris-entries" role="tabpanel">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i> RIS Entries
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($form_data)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No RIS entries found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="risTable" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>RIS No.</th>
                                    <th>Division</th>
                                    <th>Responsibility Center</th>
                                    <th>Office</th>
                                    <th>Date</th>
                                    <th>Items Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($form_data as $entry): ?>
                                    <?php
                                    // Get item count for this RIS
                                    $item_count_result = $conn->query("SELECT COUNT(*) as count FROM ris_items WHERE ris_form_id = " . $entry['id']);
                                    $item_count = $item_count_result->fetch_assoc()['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($entry['ris_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($entry['division']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['responsibility_center']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['office_name'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($entry['date'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo $item_count; ?> items</span></td>
                                        <td>
                                            <div class="form-actions">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewRIS(<?php echo $entry['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="manageRISItems(<?php echo $entry['id']; ?>)">
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
