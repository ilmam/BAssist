<?php

namespace Tests\Fixtures;

use App\Attributes\Form;
use App\Attributes\OneOf;
use App\Data\BaseData;
use App\Support\RiskStatus;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;

/**
 * One DTO using every validation level, as documented in docs/validation.md.
 */
#[OneOf('email', 'phone')] // level 0: exactly one of
class ValidationLevelsData extends BaseData
{
    public function __construct(
        #[Form('text')]
        public string $name = '',                                     // level 0: inferred required, max:255

        #[Form('text'), Rule('regex:/^[A-Z]{2,5}$/')]
        public ?string $code = null,                                  // level 1: extra rule on one field

        #[Form('textarea'), Max(20), Rule(new NoPlaceholderText)]
        public ?string $summary = null,                               // level 1 + 2: attribute + rule class

        #[Form('text')]
        public ?string $email = null,

        #[Form('text')]
        public ?string $phone = null,

        #[Form('text')]
        public ?string $starts_on = null,

        #[Form('text')]
        public ?string $ends_on = null,

        #[Form('select', 'RiskStatus')]
        public string $status = RiskStatus::OPEN,                     // level 0: must be a listed option
    ) {}

    /** Level 3: rules that relate several fields (merged on top of the inferred ones). */
    public static function rules(): array
    {
        return [
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /** Level 4: business check, runs only when every other rule passed. */
    public static function after(Validator $validator, array $input): void
    {
        if (($input['status'] ?? null) === RiskStatus::CLOSED && empty($input['ends_on'])) {
            $validator->errors()->add('status', 'Set an end date before closing.');
        }
    }
}
