<?php

namespace Database\Seeders;

use App\Models\Architecture;
use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\ChangeRequest;
use App\Models\Constraint;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Scenario;
use App\Models\ScopeItem;
use App\Models\Stakeholder;
use App\Models\StakeholderNeed;
use App\Models\StateFlow;
use App\Models\StrategicBaseline;
use App\Models\SwimlaneFlow;
use App\Models\SwimlaneFlowStep;
use App\Models\User;
use App\Services\GherkinDocumentParser;
use App\Services\SystemStakeholderSeeder;
use App\Services\TenancyProvisioner;
use App\Support\AssumptionStatus;
use App\Support\BusinessRuleStatus;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use App\Support\ConstraintStatus;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use App\Support\NfrCategory;
use App\Support\RiskCategory;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskStatus;
use App\Support\ScopeItemDirection;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Seeder;

/**
 * Full demo pack: strategy → need spine → packaging → diagrams → governance.
 *
 * Project code: DEMO-PACK (idempotent via updateOrCreate on stable titles).
 */
class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        User::query()->whereNull('tenant_id')->each(function (User $user) use ($provisioner) {
            $provisioner->provisionFor($user);
        });

        $agreedId = EntityStatus::id(EntityStatus::AGREED);
        $draftId = EntityStatus::id(EntityStatus::DRAFT);
        $mustId = EntityPriority::id(EntityPriority::MUST);
        $shouldId = EntityPriority::id(EntityPriority::SHOULD);
        $couldId = EntityPriority::id(EntityPriority::COULD);

        $project = Project::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'code' => 'DEMO-PACK',
            ],
            [
                'name' => 'Dealer Parts Digital Intake',
                'description' => 'Demo project: digitize dealer parts inquiries so procurement only acts on complete, validated requests.',
                'status_id' => $agreedId,
            ],
        );

        app(SystemStakeholderSeeder::class)->seedForProject($project);

        $this->seedStrategy($project);
        [$bnIntake, $bnComplete] = $this->seedNeedSpine($project, $agreedId, $draftId, $mustId, $shouldId);
        $this->seedScope($project, $bnIntake, $bnComplete);
        $this->seedGovernance($project);
        $steps = $this->seedSwimlane($project, $draftId);
        $this->seedStateFlow($project, $draftId);
        $this->seedArchitecture($project, $draftId);
        $this->seedSolutionPackaging($project, $mustId, $shouldId, $couldId, $agreedId, $draftId, $steps);
    }

    protected function seedStrategy(Project $project): void
    {
        StrategicBaseline::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'current_state' => <<<'TXT'
Dealer field agents submit parts inquiries by email, WhatsApp, or paper forms. Procurement re-keys data into the ERP, often discovering missing part numbers, quantities, or dealer codes after the request has already entered the queue. Cycle time and rework are high; agents have no shared view of inquiry status.
TXT,
                'future_state' => <<<'TXT'
A single digital intake channel collects mandatory fields with inline validation. Incomplete inquiries stay in Draft. Complete inquiries enter a procurement open queue with clear status visibility for agents. The ERP remains the system of record for ordering; intake does not replace inventory or pricing.
TXT,
                'change_strategy' => <<<'TXT'
Pilot with a small set of dealers, enforce a completeness gate before procurement visibility, train field agents on the new form, and keep a temporary email fallback during cutover. Measure incomplete-rate and inquiry-to-queue time weekly.
TXT,
                'status' => StrategicBaselineStatus::APPROVED,
            ],
        );
    }

    /**
     * @return array{0: BusinessNeed, 1: BusinessNeed}
     */
    protected function seedNeedSpine(
        Project $project,
        int $agreedId,
        int $draftId,
        int $mustId,
        int $shouldId,
    ): array {
        $bnIntake = BusinessNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Single digital channel for parts inquiry intake',
            ],
            [
                'need_type' => 'opportunity',
                'description' => 'Replace multi-channel ad-hoc intake with one digital form used by field agents.',
                'rationale' => 'Scattered channels lose mandatory data and provenance.',
                'impact' => 'Procurement cannot trust queue completeness.',
                'do_nothing_consequence' => 'Rework and opaque status continue; digital BA tooling demos stay empty.',
            ],
        );

        $bnComplete = BusinessNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Procurement only sees complete validated inquiries',
            ],
            [
                'need_type' => 'problem',
                'description' => 'Incomplete inquiries must remain Draft until part number, quantity, and dealer code are valid.',
                'rationale' => 'Downstream ordering fails when mandatory fields are missing.',
                'impact' => 'False starts in procurement and dealer frustration.',
                'do_nothing_consequence' => 'Completeness gate never becomes a living requirement.',
            ],
        );

        $boRework = BusinessObjective::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Reduce incomplete inquiry rework',
            ],
            [
                'description' => 'Stop procurement from acting on incomplete parts requests.',
                'success_measure' => 'Fewer than 5% of submitted inquiries returned for missing mandatory fields within 90 days of go-live.',
                'potential_value' => 'Less re-keying, fewer delayed orders, clearer audit trail from ask to queue.',
            ],
        );

        $boCycle = BusinessObjective::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Shorten inquiry-to-procurement cycle time',
            ],
            [
                'description' => 'Move complete inquiries into the procurement queue without manual triage delays.',
                'success_measure' => 'Median time from agent submit to procurement-visible status under 15 minutes for complete inquiries.',
                'potential_value' => 'Faster parts fulfilment for dealers and better SLA credibility.',
            ],
        );

        $boRework->businessNeeds()->sync([
            $bnIntake->id => ['is_primary' => true],
            $bnComplete->id => ['is_primary' => false],
        ]);
        $boCycle->businessNeeds()->sync([
            $bnIntake->id => ['is_primary' => true],
        ]);

        $endUser = Stakeholder::query()
            ->where('project_id', $project->id)
            ->where('system_key', 'end_user')
            ->firstOrFail();

        $sponsor = Stakeholder::query()
            ->where('project_id', $project->id)
            ->where('system_key', 'sponsor')
            ->first();

        $fieldAgent = Stakeholder::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Parts Field Agent',
                'is_system' => false,
            ],
            [
                'type' => 'role',
                'influence' => 'medium',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Dealer-facing agent who captures parts inquiries in the field.',
            ],
        );

        $procurementLead = Stakeholder::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Parts Procurement Lead',
                'is_system' => false,
            ],
            [
                'type' => 'role',
                'influence' => 'high',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Owns the open procurement queue and completeness expectations.',
            ],
        );

        $snSubmit = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Submit a complete parts inquiry digitally',
            ],
            [
                'description' => 'As a Parts Field Agent, I want to submit a parts inquiry through a single digital form, so that procurement receives complete, validated requests without manual re-keying.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $snSubmit->businessObjectives()->sync([$boRework->id, $boCycle->id]);
        $snSubmit->stakeholders()->sync([$fieldAgent->id, $endUser->id]);

        $snStatus = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'See inquiry status after submission',
            ],
            [
                'description' => 'As a Parts Field Agent, I want to see whether my inquiry is Draft, Submitted, or further along, so that I know when procurement can act.',
                'priority_id' => $shouldId,
                'status_id' => $agreedId,
            ],
        );
        $snStatus->businessObjectives()->sync([$boCycle->id]);
        $snStatus->stakeholders()->sync([$fieldAgent->id]);

        $snQueue = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Receive only complete inquiries in the open queue',
            ],
            [
                'description' => 'As a Parts Procurement Lead, I want the open queue to contain only complete inquiries, so that my team does not chase missing data.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $snQueue->businessObjectives()->sync([$boRework->id]);
        $stakeholderIds = [$procurementLead->id];
        if ($sponsor !== null) {
            $stakeholderIds[] = $sponsor->id;
        }
        $snQueue->stakeholders()->sync($stakeholderIds);

        // Teaching orphan (draft, unlinked) — mirrors NeedSpineSeeder patterns.
        $orphan = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'WhatsApp photo of damaged part (untriaged ask)',
            ],
            [
                'description' => 'Example orphan stakeholder need captured from the floor before linking to a business objective.',
                'priority_id' => $shouldId,
                'status_id' => $draftId,
            ],
        );
        $orphan->businessObjectives()->sync([]);
        $orphan->stakeholders()->sync([]);

        return [$bnIntake, $bnComplete];
    }

    protected function seedScope(Project $project, BusinessNeed $bnIntake, BusinessNeed $bnComplete): void
    {
        $items = [
            [
                'title' => 'Digital inquiry form with mandatory-field validation',
                'direction' => ScopeItemDirection::IN,
                'description' => 'Web form for part number, quantity, dealer code, and optional notes.',
                'business_need_id' => $bnIntake->id,
            ],
            [
                'title' => 'Completeness gate before procurement visibility',
                'direction' => ScopeItemDirection::IN,
                'description' => 'Incomplete inquiries remain Draft; complete ones become Submitted in the open queue.',
                'business_need_id' => $bnComplete->id,
            ],
            [
                'title' => 'Agent-facing inquiry status view',
                'direction' => ScopeItemDirection::IN,
                'description' => 'Read-only status of the agent\'s own inquiries.',
                'business_need_id' => $bnIntake->id,
            ],
            [
                'title' => 'ERP inventory and pricing replacement',
                'direction' => ScopeItemDirection::OUT,
                'description' => 'Ordering, stock, and price master stay in the existing ERP.',
                'business_need_id' => null,
            ],
            [
                'title' => 'Dealer payment collection',
                'direction' => ScopeItemDirection::OUT,
                'description' => 'Payment and invoicing are out of scope for this intake initiative.',
                'business_need_id' => null,
            ],
        ];

        foreach ($items as $item) {
            ScopeItem::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $item['title'],
                ],
                [
                    'direction' => $item['direction'],
                    'description' => $item['description'],
                    'business_need_id' => $item['business_need_id'],
                ],
            );
        }
    }

    protected function seedGovernance(Project $project): void
    {
        Assumption::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Dealers can access the intake form from field devices',
            ],
            [
                'description' => 'Field agents have a smartphone or tablet with network access during dealer visits.',
                'status' => AssumptionStatus::VALIDATED,
                'source' => 'Pilot interviews — Q2',
            ],
        );

        Assumption::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Part numbers in the catalog match ERP masters',
            ],
            [
                'description' => 'Catalog part numbers used on the form resolve 1:1 to ERP item codes.',
                'status' => AssumptionStatus::OPEN,
                'source' => 'Parts master data owner',
            ],
        );

        Constraint::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Must use corporate identity provider for agent login',
            ],
            [
                'description' => 'SSO via the corporate IdP is mandatory; no local passwords for agents.',
                'status' => ConstraintStatus::ACTIVE,
                'source' => 'IT security policy',
            ],
        );

        Constraint::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Arabic and English UI labels required at go-live',
            ],
            [
                'description' => 'Intake screens must ship bilingual labels; content translation is in scope for UI chrome only.',
                'status' => ConstraintStatus::ACTIVE,
                'source' => 'Regional operations',
            ],
        );

        BusinessRule::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'BR-Inquiry-Complete: mandatory fields before Submitted',
            ],
            [
                'description' => 'An inquiry may move to Submitted only when part number, quantity (positive integer), and dealer code are present and valid.',
                'status' => BusinessRuleStatus::ACTIVE,
                'source' => 'Procurement SOP',
            ],
        );

        BusinessRule::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'BR-Queue-Visibility: Submitted only in open queue',
            ],
            [
                'description' => 'Procurement open queue lists inquiries in Submitted or later statuses; Draft inquiries are invisible to procurement.',
                'status' => BusinessRuleStatus::ACTIVE,
                'source' => 'Procurement SOP',
            ],
        );

        Risk::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Agents bypass the form via email during cutover',
            ],
            [
                'description' => 'If email remains convenient, digital intake adoption stalls and completeness does not improve.',
                'category' => RiskCategory::ORGANIZATIONAL,
                'likelihood' => RiskLikelihood::HIGH,
                'impact' => RiskImpact::HIGH,
                'response' => RiskResponse::MITIGATE,
                'treatment' => 'Time-box email fallback; procurement rejects incomplete email after pilot week 4.',
                'trigger' => 'More than 30% of inquiries still arrive by email after pilot week 2.',
                'owner' => 'Parts Procurement Lead',
                'status' => RiskStatus::OPEN,
                'source' => 'Change impact workshop',
            ],
        );

        Risk::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Catalog / ERP part-number mismatch',
            ],
            [
                'description' => 'Invalid part numbers pass client validation but fail ERP order creation.',
                'category' => RiskCategory::TECHNICAL,
                'likelihood' => RiskLikelihood::MEDIUM,
                'impact' => RiskImpact::HIGH,
                'response' => RiskResponse::MITIGATE,
                'treatment' => 'Nightly sync of active part numbers; soft-warn on unknown codes in Phase 1.',
                'trigger' => 'ERP reject rate > 2% of Submitted inquiries.',
                'owner' => 'Integration lead',
                'status' => RiskStatus::OPEN,
                'source' => 'Architecture review',
            ],
        );
    }

    /**
     * @return array<string, SwimlaneFlowStep> keyed by step label
     */
    protected function seedSwimlane(Project $project, int $draftId): array
    {
        $snSubmit = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'Submit a complete parts inquiry digitally')
            ->firstOrFail();

        $snQueue = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'Receive only complete inquiries in the open queue')
            ->firstOrFail();

        $snStatus = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'See inquiry status after submission')
            ->firstOrFail();

        $flow = SwimlaneFlow::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Parts inquiry intake to procurement queue',
            ],
            [
                'description' => 'As-is target process for digital intake with completeness decision.',
                'direction' => 'TB',
                'elements' => [],
                'status_id' => $draftId,
            ],
        );

        $definitions = [
            ['lane' => 'Field Agent', 'from' => null, 'type' => 'start', 'label' => 'Need parts for dealer', 'line_title' => null, 'sn' => null],
            ['lane' => 'Field Agent', 'from' => 'Need parts for dealer', 'type' => 'process', 'label' => 'Open digital inquiry form', 'line_title' => null, 'sn' => $snSubmit->id],
            ['lane' => 'Field Agent', 'from' => 'Open digital inquiry form', 'type' => 'process', 'label' => 'Enter part, qty, dealer code', 'line_title' => null, 'sn' => $snSubmit->id],
            ['lane' => 'Intake System', 'from' => 'Enter part, qty, dealer code', 'type' => 'decision', 'label' => 'Mandatory fields valid?', 'line_title' => null, 'sn' => $snSubmit->id],
            ['lane' => 'Field Agent', 'from' => 'Mandatory fields valid?', 'type' => 'process', 'label' => 'Correct missing fields', 'line_title' => 'No', 'sn' => $snSubmit->id],
            ['lane' => 'Intake System', 'from' => 'Mandatory fields valid?', 'type' => 'process', 'label' => 'Mark inquiry Submitted', 'line_title' => 'Yes', 'sn' => $snQueue->id],
            ['lane' => 'Procurement', 'from' => 'Mark inquiry Submitted', 'type' => 'process', 'label' => 'See inquiry in open queue', 'line_title' => null, 'sn' => $snQueue->id],
            ['lane' => 'Field Agent', 'from' => 'Mark inquiry Submitted', 'type' => 'process', 'label' => 'View inquiry status', 'line_title' => null, 'sn' => $snStatus->id],
            ['lane' => 'Procurement', 'from' => 'See inquiry in open queue', 'type' => 'end', 'label' => 'Ready for ERP order', 'line_title' => null, 'sn' => null],
        ];

        // Re-link by label within this flow so re-seed stays stable.
        $byLabel = [];
        foreach ($definitions as $index => $def) {
            $step = SwimlaneFlowStep::query()->updateOrCreate(
                [
                    'swimlane_flow_id' => $flow->id,
                    'label' => $def['label'],
                ],
                [
                    'project_id' => $project->id,
                    'position' => $index,
                    'lane' => $def['lane'],
                    'from_label' => $def['from'],
                    'type' => $def['type'],
                    'line_title' => $def['line_title'],
                    'stakeholder_need_id' => $def['sn'],
                ],
            );
            $byLabel[$def['label']] = $step;
        }

        // Drop obsolete demo steps no longer in the definition list.
        $keepLabels = array_column($definitions, 'label');
        SwimlaneFlowStep::query()
            ->where('swimlane_flow_id', $flow->id)
            ->whereNotIn('label', $keepLabels)
            ->each(fn (SwimlaneFlowStep $step) => $step->delete());

        return $byLabel;
    }

    protected function seedStateFlow(Project $project, int $draftId): void
    {
        StateFlow::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Parts inquiry lifecycle',
            ],
            [
                'description' => 'Status model for a dealer parts inquiry from draft through closure.',
                'status_id' => $draftId,
                'transitions' => [
                    ['from' => 'Draft', 'to' => 'Submitted', 'trigger' => 'Agent submits with valid mandatory fields'],
                    ['from' => 'Submitted', 'to' => 'In Review', 'trigger' => 'Procurement opens the inquiry'],
                    ['from' => 'In Review', 'to' => 'Ordered', 'trigger' => 'ERP order created'],
                    ['from' => 'In Review', 'to' => 'Returned', 'trigger' => 'Procurement requests clarification'],
                    ['from' => 'Returned', 'to' => 'Draft', 'trigger' => 'Agent edits and saves'],
                    ['from' => 'Returned', 'to' => 'Submitted', 'trigger' => 'Agent resubmits complete inquiry'],
                    ['from' => 'Ordered', 'to' => 'Closed', 'trigger' => 'Parts confirmed with dealer'],
                    ['from' => 'Submitted', 'to' => 'Cancelled', 'trigger' => 'Agent or procurement cancels'],
                    ['from' => 'Cancelled', 'to' => 'Closed', 'trigger' => 'Archive'],
                ],
            ],
        );
    }

    protected function seedArchitecture(Project $project, int $draftId): void
    {
        Architecture::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'title' => 'Parts inquiry intake — C4 context & containers',
                'description' => 'Context and container view for the digital intake initiative. ERP remains external system of record.',
                'status_id' => $draftId,
                'layout' => [
                    'shapes_per_row' => 4,
                    'boundaries_per_row' => 2,
                ],
                'elements' => [
                    ['key' => 'agent', 'kind' => 'person', 'name' => 'Parts Field Agent', 'description' => 'Captures dealer inquiries'],
                    ['key' => 'procurement', 'kind' => 'person', 'name' => 'Procurement Lead', 'description' => 'Works the open queue'],
                    ['key' => 'intake', 'kind' => 'system', 'name' => 'Parts Inquiry Intake', 'description' => 'Digital form, validation, status'],
                    ['key' => 'erp', 'kind' => 'system', 'name' => 'Dealer ERP', 'description' => 'Orders, inventory, pricing', 'external' => true, 'form' => 'database'],
                    ['key' => 'idp', 'kind' => 'system', 'name' => 'Corporate IdP', 'description' => 'SSO for agents', 'external' => true],
                    ['key' => 'web', 'kind' => 'container', 'name' => 'Intake Web App', 'description' => 'Bilingual agent UI', 'parent_key' => 'intake', 'technology' => 'Laravel / Blade'],
                    ['key' => 'api', 'kind' => 'container', 'name' => 'Intake API', 'description' => 'Validation & status APIs', 'parent_key' => 'intake', 'technology' => 'Laravel'],
                    ['key' => 'db', 'kind' => 'container', 'name' => 'Inquiry Store', 'description' => 'Inquiry documents & status', 'parent_key' => 'intake', 'technology' => 'PostgreSQL', 'form' => 'database'],
                ],
                'relationships' => [
                    ['from' => 'agent', 'to' => 'web', 'label' => 'Submits and tracks inquiries'],
                    ['from' => 'procurement', 'to' => 'web', 'label' => 'Reviews open queue'],
                    ['from' => 'web', 'to' => 'api', 'label' => 'HTTPS/JSON'],
                    ['from' => 'api', 'to' => 'db', 'label' => 'Reads/writes inquiries'],
                    ['from' => 'api', 'to' => 'idp', 'label' => 'OIDC login'],
                    ['from' => 'api', 'to' => 'erp', 'label' => 'Creates order after review (phase 2)'],
                ],
            ],
        );
    }

    /**
     * @param  array<string, SwimlaneFlowStep>  $steps
     */
    protected function seedSolutionPackaging(
        Project $project,
        int $mustId,
        int $shouldId,
        int $couldId,
        int $agreedId,
        int $draftId,
        array $steps,
    ): void {
        $parser = app(GherkinDocumentParser::class);

        $snSubmit = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'Submit a complete parts inquiry digitally')
            ->firstOrFail();

        $snStatus = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'See inquiry status after submission')
            ->firstOrFail();

        $snQueue = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->where('title', 'Receive only complete inquiries in the open queue')
            ->firstOrFail();

        $gateStep = $steps['Mandatory fields valid?'] ?? null;
        $submitStep = $steps['Mark inquiry Submitted'] ?? null;
        $statusStep = $steps['View inquiry status'] ?? null;

        // --- Feature + scenarios: completeness gate (SN-parented) ---
        $featureGate = $this->upsertFeature(
            $project,
            $parser,
            title: 'Parts inquiry completeness gate',
            stakeholderNeedId: $snSubmit->id,
            changeRequestId: null,
            stepId: $gateStep?->id,
            priorityId: $mustId,
            statusId: $agreedId,
            body: <<<'GHERKIN'
Feature: Parts inquiry completeness gate
  As a Parts Field Agent
  I want inquiry status to advance only when required data is complete
  So that procurement never acts on incomplete requests
GHERKIN,
        );

        $this->syncScenarios($featureGate, $parser, [
            [
                'title' => 'Inquiry moves to Submitted when mandatory fields are present',
                'body' => <<<'GHERKIN'
Scenario: Inquiry moves to Submitted when mandatory fields are present
  Given a dealer inquiry draft with part number, quantity, and dealer code filled
  When the agent submits the inquiry
  Then the inquiry status is Submitted
  And procurement can see it in the open queue
GHERKIN,
            ],
            [
                'title' => 'Inquiry stays Draft when part number is missing',
                'body' => <<<'GHERKIN'
@edge-case
Scenario: Inquiry stays Draft when part number is missing
  Given a dealer inquiry draft missing the part number
  When the agent attempts to submit the inquiry
  Then the inquiry remains Draft
  And the agent is told which fields are still required
GHERKIN,
            ],
            [
                'title' => 'Inquiry stays Draft when quantity is not a positive integer',
                'body' => <<<'GHERKIN'
@edge-case
Scenario: Inquiry stays Draft when quantity is not a positive integer
  Given a dealer inquiry draft with quantity "0"
  When the agent attempts to submit the inquiry
  Then the inquiry remains Draft
  And the agent is told that quantity must be a positive integer
GHERKIN,
            ],
        ]);

        FunctionalRequirement::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Parts inquiry completeness gate',
            ],
            [
                'stakeholder_need_id' => $snSubmit->id,
                'change_request_id' => null,
                'swimlane_flow_step_id' => $gateStep?->id,
                'statement' => 'The system shall accept a parts inquiry for procurement only when part number, quantity, and dealer code are present and valid; otherwise the inquiry shall remain Draft and the missing or invalid fields shall be reported to the agent.',
                'trigger' => 'When a Parts Field Agent submits a dealer parts inquiry',
                'acceptance_criteria' => <<<'TXT'
- Inquiry moves to Submitted when part number, quantity, and dealer code are present
- Inquiry stays Draft when part number is missing
- Edge: Inquiry stays Draft when quantity is not a positive integer
TXT,
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );

        NonFunctionalRequirement::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Inquiry submit response time',
            ],
            [
                'stakeholder_need_id' => $snSubmit->id,
                'change_request_id' => null,
                'category' => NfrCategory::PERFORMANCE,
                'description' => 'The system shall complete parts-inquiry submit validation and return a success or field-error response within 2 seconds under normal dealer catalogue load (up to 100,000 parts).',
                'acceptance_criteria' => <<<'TXT'
- p95 submit response time ≤ 2 seconds in the reference environment
- Timeouts and errors are reported to the agent without leaving the inquiry in an unknown state
TXT,
                'priority_id' => $shouldId,
                'status_id' => $agreedId,
            ],
        );

        // --- Feature: agent status visibility ---
        $featureStatus = $this->upsertFeature(
            $project,
            $parser,
            title: 'Agent inquiry status visibility',
            stakeholderNeedId: $snStatus->id,
            changeRequestId: null,
            stepId: $statusStep?->id,
            priorityId: $shouldId,
            statusId: $agreedId,
            body: <<<'GHERKIN'
Feature: Agent inquiry status visibility
  As a Parts Field Agent
  I want to see the current status of my inquiries
  So that I know whether procurement can act
GHERKIN,
        );

        $this->syncScenarios($featureStatus, $parser, [
            [
                'title' => 'Agent sees Draft and Submitted on their inquiry list',
                'body' => <<<'GHERKIN'
Scenario: Agent sees Draft and Submitted on their inquiry list
  Given the agent has one Draft and one Submitted inquiry
  When the agent opens My inquiries
  Then both inquiries are listed with their current status
GHERKIN,
            ],
        ]);

        FunctionalRequirement::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Agent can list status of own inquiries',
            ],
            [
                'stakeholder_need_id' => $snStatus->id,
                'change_request_id' => null,
                'swimlane_flow_step_id' => $statusStep?->id,
                'statement' => 'The system shall allow an authenticated field agent to list the status of inquiries they created, including at least Draft and Submitted.',
                'trigger' => 'When the agent opens My inquiries',
                'acceptance_criteria' => "- Agent sees Draft and Submitted on their inquiry list\n",
                'priority_id' => $shouldId,
                'status_id' => $agreedId,
            ],
        );

        // --- FR only (queue visibility) covering process step ---
        FunctionalRequirement::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Open queue shows only Submitted-or-later inquiries',
            ],
            [
                'stakeholder_need_id' => $snQueue->id,
                'change_request_id' => null,
                'swimlane_flow_step_id' => $submitStep?->id,
                'statement' => 'The system shall include an inquiry in the procurement open queue only when its status is Submitted or a later active status; Draft inquiries shall not appear.',
                'trigger' => 'When procurement opens the open queue',
                'acceptance_criteria' => <<<'TXT'
- Submitted inquiry appears in the open queue
- Draft inquiry does not appear in the open queue
TXT,
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );

        // --- Change request path (exclusive CR parent for packaging) ---
        $cr = ChangeRequest::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Allow optional damage photo on inquiry',
            ],
            [
                'problem' => 'Agents sometimes need to show part damage; without a photo, procurement requests clarification by phone.',
                'proposed_change' => 'Allow an optional image attachment (max 5 MB, JPEG/PNG) on Draft and Returned inquiries; photo is not required for completeness.',
                'requestor' => 'Parts Field Agent (pilot dealer group)',
                'impact_level' => ChangeRequestImpact::MEDIUM,
                'impact_notes' => 'Storage and content-type validation; does not change mandatory-field gate.',
                'stakeholder_need_id' => $snSubmit->id,
                'priority_id' => $couldId,
                'status' => ChangeRequestStatus::APPROVED,
            ],
        );

        $featurePhoto = $this->upsertFeature(
            $project,
            $parser,
            title: 'Optional damage photo on inquiry',
            stakeholderNeedId: null,
            changeRequestId: $cr->id,
            stepId: null,
            priorityId: $couldId,
            statusId: $draftId,
            body: <<<'GHERKIN'
Feature: Optional damage photo on inquiry
  As a Parts Field Agent
  I want to attach an optional photo of the damaged part
  So that procurement can assess the request without a phone call
GHERKIN,
        );

        $this->syncScenarios($featurePhoto, $parser, [
            [
                'title' => 'Agent attaches a JPEG under 5 MB while Draft',
                'body' => <<<'GHERKIN'
Scenario: Agent attaches a JPEG under 5 MB while Draft
  Given an inquiry in Draft
  When the agent attaches a JPEG photo under 5 MB
  Then the photo is stored on the inquiry
  And the inquiry may still be submitted when mandatory fields are valid
GHERKIN,
            ],
            [
                'title' => 'System rejects attachment over 5 MB',
                'body' => <<<'GHERKIN'
@edge-case
Scenario: System rejects attachment over 5 MB
  Given an inquiry in Draft
  When the agent attaches a photo larger than 5 MB
  Then the attachment is rejected
  And the inquiry remains unchanged
GHERKIN,
            ],
        ]);

        FunctionalRequirement::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Optional damage photo attachment',
            ],
            [
                'stakeholder_need_id' => null,
                'change_request_id' => $cr->id,
                'swimlane_flow_step_id' => null,
                'statement' => 'The system shall allow an optional JPEG or PNG attachment up to 5 MB on Draft or Returned inquiries; absence of a photo shall not block transition to Submitted when mandatory fields are valid.',
                'trigger' => 'When a field agent adds or removes a photo on an editable inquiry',
                'acceptance_criteria' => <<<'TXT'
- Agent attaches a JPEG under 5 MB while Draft
- Edge: System rejects attachment over 5 MB
TXT,
                'priority_id' => $couldId,
                'status_id' => $draftId,
            ],
        );
    }

    protected function upsertFeature(
        Project $project,
        GherkinDocumentParser $parser,
        string $title,
        ?int $stakeholderNeedId,
        ?int $changeRequestId,
        ?int $stepId,
        int $priorityId,
        int $statusId,
        string $body,
    ): Feature {
        $feature = Feature::query()->firstOrNew([
            'project_id' => $project->id,
            'title' => $title,
        ]);

        $feature->fill([
            'stakeholder_need_id' => $stakeholderNeedId,
            'change_request_id' => $changeRequestId,
            'swimlane_flow_step_id' => $stepId,
            'body' => $body,
            'priority_id' => $priorityId,
            'status_id' => $statusId,
        ]);
        $feature->syncDocumentFields($parser);
        $feature->save();

        return $feature->fresh();
    }

    /**
     * @param  list<array{title: string, body: string}>  $scenarios
     */
    protected function syncScenarios(Feature $feature, GherkinDocumentParser $parser, array $scenarios): void
    {
        $keepTitles = [];

        foreach ($scenarios as $row) {
            $scenario = Scenario::query()->firstOrNew([
                'feature_id' => $feature->id,
                'title' => $row['title'],
            ]);
            $scenario->body = $row['body'];
            $scenario->syncDocumentFields($parser);
            $scenario->save();
            $keepTitles[] = $scenario->title;
        }

        Scenario::query()
            ->where('feature_id', $feature->id)
            ->whereNotIn('title', $keepTitles)
            ->each(fn (Scenario $scenario) => $scenario->delete());
    }
}
