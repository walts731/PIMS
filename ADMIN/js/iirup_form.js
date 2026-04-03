// IIRUP Form JavaScript
// This file contains all JavaScript functionality for the IIRUP form

// Global variables
let currentRow = null;

// Session storage key for IIRUP form data
const IIRUP_STORAGE_KEY = 'iirup_form_data';

// Save form data to session storage
function saveFormDataToSession() {
    try {
        const table = document.getElementById('iirupItemsTable');
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        
        const formData = {
            rows: [],
            timestamp: new Date().toISOString()
        };
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowData = {};
            
            // Get all input values
            const inputs = row.getElementsByTagName('input');
            for (let j = 0; j < inputs.length; j++) {
                const input = inputs[j];
                if (input.name) {
                    rowData[input.name] = input.value;
                }
            }
            
            // Get all select values
            const selects = row.getElementsByTagName('select');
            for (let j = 0; j < selects.length; j++) {
                const select = selects[j];
                if (select.name) {
                    rowData[select.name] = select.value;
                }
            }
            
            // Only save row if it has meaningful data
            if (rowData['particulars[]'] || rowData['property_no[]'] || rowData['qty[]']) {
                formData.rows.push(rowData);
            }
        }
        
        sessionStorage.setItem(IIRUP_STORAGE_KEY, JSON.stringify(formData));
        console.log('Form data saved to session storage:', formData);
    } catch (error) {
        console.error('Error saving form data to session:', error);
    }
}

// Load form data from session storage
function loadFormDataFromSession() {
    try {
        const storedData = sessionStorage.getItem(IIRUP_STORAGE_KEY);
        console.log('Loading from session storage. Found data:', storedData ? 'Yes' : 'No');
        
        if (!storedData) return false;
        
        const formData = JSON.parse(storedData);
        console.log('Parsed form data:', formData);
        
        // Check if data is recent (within 24 hours)
        const storedTime = new Date(formData.timestamp);
        const now = new Date();
        const hoursDiff = (now - storedTime) / (1000 * 60 * 60);
        console.log('Data age in hours:', hoursDiff);
        
        if (hoursDiff > 24) {
            console.log('Stored data is too old, clearing session storage');
            sessionStorage.removeItem(IIRUP_STORAGE_KEY);
            return false;
        }
        
        // Restore the form data
        const table = document.getElementById('iirupItemsTable');
        if (!table || !formData.rows || formData.rows.length === 0) return false;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        
        // Clear existing rows except the first one
        while (tbody.rows.length > 1) {
            tbody.deleteRow(1);
        }
        
        // Restore each row of data
        formData.rows.forEach((rowData, index) => {
            if (index === 0) {
                // Fill the first row
                fillRowWithData(tbody.rows[0], rowData);
            } else {
                // Add new row and fill it
                addIIRUPRow();
                const newRow = tbody.rows[tbody.rows.length - 1];
                fillRowWithData(newRow, rowData);
            }
        });
        
        console.log('Form data restored from session storage:', formData);
        return true;
    } catch (error) {
        console.error('Error loading form data from session:', error);
        return false;
    }
}

// Fill a row with stored data
function fillRowWithData(row, rowData) {
    // Fill all input fields
    const inputs = row.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++) {
        const input = inputs[i];
        if (input.name && rowData[input.name] !== undefined) {
            input.value = rowData[input.name];
            // Add visual highlight for filled fields
            if (rowData[input.name]) {
                input.style.backgroundColor = '#e8f5e8';
                input.style.border = '1px solid #28a745';
            }
        }
    }
    
    // Fill all select fields
    const selects = row.getElementsByTagName('select');
    for (let i = 0; i < selects.length; i++) {
        const select = selects[i];
        if (select.name && rowData[select.name] !== undefined) {
            // Add option if not exists
            if (rowData[select.name] && !select.querySelector(`option[value="${rowData[select.name]}"]`)) {
                const newOption = document.createElement('option');
                newOption.value = rowData[select.name];
                newOption.textContent = rowData[select.name];
                select.appendChild(newOption);
            }
            select.value = rowData[select.name];
        }
    }
}

