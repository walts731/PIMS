<?php
// IIRUP Form Include for form_details.php
?>

<!-- IIRUP Form Management -->
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-info text-white rounded-top-4">
                <h6 class="mb-0">
                    <i class="bi bi-eye"></i> IIRUP Form Preview
                </h6>
            </div>
            <div class="card-body">
                <div class="iirup-preview-container" style="font-family: Arial, sans-serif; font-size: 11px; color: #000; background: white; border: 2px solid #000; position: relative;">
                    <!-- Header Section -->
                    <div style="padding: 20px 20px 10px; text-align: center; position: relative;">
                        <div style="margin-bottom: 15px;">
                            <?php if (!empty($header_image)): ?>
                                <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Header Image" style="max-height: 70px;">
                            <?php else: ?>
                                <img src="../img/system_logo.png" alt="Logo" style="max-height: 70px;">
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 9px; margin-bottom: 2px;">Republic of the Philippines</div>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">MUNICIPALITY OF PILAR</div>
                        <div style="font-size: 9px; margin-bottom: 8px;">Province of Sorsogon</div>
                        <h2 style="font-size: 16px; font-weight: bold; margin: 0 0 3px; text-transform: uppercase;">INVENTORY AND INSPECTION REPORT<br>FOR UNSERVICEABLE PROPERTY</h2>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 2px;">Annex A.5</div>
                    </div>
                            </div>
                            <div style="flex: 0 0 auto;">
                                <p style="margin: 0; font-size: 10px;">Annex C</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metadata Section -->
                    <div style="padding: 10px 20px; display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 120px;">Accountable Officer:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 120px;">Department/Office:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center;">
                                <span style="min-width: 120px;">Designation:</span>
                                <span style="border-bottom: 1px solid #000; min-width: 150px; padding: 0 5px;">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table style="width: 100%; border-collapse: collapse; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                        <thead>
                            <tr>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Date Acquired</th>
                                <th width="120" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Particulars</th>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Property No.</th>
                                <th width="50" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Qty</th>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Unit Cost</th>
                                <th width="80" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Total Cost</th>
                                <th width="100" style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center; font-weight: bold;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i=0; $i<15; $i++): ?>
                                <tr>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; padding-left: 8px;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: center;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: right;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; text-align: right;">&nbsp;</td>
                                    <td style="border: 1px solid #000; padding: 4px 6px; font-size: 10px; height: 22px; padding-left: 8px;">&nbsp;</td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    
                    <!-- Appraised Value Section -->
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 50%; padding: 5px; border: 1px solid #000;">
                                    <strong>Appraised Value:</strong> _____________________
                                </td>
                                <td style="width: 50%; padding: 5px; border: 1px solid #000;">
                                    <strong>OR No.:</strong> _____________________
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Request Section -->
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 12px;">I HEREBY request inspection and disposition of the above-mentioned article/s as unserviceable property.</p>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <tr>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">Requested by:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <p style="margin: 5px 0; font-size: 12px;">of Accountable Officer</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation of Accountable Officer</p>
                                </td>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-weight: bold;">Approved by:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <p style="margin: 5px 0; font-size: 12px;">of Authorized Official</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Designation of Authorized Official</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Certification Section -->
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 12px;">I CERTIFY that I have inspected each and every article listed above and found them to be unserviceable and recommended for disposal as indicated.</p>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <tr>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <p style="margin: 5px 0; font-size: 12px;">of Inspection Officer</p>
                                </td>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-size: 12px;">Date:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Witness Certification Section -->
                    <div style="margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 12px;">I CERTIFY that I have witnessed the disposition of the above-mentioned article/s.</p>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <tr>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px;"></div>
                                    <p style="margin: 5px 0; font-size: 12px;">Signature over Printed Name</p>
                                    <p style="margin: 5px 0; font-size: 12px;">of Witness</p>
                                </td>
                                <td style="width: 50%; padding: 10px; text-align: center; vertical-align: top;">
                                    <p style="margin: 0; font-size: 12px;">Date:</p>
                                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 20px;"></div>
                                </td>
                            </tr>
                        </table>
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
