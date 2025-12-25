<?php

return function() use ($app) {
    $path = __DIR__ . '/../../../../database/Migrations';

    wp_mkdir_p($path);

    $all = $app->request->all();

    $form = [
        'id'        => $all['id'] ?? null,
        'table'     => trim($all['table'] ?? ''),
        'collation' => $all['collation'] ?? '',
        'fields'    => isset($all['fields']) ? json_decode($all['fields'], true) : [],
        'indexes'   => isset($all['indexes']) ? json_decode($all['indexes'], true) : [],
    ];

    if (empty($form['table'])) {
        return wp_send_json_error(['message' => 'Table name is required.']);
    }

    if (empty($form['fields']) || !is_array($form['fields'])) {
        return wp_send_json_error(['message' => 'No fields provided.']);
    }

    $lines = [];

    /* ----------------------------------------
     * Generate Field Definitions
     * ---------------------------------------- */
    foreach ($form['fields'] as $f) {
        $name          = trim($f['name'] ?? '');
        $type          = strtolower($f['type'] ?? '');
        $length        = $f['length'] ?? '';
        $precision     = $f['precision'] ?? '';
        $scale         = $f['scale'] ?? '';
        $nullable      = !empty($f['nullable']);
        $autoIncrement = !empty($f['autoIncrement']);
        $primary       = !empty($f['primary']);
        $options       = (array)($f['option'] ?? []);
        $default       = $f['default'] ?? '';
        $enumValues    = $f['enumValues'] ?? '';

        if (!$name || !$type) continue;

        $line = "`{$name}` {$type}";

        // Add length or precision
        if ($type === 'decimal' && $precision) {
            $line .= "({$precision}" . ($scale ? ",{$scale}" : '') . ")";
        } elseif (!empty($length) && !in_array($type, ['text', 'json', 'timestamp'])) {
            $line .= "({$length})";
        }

        // Options
        if (in_array('unsigned', $options)) {
            $line .= " UNSIGNED";
        }

        // Nullability
        $line .= $nullable ? " NULL" : " NOT NULL";

        // Default
        if (!empty($default)) {
            if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
                $line .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $line .= " DEFAULT '" . esc_sql($default) . "'";
            }
        }

        // On Update
        if (in_array('on_update_current_timestamp', $options)) {
            $line .= " ON UPDATE CURRENT_TIMESTAMP";
        }

        // Auto Increment
        if ($autoIncrement) {
            $line .= " AUTO_INCREMENT";
        }

        // Primary
        if ($primary) {
            $line .= " PRIMARY KEY";
        }

        // Enum values
        if ($type === 'enum' && !empty($enumValues)) {
            $line .= "({$enumValues})";
        }

        $lines[] = $line;
    }

    /* ----------------------------------------
     * Generate Index Definitions
     * ---------------------------------------- */
    foreach ($form['indexes'] as $idx) {
        $type   = strtoupper(trim($idx['type'] ?? 'INDEX'));
        $name   = trim($idx['name'] ?? '');
        $fields = array_filter($idx['fields'] ?? []);

        if (empty($fields)) continue;

        $fieldList = implode(', ', array_map(fn($f) => "`{$f}`", $fields));

        switch ($type) {
            case 'UNIQUE':
                $lines[] = ($name
                    ? "UNIQUE KEY `{$name}` ({$fieldList})"
                    : "UNIQUE ({$fieldList})");
                break;

            case 'FULLTEXT':
                $lines[] = ($name
                    ? "FULLTEXT KEY `{$name}` ({$fieldList})"
                    : "FULLTEXT ({$fieldList})");
                break;

            case 'SPATIAL':
                $lines[] = ($name
                    ? "SPATIAL KEY `{$name}` ({$fieldList})"
                    : "SPATIAL ({$fieldList})");
                break;

            default:
                $lines[] = ($name
                    ? "INDEX `{$name}` ({$fieldList})"
                    : "INDEX ({$fieldList})");
                break;
        }
    }

    /* ----------------------------------------
     * Combine and Save SQL
     * ---------------------------------------- */
    $tableName = $form['table'];
    $sql = "-- {$tableName}\n" . implode(",\n", $lines) . "\n";
    $file = trailingslashit($path) . "{$tableName}.sql";

    file_put_contents($file, $sql);

    wp_send_json_success([
        'message' => 'Table saved successfully.',
        'sql'     => $sql, // optional for debugging
    ]);
};
