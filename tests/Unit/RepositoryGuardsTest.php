<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Static checks on the repository for rules in CLAUDE.md that code review can miss. */
class RepositoryGuardsTest extends TestCase
{
    /**
     * Inherited migrations that still hold decimal money columns. Each is converted
     * to *_minor BIGINT by its task (1.12, 1.17, 1.23, 1.24); remove it from this
     * list then. Never add to it.
     */
    private const LEGACY_DECIMAL_MIGRATIONS = [
        '2026_11_01_000200_create_party_tables.php',
        '2026_11_01_000300_create_visa_catalog_tables.php',
        '2026_11_01_000400_create_application_tables.php',
        '2026_11_01_000500_create_finance_tables.php',
    ];

    /** Non-money columns that may legitimately be decimals. */
    private const NON_MONEY_DECIMAL = '/^(rate|rating|[a-z_]*_(rate|pct|percent))$/';

    public function test_no_new_migration_adds_a_decimal_or_float_money_column(): void
    {
        $offenders = [];

        foreach (glob(dirname(__DIR__, 2).'/database/migrations/*.php') as $file) {
            if (in_array(basename($file), self::LEGACY_DECIMAL_MIGRATIONS, true)) {
                continue;
            }

            preg_match_all("/->(?:unsigned)?(?:decimal|float|double)\(\s*'([a-z0-9_]+)'/i", file_get_contents($file), $m);

            foreach ($m[1] as $column) {
                if (! preg_match(self::NON_MONEY_DECIMAL, $column)) {
                    $offenders[] = basename($file).": {$column}";
                }
            }
        }

        $this->assertSame([], $offenders, 'Money must be *_minor BIGINT + currency with MoneyCast, never decimal/float.');
    }

    public function test_no_committed_file_contains_a_real_hostinger_username(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = ['app', 'config', 'database', 'deploy', 'memory', 'planning', 'routes', 'resources', 'tests', '.github'];
        $offenders = [];

        foreach ($paths as $path) {
            if (! is_dir("{$root}/{$path}")) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$root}/{$path}", \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                // Hostinger usernames are "u" + 9 digits; docs must use uXXXXXXX.
                if ($file->isFile() && preg_match('/\bu\d{9}\b/', file_get_contents($file->getPathname()))) {
                    $offenders[] = substr($file->getPathname(), strlen($root) + 1);
                }
            }
        }

        foreach (['CLAUDE.md', '.env.example'] as $file) {
            if (is_file("{$root}/{$file}") && preg_match('/\bu\d{9}\b/', file_get_contents("{$root}/{$file}"))) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, 'Real server details belong in GitHub secrets, not in git. Use uXXXXXXX.');
    }
}
