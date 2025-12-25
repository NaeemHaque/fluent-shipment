<?php

return function() use ($app) {
    $path = realpath(__DIR__ .'/../../../../database/Migrations');
    $files = glob($path . '/*.sql', GLOB_NOSORT);
    $migrations = [];

    $table = $app->request->table;
    $ns = $app['__namespace__'];
    $schema = $app->make($ns . '\Framework\Database\Schema');

    foreach ($files as $file) {
        $tableName = basename($file, '.sql');
        $sql = file_get_contents($file);
        $isMigrated = $schema->hasTable($tableName);

        // Remove SQL comments
        $sql = preg_replace([
            '/--.*(\r?\n|$)/',          // single-line comments starting with --
            '/#.*(\r?\n|$)/',           // single-line comments starting with #
            '/\/\*[\s\S]*?\*\//'        // multi-line comments /* ... */
        ], '', $sql);

        // Trim and remove trailing semicolon
        $sql = trim(rtrim($sql, ';'));

        $fields = [];
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines, comments, and index/key definitions
            if (!$line 
                || strpos($line, '--') === 0 
                || preg_match('/^(PRIMARY KEY|UNIQUE|INDEX|KEY|FULLTEXT)/i', $line)
            ) {
                continue;
            }

            $line = rtrim($line, ',');

            $parts = preg_split('/\s+/', $line, 2);
            if (!$parts[0]) continue;

            $fieldName = str_replace('`', '', $parts[0]);
            $rest = $parts[1] ?? '';

            preg_match('/^(?P<type>\w+(\([^)]+\))?)/i', $rest, $typeMatch);
            $type = $typeMatch['type'] ?? '';

            $unsigned = preg_match('/unsigned/i', $rest) > 0;
            $zerofill = preg_match('/zerofill/i', $rest) > 0;
            $nullable = !preg_match('/NOT NULL/i', $rest);
            $primary = preg_match('/PRIMARY KEY/i', $rest) > 0;
            if ($primary) $nullable = false;
            $autoIncrement = preg_match('/AUTO_INCREMENT/i', $rest) > 0;
            $unique = preg_match('/UNIQUE/i', $rest) > 0;

            preg_match('/DEFAULT\s+([^\s,]+)/i', $rest, $defaultMatch);
            $default = $defaultMatch[1] ?? null;
            if ($default !== null) {
                $default = trim($default, "'\"");
                if (strtoupper($default) === 'NULL') $default = null;
            }

            preg_match('/ON UPDATE\s+([^\s]+)/i', $rest, $updateMatch);
            $on_update = $updateMatch[1] ?? null;

            $fields[] = [
                'name' => $fieldName,
                'type' => $type,
                'unsigned' => $unsigned,
                'zerofill' => $zerofill,
                'nullable' => $nullable,
                'primary' => $primary,
                'autoIncrement' => $autoIncrement,
                'unique' => $unique,
                'default' => $default,
                'on_update' => $on_update,
            ];
        }

        // Parse indexes / unique / fulltext / spatial keys
        $indexes = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // Match all index types including FULLTEXT & SPATIAL
            if (preg_match('/^(PRIMARY KEY|UNIQUE(?: KEY)?|FULLTEXT(?: KEY)?|SPATIAL(?: KEY)?|INDEX|KEY)\s*(?:`?(\w+)`?)?\s*\(([^)]+)\)/i', $line, $m)) {
                $rawType = strtoupper($m[1]);
                $name = $m[2] ?? null;
                $cols = array_map(fn($c) => trim($c, '` '), explode(',', $m[3]));

                // Normalize the type
                if (str_starts_with($rawType, 'PRIMARY')) $type = 'PRIMARY';
                elseif (str_starts_with($rawType, 'UNIQUE')) $type = 'UNIQUE';
                elseif (str_starts_with($rawType, 'FULLTEXT')) $type = 'FULLTEXT';
                elseif (str_starts_with($rawType, 'SPATIAL')) $type = 'SPATIAL';
                else $type = 'INDEX';

                $indexes[] = [
                    'type' => $type,
                    'name' => $name,
                    'fields' => $cols,
                ];
            }
        }

        // Parse foreign keys
        preg_match_all('/FOREIGN KEY\s*\((`?\w+`?)\)\s*REFERENCES\s*`?(\w+)`?\s*\((`?\w+`?)\)\s*(ON DELETE\s+\w+)?\s*(ON UPDATE\s+\w+)?/i', $sql, $fkMatches, PREG_SET_ORDER);

        $relations = [];
        foreach ($fkMatches as $fk) {
            $relations[] = [
                'field' => str_replace('`', '', $fk[1]),
                'references_table' => $fk[2],
                'references_field' => str_replace('`', '', $fk[3]),
                'on_delete' => isset($fk[4]) ? trim(str_replace('ON DELETE', '', $fk[4])) : null,
                'on_update' => isset($fk[5]) ? trim(str_replace('ON UPDATE', '', $fk[5])) : null,
            ];
        }

        $migrations[] = [
            'table' => $tableName,
            'fields' => $fields,
            'indexes' => $indexes,
            'relations' => $relations,
            'is_migrated' => $isMigrated,
        ];
    }

    wp_send_json_success([
        'migrations' => $migrations
    ]);
};
