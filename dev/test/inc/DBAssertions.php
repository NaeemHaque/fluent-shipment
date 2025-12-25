<?php

namespace Dev\Test\Inc;

trait DBAssertions
{
    /**
     * Assert that a table can check a record's state matching the given data.
     */
    protected function assertDatabaseState($shouldExist, $table, $data, $message)
    {
        $exists = $this->plugin
            ->make('db')
            ->table($table)
            ->where($data)
            ->exists();

        if ($shouldExist) {
            $this->assertTrue(
                $exists,
                $message ?: "Failed asserting that a row in [$table] matches [" .
                    http_build_query($data, '', ', ') . "]."
            );
        } else {
            $this->assertFalse(
                $exists,
                $message ?: "Failed asserting that no row in [$table] matches [" .
                    http_build_query($data, '', ', ') . "]."
            );
        }
    }

    /**
     * Assert that a table contains a record matching the given data.
     */
    public function assertDatabaseHas($table, $data, $message = '')
    {
        $this->assertDatabaseState(true, $table, $data, $message);
    }

    /**
     * Assert that a table does not contain a record matching the given data.
     */
    public function assertDatabaseMissing($table, $data, $message = '')
    {
        $this->assertDatabaseState(false, $table, $data, $message);
    }

    /**
     * Assert that a table has the expected number of rows.
     */
    public function assertDatabaseCount($table, $expectedCount, $message = '')
    {
        $count = $this->plugin->make('db')->table($table)->count();

        $this->assertSame(
            $expectedCount,
            $count,
            $message ?: "Expected [$expectedCount] rows in [$table] but found [$count]."
        );
    }

    /**
     * Assert that a table has the expected number of rows matching conditions.
     */
    public function assertDatabaseCountWhere(
        $table,
        $expectedCount,
        $conditions = [],
        $message = ''
    )
    {
        $query = $this->plugin->make('db')->table($table);

        if (!empty($conditions)) {
            $query->where($conditions);
        }

        $count = $query->count();

        $this->assertSame(
            $expectedCount,
            $count,
            $message ?: "Expected [$expectedCount] rows in [$table] matching [" .
                http_build_query($conditions, '', ', ') . "] but found [$count]."
        );
    }

    /**
     * Assert that a soft-deleted record exists (assuming 'deleted_at' column).
     */
    public function assertDatabaseHasSoftDeleted($table, $data, $message = '')
    {
        $query = $this->plugin->make('db')->table($table)
            ->where($data)
            ->whereNotNull('deleted_at');

        $this->assertTrue(
            $query->exists(),
            $message ?: "Failed asserting that a soft deleted row exists in [$table] matching [" .
                http_build_query($data, '', ', ') . "]."
        );
    }

    /**
     * Assert that no soft-deleted record exists.
     */
    public function assertDatabaseDoesntHaveSoftDeleted(
        $table,
        $data,
        $message = ''
    )
    {
        $query = $this->plugin->make('db')->table($table)
            ->where($data)
            ->whereNotNull('deleted_at');

        $this->assertFalse(
            $query->exists(),
            $message ?: "Failed asserting that no soft deleted row exists in [$table] matching [" .
                http_build_query($data, '', ', ') . "]."
        );
    }
}
