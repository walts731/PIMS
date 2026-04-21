<?php
// RIS Form Include for form_details.php
?>

<!-- RIS Form Management -->
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-info text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-eye"></i> RIS Form Preview
                </h6>
            </div>
            <div class="card-body">
                <div class="ris-preview-container" style="font-family: Arial, sans-serif; font-size: 11px; color: #000; background: white; border: 2px solid #000; position: relative;">
                    <!-- Header Section -->
                    <div style="padding: 20px 20px 10px; text-align: center; position: relative;">
                        <div style="margin-bottom: 15px;">
                            <?php if (!empty($header_image)): ?>
                                <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Header Image" style="max-height: 70px;">
                            <?php else: ?>
                                <img src="../img/system_logo.png" alt="Logo" style="max-height: 70px;">
                            <?php endif; ?>
                        </div>
                        <h2 style="font-size: 18px; font-weight: bold; margin: 0 0 3px; text-transform: uppercase;">REQUEST & ISSUE SLIP</h2>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Annex A.2</div>
                    </div>
                    
                    <!-- Metadata Section -->
                    <div style="padding: 10px 20px; display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Division:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Responsibility Center:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">RIS No.:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">SAI No.:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Office:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Code:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 80px;">Date:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 120px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table style="width: 100%; border-collapse: collapse; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                        <thead>
                            <tr>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Stock No.</th>
                                <th width="50" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Unit</th>
                                <th style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Description</th>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Req. Qty</th>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Iss. Qty</th>
                                <th width="100" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Signature</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<20; $i++): ?>
                                <tr>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; padding-left: 8px;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    
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
                    <div style="display: flex; margin-top: 20px;">
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 120px; border-left: none; border-bottom: none;">
                            <div style="font-style: italic; font-weight: bold; font-size: 11px; margin-bottom: 15px;">Requested By:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">&nbsp;</div>
                                <div style="font-size: 9px; margin-bottom: 8px;">Signature over Printed Name</div>
                                <div style="width: 85%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
                                <div style="font-size: 9px; margin-top: 2px;">Date</div>
                            </div>
                        </div>
                        <div style="flex: 1; border: 1px solid #000; padding: 8px 12px 15px; display: flex; flex-direction: column; min-height: 120px; border-right: none; border-bottom: none;">
                            <div style="font-style: italic; font-weight: bold; font-size: 11px; margin-bottom: 15px;">Issued By:</div>
                            <div style="text-align: center; margin-top: auto;">
                                <div style="font-weight: bold; text-transform: uppercase; font-size: 12px; margin-bottom: 2px;">&nbsp;</div>
                                <div style="font-size: 9px; margin-bottom: 8px;">Signature over Printed Name</div>
                                <div style="width: 85%; margin: 0 auto 3px; border-bottom: 1px solid #000;"></div>
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
