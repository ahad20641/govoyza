<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A GOVOYZA_* key in an env template that config/ never reads is silently
 * ignored on the server and the app falls back to its default. Catch the typo here.
 */
class EnvTemplateTest extends TestCase
{
    private const TEMPLATES = ['deploy/env.production.example', '.env.example'];

    public function test_every_govoyza_key_in_env_templates_is_read_in_config(): void
    {
        $root = dirname(__DIR__, 2);
        $config = implode("\n", array_map('file_get_contents', glob($root.'/config/*.php')));

        $checked = 0;
        foreach (self::TEMPLATES as $template) {
            preg_match_all('/^(GOVOYZA_[A-Z0-9_]+)=/m', file_get_contents($root.'/'.$template), $m);

            foreach ($m[1] as $key) {
                $checked++;
                $this->assertMatchesRegularExpression(
                    "/env\(\s*'{$key}'/",
                    $config,
                    "{$template} sets {$key}, but nothing in config/ reads env('{$key}').",
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'No GOVOYZA_* keys found; did the template move?');
    }
}
