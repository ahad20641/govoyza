<?php

namespace App\Console\Concerns;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\Question;

/**
 * Asks for a new password twice without ever showing it.
 *
 * Laravel's secret() falls back to *visible* input when it cannot turn terminal
 * echo off. Hostinger disables shell_exec(), so PHP can never run `stty` there and
 * secret() echoes the password. We turn that fallback off and refuse instead; on
 * such servers the password comes in through --password-stdin, with the shell's
 * `read -s` doing the hiding (see deploy/hostinger.md, "Staff passwords").
 *
 * The using command must declare a `{--password-stdin}` option.
 */
trait PromptsForNewPassword
{
    /** Where --password-stdin reads from. Tests point this at a file. */
    public static string $stdin = 'php://stdin';

    /** Returns the password, or null after printing why there is none. */
    protected function newPassword(): ?string
    {
        if ($this->option('password-stdin')) {
            $handle = fopen(static::$stdin, 'r');
            $lines = preg_split('/\r?\n/', (string) stream_get_contents($handle));
            fclose($handle);
            [$password, $confirmation] = [$lines[0] ?? '', $lines[1] ?? ''];
        } else {
            try {
                $password = $this->askHidden('Password (hidden)');
                $confirmation = $this->askHidden('Password again');
            } catch (RuntimeException) {
                $this->error('This terminal cannot hide what you type, so no password was asked for.');
                $this->line('Use --password-stdin instead. See deploy/hostinger.md, "Staff passwords".');

                return null;
            }
        }

        if ($password === '') {
            $this->error('No password was given.');

            return null;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->error('The two passwords do not match.');

            return null;
        }

        return $password;
    }

    private function askHidden(string $label): string
    {
        return (string) $this->output->askQuestion(static::hiddenQuestion($label));
    }

    /** Hidden, and never falls back to showing the input. */
    public static function hiddenQuestion(string $label): Question
    {
        return (new Question($label))->setHidden(true)->setHiddenFallback(false);
    }
}
