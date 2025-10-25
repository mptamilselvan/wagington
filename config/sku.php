<?php

return [
    // Product type short codes
    'product_type_codes' => [
        'regular' => 'SP',
        'variant' => 'VP',
        'addon'   => 'AP',
    ],

    // Cart reservation window in minutes (0 disables reservation/expiry)
    'cart_reserve_minutes' => (int) env('CART_RESERVE_MINUTES', 0),

    // Category short codes (by slug, fallback to first letters if missing)
    'category_codes' => [
        'food' => 'FD',
        'toys' => 'TO',
        'clothing' => 'CL',
        'grooming' => 'GR',
        'accessories' => 'AC',
    ],

    // Include first N letters of product name before the number
    'include_name_prefix' => true,
    'name_prefix_length'  => 2,

    // Variant attribute mapping (extend as needed)
    'variant_attribute_codes' => [
        'color' => [
            'BLACK' => 'BLK', 'WHITE' => 'WHT', 'RED' => 'RD', 'BLUE' => 'BL', 'GREEN' => 'GRN',
            'YELLOW' => 'YLW', 'ORANGE' => 'ORG', 'PURPLE' => 'PUR', 'PINK' => 'PNK', 'BROWN' => 'BRN',
            'GRAY' => 'GRY', 'GREY' => 'GRY',
        ],
        'size' => [
            'EXTRA SMALL' => 'XS', 'SMALL' => 'S', 'MEDIUM' => 'M', 'LARGE' => 'L', 'EXTRA LARGE' => 'XL',
            'XXL' => 'XXL', 'XXXL' => 'XXXL',
        ],
        // 'material' will fallback to first 3 letters if not explicitly mapped
    ],
];