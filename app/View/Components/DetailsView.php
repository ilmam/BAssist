<?php

namespace App\View\Components;

use App\Services\AttachmentService;
use App\Support\AttachableSupport;
use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class DetailsView extends Component
{
    use ResolvesThemeView;

    /** @var list<\App\Models\Attachment> */
    public array $attachmentRecords = [];

    public function __construct(
        public string $model,
        public object $dto,
        public array $fields,
        public int $columns = 1,
    ) {
        if (AttachableSupport::enabled($model) && isset($dto->id) && (int) $dto->id > 0) {
            $this->attachmentRecords = app(AttachmentService::class)->list($model, (int) $dto->id);
        }
    }

    public function render()
    {
        return $this->themeView('details-view');
    }
}
