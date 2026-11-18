<?php
readonly class Foreign {
    public function __construct(
        public string $label,
        public string $table,
        public string $column,
    ) {}
}

readonly class Subquery {
    public function __construct(
        public string $label,
        public string $sql,
        public ?string $table = null,
        public ?string $sortSql = null,
    ) {}
}

readonly class Pivot {
    public function __construct(
        public string $label,
        public string $pivotTable,
        public string $localKey,
        public string $foreignKey,
        public string $targetTable,
        public string $targetLabel,
        public string $extraField,
        public string $extraLabel,
        public ?string $costColumn = null,
    ) {}
}

function ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function parse_enum(string $type): array {
    return str_getcsv(substr($type, 5, -1), ',', "'", "");
}

function safe_return_url(?string $target, string $fallback): string {
    if ($target !== null && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
        return $target;
    }
    return $fallback;
}

function parse_size_bytes(string $val): int {
    $val  = trim($val);
    $unit = strtolower($val[strlen($val) - 1] ?? '');
    $num  = (int)$val;
    return match ($unit) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => $num,
    };
}

function max_upload_bytes(): int {
    return min(
        parse_size_bytes((string)ini_get('upload_max_filesize')),
        parse_size_bytes((string)ini_get('post_max_size'))
    );
}

function col_bounds(string $sqlType): ?array {
    $unsigned = str_contains(strtolower($sqlType), 'unsigned');

    if (preg_match('/^decimal\((\d+),(\d+)\)/i', $sqlType, $m)) {
        $precision = (int)$m[1];
        $scale     = (int)$m[2];
        $intDigits = max(1, $precision - $scale);
        $maxStr    = str_repeat('9', $intDigits) . ($scale > 0 ? '.' . str_repeat('9', $scale) : '');
        $step      = $scale > 0 ? '0.' . str_repeat('0', $scale - 1) . '1' : '1';
        return [
            'min'  => $unsigned ? '0' : "-$maxStr",
            'max'  => $maxStr,
            'step' => $step,
        ];
    }

    $intLimits = [
        'tinyint'   => [$unsigned ? '0' : '-128', $unsigned ? '255' : '127'],
        'smallint'  => [$unsigned ? '0' : '-32768', $unsigned ? '65535' : '32767'],
        'mediumint' => [$unsigned ? '0' : '-8388608', $unsigned ? '16777215' : '8388607'],
        'int'       => [$unsigned ? '0' : '-2147483648', $unsigned ? '4294967295' : '2147483647'],
        'bigint'    => [$unsigned ? '0' : '-9223372036854775808', $unsigned ? '18446744073709551615' : '9223372036854775807'],
    ];

    foreach ($intLimits as $type => [$min, $max]) {
        if (preg_match("/^$type\b/i", $sqlType)) {
            return ['min' => $min, 'max' => $max, 'step' => '1'];
        }
    }

    return null;
}

function pk_where(string|array $pk, string $rawId): array {
    $cols   = (array)$pk;
    $vals   = explode(':', $rawId, count($cols));
    $where  = [];
    $params = [];

    foreach ($cols as $i => $col) {
        $where[]           = ident($col) . " = :__pk_$i";
        $params["__pk_$i"] = $vals[$i] ?? '';
    }

    return [implode(' AND ', $where), $params];
}

function redirect(string $uri, ?string $error = null): void {
    $sep = str_contains($uri, '?') ? '&' : '?';
    header('Location: ' . $uri . ($error !== null ? $sep . 'error=' . urlencode($error) : ''));
    exit;
}

function handle_uploads(string $table): array {
    $uploaded     = [];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    foreach ($_FILES as $field => $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $maxBytes = max_upload_bytes();
        if (($file['size'] ?? 0) > $maxBytes) {
            $maxMb = round($maxBytes / 1024 / 1024, 1);
            throw new RuntimeException("Розмір фото перевищує дозволений ліміт ({$maxMb} МБ).");
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($mime, $allowedMimes, true) || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new RuntimeException('Дозволені лише зображення (JPG, PNG, WEBP, GIF).');
        }

        $relPath = "uploads/$table/" . uniqid('', true) . ".$ext";
        $absPath = __DIR__ . "/$relPath";
        if (!is_dir(dirname($absPath))) {
            mkdir(dirname($absPath), 0775, true);
        }

        if (move_uploaded_file($file['tmp_name'], $absPath)) {
            $uploaded[$field] = $relPath;
        }
    }
    return $uploaded;
}
