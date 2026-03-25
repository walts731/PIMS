<script>
    // Category-specific fields configuration
    const categoryFields = <?php echo json_encode($category_fields); ?>;
    
    // Existing desktop computer data for pre-populating fields
    window.existingDesktopData = <?php echo json_encode($desktop_data); ?>;
    
    // Subcategory-specific fields configuration
    const subcategoryFields = <?php echo json_encode($subcategory_fields); ?>;
    
    // Function to load category-specific fields
    function loadCategoryFields(categoryCode) {
        const container = document.getElementById('categorySpecificFields');
        
        if (!categoryCode || !categoryFields[categoryCode]) {
            container.innerHTML = '';
            return;
        }
        
        // Get category name
        const categorySelect = document.getElementById('category_id');
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const categoryName = selectedOption ? selectedOption.textContent.split(' - ').pop().trim() : '';
        
        let fieldsHtml = '<div class="category-fields"><h6 class="mb-3"><i class="bi bi-gear"></i> ' + categoryName + ' Specific Fields</h6><div class="row">';
        
        const fields = categoryFields[categoryCode];
        let fieldCount = 0;
        
        for (const [fieldName, fieldConfig] of Object.entries(fields)) {
            let fieldHtml = '';
            
            if (fieldConfig['type'] === 'select') {
                fieldHtml = '<div class="col-md-6 mb-3">' +
                          '<label for="' + fieldName + '" class="form-label">' + fieldConfig['label'] + (fieldConfig['required'] ? ' <span class="required">*</span>' : '') + '</label>' +
                          '<select class="form-select" id="' + fieldName + '" name="' + fieldName + '" ' + (fieldConfig['required'] ? 'required' : '') + '>' +
                          '<option value="">Select ' + fieldConfig['label'] + '</option>';
                
                if (fieldConfig['options']) {
                    for (const option of fieldConfig['options']) {
                        fieldHtml += '<option value="' + option['value'] + '">' + option['text'] + '</option>';
                    }
                }
                
                fieldHtml += '</select></div>';
            } else {
                fieldHtml = '<div class="col-md-6 mb-3">' +
                          '<label for="' + fieldName + '" class="form-label">' + fieldConfig['label'] + (fieldConfig['required'] ? ' <span class="required">*</span>' : '') + '</label>' +
                          '<input type="' + fieldConfig['type'] + '" class="form-control" id="' + fieldName + '" name="' + fieldName + '" placeholder="Enter ' + fieldConfig['label'] + '" ' + (fieldConfig['required'] ? 'required' : '') + '>' +
                          '</div>';
            }
            
            fieldsHtml += fieldHtml;
            fieldCount++;
        }
        
        fieldsHtml += '</div></div>';
        
        if (fieldCount > 0) {
            container.innerHTML = fieldsHtml;
            
            // Pre-fill category-specific fields if we have existing data
            if (window.existingDesktopData && Object.keys(window.existingDesktopData).length > 0) {
                for (const [fieldName, fieldValue] of Object.entries(window.existingDesktopData)) {
                    const fieldElement = document.getElementById(fieldName);
                    if (fieldElement && fieldValue) {
                        fieldElement.value = fieldValue;
                    }
                }
            }
        } else {
            container.innerHTML = '';
        }
    }
    
    // Function to load subcategory-specific fields
    function loadSubcategoryFields(subcategoryCode) {
        const container = document.getElementById('subcategorySpecificFields');
        
        // Debug logging
        console.log('Loading subcategory fields for code:', subcategoryCode);
        console.log('Available subcategory fields:', subcategoryFields);
        
        if (!subcategoryCode || !subcategoryFields[subcategoryCode]) {
            console.log('No subcategory code or no fields found for:', subcategoryCode);
            container.innerHTML = '';
            return;
        }
        
        let fieldsHtml = '<div class="category-fields"><h6 class="mb-3"><i class="bi bi-gear"></i> Desktop Computer Specific Fields</h6><div class="row">';
        
        const fields = subcategoryFields[subcategoryCode];
        console.log('Fields to render:', fields);
        
        let fieldCount = 0;
        
        for (const [fieldName, fieldConfig] of Object.entries(fields)) {
            let fieldHtml = '';
            
            if (fieldConfig['type'] === 'select') {
                fieldHtml = '<div class="col-md-6 mb-3">' +
                          '<label for="' + fieldName + '" class="form-label">' + fieldConfig['label'] + (fieldConfig['required'] ? ' <span class="required">*</span>' : '') + '</label>' +
                          '<select class="form-select" id="' + fieldName + '" name="' + fieldName + '" ' + (fieldConfig['required'] ? 'required' : '') + '>' +
                          '<option value="">Select ' + fieldConfig['label'] + '</option>';
                
                if (fieldConfig['options']) {
                    for (const option of fieldConfig['options']) {
                        fieldHtml += '<option value="' + option['value'] + '">' + option['text'] + '</option>';
                    }
                }
                
                fieldHtml += '</select></div>';
            } else {
                fieldHtml = '<div class="col-md-6 mb-3">' +
                          '<label for="' + fieldName + '" class="form-label">' + fieldConfig['label'] + (fieldConfig['required'] ? ' <span class="required">*</span>' : '') + '</label>' +
                          '<input type="' + fieldConfig['type'] + '" class="form-control" id="' + fieldName + '" name="' + fieldName + '" placeholder="Enter ' + fieldConfig['label'] + '" ' + (fieldConfig['required'] ? 'required' : '') + '>' +
                          '</div>';
            }
            
            fieldsHtml += fieldHtml;
            fieldCount++;
        }
        
        fieldsHtml += '</div></div>';
        
        if (fieldCount > 0) {
            container.innerHTML = fieldsHtml;
            
            // Pre-fill subcategory-specific fields if we have existing data
            if (window.existingDesktopData && Object.keys(window.existingDesktopData).length > 0) {
                for (const [fieldName, fieldValue] of Object.entries(window.existingDesktopData)) {
                    const fieldElement = document.getElementById(fieldName);
                    if (fieldElement && fieldValue) {
                        fieldElement.value = fieldValue;
                    }
                }
            }
        } else {
            container.innerHTML = '';
        }
    }
</script>
