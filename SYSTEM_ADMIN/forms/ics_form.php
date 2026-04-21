<?php
// ICS Form Include for form_details.php
?>

<!-- ICS Form Management -->
<div class="card border-0 shadow-lg rounded-4">
    <div class="card-header bg-info text-white rounded-top-4">
        <h6 class="mb-0">
            <i class="bi bi-eye"></i> ICS Form Preview
        </h6>
    </div>

<div class="card-body">
                <div class="ics-preview-container" style="font-family: Arial, sans-serif; font-size: 11px; color: #000; background: white; border: 2px solid #000; position: relative;">
                    <!-- Header Section -->
                    <div style="padding: 15px 20px 5px; display: flex; align-items: center; position: relative;">
                        <div style="width: 100%; text-align: center; margin-right: 0;">
                            <?php if (!empty($header_image)): ?>
                                <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Header Image" style="max-height: 120px; width: auto; max-width: 100%;">
                            <?php else: ?>
                                <img src="../img/system_logo.png" alt="Logo" style="max-height: 80px;">
                            <?php endif; ?>
                        </div>
                        <div style="position: absolute; right: 15px; top: 15px; font-style: italic; font-size: 11px;">Annex A.3</div>
                    </div>

                    <div style="text-align: center; padding: 5px 0;">
                        <h2 style="font-size: 16px; font-weight: bold; margin: 0; letter-spacing: 1px; text-transform: uppercase;">INVENTORY CUSTODIAN SLIP</h2>
                    </div>

                    <!-- Metadata Section -->
                    <div style="padding: 5px 15px; display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;">
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Entity Name:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Fund Cluster:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">ICS No :</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table style="width: 100%; border-collapse: collapse; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                        <thead>
                            <tr>
                                <th rowspan="2" width="50" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Quantity</th>
                                <th rowspan="2" width="50" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Unit</th>
                                <th colspan="2" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Amount</th>
                                <th rowspan="2" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Description</th>
                                <th rowspan="2" width="70" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Item No.</th>
                                <th rowspan="2" width="90" style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center; font-weight: bold;">Estimated Useful Life</th>
                            </tr>
                            <tr>
                                <th width="70" style="border: 1px solid #000; padding: 3px 6px; font-size: 9px; text-align: center; font-weight: bold;">Unit Cost</th>
                                <th width="80" style="border: 1px solid #000; padding: 3px 6px; font-size: 9px; text-align: center; font-weight: bold;">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<24; $i++): ?>
                                <tr>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: right;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: right;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; padding-left: 8px;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 3px 6px; font-size: 10px; height: 20px; text-align: center;">&nbsp;</td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>

                    <!-- Signature Section -->
                    <div style="display: flex;">
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 140px; border-left: none; border-bottom: none;">
                            <div style="font-weight: bold; font-size: 11px; margin-bottom: 20px;">Received from:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 1px; text-decoration: underline;">&nbsp;</div>
                                <div style="font-size: 10px; margin-bottom: 8px; text-transform: uppercase;">&nbsp;</div>
                                <div style="width: 75%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
                                <div style="font-size: 9px; margin-top: 1px;">Date</div>
                            </div>
                        </div>
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 140px; border-right: none; border-bottom: none;">
                            <div style="font-weight: bold; font-size: 11px; margin-bottom: 20px;">Received by:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 1px; text-decoration: underline;">&nbsp;</div>
                                <div style="font-size: 10px; margin-bottom: 8px; text-transform: uppercase;">&nbsp;</div>
                                <div style="width: 75%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
                                <div style="font-size: 9px; margin-top: 1px;">Date</div>
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
