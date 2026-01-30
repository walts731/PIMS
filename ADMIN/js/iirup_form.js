// IIRUP Form JavaScript
// This file contains all JavaScript functionality for the IIRUP form

// Global variables
let currentRow = null;
var iirupSearchTimeout;
var iirupSearchIndex = -1;

// Utility function to show Bootstrap modal instead of alert
function showModal(title, message, type = 'info') {
    // Create modal container if it doesn't exist
    let modalContainer = document.getElementById('dynamicModal');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'dynamicModal';
        modalContainer.innerHTML = `
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalContainer);
    }
    
    const modalElement = modalContainer.querySelector('.modal');
    const modalTitle = modalElement.querySelector('.modal-title');
    const modalBody = modalElement.querySelector('.modal-body');
    const modalHeader = modalElement.querySelector('.modal-header');
    
    // Set content
    modalTitle.textContent = title;
    modalBody.textContent = message;
    
    // Set styling based on type
    modalHeader.className = 'modal-header';
    if (type === 'error') {
        modalHeader.classList.add('bg-danger', 'text-white');
    } else if (type === 'warning') {
        modalHeader.classList.add('bg-warning');
    } else if (type === 'success') {
        modalHeader.classList.add('bg-success', 'text-white');
    } else {
        modalHeader.classList.add('bg-primary', 'text-white');
    }
    
    // Show modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

// Initialize Bootstrap modals when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        new bootstrap.Modal(modal);
    });
    
    // Initialize autocomplete functionality
    initAutocomplete();
});

function addIIRUPRow() {
    try {
        const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
        if (!table) {
            console.error('Table not found');
            showModal('Error', 'Table not found. Please refresh the page.', 'error');
            return;
        }
        
        const newRow = table.insertRow();
        
        // Get offices for dropdown
        let officeOptions = '<option value="">Select Department/Office</option>';
        const deptOfficeSelect = document.querySelector('select[name="dept_office[]"]');
        if (deptOfficeSelect) {
            for (let i = 0; i < deptOfficeSelect.options.length; i++) {
                const option = deptOfficeSelect.options[i];
                officeOptions += '<option value="' + option.value + '">' + option.textContent + '</option>';
            }
        }
        
        const cells = [
            '<input type="date" class="form-control form-control-sm" name="date_acquired[]">',
            '<div class="autocomplete-container position-relative"><input type="text" class="form-control form-control-sm" name="particulars[]" placeholder="Type to search assets..." autocomplete="off"><div class="autocomplete-dropdown"></div></div>',
            '<input type="text" class="form-control form-control-sm" name="property_no[]">',
            '<input type="number" class="form-control form-control-sm" name="qty[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="accumulated_depreciation[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="impairment_losses[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="carrying_amount[]">',
            '<input type="text" class="form-control form-control-sm" name="inventory_remarks[]" value="unserviceable">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_sale[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_transfer[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_destruction[]">',
            '<input type="text" class="form-control form-control-sm" name="disposal_others[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_total[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="appraised_value[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="total[]">',
            '<input type="text" class="form-control form-control-sm" name="or_no[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="amount[]">',
            '<select class="form-control form-control-sm" name="dept_office[]">' + officeOptions + '</select>',
            '<input type="text" class="form-control form-control-sm" name="control_no[]">',
            '<input type="date" class="form-control form-control-sm" name="date_received[]">',
            '<div class="btn-group btn-group-sm" role="group">' +
                '<button type="button" class="btn btn-sm btn-info" onclick="openFillModal(this)" title="Fill Data">' +
                    '<i class="bi bi-pencil-fill"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-warning" onclick="clearRowData(this)" title="Clear Row">' +
                    '<i class="bi bi-arrow-clockwise"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeIIRUPRow(this)" title="Delete Row">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</div>'
        ];
        
        cells.forEach((cellHtml, index) => {
            const cell = newRow.insertCell(index);
            cell.innerHTML = cellHtml;
        });
    } catch (error) {
        console.error('Error adding row:', error);
        showModal('Error', 'Error adding row. Please try again.', 'error');
    }
}

function clearRowData(button) {
    const row = button.closest('tr');
    const inputs = row.getElementsByTagName('input');
    const selects = row.getElementsByTagName('select');
    
    // Clear all input values and make them editable
    inputs.forEach(input => {
        input.value = '';
        input.readOnly = false;
        input.style.backgroundColor = '';
    });
    
    // Reset select fields and make them editable
    selects.forEach(select => {
        select.value = '';
        select.disabled = false;
        select.style.backgroundColor = '';
    });
    
    // Hide autocomplete dropdown if visible
    const dropdown = row.querySelector('.autocomplete-dropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

function removeIIRUPRow(button) {
    try {
        const row = button.closest('tr');
        const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
        
        if (!table) {
            console.error('Table not found');
            showModal('Error', 'Table not found. Please refresh the page.', 'error');
            return;
        }
        
        if (table.rows.length > 1) {
            row.remove();
        } else {
            showModal('Warning', 'At least one row is required', 'warning');
        }
    } catch (error) {
        console.error('Error removing row:', error);
        showModal('Error', 'Error removing row. Please try again.', 'error');
    }
}

function resetIIRUPForm() {
    try {
        const modalElement = document.getElementById('resetConfirmModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('Reset modal not found');
            showModal('Error', 'Reset modal not found. Please refresh the page.', 'error');
        }
    } catch (error) {
        console.error('Error opening reset modal:', error);
        showModal('Error', 'Error opening reset modal. Please refresh the page.', 'error');
    }
}

function confirmReset() {
    try {
        // Close the modal
        const modalElement = document.getElementById('resetConfirmModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        
        // Reset the form
        const form = document.getElementById('iirupForm');
        if (form) {
            form.reset();
        }
        
        // Clear all read-only states and backgrounds
        const allInputs = document.querySelectorAll('#iirupItemsTable input');
        const allSelects = document.querySelectorAll('#iirupItemsTable select');
        
        allInputs.forEach(input => {
            input.readOnly = false;
            input.style.backgroundColor = '';
        });
        
        allSelects.forEach(select => {
            select.disabled = false;
            select.style.backgroundColor = '';
        });
        
        const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
        if (table) {
            while (table.rows.length > 1) {
                table.deleteRow(1);
            }
        }
    } catch (error) {
        console.error('Error resetting form:', error);
        showModal('Error', 'Error resetting form. Please refresh the page.', 'error');
    }
}

function openFillModal(button) {
    try {
        currentRow = button.closest('tr');
        let modalElement = document.getElementById('fillDataModal');
        
        if (!modalElement) {
            console.error('Fill data modal not found in DOM');
            showModal('Error', 'Fill data modal not found. Please refresh the page.', 'error');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        
        // Reset modal fields to editable state
        const modalInputs = modalElement.querySelectorAll('input');
        const modalSelects = modalElement.querySelectorAll('select');
        
        modalInputs.forEach(input => {
            input.readOnly = false;
            input.style.backgroundColor = '';
            input.value = '';
        });
        
        modalSelects.forEach(select => {
            select.disabled = false;
            select.style.backgroundColor = '';
            select.value = '';
        });
        
        // Get current values from the row
        const inputs = currentRow.getElementsByTagName('input');
        const selects = currentRow.getElementsByTagName('select');
        
        // Populate modal with current values (all 22 fields)
        const modal_date_acquired = document.getElementById('modal_date_acquired');
        const modal_particulars = document.getElementById('modal_particulars');
        const modal_property_no = document.getElementById('modal_property_no');
        const modal_qty = document.getElementById('modal_qty');
        const modal_unit_cost = document.getElementById('modal_unit_cost');
        const modal_total_cost = document.getElementById('modal_total_cost');
        const modal_accumulated_depreciation = document.getElementById('modal_accumulated_depreciation');
        const modal_impairment_losses = document.getElementById('modal_impairment_losses');
        const modal_carrying_amount = document.getElementById('modal_carrying_amount');
        const modal_inventory_remarks = document.getElementById('modal_inventory_remarks');
        const modal_disposal_sale = document.getElementById('modal_disposal_sale');
        const modal_disposal_transfer = document.getElementById('modal_disposal_transfer');
        const modal_disposal_destruction = document.getElementById('modal_disposal_destruction');
        const modal_disposal_others = document.getElementById('modal_disposal_others');
        const modal_disposal_total = document.getElementById('modal_disposal_total');
        const modal_appraised_value = document.getElementById('modal_appraised_value');
        const modal_total = document.getElementById('modal_total');
        const modal_or_no = document.getElementById('modal_or_no');
        const modal_amount = document.getElementById('modal_amount');
        const modal_dept_office = document.getElementById('modal_dept_office');
        const modal_control_no = document.getElementById('modal_control_no');
        const modal_date_received = document.getElementById('modal_date_received');
        
        if (modal_date_acquired) modal_date_acquired.value = inputs[0].value || '';
        if (modal_particulars) modal_particulars.value = inputs[1].value || '';
        if (modal_property_no) modal_property_no.value = inputs[2].value || '';
        if (modal_qty) modal_qty.value = inputs[3].value || '';
        if (modal_unit_cost) modal_unit_cost.value = inputs[4].value || '';
        if (modal_total_cost) modal_total_cost.value = inputs[5].value || '';
        if (modal_accumulated_depreciation) modal_accumulated_depreciation.value = inputs[6].value || '';
        if (modal_impairment_losses) modal_impairment_losses.value = inputs[7].value || '';
        if (modal_carrying_amount) modal_carrying_amount.value = inputs[8].value || '';
        if (modal_inventory_remarks) modal_inventory_remarks.value = inputs[9].value || '';
        if (modal_disposal_sale) modal_disposal_sale.value = inputs[10].value || '';
        if (modal_disposal_transfer) modal_disposal_transfer.value = inputs[11].value || '';
        if (modal_disposal_destruction) modal_disposal_destruction.value = inputs[12].value || '';
        if (modal_disposal_others) modal_disposal_others.value = inputs[13].value || '';
        if (modal_disposal_total) modal_disposal_total.value = inputs[14].value || '';
        if (modal_appraised_value) modal_appraised_value.value = inputs[15].value || '';
        if (modal_total) modal_total.value = inputs[16].value || '';
        if (modal_or_no) modal_or_no.value = inputs[17].value || '';
        if (modal_amount) modal_amount.value = inputs[18].value || '';
        if (modal_dept_office) modal_dept_office.value = selects[0].value || '';
        if (modal_control_no) modal_control_no.value = inputs[19].value || '';
        if (modal_date_received) modal_date_received.value = inputs[20].value || '';
        
        modal.show();
    } catch (error) {
        console.error('Error opening fill modal:', error);
        showModal('Error', 'Error opening fill modal: ' + error.message, 'error');
    }
}

function saveFillData() {
    try {
        if (!currentRow) {
            console.error('No current row selected');
            showModal('Error', 'No row selected for editing.', 'error');
            return;
        }
        
        const inputs = currentRow.getElementsByTagName('input');
        const selects = currentRow.getElementsByTagName('select');
        
        // Get modal elements
        const modal_date_acquired = document.getElementById('modal_date_acquired');
        const modal_particulars = document.getElementById('modal_particulars');
        const modal_property_no = document.getElementById('modal_property_no');
        const modal_qty = document.getElementById('modal_qty');
        const modal_unit_cost = document.getElementById('modal_unit_cost');
        const modal_total_cost = document.getElementById('modal_total_cost');
        const modal_accumulated_depreciation = document.getElementById('modal_accumulated_depreciation');
        const modal_impairment_losses = document.getElementById('modal_impairment_losses');
        const modal_carrying_amount = document.getElementById('modal_carrying_amount');
        const modal_inventory_remarks = document.getElementById('modal_inventory_remarks');
        const modal_disposal_sale = document.getElementById('modal_disposal_sale');
        const modal_disposal_transfer = document.getElementById('modal_disposal_transfer');
        const modal_disposal_destruction = document.getElementById('modal_disposal_destruction');
        const modal_disposal_others = document.getElementById('modal_disposal_others');
        const modal_disposal_total = document.getElementById('modal_disposal_total');
        const modal_appraised_value = document.getElementById('modal_appraised_value');
        const modal_total = document.getElementById('modal_total');
        const modal_or_no = document.getElementById('modal_or_no');
        const modal_amount = document.getElementById('modal_amount');
        const modal_dept_office = document.getElementById('modal_dept_office');
        const modal_control_no = document.getElementById('modal_control_no');
        const modal_date_received = document.getElementById('modal_date_received');
        
        // Save modal values back to the row (all 22 fields)
        if (modal_date_acquired && inputs[0]) inputs[0].value = modal_date_acquired.value;
        if (modal_particulars && inputs[1]) inputs[1].value = modal_particulars.value;
        if (modal_property_no && inputs[2]) inputs[2].value = modal_property_no.value;
        if (modal_qty && inputs[3]) inputs[3].value = modal_qty.value;
        if (modal_unit_cost && inputs[4]) inputs[4].value = modal_unit_cost.value;
        if (modal_total_cost && inputs[5]) inputs[5].value = modal_total_cost.value;
        if (modal_accumulated_depreciation && inputs[6]) inputs[6].value = modal_accumulated_depreciation.value;
        if (modal_impairment_losses && inputs[7]) inputs[7].value = modal_impairment_losses.value;
        if (modal_carrying_amount && inputs[8]) inputs[8].value = modal_carrying_amount.value;
        if (modal_inventory_remarks && inputs[9]) inputs[9].value = modal_inventory_remarks.value;
        if (modal_disposal_sale && inputs[10]) inputs[10].value = modal_disposal_sale.value;
        if (modal_disposal_transfer && inputs[11]) inputs[11].value = modal_disposal_transfer.value;
        if (modal_disposal_destruction && inputs[12]) inputs[12].value = modal_disposal_destruction.value;
        if (modal_disposal_others && inputs[13]) inputs[13].value = modal_disposal_others.value;
        if (modal_disposal_total && inputs[14]) inputs[14].value = modal_disposal_total.value;
        if (modal_appraised_value && inputs[15]) inputs[15].value = modal_appraised_value.value;
        if (modal_total && inputs[16]) inputs[16].value = modal_total.value;
        if (modal_or_no && inputs[17]) inputs[17].value = modal_or_no.value;
        if (modal_amount && inputs[18]) inputs[18].value = modal_amount.value;
        if (modal_dept_office && selects[0]) selects[0].value = modal_dept_office.value;
        if (modal_control_no && inputs[19]) inputs[19].value = modal_control_no.value;
        if (modal_date_received && inputs[20]) inputs[20].value = modal_date_received.value;
        
        // Close modal
        const modalElement = document.getElementById('fillDataModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        
        // Clear current row reference
        currentRow = null;
    } catch (error) {
        console.error('Error saving fill data:', error);
        showModal('Error', 'Error saving data. Please try again.', 'error');
    }
}

function initAutocomplete() {
    // Add event listeners to all particulars inputs (both table and modal)
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name="particulars[]"]') || e.target.matches('#modal_particulars')) {
            const input = e.target;
            const container = input.closest('.autocomplete-container');
            const dropdown = container.querySelector('.autocomplete-dropdown');
            
            clearTimeout(iirupSearchTimeout);
            iirupSearchTimeout = setTimeout(() => {
                searchAssets(input.value, dropdown, input);
            }, 150);
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.target.matches('input[name="particulars[]"]') || e.target.matches('#modal_particulars')) {
            const container = e.target.closest('.autocomplete-container');
            const dropdown = container.querySelector('.autocomplete-dropdown');
            const items = dropdown.querySelectorAll('.autocomplete-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                iirupSearchIndex = Math.min(iirupSearchIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                iirupSearchIndex = Math.max(iirupSearchIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (iirupSearchIndex >= 0 && items[iirupSearchIndex]) {
                    items[iirupSearchIndex].click();
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                iirupSearchIndex = -1;
            }
        }
    });
}

function searchAssets(query, dropdown, input) {
    if (query.length < 1) {
        dropdown.style.display = 'none';
        return;
    }
    
    console.log('Searching for:', query);
    fetch('../api/search_assets.php?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            console.log('Search results:', data);
            if (data.success && data.assets.length > 0) {
                displaySearchResults(data.assets, dropdown, input);
            } else {
                dropdown.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error searching assets:', error);
            dropdown.style.display = 'none';
        });
}

function displaySearchResults(assets, dropdown, input) {
    dropdown.innerHTML = '';
    iirupSearchIndex = -1;
    
    assets.forEach((asset, index) => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.innerHTML = `
            <strong>${asset.description}</strong>
            <small>Property No: ${asset.property_no || 'N/A'} | Inventory Tag: ${asset.inventory_tag || 'N/A'} | Value: ₱${parseFloat(asset.value || 0).toFixed(2)} | Status: ${asset.status}</small>
        `;
        
        item.addEventListener('click', function() {
            if (input.id === 'modal_particulars') {
                selectAssetForModal(asset, input);
            } else {
                selectAsset(asset, input);
            }
            dropdown.style.display = 'none';
        });
        
        dropdown.appendChild(item);
    });
    
    dropdown.style.display = 'block';
}

function updateSelection(items) {
    items.forEach((item, index) => {
        if (index === iirupSearchIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

function selectAsset(asset, input) {
    const row = input.closest('tr');
    const inputs = row.getElementsByTagName('input');
    const selects = row.getElementsByTagName('select');
    
    // Fill the form fields with asset data
    // Find the correct input indices (accounting for the autocomplete container)
    let inputIndex = 0;
    for (let i = 0; i < inputs.length; i++) {
        if (inputs[i].name === 'particulars[]') {
            inputs[i].value = asset.description;
            inputIndex = i;
            break;
        }
    }
    
    // Fill property_no field
    const propertyNo = row.querySelector('input[name="property_no[]"]');
    if (propertyNo && asset.property_no) {
        propertyNo.value = asset.property_no;
    }
    
    // Fill other fields with asset data
    const dateAcquired = row.querySelector('input[name="date_acquired[]"]');
    if (dateAcquired && asset.acquisition_date) {
        const dateObj = new Date(asset.acquisition_date);
        if (!isNaN(dateObj.getTime())) {
            dateAcquired.value = dateObj.toISOString().split('T')[0];
        }
    }
    
    const qty = row.querySelector('input[name="qty[]"]');
    if (qty) {
        qty.value = 1;
    }
    
    const unitCost = row.querySelector('input[name="unit_cost[]"]');
    if (unitCost && asset.value) {
        unitCost.value = asset.value;
    }
    
    const totalCost = row.querySelector('input[name="total_cost[]"]');
    if (totalCost && asset.value) {
        totalCost.value = asset.value;
    }
    
    // Set department/office if available
    if (asset.office_name) {
        const deptOffice = row.querySelector('select[name="dept_office[]"]');
        if (deptOffice) {
            // Add option if not exists
            let optionExists = false;
            for (let option of deptOffice.options) {
                if (option.value === asset.office_name) {
                    optionExists = true;
                    break;
                }
            }
            if (!optionExists) {
                const newOption = document.createElement('option');
                newOption.value = asset.office_name;
                newOption.textContent = asset.office_name;
                deptOffice.appendChild(newOption);
            }
            deptOffice.value = asset.office_name;
        }
    }
}

function selectAssetForModal(asset, input) {
    // Fill modal fields with asset data
    const particularsField = document.getElementById('modal_particulars');
    particularsField.value = asset.description;
    
    // Fill other modal fields
    if (asset.property_no) {
        const propertyNoField = document.getElementById('modal_property_no');
        propertyNoField.value = asset.property_no;
    }
    
    if (asset.acquisition_date) {
        const dateField = document.getElementById('modal_date_acquired');
        const dateObj = new Date(asset.acquisition_date);
        if (!isNaN(dateObj.getTime())) {
            dateField.value = dateObj.toISOString().split('T')[0];
        }
    }
    
    const qtyField = document.getElementById('modal_qty');
    qtyField.value = 1;
    
    if (asset.value) {
        const unitCostField = document.getElementById('modal_unit_cost');
        unitCostField.value = asset.value;
        
        const totalCostField = document.getElementById('modal_total_cost');
        totalCostField.value = asset.value;
    }
    
    // Set department/office if available
    if (asset.office_name) {
        const deptOffice = document.getElementById('modal_dept_office');
        if (deptOffice) {
            // Add option if not exists
            let optionExists = false;
            for (let option of deptOffice.options) {
                if (option.value === asset.office_name) {
                    optionExists = true;
                    break;
                }
            }
            if (!optionExists) {
                const newOption = document.createElement('option');
                newOption.value = asset.office_name;
                newOption.textContent = asset.office_name;
                deptOffice.appendChild(newOption);
            }
            deptOffice.value = asset.office_name;
        }
    }
}

function clearParticulars(button) {
    const container = button.closest('.autocomplete-container');
    const input = container.querySelector('input[name="particulars[]"]');
    if (input) {
        input.value = '';
        input.focus();
    }
    
    // Hide autocomplete dropdown if visible
    const dropdown = container.querySelector('.autocomplete-dropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

function clearModalParticulars() {
    const input = document.getElementById('modal_particulars');
    if (input) {
        input.value = '';
        input.focus();
    }
    
    // Hide autocomplete dropdown if visible
    const dropdown = document.querySelector('#fillDataModal .autocomplete-dropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}
