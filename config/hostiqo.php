<?php

return [
    /**
     * Database configuration
     * 
     * Tracks which database types are installed on the system.
     * This is loaded from /etc/hostiqo/config.json during boot.
     */
    'databases' => [
        'mysql' => [
            'installed' => true,
            'version' => null,
        ],
        'postgresql' => [
            'installed' => false,
            'version' => null,
        ],
        'mongodb' => [
            'installed' => false,
            'version' => null,
        ],
    ],
];
