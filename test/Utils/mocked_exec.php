<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Google\Cloud\Utils;

// Provide a fake version of `exec` in the `Google\Cloud\Utils` namespace.

$_commands = [];
$_output = [];
$_ret = [];
$_result = [];

function exec($command, &$output, &$ret)
{
    global $_commands, $_output, $_ret, $_result;
    // Store the command
    $_commands[] = $command;
    $output = [array_shift($_output)];
    $ret = array_shift($_ret);
    return array_shift($_result);
}

function mockExecInit($commands, $output, $ret, $result)
{
    global $_commands, $_output, $_ret, $_result;
    $_commands = $commands;
    $_output = $output;
    $_ret = $ret;
    $_result = $result;
}
