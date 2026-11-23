<?php

declare(strict_types=1);

/**
 * Nigerian National Numbering Plan - Mobile Prefix Allocation Table
 *
 * Source: Nigerian Communications Commission (NCC) National Numbering Plan
 * Last verified: November 2024 / Q4 2024 NCC Allocation Updates
 *
 * Note: These prefixes indicate the *originally allocated donor network*.
 * Due to Mobile Number Portability (MNP), the actual serving network may differ
 * if the subscriber has ported their line.
 */

return [
    'metadata' => [
        'source' => 'Nigerian Communications Commission (NCC) National Numbering Plan',
        'country' => 'Nigeria',
        'country_code' => '+234',
        'iso' => 'NG',
        'last_verified' => '2024-11-01',
    ],

    // Mappings: Prefix (National format with leading zero) => Network Identifier
    'prefixes' => [
        // ==========================================
        // MTN Nigeria
        // ==========================================
        '07025' => 'mtn', // Visafone legacy range migrated to MTN
        '07026' => 'mtn', // Visafone legacy range migrated to MTN
        '0703' => 'mtn',
        '0704' => 'mtn', // Formerly Visafone CDMA, migrated to MTN
        '0706' => 'mtn',
        '0707' => 'mtn',
        '0803' => 'mtn',
        '0806' => 'mtn',
        '0810' => 'mtn',
        '0813' => 'mtn',
        '0814' => 'mtn',
        '0816' => 'mtn',
        '0903' => 'mtn',
        '0906' => 'mtn',
        '0913' => 'mtn',
        '0916' => 'mtn',

        // ==========================================
        // Airtel Nigeria (formerly Celtel / Vmobile / Econet)
        // ==========================================
        '0701' => 'airtel',
        '0708' => 'airtel',
        '0802' => 'airtel',
        '0808' => 'airtel',
        '0812' => 'airtel',
        '0901' => 'airtel',
        '0902' => 'airtel',
        '0904' => 'airtel',
        '0907' => 'airtel',
        '0911' => 'airtel',
        '0912' => 'airtel',

        // ==========================================
        // Globacom (Glo)
        // ==========================================
        '0705' => 'glo',
        '0805' => 'glo',
        '0807' => 'glo',
        '0811' => 'glo',
        '0815' => 'glo',
        '0905' => 'glo',
        '0915' => 'glo',

        // ==========================================
        // 9mobile (formerly Etisalat Nigeria)
        // ==========================================
        '0809' => '9mobile',
        '0817' => '9mobile',
        '0818' => '9mobile',
        '0908' => '9mobile',
        '0909' => '9mobile',
    ],
];
