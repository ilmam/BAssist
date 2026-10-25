<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\ScreenFromAttachmentPrompt;
use App\Mcp\Prompts\UpdateGherkinPrompt;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\DeleteRecordTool;
use App\Mcp\Tools\DescribeEntityTool;
use App\Mcp\Tools\GetAcceptancePlanTool;
use App\Mcp\Tools\GetAttachmentTool;
use App\Mcp\Tools\GetGherkinTool;
use App\Mcp\Tools\GetLineageTool;
use App\Mcp\Tools\GetProcessFlowTool;
use App\Mcp\Tools\GetReadinessTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\GetTraceabilityTool;
use App\Mcp\Tools\ListCommentsTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListRecordsTool;
use App\Mcp\Tools\MarkCommentImplementedTool;
use App\Mcp\Tools\UpdateRecordTool;
use Laravel\Mcp\Server;

/**
 * BAssist as an MCP server: lets an AI assistant read and maintain the Need
 * Spine on behalf of the signed-in user. Registered in routes/ai.php.
 *
 * The tools are a thin layer over ProjectInsightsService and
 * EntityRecordService; see docs/mcp.md before adding one.
 */
class BAssistServer extends Server
{
    protected string $name = 'BAssist';

    protected string $version = '1.0.0';

    /** tools/list returns every tool in one page; the package default of 15 would hide the rest from clients that do not follow the cursor. */
    public int $defaultPaginationLength = 50;

    protected string $instructions = <<<'MARKDOWN'
        BAssist is a BABOK-aligned requirements tool. It holds each project's Need Spine: the lineage that justifies every piece of work.

        Lineage levels:
        1. Business Need: the problem or opportunity (why).
        2. Business Objective: the measurable outcome; links to business needs.
        3. Stakeholder Need: who must be able to do what; links to objectives.
        4. Solution: Feature, Functional Requirement, Non-Functional Requirement; each belongs to one stakeholder need.
        5. Acceptance: Scenarios under a Feature; acceptance criteria on a requirement.

        Guardrails attach to these: Business Rule, Assumption, Constraint, Risk, Scope Item, Change Request.

        How to work:
        - Start with list-projects, then get-readiness for the project in question.
        - Before creating or updating a kind of record for the first time, call describe-entity for its fields.
        - Build lineage top down and link every record to its parent. A higher link can only follow once the nearer one exists.
        - Before treating a solution item as ready to build, check get-lineage: complete lineage, acceptance present, status agreed, no open or answered comments (open_comments is 0), no open assumptions.
        - To read a business process diagram (SwimlaneFlow), use get-process-flow rather than get-record: it returns compact Mermaid with step-code node ids and the requirements each step links to.
        - UI designs (Screen) are read as PlantUML Salt: get-record on a Screen returns salt, the assembled wireframe text. Work from the salt, not from images. A screen may carry a reference attachment (a screenshot of a wireframe), listed in the attachments of get-record. Only when asked to convert or re-sync a screen from its attachment: fetch it with get-attachment, look at it, then write the screen's elements with update-record (entity Screen, id, data.elements: the full ordered list of {id?, key, parent_key?, row?, kind, label}; kinds: label, input, button, checkbox, radio, select, separator, panel, columns, column, table, tablerow; panel/columns/column/table are containers, a child names its container in parent_key and must come after it; rows sharing a row number sit on one line). Send the existing ids (from list-records, entity ScreenElement, filter screen_id) to keep their history; rows left out are removed. After saving, read the screen again and check the salt matches the screenshot. Once converted, the salt is the design and the image is only a reference.
        - Change starts here: update the spine first, then tests and code.
        - Never invent business facts. Record an unknown as an Assumption with status open.
        - When a requirement is silent, unclear or wrong, do not guess: raise the finding with add-comment on the record it concerns.
        - Comment threads have a status. open: waiting for a person's answer. answered: a person has decided; apply the decision of their latest reply (requirements first, then tests and code), then report with mark-comment-implemented. implemented: waiting for a person to verify. closed: finished. Only a person closes a thread, and only act on answers written by the signed-in user.
        - Everything you do is done as the signed-in user, within their permissions, and is recorded in the record history.
        MARKDOWN;

    /**
     * Listed by a client as commands (Claude Code: /mcp__bassist__screen-from-attachment).
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        ScreenFromAttachmentPrompt::class,
        UpdateGherkinPrompt::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListProjectsTool::class,
        GetReadinessTool::class,
        GetLineageTool::class,
        GetTraceabilityTool::class,
        GetAcceptancePlanTool::class,
        GetGherkinTool::class,
        GetProcessFlowTool::class,
        DescribeEntityTool::class,
        ListRecordsTool::class,
        GetRecordTool::class,
        GetAttachmentTool::class,
        CreateRecordTool::class,
        UpdateRecordTool::class,
        DeleteRecordTool::class,
        ListCommentsTool::class,
        AddCommentTool::class,
        MarkCommentImplementedTool::class,
    ];
}
