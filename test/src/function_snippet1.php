<?php

namespace Google\Cloud\TestUtils;

function function_snippet1()
{
    $args = func_get_args();
    printf(
        'function_snippet1 called with %d parameters (%s)',
        count($args),
        var_export($args, true)
    );
}

call_user_func_array(__NAMESPACE__ . '\function_snippet1', array_slice($argv, 1));