// Clear session storage
function clearIIRUPSessionData() {
    try {
        console.log('Attempting to clear session storage for key:', IIRUP_STORAGE_KEY);
        sessionStorage.removeItem(IIRUP_STORAGE_KEY);
        console.log('IIRUP session data cleared successfully');
    } catch (error) {
        console.error('Error clearing session data:', error);
    }
}

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
    // Wait a bit longer to ensure all content is loaded
    setTimeout(function() {
        // Check if there's a success message indicating form was just saved
        const successAlert = document.querySelector('.alert-success');
        console.log('Success alert found:', successAlert);
        if (successAlert) {
            console.log('Success alert text:', successAlert.textContent);
            console.log('Contains IIRUP Form:', successAlert.textContent.includes('IIRUP Form'));
            console.log('Contains created successfully:', successAlert.textContent.includes('has been created successfully'));
        }
        
        if (successAlert && (successAlert.textContent.includes('IIRUP Form') && successAlert.textContent.includes('has been created successfully'))) {
            // Clear session storage to reset data restoration after successful save
            console.log('Clearing session storage after successful form submission');
            clearIIRUPSessionData();
            console.log('Session data cleared after successful form submission');
        } else {
            console.log('No success message detected, loading from session storage');
        }
        
        // Initialize all modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            new bootstrap.Modal(modal);
        });
        
        // Initialize datalist functionality
        initDatalist();
        
        // Load existing form data from session storage (only if no success message)
        const hasStoredData = loadFormDataFromSession();
        
        // Show notification if data was restored
        if (hasStoredData) {
            const restoreDiv = document.createElement('div');
            restoreDiv.className = 'alert alert-info alert-dismissible fade show';
            restoreDiv.innerHTML = `
                <i class="bi bi-clock-history"></i> 
                <strong>Previous data restored!</strong> Your previous IIRUP form data has been restored.
                <br><small class="text-muted">
                    You can continue adding more assets or modify existing items.
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const pageHeader = document.querySelector('.page-header');
            if (pageHeader) {
                pageHeader.parentNode.insertBefore(restoreDiv, pageHeader.nextSibling);
            }
        }
    }, 500); // 500ms delay to ensure DOM is fully loaded
    
    // Add auto-save functionality
    // Save data when form inputs change
    document.addEventListener('input', function(e) {
        if (e.target.closest('#iirupItemsTable')) {
            saveFormDataToSession();
        }
    });
    
    // Save data when form selects change
    document.addEventListener('change', function(e) {
        if (e.target.closest('#iirupItemsTable')) {
            saveFormDataToSession();
        }
    });
    
    // Save data when rows are added or removed
    const originalAddRow = window.addIIRUPRow;
    window.addIIRUPRow = function() {
        const result = originalAddRow.apply(this, arguments);
        setTimeout(saveFormDataToSession, 100); // Small delay to ensure DOM is updated
        return result;
    };
    
    const originalRemoveRow = window.removeIIRUPRow;
    window.removeIIRUPRow = function() {
        const result = originalRemoveRow.apply(this, arguments);
        setTimeout(saveFormDataToSession, 100); // Small delay to ensure DOM is updated
        return result;
    };
    
    // Clear session data when form is reset
    const resetButton = document.querySelector('button[onclick*="resetIIRUPForm"]');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            clearIIRUPSessionData();
        });
    }
    
    // Clear auto-fill session flag when form is submitted
    const form = document.getElementById('iirupForm');
    if (form) {
        form.addEventListener('submit', function() {
            clearAutoFillSession();
            clearIIRUPSessionData();
        });
    }
    
    // Clear auto-fill session flag when navigating away from the page
    window.addEventListener('beforeunload', function() {
        // Use navigator.sendBeacon for reliable delivery during page unload
        if (navigator.sendBeacon) {
            const data = new FormData();
            data.append('clear_auto_fill', 'true');
            navigator.sendBeacon('../includes/clear_auto_fill_session.php', data);
        }
    });
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
            '<div class="position-relative"><input type="text" class="form-control form-control-sm" name="particulars[]" placeholder="Type to search assets..." list="assetList" autocomplete="off"><button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="right: 2px; top: 2px; padding: 2px 6px; font-size: 10px;" onclick="clearParticulars(this)" title="Clear"><i class="bi bi-x"></i></button></div>',
            '<input type="text" class="form-control form-control-sm" name="property_no[]">',
            '<input type="number" class="form-control form-control-sm" name="qty[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]">',
            '<input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]">',
            '<select class="form-control form-control-sm" name="dept_office[]">' + officeOptions + '</select>',
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
                // Hidden fields for component data
                '<input type="hidden" name="component_type[]" value="">' +
                '<input type="hidden" name="peripheral_name[]" value="">' +
                '<input type="hidden" name="peripheral_model[]" value="">' +
                '<input type="hidden" name="peripheral_serial_number[]" value="">' +
                '<input type="hidden" name="peripheral_status[]" value="">' +
                '<input type="hidden" name="asset_id[]" value="">' +
            '</div>'
        ];
        
        cells.forEach((cellHtml, index) => {
            const cell = newRow.insertCell(index);
            cell.innerHTML = cellHtml;
        });
        
        // Re-initialize autocomplete for the new row
        const newParticularsInput = newRow.querySelector('input[name="particulars[]"]');
        if (newParticularsInput) {
            console.log('New row added, autocomplete initialized');
        }
        
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
        // Clear auto-fill session flag
        clearAutoFillSession();
        
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

// Clear auto-fill session flag
function clearAutoFillSession() {
    try {
        fetch('../includes/clear_auto_fill_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Auto-fill session cleared');
            }
        })
        .catch(error => {
            console.error('Error clearing auto-fill session:', error);
        });
    } catch (error) {
        console.error('Error clearing auto-fill session:', error);
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
        
        // Populate modal with current values (7 fields now: 6 inputs + 1 select)
        const modal_date_acquired = document.getElementById('modal_date_acquired');
        const modal_particulars = document.getElementById('modal_particulars');
        const modal_property_no = document.getElementById('modal_property_no');
        const modal_qty = document.getElementById('modal_qty');
        const modal_unit_cost = document.getElementById('modal_unit_cost');
        const modal_total_cost = document.getElementById('modal_total_cost');
        const modal_dept_office = document.getElementById('modal_dept_office');
        
        // Access the existing input elements (0-5) and select element (0)
        if (modal_date_acquired && inputs[0]) modal_date_acquired.value = inputs[0].value || '';
        if (modal_particulars && inputs[1]) modal_particulars.value = inputs[1].value || '';
        if (modal_property_no && inputs[2]) modal_property_no.value = inputs[2].value || '';
        if (modal_qty && inputs[3]) modal_qty.value = inputs[3].value || '';
        if (modal_unit_cost && inputs[4]) modal_unit_cost.value = inputs[4].value || '';
        if (modal_total_cost && inputs[5]) modal_total_cost.value = inputs[5].value || '';
        if (modal_dept_office && selects[0]) modal_dept_office.value = selects[0].value || '';
        
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
        
        // Get modal elements (7 fields now: 6 inputs + 1 select)
        const modal_date_acquired = document.getElementById('modal_date_acquired');
        const modal_particulars = document.getElementById('modal_particulars');
        const modal_property_no = document.getElementById('modal_property_no');
        const modal_qty = document.getElementById('modal_qty');
        const modal_unit_cost = document.getElementById('modal_unit_cost');
        const modal_total_cost = document.getElementById('modal_total_cost');
        const modal_dept_office = document.getElementById('modal_dept_office');
        
        // Save modal values back to the row (7 fields now: 6 inputs + 1 select)
        if (modal_date_acquired && inputs[0]) inputs[0].value = modal_date_acquired.value;
        if (modal_particulars && inputs[1]) inputs[1].value = modal_particulars.value;
        if (modal_property_no && inputs[2]) inputs[2].value = modal_property_no.value;
        if (modal_qty && inputs[3]) inputs[3].value = modal_qty.value;
        if (modal_unit_cost && inputs[4]) inputs[4].value = modal_unit_cost.value;
        if (modal_total_cost && inputs[5]) inputs[5].value = modal_total_cost.value;
        if (modal_dept_office && selects[0]) selects[0].value = modal_dept_office.value;
        
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

// Initialize datalist functionality
function initDatalist() {
    // Add event listeners to all particulars inputs for datalist selection
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name="particulars[]"]')) {
            const input = e.target;
            
            // Auto-save when user types
            setTimeout(saveFormDataToSession, 500);
            
            // Check if user selected from datalist by comparing with available options
            setTimeout(() => {
                const selectedValue = input.value;
                if (selectedValue) {
                    // Find the matching option in the datalist
                    const datalist = document.getElementById('assetList');
                    const options = datalist.querySelectorAll('option');
                    
                    for (let option of options) {
                        if (option.value === selectedValue) {
                            // Fill the form with asset data
                            fillRowFromDatalist(input, option);
                            break;
                        }
                    }
                }
            }, 100); // Small delay to ensure datalist selection is processed
        }
    });
    
    // Also handle blur event as fallback
    document.addEventListener('blur', function(e) {
        if (e.target.matches('input[name="particulars[]"]')) {
            const input = e.target;
            const selectedValue = input.value;
            
            if (selectedValue) {
                // Find the matching option in the datalist
                const datalist = document.getElementById('assetList');
                const options = datalist.querySelectorAll('option');
                
                for (let option of options) {
                    if (option.value === selectedValue) {
                        // Fill the form with asset data
                        fillRowFromDatalist(input, option);
                        break;
                    }
                }
            }
        }
    }, true); // Use capture to ensure it fires
}

function fillRowFromDatalist(input, option) {
    console.log('Filling row from datalist selection');
    const row = input.closest('tr');
    if (!row) {
        console.error('Could not find parent row');
        return;
    }
    
    // Get asset data from data attributes
    const assetData = {
        id: option.getAttribute('data-id'),
        description: option.value,
        property_no: option.getAttribute('data-property_no'),
        inventory_tag: option.getAttribute('data-inventory_tag'),
        value: option.getAttribute('data-value'),
        acquisition_date: option.getAttribute('data-acquisition_date'),
        office_name: option.getAttribute('data-office_name'),
        model: option.getAttribute('data-model'),
        serial_number: option.getAttribute('data-serial_number'),
        employee_name: option.getAttribute('data-employee_name')
    };
    
    console.log('Asset data:', assetData);
    
    // Fill the form fields with asset data
    fillRowWithAssetData(row, assetData);
    
    // Auto-fill Accountable Officer field if employee is available
    if (assetData.employee_name) {
        const accountableOfficerInput = document.querySelector('input[name="accountable_officer"]');
        if (accountableOfficerInput && !accountableOfficerInput.value) {
            accountableOfficerInput.value = assetData.employee_name;
            accountableOfficerInput.style.backgroundColor = '#e8f5e8';
            accountableOfficerInput.style.border = '1px solid #28a745';
            console.log('Auto-filled accountable officer:', assetData.employee_name);
        }
    }
    
    // Save form data
    setTimeout(saveFormDataToSession, 100);
    console.log('Row filled successfully');
}

function fillRowWithAssetData(row, asset) {
    console.log('Filling row with asset data:', asset);
    const inputs = row.getElementsByTagName('input');
    const selects = row.getElementsByTagName('select');
    
    // Fill the description/particulars field
    const particularsInput = row.querySelector('input[name="particulars[]"]');
    if (particularsInput && asset.description) {
        particularsInput.value = asset.description;
        particularsInput.style.backgroundColor = '#e8f5e8';
        particularsInput.style.border = '1px solid #28a745';
        console.log('Filled description:', asset.description);
    }
    
    // Fill property_no field
    const propertyNo = row.querySelector('input[name="property_no[]"]');
    if (propertyNo && asset.property_no) {
        propertyNo.value = asset.property_no;
        propertyNo.style.backgroundColor = '#e8f5e8';
        propertyNo.style.border = '1px solid #28a745';
        console.log('Filled property number:', asset.property_no);
    }
    
    // Fill acquisition date
    const dateAcquired = row.querySelector('input[name="date_acquired[]"]');
    if (dateAcquired && asset.acquisition_date) {
        const dateObj = new Date(asset.acquisition_date);
        if (!isNaN(dateObj.getTime())) {
            dateAcquired.value = dateObj.toISOString().split('T')[0];
            dateAcquired.style.backgroundColor = '#e8f5e8';
            dateAcquired.style.border = '1px solid #28a745';
            console.log('Filled acquisition date:', dateAcquired.value);
        }
    }
    
    // Fill quantity
    const qty = row.querySelector('input[name="qty[]"]');
    if (qty) {
        qty.value = 1;
        qty.style.backgroundColor = '#e8f5e8';
        qty.style.border = '1px solid #28a745';
        console.log('Filled quantity: 1');
    }
    
    // Fill unit cost
    const unitCost = row.querySelector('input[name="unit_cost[]"]');
    if (unitCost && asset.value) {
        unitCost.value = asset.value;
        unitCost.style.backgroundColor = '#e8f5e8';
        unitCost.style.border = '1px solid #28a745';
        console.log('Filled unit cost:', asset.value);
    }
    
    // Fill total cost
    const totalCost = row.querySelector('input[name="total_cost[]"]');
    if (totalCost && asset.value) {
        totalCost.value = asset.value;
        totalCost.style.backgroundColor = '#e8f5e8';
        totalCost.style.border = '1px solid #28a745';
        console.log('Filled total cost:', asset.value);
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
            deptOffice.style.backgroundColor = '#e8f5e8';
            deptOffice.style.border = '1px solid #28a745';
            console.log('Filled department/office:', asset.office_name);
        }
    }
    
    console.log('Row filled successfully with all fields');
    return true; // Successfully filled
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

function isRowEmpty(row) {
    const inputs = row.getElementsByTagName('input');
    const selects = row.getElementsByTagName('select');
    
    // Check if all meaningful fields are empty
    const particularsInput = row.querySelector('input[name="particulars[]"]');
    const propertyNoInput = row.querySelector('input[name="property_no[]"]');
    const qtyInput = row.querySelector('input[name="qty[]"]');
    
    return (!particularsInput || !particularsInput.value.trim()) && 
           (!propertyNoInput || !propertyNoInput.value.trim()) && 
           (!qtyInput || !qtyInput.value);
}

// Check for duplicate property numbers in the table
function isPropertyNoDuplicate(propertyNo, excludeRow = null) {
    console.log('Checking for duplicate property number:', propertyNo);
    console.log('Excluding row:', excludeRow);
    
    const table = document.getElementById('iirupItemsTable');
    if (!table) {
        console.log('Table not found');
        return false;
    }
    
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = tbody.getElementsByTagName('tr');
    
    console.log('Total rows in table:', rows.length);
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        console.log('Checking row', i, 'against exclude row:', excludeRow === row);
        
        if (excludeRow && rows[i] === excludeRow) {
            console.log('Skipping excluded row', i);
            continue; // Skip the row we're checking
        }
        
        const propertyNoInput = rows[i].querySelector('input[name="property_no[]"]');
        const existingPropertyNo = propertyNoInput ? propertyNoInput.value.trim() : '';
        console.log('Row', i, 'property number:', existingPropertyNo);
        
        if (propertyNoInput && existingPropertyNo === propertyNo.trim()) {
            console.log('Found duplicate property number:', existingPropertyNo);
            return true; // Found duplicate
        }
    }
    
    console.log('No duplicate found for property number:', propertyNo);
    return false; // No duplicate found
}

function clearParticulars(button) {
    const container = button.closest('div');
    const input = container.querySelector('input[name="particulars[]"]');
    if (input) {
        input.value = '';
        input.focus();
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
