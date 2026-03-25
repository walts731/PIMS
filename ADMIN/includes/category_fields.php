<?php
// Category-specific fields configuration
$category_fields = [
    '07' => [
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'plate_number' => ['label' => 'Plate Number', 'type' => 'text', 'required' => true],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'engine_number' => ['label' => 'Engine Number', 'type' => 'text', 'required' => true],
        'chassis_number' => ['label' => 'Chassis Number', 'type' => 'text', 'required' => true],
        'year_model' => ['label' => 'Year Model', 'type' => 'number', 'required' => false]
    ],
    '05-030' => [
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => true],
        'ram' => ['label' => 'RAM (GB)', 'type' => 'number', 'required' => true],
        'storage_type' => ['label' => 'Storage Type', 'type' => 'select', 'required' => true, 'options' => [
            ['value' => 'ssd', 'text' => 'SSD'],
            ['value' => 'hdd', 'text' => 'HDD'],
            ['value' => 'hybrid', 'text' => 'Hybrid']
        ]],
        'storage_capacity' => ['label' => 'Storage Capacity (GB)', 'type' => 'number', 'required' => true],
        'graphics' => ['label' => 'Graphics Card', 'type' => 'text', 'required' => false],
        'operating_system' => ['label' => 'Operating System', 'type' => 'text', 'required' => true],
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => true],
        'warranty' => ['label' => 'Warranty Period', 'type' => 'text', 'required' => false]
    ],
    '06' => [
        'software_name' => ['label' => 'Software Name', 'type' => 'text', 'required' => true],
        'version' => ['label' => 'Version', 'type' => 'text', 'required' => true],
        'license_key' => ['label' => 'License Key', 'type' => 'text', 'required' => false],
        'expiry_date' => ['label' => 'Expiry Date', 'type' => 'date', 'required' => false]
    ],
    '03' => [
        'lot_number' => ['label' => 'Lot Number', 'type' => 'text', 'required' => true],
        'area_size' => ['label' => 'Area Size (sqm)', 'type' => 'text', 'required' => true],
        'location' => ['label' => 'Location', 'type' => 'text', 'required' => true],
        'tax_declaration' => ['label' => 'Tax Declaration No', 'type' => 'text', 'required' => false]
    ]
];
?>
