<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The portable scenario-coverage check (guardrail A4) runs on plain folders, so it is
 * exercised here against small fixture trees written to a temporary directory.
 */
class SpineCoverageCheckTest extends TestCase
{
    private string $root;

    private ?string $python = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['python3', 'python'] as $candidate) {
            $probe = new Process([$candidate, '--version']);
            $probe->run();
            if ($probe->isSuccessful()) {
                $this->python = $candidate;
                break;
            }
        }

        if ($this->python === null) {
            $this->markTestSkipped('Python 3 is not available.');
        }

        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'spine-coverage-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/features', 0777, true);
        mkdir($this->root.'/tests', 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && is_dir($this->root)) {
            $this->remove($this->root);
        }

        parent::tearDown();
    }

    public function test_it_passes_when_every_scenario_has_a_test_and_every_test_names_a_scenario(): void
    {
        $this->feature("@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Quote\n  Given a vehicle\n");
        $this->test("// covers SC-1\n");

        [$status, $output] = $this->check();

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('1 of 1 scenarios have a test', $output);
    }

    public function test_an_agreed_scenario_without_a_test_fails_but_a_draft_only_warns(): void
    {
        $this->feature(
            "@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Quote\n  Given a vehicle\n\n".
            "@SC-2 @status:draft @rev:bbbbbbbbbbbb\nScenario: Print\n  Given a quote\n"
        );

        [$status, $output] = $this->check();
        $this->assertSame(1, $status);
        $this->assertStringContainsString('SC-1 "Quote" (agreed) has no test', $output);
        $this->assertStringContainsString('warning:', $output);
        $this->assertStringContainsString('SC-2 "Print" (draft) has no test', $output);

        $this->test('SC-1');
        [$status] = $this->check();
        $this->assertSame(0, $status, 'the draft scenario is only a warning');

        [$status] = $this->check('--strict');
        $this->assertSame(1, $status, 'strict mode requires a test for drafts too');
    }

    public function test_a_deprecated_scenario_needs_no_test(): void
    {
        $this->feature("@SC-1 @status:deprecated @rev:aaaaaaaaaaaa\nScenario: Old\n  Given nothing\n");

        [$status, $output] = $this->check();

        $this->assertSame(0, $status, $output);
    }

    public function test_a_test_naming_an_unknown_scenario_fails(): void
    {
        $this->feature("@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Quote\n  Given a vehicle\n");
        $this->test('SC-1 and SC-9');

        [$status, $output] = $this->check();

        $this->assertSame(1, $status);
        $this->assertStringContainsString('SC-9 is named in', $output);
    }

    public function test_a_scenario_without_a_code_and_a_feature_without_scenarios_fail(): void
    {
        $this->feature("Feature: Quotes\n\n  Scenario: Handwritten\n    Given a vehicle\n", 'a.feature');
        $this->feature("Feature: Empty\n", 'b.feature');

        [$status, $output] = $this->check();

        $this->assertSame(1, $status);
        $this->assertStringContainsString('has no SC code', $output);
        $this->assertStringContainsString('feature "Empty" has no scenario', $output);
    }

    public function test_the_same_code_twice_fails(): void
    {
        $this->feature("@SC-1 @status:agreed\nScenario: One\n  Given a\n\n@SC-1 @status:agreed\nScenario: Two\n  Given b\n");
        $this->test('SC-1');

        [$status, $output] = $this->check();

        $this->assertSame(1, $status);
        $this->assertStringContainsString('SC-1 is also defined at', $output);
    }

    public function test_an_empty_feature_folder_fails_instead_of_passing_silently(): void
    {
        [$status, $output] = $this->check();

        $this->assertSame(1, $status);
        $this->assertStringContainsString('no scenarios found', $output);
    }

    public function test_a_baseline_freezes_known_errors_so_only_new_ones_fail(): void
    {
        $this->feature("@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Old\n  Given a\n");

        [$status] = $this->check('--baseline', 'baseline.txt', '--write-baseline');
        $this->assertSame(0, $status);
        $this->assertFileExists($this->root.'/baseline.txt');

        [$status, $output] = $this->check('--baseline', 'baseline.txt');
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('known:', $output);

        $this->feature("@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Old\n  Given a\n\n@SC-2 @status:agreed @rev:bbbbbbbbbbbb\nScenario: New\n  Given b\n");

        [$status, $output] = $this->check('--baseline', 'baseline.txt');
        $this->assertSame(1, $status);
        $this->assertStringContainsString('SC-2', $output);
    }

    public function test_a_baseline_entry_that_is_fixed_is_reported_so_the_list_only_shrinks(): void
    {
        $this->feature("@SC-1 @status:agreed @rev:aaaaaaaaaaaa\nScenario: Old\n  Given a\n");
        $this->check('--baseline', 'baseline.txt', '--write-baseline');

        $this->test("// covers SC-1\n");

        [$status, $output] = $this->check('--baseline', 'baseline.txt');
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('baseline entry no longer applies', $output);
    }

    /** @return array{0: int, 1: string} */
    private function check(string ...$extra): array
    {
        $process = new Process(
            [$this->python, dirname(__DIR__, 2).'/scripts/spine-coverage-check.py', '--features', 'features', '--tests', 'tests', ...$extra],
            $this->root,
        );
        $process->run();

        return [$process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
    }

    private function feature(string $content, string $name = 'quotes.feature'): void
    {
        file_put_contents($this->root.'/features/'.$name, $content);
    }

    private function test(string $content, string $name = 'QuoteTest.php'): void
    {
        file_put_contents($this->root.'/tests/'.$name, $content);
    }

    private function remove(string $path): void
    {
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path.DIRECTORY_SEPARATOR.$entry;
            is_dir($full) ? $this->remove($full) : unlink($full);
        }
        rmdir($path);
    }
}
