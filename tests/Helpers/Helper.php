<?php

// Stub helper to satisfy Composer autoload-dev references in some environments.
// This file intentionally left minimal to avoid loading errors in development.

if (!function_exists('tests_helper_loaded')) {
    function tests_helper_loaded(): bool
    {
        return true;
    }
}


