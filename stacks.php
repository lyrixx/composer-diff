<?php

use PhpWasm\Stack;

return [
    // build/app/ is filled by "castor app:build", see castor.php
    Stack::named('composer-diff')
        // Keep in sync with config.platform.php in composer.json
        ->php('8.5.11')
        ->extensions('ctype', 'filter', 'mbstring', 'tokenizer')
        ->embed('build/app')
        ->targets('web')
        ->memory('256mb'),
];
