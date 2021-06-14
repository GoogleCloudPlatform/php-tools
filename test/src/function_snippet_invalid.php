<?php

namespace Google\Cloud\TestUtils;

function function_with_inconsistent_name()
{
    print('The function name is not consistent with the filename!');
}

call_user_func_array(__NAMESPACE__ . '\function_with_inconsistent_name', array_slice($argv, 1));
