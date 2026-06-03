<?php

return [
    'enabled' => (bool) env('DEPLOY_ENABLED', false),

    'secret' => (string) env('DEPLOY_SECRET', ''),
];
