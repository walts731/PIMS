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
                            <tr>
                                <th width="40" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Qty.</th>
                                <th width="50" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Unit</th>
                                <th style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Description</th>
                                <th width="140" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Property Number</th>
                                <th width="100" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Date Acquired</th>
                                <th width="100" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<24; $i++): ?>
                                <tr>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; padding-left: 8px;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: right;">&nbsp;</td>
                                </tr>
                            <?php endfor; ?>
                            

                            <tr style="font-weight: bold;">
                                <td colspan="3" style="border: none;">&nbsp;</td>
                                <td align="center">TOTAL</td>
                                <td style="border-right: none;">&nbsp;</td>
                                <td align="right">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Signature Section -->
                    <div style="display: flex;">
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 150px; border-left: none; border-bottom: none;">
                            <div style="font-style: italic; font-weight: bold; font-size: 11px; margin-bottom: 25px;">Received by:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">&nbsp;</div>
                                <div style="font-size: 9px; margin-bottom: 10px;">Signature over Printed Name</div>
                                

                                <div style="font-weight: bold; margin-top: 10px; text-transform: none;"><?php echo htmlspecialchars($form['first_name'] . ' ' . $form['last_name']); ?></div>
                                <div style="width: 85%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
                                <div style="font-size: 9px; margin-bottom: 10px;">Position / Office</div>
                                

                                <div style="width: 65%; margin: 10px auto 0; border-bottom: 1px solid #000; text-align: center; font-size: 10px; min-height: 15px;">
                                    <?php echo date('m/d/Y'); ?>
                                </div>
                                <div style="font-size: 9px; margin-top: 2px;">Date</div>
                            </div>
                        </div>
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 150px; border-right: none; border-bottom: none;">
                            <div style="font-style: italic; font-weight: bold; font-size: 11px; margin-bottom: 25px;">Issued by:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">&nbsp;</div>
                                <div style="font-size: 9px; margin-bottom: 10px;">Signature over Printed Name</div>
                                

                                <div style="font-weight: bold; margin-top: 10px; text-transform: none;">&nbsp;</div>
                                <div style="width: 85%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
                                <div style="font-size: 9px; margin-bottom: 10px;">Position / Office</div>
                                

                                <div style="width: 65%; margin: 10px auto 0; border-bottom: 1px solid #000; text-align: center; font-size: 10px; min-height: 15px;">
                                    &nbsp;
                                </div>
                                <div style="font-size: 9px; margin-top: 2px;">Date</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Controls -->
                <div class="mt-3 text-center">
                    <button class="btn btn-outline-secondary btn-sm" onclick="zoomPreview()">
                        <i class="bi bi-zoom-in"></i> Zoom
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
