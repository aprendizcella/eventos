<?php

declare(strict_types=1);

return [
    /*
     * A failed dependency must be visible to load balancers and uptime monitors.
     */
    'json_results_failure_status' => 503,
];
