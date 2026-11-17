<?php

return [
    // Set OPERATING_STATE when deploying the application for another state.
    // The shipped location seeders currently provide Bauchi State data by default.
    'operating_state' => env('OPERATING_STATE', 'Bauchi'),
];
