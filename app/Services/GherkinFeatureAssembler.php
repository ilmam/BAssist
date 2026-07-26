<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Scenario;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Assembles Feature + Scenario document bodies into a .feature file.
 * Storage is the source of truth — this concatenates, it does not invent narrative.
 * Traceability FK is Feature.stakeholder_need_id; Feature.body may include @need:{code}
 * (kept in sync on save) so the assembled .feature carries the link once at the top.
 */
class GherkinFeatureAssembler
{
    public function __construct(
        protected GherkinDocumentParser $parser = new GherkinDocumentParser,
    ) {
    }

    /**
     * @return list<string>
     */
    public function parseTags(?string $tags): array
    {
        return $this->parser->parseTags($tags);
    }

    /**
     * @return list<string>
     */
    public function collectTags(?string $tagsField, ?string ...$bodies): array
    {
        $tags = $this->parseTags($tagsField);

        foreach ($bodies as $body) {
            $tags = array_merge($tags, $this->parseTags($body));
        }

        return array_values(array_unique($tags));
    }

    /**
     * Tags visible in the Feature document (from body only).
     *
     * @return list<string>
     */
    public function featureDisplayTags(Feature $feature): array
    {
        return $this->parser->leadingTags($feature->body);
    }

    /**
     * Tags visible in a Scenario document (from body only).
     *
     * @return list<string>
     */
    public function scenarioDisplayTags(Scenario $scenario): array
    {
        return $this->parser->leadingTags($scenario->body);
    }

    /**
     * Full .feature file: feature.body + scenario bodies (no invented tags).
     */
    public function assembleFeature(Feature $feature): string
    {
        $feature->loadMissing([
            'scenarios' => fn ($query) => $query->orderBy('id'),
        ]);

        $parts = [];

        $featureBody = rtrim((string) ($feature->body ?? ''));
        if ($featureBody !== '') {
            $parts[] = $featureBody;
        }

        /** @var Collection<int, Scenario> $scenarios */
        $scenarios = $feature->scenarios;
        foreach ($scenarios as $scenario) {
            $scenarioBody = rtrim((string) ($scenario->body ?? ''));
            if ($scenarioBody !== '') {
                $parts[] = $scenarioBody;
            }
        }

        if ($parts === []) {
            return '';
        }

        return rtrim(implode("\n\n", $parts))."\n";
    }

    /**
     * Scenario document as stored (edit what you see).
     */
    public function assembleScenario(Scenario $scenario): string
    {
        $body = rtrim((string) ($scenario->body ?? ''));

        return $body === '' ? '' : $body."\n";
    }

    public function downloadFilename(Feature $feature): string
    {
        $code = trim((string) ($feature->code ?? ''));
        $base = $code !== ''
            ? $code.'-'.Str::slug((string) $feature->title)
            : Str::slug((string) $feature->title);

        if ($base === '') {
            $base = 'feature-'.$feature->id;
        }

        return $base.'.feature';
    }
}
