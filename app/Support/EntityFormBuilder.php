<?php

namespace App\Support;

use App\Helpers\FormHelper;

/**
 * Builds view-ready form field definitions for an entity's DTO.
 *
 * Centralises the form-building concern (field discovery + populating related
 * dropdown option lists) so that both the generic BaseController and any custom
 * controller can produce identical form fields without duplicating logic.
 */
class EntityFormBuilder
{
    public function __construct(
        private readonly RepositoryResolver $repositories = new RepositoryResolver,
    ) {}

    /**
     * Produce the form fields array consumed by form views.
     *
     * @return array<string, array<string, mixed>>
     */
    public function fields(string $dtoClass, bool $forQuickCreate = false): array
    {
        $metadata = DtoMetadata::for($dtoClass);
        $fields = $forQuickCreate
            ? $metadata->quickCreateVisibleFormFields()
            : $metadata->formFields();

        return FormHelper::getFormFields($this->populateSelectOptions($fields));
    }

    /**
     * @return array<string, mixed>
     */
    public function quickCreateHiddenDefaults(string $dtoClass, ?object $emptyDto = null): array
    {
        return DtoMetadata::for($dtoClass)->quickCreateHiddenDefaults($emptyDto);
    }

    /**
     * Resolve option lists for every `select` field from its related repository
     * or from an App\Support\* class that exposes selectOptions().
     *
     * @param  array<string, array<int|string, mixed>>  $fields
     * @return array<string, array<int|string, mixed>>
     */
    protected function populateSelectOptions(array $fields): array
    {
        foreach ($fields as &$options) {
            $type = $options[0] ?? null;
            $relatedModel = $options[1] ?? null;

            if (! in_array($type, ['select', 'kt-select'], true) || empty($relatedModel)) {
                continue;
            }

            $supportClass = 'App\\Support\\'.$relatedModel;
            if (class_exists($supportClass) && method_exists($supportClass, 'selectOptions')) {
                $options['list'] = $supportClass::selectOptions();
                continue;
            }

            $options['list'] = $this->repositories->for($relatedModel)->getSelectOptions();
        }

        return $fields;
    }
}
