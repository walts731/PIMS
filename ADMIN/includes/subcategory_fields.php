<?php
// Subcategory-specific fields configuration
$subcategory_fields = [
    'COMPUTER DESKTOP' => [
        'monitor_name' => ['label' => 'Monitor Name', 'type' => 'text', 'required' => false],
        'monitor_model' => ['label' => 'Monitor Model', 'type' => 'text', 'required' => false],
        'monitor_serial_number' => ['label' => 'Monitor Serial Number', 'type' => 'text', 'required' => false],
        'monitor_status' => ['label' => 'Monitor Status', 'type' => 'select', 'required' => false, 'options' => [
            ['value' => 'serviceable', 'text' => 'Serviceable'],
            ['value' => 'unserviceable', 'text' => 'Unserviceable'],
            ['value' => 'red_tagged', 'text' => 'Red Tagged'],
            ['value' => 'no_tag', 'text' => 'No Tag']
        ]],
        'ups_name' => ['label' => 'UPS Name', 'type' => 'text', 'required' => false],
        'ups_model' => ['label' => 'UPS Model', 'type' => 'text', 'required' => false],
        'ups_serial_number' => ['label' => 'UPS Serial Number', 'type' => 'text', 'required' => false],
        'ups_status' => ['label' => 'UPS Status', 'type' => 'select', 'required' => false, 'options' => [
            ['value' => 'serviceable', 'text' => 'Serviceable'],
            ['value' => 'unserviceable', 'text' => 'Unserviceable'],
            ['value' => 'red_tagged', 'text' => 'Red Tagged'],
            ['value' => 'no_tag', 'text' => 'No Tag']
        ]]
    ],
    'LAPTOP' => [
        // Basic Information
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => true],
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => false],
        'ram' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => false],
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
        'screen_size' => ['label' => 'Screen Size (inches)', 'type' => 'text', 'required' => false],
        'weight' => ['label' => 'Weight (kg)', 'type' => 'text', 'required' => false],
        'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
        
        // Battery and Power
        'battery_type' => ['label' => 'Battery Type', 'type' => 'text', 'required' => false],
        'battery_capacity' => ['label' => 'Battery Capacity (Wh)', 'type' => 'text', 'required' => false],
        'power_adapter' => ['label' => 'Power Adapter', 'type' => 'text', 'required' => false],
        
        // Connectivity
        'wifi' => ['label' => 'WiFi', 'type' => 'text', 'required' => false],
        'bluetooth' => ['label' => 'Bluetooth', 'type' => 'text', 'required' => false],
        'ports' => ['label' => 'Ports', 'type' => 'text', 'required' => false],
        
        // Additional Features
        'webcam' => ['label' => 'Webcam', 'type' => 'text', 'required' => false],
        'speakers' => ['label' => 'Speakers', 'type' => 'text', 'required' => false],
        'microphone' => ['label' => 'Microphone', 'type' => 'text', 'required' => false],
        'fingerprint_reader' => ['label' => 'Fingerprint Reader', 'type' => 'text', 'required' => false],
        'backlit_keyboard' => ['label' => 'Backlit Keyboard', 'type' => 'text', 'required' => false]
    ],
    'COMPUTER' => [
        // Basic Information
        'model' => ['label' => 'Model', 'type' => 'text', 'required' => true],
        'serial_number' => ['label' => 'Serial Number', 'type' => 'text', 'required' => true],
        'processor' => ['label' => 'Processor', 'type' => 'text', 'required' => false],
        'ram' => ['label' => 'RAM (GB)', 'type' => 'text', 'required' => false],
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
