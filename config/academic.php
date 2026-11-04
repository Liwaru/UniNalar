<?php

return [
    'krs' => [
        'maximum_sks' => 24,
    ],

    'grade_points' => [
        'A' => 4.00,
        'AB' => 3.50,
        'B' => 3.00,
        'BC' => 2.50,
        'C' => 2.00,
        'D' => 1.00,
        'E' => 0.00,
    ],

    'attendance' => [
        'status_aliases' => [
            'hadir' => ['hadir'],
            'terlambat' => ['terlambat'],
            'izin' => ['izin'],
            'alpha' => ['alpha', 'alpa'],
        ],
        'attended_statuses' => ['hadir', 'terlambat'],
        'minimum_valid_records_for_percentage' => 3,
    ],

    'risk' => [
        'thresholds' => [
            'needs_attention' => ['ips_below' => 2.00, 'attendance_below' => 75.00],
            'monitor' => ['ips_below' => 2.50, 'attendance_below' => 85.00],
            'safe' => ['ips_at_least' => 2.50, 'attendance_at_least' => 85.00, 'no_failed_grades' => true],
        ],
    ],

    'follow_up_statuses' => ['menunggu', 'dalam_proses', 'selesai'],
];
