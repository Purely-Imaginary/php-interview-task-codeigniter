<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    public array $coaster = [
        'create' => [
            'personnel_count'       => 'required|integer|greater_than[0]',
            'daily_clients'         => 'required|integer|greater_than[0]',
            'track_length_meters'   => 'required|integer|greater_than[0]',
            'operating_hours_start' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            'operating_hours_end'   => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
        ],
        'update' => [
            'personnel_count'       => 'required|integer|greater_than[0]',
            'daily_clients'         => 'required|integer|greater_than[0]',
            'operating_hours_start' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            'operating_hours_end'   => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
        ],
    ];

    public array $wagon = [
        'create' => [
            'seat_count' => 'required|integer|greater_than[0]',
            'speed_mps'  => 'required|numeric|greater_than[0]',
        ],
    ];
}
