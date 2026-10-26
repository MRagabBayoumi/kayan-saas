<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Centralized Python Print Engine Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration drives the centralized Python printing engine
    | located in scripts/printing/print_engine.py.
    |
    */

    'enabled' => (bool) env('PRINTING_ENGINE_ENABLED', true),

    'python_path' => env('PRINTING_PYTHON_PATH', 'C:\\Users\\PC\\Desktop\\اعلانات\\barcode_printer\\.venv\\Scripts\\python.exe'),

    'engine_script' => env('PRINTING_ENGINE_SCRIPT', base_path('scripts/printing/print_engine.py')),

    'timeout' => (int) env('PRINTING_TIMEOUT', 15),

    'default_printer' => env('PRINTING_DEFAULT_PRINTER', 'Xprinter XP-233B'),

    // Thermal receipt printer path or name (supports UNC network shares e.g. \\192.168.0.41\XP-80C)
    'thermal_printer_path' => env('THERMAL_PRINTER_PATH', env('PRINTING_DEFAULT_RECEIPT_PRINTER', 'XP-80C')),

    'default_receipt_printer' => env('THERMAL_PRINTER_PATH', env('PRINTING_DEFAULT_RECEIPT_PRINTER', 'XP-80C')),

    'default_document_printer' => env('PRINTING_DEFAULT_DOCUMENT_PRINTER', 'TOSHIBA Generic Printer XL'),

    // Optional network share authentication
    'network_user' => env('THERMAL_PRINTER_USER', env('PRINTER_NETWORK_USER', null)),

    'network_password' => env('THERMAL_PRINTER_PASSWORD', env('PRINTER_NETWORK_PASSWORD', null)),

];
