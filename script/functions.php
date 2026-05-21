<?php
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<pre style="background: #222; color: #0f0; padding: 10px; border-radius: 5px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}