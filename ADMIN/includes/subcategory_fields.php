<?php
// Subcategory-specific fields configuration
$subcategory_fields = [
    // Note: COMPUTER DESKTOP now uses the general peripherals system
    // Peripherals can be added dynamically for any asset type
    'LAPTOP' => [
        // Basic Information
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => false],
        'ram_capacity' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => false],
        'storage_capacity' => ['label' => 'Storage Capacity', 'type' => 'text', 'required' => false],
        'storage_type' => ['label' => 'Storage Type', 'type' => 'select', 'required' => false, 'options' => [
            ['value' => 'ssd', 'text' => 'SSD'],
            ['value' => 'hdd', 'text' => 'HDD'],
            ['value' => 'hybrid', 'text' => 'Hybrid']
        ]],
        'graphics' => ['label' => 'Graphics Card', 'type' => 'text', 'required' => false],
        'operating_system' => ['label' => 'Operating System', 'type' => 'text', 'required' => false],
        'warranty_provider' => ['label' => 'Warranty Provider', 'type' => 'text', 'required' => false],
        'warranty_expiry' => ['label' => 'Warranty Expiry', 'type' => 'date', 'required' => false]
    ],
    'COMPUTER' => [
        // Basic Information
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => false],
        'ram_capacity' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => false],
        'storage_capacity' => ['label' => 'Storage Capacity', 'type' => 'text', 'required' => false],
        'storage_type' => ['label' => 'Storage Type', 'type' => 'select', 'required' => false, 'options' => [
            ['value' => 'ssd', 'text' => 'SSD'],
            ['value' => 'hdd', 'text' => 'HDD'],
            ['value' => 'hybrid', 'text' => 'Hybrid']
        ]],
        'graphics' => ['label' => 'Graphics Card', 'type' => 'text', 'required' => false],
        'operating_system' => ['label' => 'Operating System', 'type' => 'text', 'required' => false],
        'brand' => ['label' => 'Brand', 'type' => 'text', 'required' => false],
        'warranty' => ['label' => 'Warranty Period', 'type' => 'text', 'required' => false],
        
        // Physical Characteristics
        'form_factor' => ['label' => 'Form Factor', 'type' => 'select', 'required' => false, 'options' => [
            ['value' => 'tower', 'text' => 'Tower'],
            ['value' => 'desktop', 'text' => 'Desktop'],
            ['value' => 'all-in-one', 'text' => 'All-in-One'],
            ['value' => 'mini', 'text' => 'Mini PC'],
            ['value' => 'rack', 'text' => 'Rack Mount']
        ]],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        'dimensions' => ['label' => 'Dimensions (LxWxH cm)', 'type' => 'text', 'required' => false],
        'weight' => ['label' => 'Weight (kg)', 'type' => 'text', 'required' => false],
        
        // Power and Cooling
        'power_supply' => ['label' => 'Power Supply (W)', 'type' => 'text', 'required' => false],
        'cooling_system' => ['label' => 'Cooling System', 'type' => 'text', 'required' => false],
        'noise_level' => ['label' => 'Noise Level (dB)', 'type' => 'text', 'required' => false],
        
        // Connectivity
        'wifi' => ['label' => 'WiFi', 'type' => 'text', 'required' => false],
        'bluetooth' => ['label' => 'Bluetooth', 'type' => 'text', 'required' => false],
        'ethernet' => ['label' => 'Ethernet', 'type' => 'text', 'required' => false],
        'ports' => ['label' => 'Ports', 'type' => 'text', 'required' => false],
        
        // Storage
        'hard_drive_bays' => ['label' => 'Hard Drive Bays', 'type' => 'text', 'required' => false],
        'optical_drive' => ['label' => 'Optical Drive', 'type' => 'text', 'required' => false],
        
        // Additional Features
        'speakers' => ['label' => 'Speakers', 'type' => 'text', 'required' => false],
        'microphone' => ['label' => 'Microphone', 'type' => 'text', 'required' => false],
        'card_reader' => ['label' => 'Card Reader', 'type' => 'text', 'required' => false]
    ]
  
];
?>
