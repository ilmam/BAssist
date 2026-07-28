<?php

namespace App\Support;

/**
 * Generates blade fragments with explicit Form::field() lines from DTO metadata.
 *
 * Used when ejecting or materializing per-entity form views so hybrid pages
 * follow the same layout as the generic templates but own their field markup.
 */
class EntityFormMaterializer
{
    public function editDtoClass(string $model): string
    {
        return RepositoryResolver::make($model)->editDto;
    }

    public function pageFormBody(string $model): string
    {
        return $this->renderFormBody($model, formId: 'form1', inModal: false);
    }

    public function modalFormBody(string $model): string
    {
        return $this->renderFormBody($model, formId: 'modalForm', inModal: true);
    }

    private function renderFormBody(string $model, string $formId, bool $inModal): string
    {
        $blocks = $this->fieldBlocks($model);

        $bodyClass = $inModal ? '' : 'kt-card-body border-t border-border p-5 lg:p-7.5';
        $footerClass = $inModal
            ? 'flex justify-end gap-2.5 mt-5'
            : 'kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5';

        $openAttributes = $inModal
            ? ", 'attributes' => ['data-modal-form' => 'true']"
            : '';

        $listDeclarations = $blocks['list_declarations'] !== ''
            ? "\n\n{$blocks['list_declarations']}"
            : '';

        $cancelButton = $inModal
            ? '                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>'
            : '                <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>';

        return <<<BLADE
        @php
            use App\Facades\Form;

            \$formRoute = in_array(\$verb, ['POST', 'post'], true)
                ? ['route' => \$route]
                : ['route' => [\$route, \$dto->id]];
            \$formOpenOptions = array_merge(\$formRoute, ['id' => '{$formId}', 'files' => true, 'method' => 'post'{$openAttributes}]);{$listDeclarations}
        @endphp

        {{ Form::open(\$formOpenOptions) }}
            <div class="{$bodyClass}">
                <div class="space-y-6">
                    @if (! in_array(\$verb, ['POST', 'post'], true))
                        @method(\$verb)
                    @endif

                    @if (\$dto->id ?? null)
                        {{ Form::hidden('id', \$dto->id) }}
                    @endif

                    <div class="form-fields">
{$blocks['field_lines']}
                    </div>
                </div>
            </div>
            <div class="{$footerClass}">
{$cancelButton}
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
BLADE;
    }

    /**
     * @return array{list_declarations: string, field_lines: string}
     */
    private function fieldBlocks(string $model): array
    {
        $fields = DtoMetadata::for($this->editDtoClass($model))->formFields();

        $listDeclarations = [];
        $fieldLines = [];

        foreach ($fields as $fieldName => $args) {
            $type = is_array($args) ? (string) ($args[0] ?? 'text') : 'text';
            $relatedModel = is_array($args) ? (string) ($args[1] ?? '') : '';
            $language = is_array($args) ? (string) ($args['language'] ?? '') : '';
            $help = is_array($args) ? (string) ($args['help'] ?? '') : '';

            $listExpression = 'null';

            if (in_array($type, ['select', 'kt-select', 'checkbox', 'radio'], true)) {
                $listVar = '$'.str_replace('_id', 'List', $fieldName);

                if ($relatedModel !== '') {
                    $listDeclarations[] = "            {$listVar} = app(\\App\\Support\\RepositoryResolver::class)->for('{$relatedModel}')->getSelectOptions();";
                } else {
                    $listDeclarations[] = "            {$listVar} = [];";
                }

                $listExpression = $listVar;
            }

            $attributeParts = [];
            if ($type === 'code' && $language !== '') {
                $escapedLanguage = str_replace("'", "\\'", $language);
                $attributeParts[] = "'data-language' => '{$escapedLanguage}'";
            }
            if ($help !== '') {
                $escapedHelp = str_replace("'", "\\'", $help);
                $attributeParts[] = "'data-field-help' => '{$escapedHelp}'";
            }

            $attributesExpression = $attributeParts === []
                ? 'null'
                : '['.implode(', ', $attributeParts).']';

            $fieldLines[] = "                    {{ Form::field('{$type}', '{$fieldName}', \$dto->{$fieldName} ?? null, {$listExpression}, {$attributesExpression}) }}";
        }

        if ($fieldLines === []) {
            $fieldLines[] = '                    {{-- No Form properties found on the edit DTO. --}}';
        }

        return [
            'list_declarations' => implode("\n", $listDeclarations),
            'field_lines' => implode("\n", $fieldLines),
        ];
    }
}
