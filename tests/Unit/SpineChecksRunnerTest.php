<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The delivery-check runner (guardrail A5) is plain POSIX sh driven by a `.spine-checks` file,
 * so it is exercised against a throw-away folder.
 */
class SpineChecksRunnerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $probe = new Process(['sh', '-c', 'echo ok']);
        $probe->run();
        if (! $probe->isSuccessful()) {
            $this->markTestSkipped('A POSIX sh is not available.');
        }

        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'spine-checks-'.bin2hex(random_bytes(4));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && is_dir($this->root)) {
            foreach (glob($this->root.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
                is_file($file) && unlink($file);
            }
            rmdir($this->root);
        }

        parent::tearDown();
    }

    public function test_it_passes_when_every_check_for_the_tier_passes(): void
    {
        $this->checks("# comment\nfast: echo one\nfast: echo two\n");

        [$status, $output] = $this->runner('fast');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('all 2 fast check(s) passed', $output);
    }

    public function test_every_check_runs_even_after_a_failure_and_the_run_fails(): void
    {
        $this->checks("fast: exit 3\nfast: echo still-runs\n");

        [$status, $output] = $this->runner('fast');

        $this->assertSame(1, $status);
        $this->assertStringContainsString('FAILED: exit 3', $output);
        $this->assertStringContainsString('still-runs', $output);
        $this->assertStringContainsString('1 of 2 fast check(s) failed', $output);
    }

    public function test_the_fast_tier_skips_full_checks_and_the_full_tier_runs_both(): void
    {
        $this->checks("fast: echo quick\nfull: echo slow\n");

        [, $fast] = $this->runner('fast');
        $this->assertStringContainsString('quick', $fast);
        $this->assertStringNotContainsString('slow', $fast);

        [$status, $full] = $this->runner('full');
        $this->assertSame(0, $status, $full);
        $this->assertStringContainsString('quick', $full);
        $this->assertStringContainsString('slow', $full);
    }

    public function test_a_missing_checks_file_or_an_empty_tier_is_a_setup_error_not_a_pass(): void
    {
        [$status, $output] = $this->runner('fast');
        $this->assertSame(2, $status);
        $this->assertStringContainsString('not found', $output);

        $this->checks("full: echo only-slow\n");
        [$status, $output] = $this->runner('fast');
        $this->assertSame(2, $status);
        $this->assertStringContainsString('no fast checks', $output);
    }

    public function test_an_unknown_tier_is_refused(): void
    {
        $this->checks("fast: echo x\n");

        [$status] = $this->runner('sometimes');

        $this->assertSame(2, $status);
    }

    /** @return array{0: int, 1: string} */
    private function runner(string $tier): array
    {
        $process = new Process(
            ['sh', dirname(__DIR__, 2).'/scripts/spine-checks.sh', $tier],
            $this->root,
            ['SPINE_CHECKS_FILE' => $this->root.'/.spine-checks'],
        );
        $process->run();

        return [$process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
    }

    private function checks(string $content): void
    {
        file_put_contents($this->root.'/.spine-checks', $content);
    }
}
