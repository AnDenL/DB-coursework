<?php
function ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function get_str(string $key, string $default = ''): string {
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? $v : $default;
}
