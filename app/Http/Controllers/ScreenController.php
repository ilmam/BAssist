<?php

namespace App\Http\Controllers;

/**
 * Screen details page: metadata, the Salt mockup and the element rows
 * (docs/design-layer.md). Create/edit use the shared CRUD forms.
 */
class ScreenController extends CrudController
{
    public function show($id)
    {
        $screen = $this->modelRepository->findModel($id, ['functionalRequirements', 'screenElements.functionalRequirement']);

        return view(model_page_view($this->modelName, 'details'), $this->detailsViewData($id, [
            'screen' => $screen,
        ]));
    }

    public function modalView($id)
    {
        return redirect()->route('screens.show', $id);
    }
}
