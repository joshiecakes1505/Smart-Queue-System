<?php

return [
    /*
     * How long a queue stays in "skipped" status before the scheduler
     * automatically returns it to the waiting line.
     */
    'reinstatement_delay_minutes' => env('QUEUE_REINSTATEMENT_DELAY_MINUTES', 5),

    /*
     * Maximum number of times a queue may be automatically reinstated
     * after being skipped. The next skip past this limit expires it.
     */
    'max_reinstatements' => env('QUEUE_MAX_REINSTATEMENTS', 2),
];
