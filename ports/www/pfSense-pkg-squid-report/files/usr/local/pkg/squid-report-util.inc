<?php
/*
 * squid-report-util.inc
 *
 * part of CTECH packages for pfSense(R) software
 * Copyright (c) 2024 CTECH
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once('config.inc');
require_once('util.inc');

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        $needle_len = strlen($needle);
        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, -$needle_len));
    }
}

if (!function_exists('array_get_path')) {
    function array_get_path(array &$arr, string $path, $default = null)
    {
        $vpath = explode('/', $path);
        $el = $arr;
        foreach ($vpath as $key) {
            if (mb_strlen($key) == 0) {
                continue;
            }
            if (is_array($el) && array_key_exists($key, $el)) {
                $el = $el[$key];
            } else {
                return ($default);
            }
        }

        if (($default !== null) && ($el === '')) {
            return ($default);
        }

        return ($el);
    }
}

if (!function_exists('config_get_path')) {
    function config_get_path(string $path, $default = null)
    {
        // safety check in case $path was called with null keys
        if (str_ends_with(trim($path), '/') || str_contains($path, '//')) {
            log_error(gettext('Warning: invalid config path') . " \"{$path}\"");
            return $default;
        }
        global $config;
        return is_array($config) ? array_get_path($config, $path, $default) : $default;
    }
}

if (!function_exists('array_del_path')) {
    function array_del_path(array &$arr, string $path, $default = null)
    {
        $vpath = explode('/', $path);
        $vkey = array_pop($vpath);
        $el =& $arr;
        foreach ($vpath as $key) {
            if (mb_strlen($key) == 0) {
                continue;
            }
            if (is_array($el) && array_key_exists($key, $el)) {
                $el =& $el[$key];
            } else {
                return $default;
            }
        }

        if (!(is_array($el) && array_key_exists($vkey, $el))) {
            return $default;
        }

        $ret = $el[$vkey];
        unset($el[$vkey]);
        return ($ret);
    }
}

if (!function_exists('config_del_path')) {
    function config_del_path(string $path, $default = null)
    {
        // safety check in case $path was called with null keys
        if (str_ends_with(trim($path), '/') || str_contains($path, '//')) {
            log_error(gettext('Warning: invalid config path') . " \"{$path}\"");
            return $default;
        }
        global $config;
        return is_array($config) ? array_del_path($config, $path, $default) : $default;
    }
}
