<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityNumber;
use App\Support\RiskCategory;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskScore;
use App\Support\RiskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class Risk extends BaseModel
{
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'category',
        'likelihood',
        'impact',
        'response',
        'treatment',
        'trigger',
        'owner',
        'status',
        'source',
        'related_to',
    ];

    protected $appends = [
        'score',
        'score_band',
        'score_label',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'RSK';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = RiskStatus::default();
            }
            if (blank($model->category)) {
                $model->category = RiskCategory::default();
            }
            if (blank($model->likelihood)) {
                $model->likelihood = RiskLikelihood::default();
            }
            if (blank($model->impact)) {
                $model->impact = RiskImpact::default();
            }
        });
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function score(): Attribute
    {
        return Attribute::get(fn (): int => RiskScore::calculate(
            (string) $this->likelihood,
            (string) $this->impact,
        ));
    }

    protected function scoreBand(): Attribute
    {
        return Attribute::get(fn (): string => RiskScore::band((int) $this->score));
    }

    protected function scoreLabel(): Attribute
    {
        return Attribute::get(fn (): string => RiskScore::display((int) $this->score));
    }

    public function isCritical(): bool
    {
        return RiskScore::isCritical((int) $this->score);
    }

    public function hasCoverageGap(): bool
    {
        $treatment = trim((string) ($this->treatment ?? ''));
        $response = trim((string) ($this->response ?? ''));

        return $response === RiskResponse::ACCEPT && $treatment === '';
    }

    public function statusLabel(): string
    {
        return RiskStatus::label((string) $this->status);
    }

    public function categoryLabel(): string
    {
        return RiskCategory::label((string) $this->category);
    }

    public function likelihoodLabel(): string
    {
        return RiskLikelihood::label((string) $this->likelihood);
    }

    public function impactLabel(): string
    {
        return RiskImpact::label((string) $this->impact);
    }

    public function responseLabel(): string
    {
        $response = trim((string) ($this->response ?? ''));

        return $response !== '' ? RiskResponse::label($response) : '—';
    }
}
