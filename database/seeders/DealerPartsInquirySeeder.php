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
use App\Services\SystemStakeholderSeeder;
use App\Services\TenancyProvisioner;
use App\Support\BusinessRuleStatus;
use App\Support\ConstraintStatus;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use App\Support\NeedType;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Seeder;

/**
 * Seed the real Dealer Parts Inquiry project (TIQ-DPI) from the requirements document.
 *
 * Idempotent via updateOrCreate on stable titles. Removes invented filler that is
 * not in the source document (assumptions, risks, scope, BDD, C4, change requests).
 */
class DealerPartsInquirySeeder extends Seeder
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
        $mustId = EntityPriority::id(EntityPriority::MUST);
        $shouldId = EntityPriority::id(EntityPriority::SHOULD);
        $couldId = EntityPriority::id(EntityPriority::COULD);

        $project = Project::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'code' => 'TIQ-DPI',
            ],
            [
                'name' => 'Dealer Parts Inquiry',
                'description' => 'Replace fragmented WhatsApp/email/phone parts inquiry channels with a centralized digital platform that is the official record of truth for dealer-to-TIQ Parts Field interactions.',
                'status_id' => $agreedId,
            ],
        );

        app(SystemStakeholderSeeder::class)->seedForProject($project);

        $this->purgeNonDocumentArtifacts($project);

        $this->seedStrategy($project);
        $sns = $this->seedNeedSpine($project, $agreedId, $mustId, $shouldId, $couldId);
        $this->seedConstraintsAndRules($project);
        $this->seedStateFlow($project, $agreedId);
        $steps = $this->seedSwimlane($project, $agreedId, $sns);
        $this->seedFunctionalRequirements($project, $mustId, $shouldId, $couldId, $agreedId, $steps, $sns);
    }

    /**
     * Remove artifacts not present in the source requirements (earlier seeder filler).
     */
    protected function purgeNonDocumentArtifacts(Project $project): void
    {
        Feature::query()
            ->where('project_id', $project->id)
            ->each(function (Feature $feature): void {
                Scenario::query()->where('feature_id', $feature->id)->each(
                    fn (Scenario $scenario) => $scenario->delete(),
                );
                $feature->delete();
            });

        ChangeRequest::query()->where('project_id', $project->id)->each(
            fn (ChangeRequest $cr) => $cr->delete(),
        );

        Assumption::query()->where('project_id', $project->id)->each(
            fn (Assumption $row) => $row->delete(),
        );

        Risk::query()->where('project_id', $project->id)->each(
            fn (Risk $row) => $row->delete(),
        );

        ScopeItem::query()->where('project_id', $project->id)->each(
            fn (ScopeItem $row) => $row->delete(),
        );

        Architecture::query()->where('project_id', $project->id)->each(
            fn (Architecture $row) => $row->delete(),
        );
    }

    protected function seedStrategy(Project $project): void
    {
        StrategicBaseline::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'current_state' => <<<'TXT'
Dealer parts inquiries are handled through fragmented, informal channels (WhatsApp, email, and phone). There is frequent human error, no reliable audit trail, and little structured data for performance measurement. Technical and commercial history is trapped in private chats and personal inboxes.
TXT,
                'future_state' => <<<'TXT'
A single centralized digital platform is the official record of truth for dealer-to-TIQ parts inquiries. Inquiries follow a standardized lifecycle, attachments and threaded communication are preserved, and the Parts Field team has visibility into volumes, bottlenecks, and regional performance against service levels.
TXT,
                'change_strategy' => <<<'TXT'
Deliver DPI as a ticket type within the Support system (NFR-02 Service Field rule). Make the digital channel the official path for dealer parts inquiries, and use KPI dashboards (volume, response time, resolution rate) from day one.
TXT,
                'status' => StrategicBaselineStatus::APPROVED,
            ],
        );
    }

    /**
     * Spine: Business Need (why) → Business Objective (what) → Stakeholder Need.
     *
     * @return array<string, StakeholderNeed>
     */
    protected function seedNeedSpine(
        Project $project,
        int $agreedId,
        int $mustId,
        int $shouldId,
        int $couldId,
    ): array {
   
        // --- Business Needs (why) — problem/opportunity focused titles ---
        $bn01 = $this->upsertBusinessNeed(
            $project,
            titles: [
                'Process standardization and automation',
                'Operational Friction and Error in Informal Channels',
                'Process Standardization: Operational Friction and Error in Informal Channels',
            ],
            attributes: [
                'title' => 'Operational Friction and Error in Informal Channels',
                'need_type' => NeedType::PROBLEM,
                'description' => 'BN-01 Process Standardization and Automation — eliminate overhead and error from informal dealer parts inquiry channels.',
                'rationale' => 'Dealer parts inquiries are currently handled through fragmented channels (WhatsApp, email, and phone) which lack mandatory data enforcement, lifecycle tracking, or consistent handling.',
                'impact' => 'The Parts Field team wastes significant administrative hours chasing incomplete inquiry data, leading to elevated error rates and missed service levels.',
                'do_nothing_consequence' => 'Operational overhead and human error continue unchecked; no single process owner can enforce corporate handling standards.',
            ],
        );

        $bn02 = $this->upsertBusinessNeed(
            $project,
            titles: [
                'Knowledge asset preservation',
                'Siloed Technical and Commercial History',
                'Knowledge Asset Preservation: Siloed Technical and Commercial History',
            ],
            attributes: [
                'title' => 'Siloed Technical and Commercial History',
                'need_type' => NeedType::PROBLEM,
                'description' => 'BN-02 Knowledge Asset Preservation — keep technical and commercial inquiry history as a corporate asset.',
                'rationale' => 'Critical technical troubleshooting details, part numbers, and commercial resolutions remain trapped in private chats and personal inboxes.',
                'impact' => 'Institutional knowledge disappears with staff turnover, forcing recurring investigations into previously solved part issues.',
                'do_nothing_consequence' => 'Organizational memory stays siloed, and commercial or technical disputes cannot be reconstructed from an official system of record.',
            ],
        );

        $bn03 = $this->upsertBusinessNeed(
            $project,
            titles: [
                'Operational transparency',
                'Lack of Objective Performance Visibility',
                'Operational Transparency: Lack of Objective Performance Visibility',
            ],
            attributes: [
                'title' => 'Lack of Objective Performance Visibility',
                'need_type' => NeedType::OPPORTUNITY,
                'description' => 'BN-03 Operational Transparency — enable data-driven management of inquiry performance.',
                'rationale' => 'Management currently operates without structured data regarding inquiry volumes, resolution bottlenecks, or regional performance variances.',
                'impact' => 'Performance evaluations and staffing decisions remain anecdotal rather than data-driven.',
                'do_nothing_consequence' => 'Service-level bottlenecks persist invisibly, and management cannot objectively allocate regional support.',
            ],
        );

        $bn04 = $this->upsertBusinessNeed(
            $project,
            titles: [
                'Compliance and accountability',
                'Accountability and Dispute Defense',
                'Vulnerability in Commercial and Technical Disputes',
                'Accountability and Dispute Defense: Vulnerability in Commercial Disputes',
                'Vulnerability in Commercial Disputes',
            ],
            attributes: [
                'title' => 'Vulnerability in Commercial and Technical Disputes',
                'need_type' => NeedType::PROBLEM,
                'description' => 'BN-04 Accountability and Dispute Defense — establish a verifiable trail for dealer-to-TIQ interactions.',
                'rationale' => 'Informal communication channels leave no reliable, verifiable trail of status modifications, commitments, or resolutions.',
                'impact' => 'The enterprise is exposed to liability and financial loss when high-value part disputes arise and cannot be defended.',
                'do_nothing_consequence' => 'Commercial disputes remain legally and operationally indefensible due to the absence of a reliable audit trail.',
            ],
        );

        // Keep only the four canonical needs for this project (drop rename duplicates).
        BusinessNeed::query()
            ->where('project_id', $project->id)
            ->whereNotIn('id', [$bn01->id, $bn02->id, $bn03->id, $bn04->id])
            ->each(fn (BusinessNeed $need) => $need->delete());

        // --- Business Objectives (what) — children of needs; pivot is_primary = primary parent need ---
        $bo01 = $this->upsertBusinessObjective(
            $project,
            titles: [
                'Centralization and process integrity',
                'Channel Centralization and Process Integrity',
            ],
            attributes: [
                'title' => 'Channel Centralization and Process Integrity',
                'description' => 'Transition 100% of regional dealer parts inquiries from informal channels to the official digital platform.',
                'success_measure' => '100% platform adoption for active dealers within 60 days of launch; zero unrecorded offline requests.',
                'potential_value' => 'Lower operational overhead and human error through a single official inquiry channel.',
            ],
        );

        $bo02 = $this->upsertBusinessObjective(
            $project,
            titles: [
                'Knowledge asset integrity',
                'Institutional Knowledge Retention',
            ],
            attributes: [
                'title' => 'Institutional Knowledge Retention',
                'description' => 'Capture, structure, and archive all dealer inquiry details, attachments, and historical resolutions as a centralized corporate knowledge base.',
                'success_measure' => '100% of resolved parts inquiries are searchable and retrievable, preventing repeat troubleshooting investigations.',
                'potential_value' => 'Inquiry history becomes a reusable corporate asset for TIQ and dealer Parts teams.',
            ],
        );

        $bo03 = $this->upsertBusinessObjective(
            $project,
            titles: [
                'Operational transparency',
                'KPI dashboards from day one',
                'Objective Operational Transparency',
            ],
            attributes: [
                'title' => 'Objective Operational Transparency',
                'description' => 'Enable data-driven management oversight regarding inquiry volumes, resolution bottlenecks, and regional performance against service levels.',
                'success_measure' => 'Real-time tracking of inquiry volume, response time, and resolution rate available from deployment day one.',
                'potential_value' => 'Data-driven staffing and SLA management across regions.',
            ],
        );

        $bo04 = $this->upsertBusinessObjective(
            $project,
            titles: [
                'Compliance and audit readiness',
                'Risk Mitigation and Accountability',
            ],
            attributes: [
                'title' => 'Risk Mitigation and Accountability',
                'description' => 'Establish an unalterable, transparent history of all status modifications and communications for commercial dispute defense.',
                'success_measure' => '100% of ticket state changes and user actions are immutably logged with timestamps and actor metadata.',
                'potential_value' => 'Defensible audit evidence for commercial and technical disputes.',
            ],
        );

        // Objective → primary parent Need (why).
        $bo01->businessNeeds()->sync([$bn01->id => ['is_primary' => true]]);
        $bo02->businessNeeds()->sync([$bn02->id => ['is_primary' => true]]);
        $bo03->businessNeeds()->sync([$bn03->id => ['is_primary' => true]]);
        $bo04->businessNeeds()->sync([$bn04->id => ['is_primary' => true]]);

        $sponsor = Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Omar San', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'high',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Sponsor / Product Owner — final approval; provides core business vision.',
            ],
        );

        $partsField = Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Parts Field Team', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'high',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Internal users — respond to inquiries; primary consumers of reports.',
            ],
        );

        $dealers = Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Dealers', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'medium',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'External users — initiate inquiries; provide required part data.',
            ],
        );

        Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'DX Team', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'medium',
                'interest' => 'medium',
                'status_id' => $agreedId,
                'notes' => 'Support — technical oversight and infrastructure support.',
            ],
        );

        Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Shift Software', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'medium',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Implementation team — software development and delivery.',
            ],
        );

        // Stakeholder Needs hang under Business Objectives (what), one parent objective each.
        $sn01 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Official communication channel for parts inquiries'],
            [
                'description' => 'Dealers require a secure, unified digital portal to submit and track parts inquiries, replacing unofficial channels.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn01->businessObjectives()->sync([$bo01->id]);
        $sn01->stakeholders()->sync([$dealers->id]);

        $sn02 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Technical data enrichment via attachments'],
            [
                'description' => 'Dealers require the capability to provide visual and documentary evidence (images/PDFs) within an inquiry to defend against commercial and technical discrepancies.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn02->businessObjectives()->sync([$bo04->id]);
        $sn02->stakeholders()->sync([$dealers->id]);

        $sn03 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Centralized communication history on each inquiry'],
            [
                'description' => 'Dealers and TIQ Parts Field require all status updates and follow-up communications to be permanently linked to the original inquiry to preserve institutional knowledge.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn03->businessObjectives()->sync([$bo02->id]);
        $sn03->stakeholders()->sync([$dealers->id, $partsField->id]);

        $sn04 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Segregate inquiries by region and branch'],
            [
                'description' => 'The Parts Field Team requires the ability to segregate and filter inquiries by Region and Branch to manage operational workloads effectively.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn04->businessObjectives()->sync([$bo02->id]);
        $sn04->stakeholders()->sync([$partsField->id]);

        $sn05 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'System-enforced inquiry lifecycle'],
            [
                'description' => 'The Parts Field Team requires a system-enforced workflow so every inquiry follows a strict, auditable lifecycle.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn05->businessObjectives()->sync([$bo01->id]);
        $sn05->stakeholders()->sync([$partsField->id]);

        $sn06 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Real-time performance analytics'],
            [
                'description' => 'Management requires automated, real-time visibility into inquiry volumes and Time-to-Resolution.',
                'priority_id' => $shouldId,
                'status_id' => $agreedId,
            ],
        );
        $sn06->businessObjectives()->sync([$bo03->id]);
        $sn06->stakeholders()->sync([$sponsor->id]);

        $sn07 = StakeholderNeed::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Ad-hoc export for custom analytics'],
            [
                'description' => 'Management requires ad-hoc export of filtered inquiry data to Excel/CSV for custom analytical reporting.',
                'priority_id' => $couldId,
                'status_id' => $agreedId,
            ],
        );
        $sn07->businessObjectives()->sync([$bo03->id]);
        $sn07->stakeholders()->sync([$sponsor->id]);

        return [
            'sr01' => $sn01,
            'sr02' => $sn02,
            'sr03' => $sn03,
            'sr04' => $sn04,
            'sr05' => $sn05,
            'sr06' => $sn06,
            'sr07' => $sn07,
        ];
    }

    protected function seedConstraintsAndRules(Project $project): void
    {
        // Constraint/BusinessRule models have no priority_id; Priority is recorded in source.
        $constraints = [
            [
                'title' => 'NFR-01: Security & Data Isolation',
                'description' => 'The system shall enforce database-level branch isolation, restricting Dealer user access exclusively to their assigned Branch ID, while granting full cross-regional visibility only to TIQ Admin and Parts Management roles.',
                'source' => 'DPI NFR-01 (Must)',
                'aliases' => ['NFR-01: Role-based data isolation'],
            ],
            [
                'title' => 'NFR-02: Architectural Integration',
                'description' => 'The Dealer Parts Inquiry platform shall be implemented as a distinct ticket type within the existing enterprise Support system framework (Service Field rule).',
                'source' => 'DPI NFR-02 (Must)',
                'aliases' => ['NFR-02: Modular Support ticket architecture'],
            ],
            [
                'title' => 'NFR-03: Performance',
                'description' => 'Dashboard widgets and filtered ticket lists shall load in under 3 seconds under normal concurrent operational load (all active Parts users).',
                'source' => 'DPI NFR-03 (Should)',
                'aliases' => ['NFR-03: Dashboard latency under 3 seconds'],
            ],
            [
                'title' => 'NFR-04: Audit Logging',
                'description' => 'The system shall maintain an immutable, unalterable log of all user actions related to ticket state changes, capturing User ID, Timestamp, and Action Performed, exportable solely by Authorized Management.',
                'source' => 'DPI NFR-04 (Must)',
                'aliases' => ['NFR-04: Immutable audit logging'],
            ],
        ];

        foreach ($constraints as $row) {
            $existing = Constraint::query()
                ->where('project_id', $project->id)
                ->where(function ($q) use ($row) {
                    $q->where('title', $row['title'])
                        ->orWhereIn('title', $row['aliases']);
                })
                ->first();

            if ($existing) {
                $existing->fill([
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => ConstraintStatus::ACTIVE,
                    'source' => $row['source'],
                ])->save();
            } else {
                Constraint::query()->create([
                    'project_id' => $project->id,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => ConstraintStatus::ACTIVE,
                    'source' => $row['source'],
                ]);
            }
        }

        // Drop earlier inventiveness that duplicated FR-02 as a constraint.
        Constraint::query()
            ->where('project_id', $project->id)
            ->where('title', 'Attachment formats limited to JPG, PNG, PDF')
            ->each(fn (Constraint $row) => $row->delete());

        $rules = [
            [
                'title' => 'BR-01: Allowed Status Transitions',
                'description' => 'Ticket status changes shall strictly follow the approved lifecycle path: Open (Dealer action) → TIQ Responded or Closed (TIQ action) → Dealer Responded (Dealer follow-up) → TIQ Responded or Closed (TIQ action). Closed tickets reject all further edits, comments, and attachments.',
                'source' => 'DPI BR-01 (Must)',
                'aliases' => [
                    'Allowed inquiry status transitions',
                    'BR-DPI-Lifecycle: allowed status transitions',
                ],
            ],
            [
                'title' => 'BR-02: Thread Integrity',
                'description' => 'Each ticket shall enforce a single, non-editable, non-deletable chronological communication thread where every status change and message is automatically timestamped and logged.',
                'source' => 'DPI BR-02 (Must)',
                'aliases' => [
                    'Immutable chronological inquiry thread',
                    'BR-DPI-Thread: immutable chronological thread',
                ],
            ],
            [
                'title' => 'BR-03: Creation Validation',
                'description' => 'A dealer inquiry ticket shall be created only when all mandatory data elements—Part Number, Dealer ID, Branch, Inquiry Type, and Inquiry Description—are present and valid.',
                'source' => 'DPI BR-03 (Must)',
                'aliases' => [
                    'Mandatory fields for ticket creation',
                    'BR-DPI-Mandatory: ticket create fields',
                ],
            ],
        ];

        foreach ($rules as $row) {
            $existing = BusinessRule::query()
                ->where('project_id', $project->id)
                ->where(function ($q) use ($row) {
                    $q->where('title', $row['title'])
                        ->orWhereIn('title', $row['aliases']);
                })
                ->first();

            if ($existing) {
                $existing->fill([
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => BusinessRuleStatus::ACTIVE,
                    'source' => $row['source'],
                ])->save();
            } else {
                BusinessRule::query()->create([
                    'project_id' => $project->id,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => BusinessRuleStatus::ACTIVE,
                    'source' => $row['source'],
                ]);
            }
        }

        BusinessRule::query()
            ->where('project_id', $project->id)
            ->whereIn('title', [
                'BR-DPI-Audit-Export: management-only audit export',
            ])
            ->each(fn (BusinessRule $row) => $row->delete());
    }

    protected function seedStateFlow(Project $project, int $agreedId): void
    {
        StateFlow::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'DPI inquiry lifecycle'],
            [
                'description' => 'FR-04 state definitions and transition rules from the Dealer Parts Inquiry requirements.',
                'status_id' => $agreedId,
                'transitions' => [
                    ['from' => '(none)', 'to' => 'Open', 'trigger' => 'Dealer creates ticket'],
                    ['from' => 'Open', 'to' => 'TIQ Responded', 'trigger' => 'TIQ provides answer or requests more info'],
                    ['from' => 'Open', 'to' => 'Closed', 'trigger' => 'TIQ resolves/closes'],
                    ['from' => 'TIQ Responded', 'to' => 'Dealer Responded', 'trigger' => 'Dealer provides follow-up'],
                    ['from' => 'TIQ Responded', 'to' => 'Closed', 'trigger' => 'TIQ accepts resolution / closes'],
                    ['from' => 'Dealer Responded', 'to' => 'TIQ Responded', 'trigger' => 'TIQ provides new answer'],
                    ['from' => 'Dealer Responded', 'to' => 'Closed', 'trigger' => 'TIQ resolves/closes'],
                ],
            ],
        );
    }

    /**
     * Swimlane aligned to the FR-04 transition table (Dealer ↔ TIQ Parts Field).
     *
     * @param  array<string, StakeholderNeed>  $sns
     * @return array<string, SwimlaneFlowStep>
     */
    protected function seedSwimlane(Project $project, int $agreedId, array $sns): array
    {
        // Drop the older invented process title if present.
        SwimlaneFlow::query()
            ->where('project_id', $project->id)
            ->where('title', 'Dealer parts inquiry lifecycle')
            ->each(fn (SwimlaneFlow $flow) => $flow->delete());

        $flow = SwimlaneFlow::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'DPI inquiry handling',
            ],
            [
                'description' => 'Process view of the FR-04 inquiry lifecycle between Dealer and TIQ Parts Field.',
                'direction' => 'TB',
                'elements' => null,
                'status_id' => $agreedId,
            ],
        );

        $definitions = [
            ['lane' => 'Dealer', 'from' => null, 'type' => 'start', 'label' => 'Need parts inquiry', 'line_title' => null, 'sn' => null],
            ['lane' => 'Dealer', 'from' => 'Need parts inquiry', 'type' => 'process', 'label' => 'Create ticket', 'line_title' => null, 'sn' => $sns['sr01']->id],
            ['lane' => 'Dealer', 'from' => 'Create ticket', 'type' => 'process', 'label' => 'Attach images or PDFs', 'line_title' => null, 'sn' => $sns['sr02']->id],
            ['lane' => 'Support System', 'from' => 'Attach images or PDFs', 'type' => 'process', 'label' => 'Open ticket and notify TIQ', 'line_title' => null, 'sn' => $sns['sr05']->id],
            ['lane' => 'Parts Field', 'from' => 'Open ticket and notify TIQ', 'type' => 'process', 'label' => 'Review inquiry', 'line_title' => null, 'sn' => $sns['sr04']->id],
            ['lane' => 'Parts Field', 'from' => 'Review inquiry', 'type' => 'decision', 'label' => 'Need more information?', 'line_title' => null, 'sn' => $sns['sr05']->id],
            ['lane' => 'Parts Field', 'from' => 'Need more information?', 'type' => 'process', 'label' => 'Request more information', 'line_title' => 'Yes', 'sn' => $sns['sr05']->id],
            ['lane' => 'Dealer', 'from' => 'Request more information', 'type' => 'process', 'label' => 'Provide follow-up', 'line_title' => null, 'sn' => $sns['sr03']->id],
            ['lane' => 'Parts Field', 'from' => 'Provide follow-up', 'type' => 'process', 'label' => 'Answer and close', 'line_title' => null, 'sn' => $sns['sr05']->id],
            ['lane' => 'Parts Field', 'from' => 'Need more information?', 'type' => 'process', 'label' => 'Resolve and close', 'line_title' => 'No', 'sn' => $sns['sr05']->id],
            ['lane' => 'Support System', 'from' => 'Resolve and close', 'type' => 'end', 'label' => 'Inquiry closed', 'line_title' => null, 'sn' => $sns['sr03']->id],
            ['lane' => 'Support System', 'from' => 'Answer and close', 'type' => 'end', 'label' => 'Inquiry closed after follow-up', 'line_title' => null, 'sn' => $sns['sr03']->id],
        ];

        SwimlaneFlowStep::query()
            ->where('swimlane_flow_id', $flow->id)
            ->each(fn (SwimlaneFlowStep $step) => $step->delete());

        $byLabel = [];
        foreach ($definitions as $index => $def) {
            $step = SwimlaneFlowStep::query()->create([
                'swimlane_flow_id' => $flow->id,
                'project_id' => $project->id,
                'position' => $index,
                'lane' => $def['lane'],
                'from_label' => $def['from'],
                'type' => $def['type'],
                'label' => $def['label'],
                'line_title' => $def['line_title'],
                'stakeholder_need_id' => $def['sn'],
            ]);
            $byLabel[$def['label']] = $step;
        }

        return $byLabel;
    }

    /**
     * @param  array<string, SwimlaneFlowStep>  $steps
     * @param  array<string, StakeholderNeed>  $sns
     */
    protected function seedFunctionalRequirements(
        Project $project,
        int $mustId,
        int $shouldId,
        int $couldId,
        int $agreedId,
        array $steps,
        array $sns,
    ): void {
        $createStep = $steps['Create ticket'] ?? null;
        $attachStep = $steps['Attach images or PDFs'] ?? null;
        $openStep = $steps['Open ticket and notify TIQ'] ?? null;
        $closeStep = $steps['Resolve and close'] ?? null;
        $reviewStep = $steps['Review inquiry'] ?? null;

        $frDefs = [
            [
                'titles' => ['FR-01 Ticket creation with mandatory fields'],
                'title' => 'FR-01 Ticket creation with mandatory fields',
                'sn' => 'sr01',
                'step' => $createStep,
                'priority' => $mustId,
                'statement' => 'The system shall allow Dealers to create inquiries. Mandatory fields include Part Number, Dealer ID, Branch, Inquiry Type, and Inquiry Description.',
                'trigger' => 'When a Dealer submits a new parts inquiry',
                'ac' => "- Ticket is created in Open when all mandatory fields are present\n- Submission is blocked with clear errors when mandatory fields are missing (FR-10)",
            ],
            [
                'titles' => ['FR-02 Multiple attachments per inquiry'],
                'title' => 'FR-02 Multiple attachments per inquiry',
                'sn' => 'sr02',
                'step' => $attachStep,
                'priority' => $mustId,
                'statement' => 'The system shall support multiple attachments per inquiry. Supported formats: JPG, PNG, PDF.',
                'trigger' => 'When a Dealer adds files to an inquiry',
                'ac' => "- Multiple JPG/PNG/PDF files can be attached\n- Unsupported types or failed size constraints are rejected with clear errors (FR-10)",
            ],
            [
                'titles' => ['FR-03 Immutable chronological threading'],
                'title' => 'FR-03 Immutable chronological threading',
                'sn' => 'sr03',
                'step' => $openStep,
                'priority' => $mustId,
                'statement' => 'The system shall maintain a single, non-editable, and non-deletable thread for each ticket to preserve audit trail integrity.',
                'trigger' => 'When any message is posted or status changes on a ticket',
                'ac' => "- Thread entries cannot be edited or deleted by users\n- All follow-ups remain linked to the original inquiry",
            ],
            [
                'titles' => ['FR-04 Inquiry state enforcement'],
                'title' => 'FR-04 Inquiry state enforcement',
                'sn' => 'sr05',
                'step' => $closeStep,
                'priority' => $mustId,
                'statement' => 'The system shall restrict ticket status changes to Open, TIQ Responded, Dealer Responded, and Closed per the approved transition table. Closed is final; no further edits allowed.',
                'trigger' => 'When a user attempts a status change or posts a reply',
                'ac' => "- Illegal transitions are rejected\n- Closed tickets reject further comments and attachments\n- Dealer reply from TIQ Responded sets Dealer Responded",
            ],
            [
                'titles' => [
                    'FR-05 Automated timestamping of history',
                    'FR-05 Automated timestamp and actor metadata',
                ],
                'title' => 'FR-05 Automated timestamp and actor metadata',
                'sn' => 'sr03',
                'step' => $openStep,
                'priority' => $mustId,
                'statement' => 'The system shall automatically record a timestamp and actor metadata for every status modification and message transmission.',
                'trigger' => 'When a status is modified or a message is transmitted',
                'ac' => "- Each status change stores timestamp and actor metadata\n- Each message transmission stores timestamp and actor metadata\n- History is available on the inquiry thread",
            ],
            [
                'titles' => [
                    'FR-06 Advanced dashboard filtering',
                    'FR-06 Advanced Dashboard Filtering',
                ],
                'title' => 'FR-06 Advanced Dashboard Filtering',
                'sn' => 'sr04',
                'step' => $reviewStep,
                'priority' => $mustId,
                'statement' => 'The internal dashboard shall allow authorized users to filter inquiry data dynamically by Status, Priority, Inquiry Type, Region, Branch, Dealer, and Date Range while enforcing regional security data isolation (NFR-01).',
                'trigger' => 'When an authorized user opens or filters the internal dashboard',
                'ac' => "- Filters can be combined across Status, Priority, Inquiry Type, Region, Branch, Dealer, and Date Range\n- Results respect NFR-01 regional data isolation",
            ],
            [
                'titles' => [
                    'FR-07 Filtered CSV export',
                    'FR-07 Filtered Data Export',
                ],
                'title' => 'FR-07 Filtered Data Export',
                'sn' => 'sr07',
                'step' => $reviewStep,
                'priority' => $couldId,
                'statement' => 'The system shall allow authorized management users to export filtered dashboard view datasets into standard CSV format.',
                'trigger' => 'When an authorized management user exports the current filtered dashboard view',
                'ac' => "- Export matches the active filtered dashboard view\n- Unauthorized users cannot export",
            ],
            [
                'titles' => [
                    'FR-08 Total Resolution Time KPI',
                    'FR-08 Total Resolution Time KPI Calculation',
                ],
                'title' => 'FR-08 Total Resolution Time KPI Calculation',
                'sn' => 'sr06',
                'step' => $reviewStep,
                'priority' => $shouldId,
                'statement' => 'The system shall automatically calculate and display Total Resolution Time metrics summary widgets on the management dashboard from deployment day one.',
                'trigger' => 'When Management opens the KPI dashboard',
                'ac' => "- Total Resolution Time is calculated automatically per closed inquiry\n- Summary widgets are visible on the management dashboard from deployment day one",
            ],
            [
                'titles' => [
                    'FR-09 Automated notifications',
                    'FR-09 Automated Notification Dispatch',
                ],
                'title' => 'FR-09 Automated Notification Dispatch',
                'sn' => 'sr01',
                'step' => $openStep,
                'priority' => $mustId,
                'statement' => 'The system shall automatically dispatch email and system alerts to the TIQ team upon new ticket creation, and to Dealers upon any status update or response.',
                'trigger' => 'When a ticket is created or its status is updated or a response is posted',
                'ac' => "- TIQ receives email/system alerts on new ticket creation\n- Dealers receive email/system alerts on status updates or responses",
            ],
            [
                'titles' => [
                    'FR-10 Form and upload validation',
                    'FR-10 Input Validation and Exception Handling',
                ],
                'title' => 'FR-10 Input Validation and Exception Handling',
                'sn' => 'sr01',
                'step' => $createStep,
                'priority' => $mustId,
                'statement' => 'The system shall validate all mandatory input fields and file constraints upon submission attempts, blocking invalid entries and displaying descriptive error exceptions.',
                'trigger' => 'When a user attempts to submit a form or upload a file',
                'ac' => "- Invalid or missing mandatory fields block submission with descriptive errors\n- File constraint failures block upload with descriptive errors",
            ],
        ];

        $keepIds = [];
        foreach ($frDefs as $def) {
            $fr = FunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereIn('title', $def['titles'])
                ->first();

            if ($fr === null) {
                $fr = new FunctionalRequirement(['project_id' => $project->id]);
            }

            $fr->fill([
                'title' => $def['title'],
                'stakeholder_need_id' => $sns[$def['sn']]->id,
                'change_request_id' => null,
                'swimlane_flow_step_id' => $def['step']?->id,
                'statement' => $def['statement'],
                'trigger' => $def['trigger'],
                'acceptance_criteria' => $def['ac'],
                'priority_id' => $def['priority'],
                'status_id' => $agreedId,
            ])->save();

            $keepIds[] = $fr->id;
        }

        FunctionalRequirement::query()
            ->where('project_id', $project->id)
            ->whereNotIn('id', $keepIds)
            ->each(fn (FunctionalRequirement $fr) => $fr->delete());
    }

    /**
     * @param  list<string>  $titles
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertBusinessNeed(Project $project, array $titles, array $attributes): BusinessNeed
    {
        $need = BusinessNeed::query()
            ->where('project_id', $project->id)
            ->whereIn('title', $titles)
            ->first();

        if ($need === null) {
            $need = new BusinessNeed(['project_id' => $project->id]);
        }

        $need->fill($attributes)->save();

        return $need->fresh();
    }

    /**
     * @param  list<string>  $titles
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertBusinessObjective(Project $project, array $titles, array $attributes): BusinessObjective
    {
        $objective = BusinessObjective::query()
            ->where('project_id', $project->id)
            ->whereIn('title', $titles)
            ->first();

        if ($objective === null) {
            $objective = new BusinessObjective(['project_id' => $project->id]);
        }

        $objective->fill($attributes)->save();

        return $objective->fresh();
    }
}
