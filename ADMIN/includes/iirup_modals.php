<?php
// IIRUP Form Modals
// This file contains all modal dialogs for the IIRUP form
?>

<!-- Fill Data Modal -->
<div class="modal fade" id="fillDataModal" tabindex="-1" aria-labelledby="fillDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fillDataModalLabel">
                    <i class="bi bi-pencil-fill"></i> Fill Row Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date Acquired</label>
                        <input type="date" class="form-control" id="modal_date_acquired">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Property No.</label>
                        <input type="text" class="form-control" id="modal_property_no">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Particulars/Articles</label>
                        <div class="autocomplete-container position-relative">
                            <input type="text" class="form-control" id="modal_particulars" placeholder="Type to search assets..." autocomplete="off">
                            <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="right: 2px; top: 2px; padding: 2px 6px; font-size: 10px;" onclick="clearModalParticulars()" title="Clear">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="autocomplete-dropdown"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="modal_qty">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" step="0.01" class="form-control" id="modal_unit_cost">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Total Cost</label>
                        <input type="number" step="0.01" class="form-control" id="modal_total_cost">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Accumulated Depreciation</label>
                        <input type="number" step="0.01" class="form-control" id="modal_accumulated_depreciation">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Impairment Losses</label>
                        <input type="number" step="0.01" class="form-control" id="modal_impairment_losses">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Carrying Amount</label>
                        <input type="number" step="0.01" class="form-control" id="modal_carrying_amount">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Inventory Remarks</label>
                        <input type="text" class="form-control" id="modal_inventory_remarks" value="unserviceable">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Appraised Value</label>
                        <input type="number" step="0.01" class="form-control" id="modal_appraised_value">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Disposal - Sale</label>
                        <input type="number" step="0.01" class="form-control" id="modal_disposal_sale">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Disposal - Transfer</label>
                        <input type="number" step="0.01" class="form-control" id="modal_disposal_transfer">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Disposal - Destruction</label>
                        <input type="number" step="0.01" class="form-control" id="modal_disposal_destruction">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Disposal - Others</label>
                        <input type="text" class="form-control" id="modal_disposal_others">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Disposal Total</label>
                        <input type="number" step="0.01" class="form-control" id="modal_disposal_total">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Total</label>
                        <input type="number" step="0.01" class="form-control" id="modal_total">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">OR No.</label>
                        <input type="text" class="form-control" id="modal_or_no">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="modal_amount">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Department/Office</label>
                        <select class="form-control" id="modal_dept_office">
                            <option value="">Select Department/Office</option>
                            <?php
                            // Fetch offices from database
                            $offices_result = $conn->query("SELECT office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                            if ($offices_result) {
                                while ($office = $offices_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($office['office_name']) . '">' . htmlspecialchars($office['office_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Control No.</label>
                        <input type="text" class="form-control" id="modal_control_no">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date Received</label>
                        <input type="date" class="form-control" id="modal_date_received">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFillData()">
                    <i class="bi bi-check-lg"></i> Save Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetConfirmModalLabel">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Reset Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the form?</p>
                <p class="text-muted">All data will be lost and cannot be recovered.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmReset()">
                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                </button>
            </div>
        </div>
    </div>
</div>
