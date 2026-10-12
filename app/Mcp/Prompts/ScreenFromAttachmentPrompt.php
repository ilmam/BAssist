<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * A client lists this as a command (Claude Code: /mcp__bassist__screen-from-attachment).
 * BAssist cannot read an image itself, so the prompt hands the steps to the
 * assistant; what it saves is ordinary Screen element rows (docs/design-layer.md).
 */
class ScreenFromAttachmentPrompt extends Prompt
{
    protected string $name = 'screen-from-attachment';

    protected string $title = 'Screen from attachment';

    protected string $description = 'Convert the reference attachment (screenshot, PDF or HTML of a wireframe) of a UI design (Screen) into its PlantUML Salt elements, so later work reads the Salt instead of the image.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('project', 'Project name or code, for example "Demo Parts Digital Intake".', required: true),
            new Argument('screen', 'Screen (UI design) title. Leave empty to use the one screen in the project that has an attachment.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $project = trim((string) $request->get('project'));
        $screen = trim((string) $request->get('screen'));

        $find = $screen !== ''
            ? "the UI design (Screen) titled \"{$screen}\""
            : 'the UI design (Screen) that has an attachment. If several do, list them and ask which one';

        return Response::text(<<<TEXT
            Convert a reference attachment into a UI design in BAssist.

            1. Call list-projects and find the project "{$project}".
            2. Call list-records (entity Screen, that project_id) and pick {$find}.
            3. Call get-record (entity Screen) and read its attachments. If it has none, stop and say so.
            4. Call get-attachment for the attachment (for a PDF, for each page you need). Look at it carefully. HTML comes back as cleaned markup: take labels, input types, placeholders, buttons, tables and nesting from the tags; if both an image and an HTML file are attached, use the HTML for the exact text and structure and the image for the layout.
            5. Call list-records (entity ScreenElement, filter screen_id) to see the elements already there.
            6. Call update-record (entity Screen, the screen id) with data.elements: the complete ordered list of {id?, key, parent_key?, row?, kind, label}.
               - kinds: label, input, button, checkbox, radio, select, separator, panel, columns, column, table, tablerow.
               - panel, columns, column and table are containers; a child names its container in parent_key and must come after it.
               - rows sharing a row number sit on one line.
               - keep an existing element's id when its kind and label still match, so its history survives; rows you leave out are removed.
               - write only what is visible in the attachment. Never invent fields; if something is unclear, leave it out and say so.
            7. Call get-record on the screen again and compare its salt with the attachment. Report what you built and anything that does not match or that you were unsure of.

            Do not change the title, status or requirement links unless asked. From now on the salt is the design; the attachment stays as a reference only.
            TEXT);
    }
}
