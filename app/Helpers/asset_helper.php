<?php

if (!function_exists('av')) {
    function av(string $path): string
    {
        $full = FCPATH . $path;
        $v = is_file($full) ? filemtime($full) : time();
        return base_url($path) . '?v=' . $v;
    }
}
