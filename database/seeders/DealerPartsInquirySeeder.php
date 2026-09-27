<?php

namespace Database\Seeders;

use App\Models\Architecture;
use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Models\DataDictionary;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Risk;
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
use App\Support\NfrCategory;
use App\Support\ScopeItemDirection;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Seeder;

/**
 * Seed the real Dealer Parts Inquiry project (TIQ-DPI) from the requirements document.
 *
 * Idempotent via updateOrCreate on stable titles. Removes invented filler that is
 * not in the source document (assumptions, risks, architecture). Scope/features
 * already on the project are aligned, not wiped.
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
                'description' => 'Replace fragmented WhatsApp/email/phone parts inquiry channels with a centralized digital platform that is the official record of truth for dealer-to-TIQ Parts Field and Parts Planning interactions.',
                'status_id' => $agreedId,
            ],
        );

        app(SystemStakeholderSeeder::class)->seedForProject($project);

        $this->purgeNonDocumentArtifacts($project);

        $this->seedStrategy($project);
        $sns = $this->seedNeedSpine($project, $agreedId, $mustId, $shouldId, $couldId);
        $this->seedConstraintsAndRules($project);
        $this->seedNonFunctionalRequirements($project, $mustId, $shouldId, $agreedId, $sns);
        $this->seedStateFlow($project, $agreedId);
        $this->seedDataDictionary($project, $agreedId);
        $steps = $this->seedSwimlane($project, $agreedId, $sns);
        $this->seedFunctionalRequirements($project, $mustId, $shouldId, $couldId, $agreedId, $steps, $sns);
        $this->alignScope($project);
    }

    /**
     * Remove artifacts not present in the source requirements (earlier seeder filler).
     */
    protected function purgeNonDocumentArtifacts(Project $project): void
    {
        Assumption::query()->where('project_id', $project->id)->each(
            fn (Assumption $row) => $row->delete(),
        );

        Risk::query()->where('project_id', $project->id)->each(
            fn (Risk $row) => $row->delete(),
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
A single centralized digital platform is the official record of truth for dealer-to-TIQ parts inquiries. Inquiries are classified by enquiry type (EPC, Parts Availability, Ordering, ETA, and further configured types), with Main Type set automatically to Planning or Field. Attachments and threaded multi-round TIQ/dealer correspondence are preserved. Parts Field and Parts Planning share the same inquiry workspace, and users reach the system from the Hub via Support or Services.
TXT,
                'change_strategy' => <<<'TXT'
Deliver DPI so the Hub provides a shortcut into either the Support system or the Services system, implemented as a ticket type consistent with the existing ticket model. Make the digital channel the official path for dealer parts inquiries. Use KPI dashboards (volume, response time, resolution rate) from day one. Do not split TIQ access by enquiry type or main type.
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
                'notes' => 'Internal users — respond to Field inquiries (including EPC); consume reports together with Parts Planning. Shared access to all enquiry types.',
            ],
        );

        $partsPlanning = Stakeholder::query()->updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Parts Planning Team', 'is_system' => false],
            [
                'type' => 'role',
                'influence' => 'high',
                'interest' => 'high',
                'status_id' => $agreedId,
                'notes' => 'Internal users — respond to part-number availability, ordering, and ETA inquiries. Share the same inquiry workspace as the Parts Field team (no access split by type).',
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

        $sn03 = $this->upsertStakeholderNeed(
            $project,
            titles: [
                'Centralized communication history on each inquiry',
            ],
            attributes: [
                'title' => 'Centralized communication history on each inquiry',
                'description' => 'Dealers and TIQ Parts Field and Parts Planning require all status updates and follow-up communications, including multiple TIQ and dealer responses, to be permanently linked to the original inquiry.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn03->businessObjectives()->sync([$bo02->id]);
        $sn03->stakeholders()->sync([$dealers->id, $partsField->id, $partsPlanning->id]);

        $sn04 = $this->upsertStakeholderNeed(
            $project,
            titles: [
                'Filter inquiries by region, branch, and type',
                'Segregate inquiries by region and branch',
            ],
            attributes: [
                'title' => 'Filter inquiries by region, branch, and type',
                'description' => 'The Parts Field and Parts Planning teams require the ability to filter and group inquiries by Region, Branch, Enquiry Type, and Main Type (Planning or Field). Access shall not be segregated by enquiry type or main type.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn04->businessObjectives()->sync([$bo02->id]);
        $sn04->stakeholders()->sync([$partsField->id, $partsPlanning->id]);

        $sn05 = $this->upsertStakeholderNeed(
            $project,
            titles: [
                'System-enforced inquiry lifecycle',
            ],
            attributes: [
                'title' => 'System-enforced inquiry lifecycle',
                'description' => 'The Parts Field and Parts Planning teams require a system-enforced workflow so every inquiry follows a standardized lifecycle (Open > multiple TIQ/Dealer responses > Close).',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn05->businessObjectives()->sync([$bo01->id]);
        $sn05->stakeholders()->sync([$partsField->id, $partsPlanning->id]);

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

        $sn08 = $this->upsertStakeholderNeed(
            $project,
            titles: ['Enquiry type capture'],
            attributes: [
                'title' => 'Enquiry type capture',
                'description' => 'Dealers require the ability to classify each inquiry by type (including EPC, Parts Availability, Ordering, and ETA) so the request is associated with the correct operational main type.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn08->businessObjectives()->sync([$bo01->id]);
        $sn08->stakeholders()->sync([$dealers->id]);

        $sn09 = $this->upsertStakeholderNeed(
            $project,
            titles: ['Automatic main type selection'],
            attributes: [
                'title' => 'Automatic main type selection',
                'description' => 'TIQ teams require the system to automatically set the main type to Planning or Field based on the selected enquiry type, without a separate manual choice.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn09->businessObjectives()->sync([$bo01->id]);
        $sn09->stakeholders()->sync([$partsField->id, $partsPlanning->id]);

        $sn10 = $this->upsertStakeholderNeed(
            $project,
            titles: ['Shared access across enquiry types'],
            attributes: [
                'title' => 'Shared access across enquiry types',
                'description' => 'Parts Field and Parts Planning require full shared access to all inquiries regardless of enquiry type or main type. Type is used for classification, routing hints, and filtering only — not for access control.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn10->businessObjectives()->sync([$bo02->id]);
        $sn10->stakeholders()->sync([$partsField->id, $partsPlanning->id]);

        $sn11 = $this->upsertStakeholderNeed(
            $project,
            titles: ['Hub shortcut to Support or Services'],
            attributes: [
                'title' => 'Hub shortcut to Support or Services',
                'description' => 'Users require a Hub shortcut to the Dealer Parts Inquiry system that opens the new system from either the Support system or the Services system.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn11->businessObjectives()->sync([$bo01->id]);
        $sn11->stakeholders()->sync([$dealers->id, $partsField->id, $partsPlanning->id]);

        $sn12 = $this->upsertStakeholderNeed(
            $project,
            titles: ['Multi-round TIQ and dealer correspondence'],
            attributes: [
                'title' => 'Multi-round TIQ and dealer correspondence',
                'description' => 'Dealers and TIQ teams require the ability to exchange multiple TIQ and dealer responses on the same inquiry before closure, preserving the full chronological thread.',
                'priority_id' => $mustId,
                'status_id' => $agreedId,
            ],
        );
        $sn12->businessObjectives()->sync([$bo02->id]);
        $sn12->stakeholders()->sync([$dealers->id, $partsField->id, $partsPlanning->id]);

        $sns = [
            'sr01' => $sn01,
            'sr02' => $sn02,
            'sr03' => $sn03,
            'sr04' => $sn04,
            'sr05' => $sn05,
            'sr06' => $sn06,
            'sr07' => $sn07,
            'sr08' => $sn08,
            'sr09' => $sn09,
            'sr10' => $sn10,
            'sr11' => $sn11,
            'sr12' => $sn12,
        ];

        StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->whereNotIn('id', array_map(fn (StakeholderNeed $sn) => $sn->id, $sns))
            ->each(fn (StakeholderNeed $sn) => $sn->delete());

        return $sns;
    }

    protected function seedConstraintsAndRules(Project $project): void
    {
        // Constraint/BusinessRule models have no priority_id; Priority is recorded in source.
        $constraints = [
            [
                'title' => 'NFR-01: Security & Data Isolation',
                'description' => 'The system shall enforce database-level branch isolation for Dealers (assigned Branch ID only) and grant full cross-regional visibility to TIQ Admin and Parts Management. Parts Field and Parts Planning share all inquiries. Enquiry type and main type shall not be used as access-control attributes; type filters are optional views only.',
                'source' => 'DPI NFR-01 (Must)',
                'aliases' => ['NFR-01: Role-based data isolation'],
            ],
            [
                'title' => 'NFR-02: Hub / Support / Services',
                'description' => 'The new system shall be reachable from the Hub. The Hub shall provide a shortcut that takes the user to Dealer Parts Inquiry through either the Support system or the Services system. DPI shall be implemented as another ticket type within Support and/or Services, consistent with the existing ticket model.',
                'source' => 'DPI NFR-02 (Must)',
                'aliases' => [
                    'NFR-02: Architectural Integration',
                    'NFR-02: Modular Support ticket architecture',
                ],
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
                'description' => 'Ticket status changes shall follow: Create→Open (Dealer); Open→TIQ Responded or Closed (TIQ Field or Planning); TIQ Responded→Dealer Responded (Dealer follow-up), TIQ Responded (further TIQ response), or Closed (TIQ); Dealer Responded→TIQ Responded (new TIQ answer), Dealer Responded (further dealer response), or Closed (TIQ). The TIQ ↔ Dealer cycle may repeat until Closed. Closed tickets reject all further edits, comments, and attachments.',
                'source' => 'DPI BR-01 (Must)',
                'aliases' => [
                    'Allowed inquiry status transitions',
                    'BR-DPI-Lifecycle: allowed status transitions',
                ],
            ],
            [
                'title' => 'BR-02: Thread Integrity',
                'description' => 'Each ticket shall enforce a single, non-editable, non-deletable chronological communication thread where every status change and message is automatically timestamped and logged. Consecutive messages from the same party remain on the thread without requiring a status change.',
                'source' => 'DPI BR-02 (Must)',
                'aliases' => [
                    'Immutable chronological inquiry thread',
                    'BR-DPI-Thread: immutable chronological thread',
                ],
            ],
            [
                'title' => 'BR-03: Creation Validation',
                'description' => 'A dealer inquiry ticket shall be created only when all mandatory data elements—Part Number, Dealer ID, Branch, Enquiry Type, and Inquiry Description—are present and valid. Enquiry Type shall be selected from the catalog (EPC, Parts Availability, Ordering, ETA, and further configured types).',
                'source' => 'DPI BR-03 (Must)',
                'aliases' => [
                    'Mandatory fields for ticket creation',
                    'BR-DPI-Mandatory: ticket create fields',
                ],
            ],
            [
                'title' => 'BR-04: Enquiry Type to Main Type Mapping',
                'description' => 'Upon selection of an enquiry type, Main Type is set automatically: EPC → Field; Parts Availability → Planning; Ordering → Planning; ETA → Planning. Additional types follow their configured mapping. The dealer shall not select the main type manually. Mapping does not restrict TIQ access.',
                'source' => 'DPI FR-12 (Must)',
                'aliases' => [],
            ],
        ];

        $keepRuleIds = [];
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
                $keepRuleIds[] = $existing->id;
            } else {
                $created = BusinessRule::query()->create([
                    'project_id' => $project->id,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => BusinessRuleStatus::ACTIVE,
                    'source' => $row['source'],
                ]);
                $keepRuleIds[] = $created->id;
            }
        }

        BusinessRule::query()
            ->where('project_id', $project->id)
            ->whereNotIn('id', $keepRuleIds)
            ->each(fn (BusinessRule $row) => $row->delete());
    }

    protected function seedDataDictionary(Project $project, int $agreedId): void
    {
        DataDictionary::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'DPI inquiry data dictionary',
            ],
            [
                'description' => 'Business data for dealer parts inquiry tickets. Mandatory create fields follow FR-01 / BR-03. Enquiry Type maps automatically to Main Type (Planning or Field).',
                'status_id' => $agreedId,
                'entities' => [
                    [
                        'name' => 'Dealer',
                        'meaning' => 'Selling partner who raises parts inquiries.',
                        'fields' => [
                            ['name' => 'dealer_id', 'meaning' => 'Dealer identifier', 'type' => '', 'is_pk' => true, 'references' => ''],
                            ['name' => 'name', 'meaning' => 'Dealer trading name', 'type' => '', 'is_pk' => false, 'references' => ''],
                            ['name' => 'region', 'meaning' => 'Operating region', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                    [
                        'name' => 'Branch',
                        'meaning' => 'Dealer location that owns the inquiry.',
                        'fields' => [
                            ['name' => 'branch_id', 'meaning' => 'Branch identifier', 'type' => '', 'is_pk' => true, 'references' => ''],
                            ['name' => 'dealer_id', 'meaning' => 'Owning dealer', 'type' => '', 'is_pk' => false, 'references' => 'Dealer'],
                            ['name' => 'name', 'meaning' => 'Branch name', 'type' => '', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                    [
                        'name' => 'EnquiryType',
                        'meaning' => 'Configurable catalog of dealer enquiry types. Each type maps to exactly one main type.',
                        'fields' => [
                            ['name' => 'code', 'meaning' => 'Type code (EPC, PARTS_AVAILABILITY, ORDERING, ETA, …)', 'type' => 'string', 'is_pk' => true, 'references' => ''],
                            ['name' => 'label', 'meaning' => 'Display name', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                            ['name' => 'main_type', 'meaning' => 'Planning or Field (automatic assignment target)', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                    [
                        'name' => 'Inquiry',
                        'meaning' => 'Official ticket for a dealer-to-TIQ parts question.',
                        'fields' => [
                            ['name' => 'inquiry_id', 'meaning' => 'Ticket identifier', 'type' => '', 'is_pk' => true, 'references' => ''],
                            ['name' => 'dealer_id', 'meaning' => 'Submitting dealer (mandatory)', 'type' => '', 'is_pk' => false, 'references' => 'Dealer'],
                            ['name' => 'branch_id', 'meaning' => 'Submitting branch (mandatory)', 'type' => '', 'is_pk' => false, 'references' => 'Branch'],
                            ['name' => 'enquiry_type', 'meaning' => 'Catalog type: EPC, Parts Availability, Ordering, ETA, or another configured type (mandatory)', 'type' => 'string', 'is_pk' => false, 'references' => 'EnquiryType'],
                            ['name' => 'main_type', 'meaning' => 'Planning or Field; set automatically from enquiry_type; not an access-control attribute', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                            ['name' => 'description', 'meaning' => 'Inquiry description (mandatory)', 'type' => '', 'is_pk' => false, 'references' => ''],
                            ['name' => 'status', 'meaning' => 'Open, TIQ Responded, Dealer Responded, or Closed', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                            ['name' => 'submitted_at', 'meaning' => 'When the dealer created the ticket', 'type' => '', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                    [
                        'name' => 'InquiryLineItem',
                        'meaning' => 'A part asked about on the ticket.',
                        'fields' => [
                            ['name' => 'item_id', 'meaning' => 'Line identifier', 'type' => '', 'is_pk' => true, 'references' => ''],
                            ['name' => 'inquiry_id', 'meaning' => 'Parent ticket', 'type' => '', 'is_pk' => false, 'references' => 'Inquiry'],
                            ['name' => 'part_number', 'meaning' => 'Part number (mandatory on create)', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                            ['name' => 'quantity', 'meaning' => 'Requested quantity when applicable', 'type' => '', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                    [
                        'name' => 'InquiryAttachment',
                        'meaning' => 'Image or PDF supporting the inquiry (FR-02).',
                        'fields' => [
                            ['name' => 'attachment_id', 'meaning' => 'Attachment identifier', 'type' => '', 'is_pk' => true, 'references' => ''],
                            ['name' => 'inquiry_id', 'meaning' => 'Parent ticket', 'type' => '', 'is_pk' => false, 'references' => 'Inquiry'],
                            ['name' => 'file_name', 'meaning' => 'Original file name', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                            ['name' => 'notes', 'meaning' => 'Optional caption from the dealer', 'type' => '', 'is_pk' => false, 'references' => ''],
                        ],
                    ],
                ],
            ],
        );
    }

    protected function seedStateFlow(Project $project, int $agreedId): void
    {
        StateFlow::query()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'DPI inquiry lifecycle'],
            [
                'description' => 'FR-04 / FR-15 state definitions: multi-round TIQ (Field or Planning) and dealer responses until Closed.',
                'status_id' => $agreedId,
                'transitions' => [
                    ['from' => '(none)', 'to' => 'Open', 'trigger' => 'Dealer creates ticket and selects enquiry type'],
                    ['from' => 'Open', 'to' => 'TIQ Responded', 'trigger' => 'TIQ Field or Planning provides answer or requests more info'],
                    ['from' => 'Open', 'to' => 'Closed', 'trigger' => 'TIQ Field or Planning resolves/closes'],
                    ['from' => 'TIQ Responded', 'to' => 'TIQ Responded', 'trigger' => 'TIQ adds a further response'],
                    ['from' => 'TIQ Responded', 'to' => 'Dealer Responded', 'trigger' => 'Dealer provides follow-up'],
                    ['from' => 'TIQ Responded', 'to' => 'Closed', 'trigger' => 'TIQ accepts resolution / closes'],
                    ['from' => 'Dealer Responded', 'to' => 'Dealer Responded', 'trigger' => 'Dealer adds a further response'],
                    ['from' => 'Dealer Responded', 'to' => 'TIQ Responded', 'trigger' => 'TIQ Field or Planning provides a new answer'],
                    ['from' => 'Dealer Responded', 'to' => 'Closed', 'trigger' => 'TIQ Field or Planning resolves/closes'],
                ],
            ],
        );
    }

    /**
     * Swimlane: Dealer, Hub, Support/Services, and one shared Field/Planning lane.
     * Field and Planning execute the same steps with no split paths.
     *
     * @param  array<string, StakeholderNeed>  $sns
     * @return array<string, SwimlaneFlowStep>
     */
    protected function seedSwimlane(Project $project, int $agreedId, array $sns): array
    {
        SwimlaneFlow::withTrashed()
            ->where('project_id', $project->id)
            ->get()
            ->each(function (SwimlaneFlow $old): void {
                if ($old->title !== 'DPI inquiry handling') {
                    SwimlaneFlowStep::withTrashed()
                        ->where('swimlane_flow_id', $old->id)
                        ->forceDelete();
                    $old->forceDelete();
                }
            });

        SwimlaneFlowStep::withTrashed()
            ->where('project_id', $project->id)
            ->forceDelete();

        $flow = SwimlaneFlow::withTrashed()
            ->where('project_id', $project->id)
            ->where('title', 'DPI inquiry handling')
            ->first();

        if ($flow === null) {
            $flow = new SwimlaneFlow([
                'project_id' => $project->id,
                'title' => 'DPI inquiry handling',
            ]);
        } elseif ($flow->trashed()) {
            $flow->restore();
        }

        $flow->fill([
            'description' => 'Dealer creates a typed inquiry from Hub (Support or Services). Main type is auto-assigned. Parts Field and Parts Planning share one lane and the same steps; either person can review, respond, or close.',
            'direction' => 'TB',
            'elements' => null,
            'status_id' => $agreedId,
        ])->save();

        $tiq = 'Parts Field / Planning';

        $definitions = [
            ['lane' => 'Dealer', 'from' => null, 'type' => 'start', 'label' => 'Need parts inquiry', 'line_title' => null, 'sn' => null, 'color' => 'blue'],
            ['lane' => 'Dealer', 'from' => 'Need parts inquiry', 'type' => 'process', 'label' => 'Create ticket', 'line_title' => null, 'sn' => $sns['sr01']->id, 'color' => 'blue'],
            ['lane' => 'Dealer', 'from' => 'Create ticket', 'type' => 'process', 'label' => 'Attach images or PDFs', 'line_title' => null, 'sn' => $sns['sr02']->id, 'color' => 'blue'],
            ['lane' => 'Hub', 'from' => 'Attach images or PDFs', 'type' => 'process', 'label' => 'Open Hub shortcut', 'line_title' => null, 'sn' => $sns['sr11']->id, 'color' => 'lavender'],
            ['lane' => 'Hub', 'from' => 'Open Hub shortcut', 'type' => 'process', 'label' => 'Go to Support or Services', 'line_title' => null, 'sn' => $sns['sr11']->id, 'color' => 'lavender'],
            ['lane' => 'Support / Services', 'from' => 'Go to Support or Services', 'type' => 'process', 'label' => 'Open ticket and notify TIQ', 'line_title' => null, 'sn' => $sns['sr09']->id, 'color' => 'mint'],
            ['lane' => $tiq, 'from' => 'Open ticket and notify TIQ', 'type' => 'process', 'label' => 'Review inquiry', 'line_title' => null, 'sn' => $sns['sr10']->id, 'color' => 'peach'],
            ['lane' => $tiq, 'from' => 'Review inquiry', 'type' => 'decision', 'label' => 'Need more information?', 'line_title' => null, 'sn' => $sns['sr12']->id, 'color' => 'peach'],
            ['lane' => $tiq, 'from' => 'Need more information?', 'type' => 'process', 'label' => 'Request more information', 'line_title' => 'Yes', 'sn' => $sns['sr12']->id, 'color' => 'peach'],
            ['lane' => 'Dealer', 'from' => 'Request more information', 'type' => 'process', 'label' => 'Provide follow-up', 'line_title' => null, 'sn' => $sns['sr12']->id, 'color' => 'blue'],
            ['lane' => 'Dealer', 'from' => 'Provide follow-up', 'type' => 'process', 'label' => 'Add further dealer response', 'line_title' => null, 'sn' => $sns['sr12']->id, 'color' => 'blue'],
            ['lane' => $tiq, 'from' => 'Provide follow-up', 'type' => 'process', 'label' => 'Add further TIQ response', 'line_title' => null, 'sn' => $sns['sr12']->id, 'color' => 'peach'],
            ['lane' => $tiq, 'from' => 'Provide follow-up', 'type' => 'process', 'label' => 'Answer and close', 'line_title' => null, 'sn' => $sns['sr05']->id, 'color' => 'peach'],
            ['lane' => $tiq, 'from' => 'Need more information?', 'type' => 'process', 'label' => 'Resolve and close', 'line_title' => 'No', 'sn' => $sns['sr05']->id, 'color' => 'peach'],
            ['lane' => 'Support / Services', 'from' => 'Resolve and close', 'type' => 'end', 'label' => 'Inquiry closed', 'line_title' => null, 'sn' => $sns['sr03']->id, 'color' => 'mint'],
            ['lane' => 'Support / Services', 'from' => 'Answer and close', 'type' => 'end', 'label' => 'Inquiry closed after follow-up', 'line_title' => null, 'sn' => $sns['sr03']->id, 'color' => 'mint'],
        ];

        $byLabel = [];
        foreach ($definitions as $index => $def) {
            $step = SwimlaneFlowStep::query()->create([
                'swimlane_flow_id' => $flow->id,
                'project_id' => $project->id,
                'position' => $index,
                'lane' => $def['lane'],
                'lane_color' => $def['color'],
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
        $hubStep = $steps['Open Hub shortcut'] ?? null;
        $furtherStep = $steps['Add further TIQ response'] ?? null;

        $frDefs = [
            [
                'titles' => ['FR-01 Ticket creation with mandatory fields'],
                'title' => 'FR-01 Ticket creation with mandatory fields',
                'sn' => 'sr01',
                'step' => $createStep,
                'priority' => $mustId,
                'statement' => 'The system shall allow Dealers to create inquiries. Mandatory fields include Part Number, Dealer ID, Branch, Enquiry Type, and Inquiry Description. Enquiry Type shall be selected from the defined catalog (EPC, Parts Availability, Ordering, ETA, and any additional configured types).',
                'trigger' => 'When a Dealer submits a new parts inquiry',
                'ac' => "- Ticket is created in Open when all mandatory fields are present\n- Enquiry Type is selected from the catalog\n- Submission is blocked with clear errors when mandatory fields are missing (FR-10)",
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
                'statement' => 'The system shall restrict ticket status changes to Open, TIQ Responded, Dealer Responded, and Closed per the approved transition table. TIQ Responded and Dealer Responded may be entered multiple times. Consecutive messages from the same party do not require a status change. Closed is final; no further edits allowed.',
                'trigger' => 'When a user attempts a status change or posts a reply',
                'ac' => "- Illegal transitions are rejected\n- Closed tickets reject further comments and attachments\n- Dealer reply from TIQ Responded sets Dealer Responded\n- Further TIQ or dealer messages on an open ticket are accepted\n- Either Parts Field or Parts Planning may respond or close",
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
                'statement' => 'The internal dashboard shall allow authorized users to filter inquiry data dynamically by Status, Priority, Enquiry Type, Main Type (Planning/Field), Region, Branch, Dealer, and Date Range. Type filters are optional views only and shall not hide inquiries from either TIQ team.',
                'trigger' => 'When an authorized user opens or filters the internal dashboard',
                'ac' => "- Filters can be combined across Status, Priority, Enquiry Type, Main Type, Region, Branch, Dealer, and Date Range\n- Type filters do not restrict Parts Field or Parts Planning access\n- Dealer results still respect branch isolation (NFR-01)",
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
                'statement' => 'The system shall automatically dispatch email and system alerts to the TIQ Parts Field and Parts Planning teams upon new ticket creation, and to Dealers upon any status update or response.',
                'trigger' => 'When a ticket is created or its status is updated or a response is posted',
                'ac' => "- Parts Field and Parts Planning receive email/system alerts on new ticket creation\n- Dealers receive email/system alerts on status updates or responses",
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
            [
                'titles' => ['FR-11 Enquiry type catalog'],
                'title' => 'FR-11 Enquiry type catalog',
                'sn' => 'sr08',
                'step' => $createStep,
                'priority' => $mustId,
                'statement' => 'The system shall present a selectable enquiry-type catalog that includes at least EPC, Parts Availability, Ordering, and ETA. Authorized administrators may add further types. Each type shall be mapped to exactly one main type: Planning or Field.',
                'trigger' => 'When a Dealer selects Enquiry Type on create, or an administrator maintains the catalog',
                'ac' => "- Catalog includes EPC, Parts Availability, Ordering, and ETA\n- Additional types can be added with a Planning or Field mapping\n- Create is blocked if Enquiry Type is missing",
            ],
            [
                'titles' => ['FR-12 Automatic main type assignment'],
                'title' => 'FR-12 Automatic main type assignment',
                'sn' => 'sr09',
                'step' => $openStep,
                'priority' => $mustId,
                'statement' => 'Upon selection of an enquiry type, the system shall automatically set Main Type to Planning or Field according to the type mapping. The dealer shall not select the main type manually. Initial mapping: EPC → Field; Parts Availability → Planning; Ordering → Planning; ETA → Planning.',
                'trigger' => 'When Enquiry Type is selected or changed on an inquiry',
                'ac' => "- EPC sets Main Type to Field\n- Parts Availability, Ordering, and ETA set Main Type to Planning\n- Dealer UI has no Main Type control",
            ],
            [
                'titles' => ['FR-13 No access segregation by type'],
                'title' => 'FR-13 No access segregation by type',
                'sn' => 'sr10',
                'step' => $reviewStep,
                'priority' => $mustId,
                'statement' => 'The system shall not restrict TIQ Parts Field or Parts Planning users from viewing, responding to, or closing inquiries based on enquiry type or main type. Both teams share the same inquiry population.',
                'trigger' => 'When a Parts Field or Parts Planning user opens the inquiry list or a ticket',
                'ac' => "- Field users can open Planning-type inquiries\n- Planning users can open Field-type inquiries\n- Type filters never hide tickets the user is authorized to see as TIQ staff",
            ],
            [
                'titles' => ['FR-14 Hub shortcuts'],
                'title' => 'FR-14 Hub shortcuts',
                'sn' => 'sr11',
                'step' => $hubStep,
                'priority' => $mustId,
                'statement' => 'The Hub shall provide a shortcut to the Dealer Parts Inquiry system. From the Hub, users shall be able to open the new system via either the Support system or the Services system.',
                'trigger' => 'When a user launches DPI from the Hub',
                'ac' => "- Hub exposes a DPI shortcut\n- The shortcut can land in Support or in Services",
            ],
            [
                'titles' => ['FR-15 Multiple TIQ and dealer responses'],
                'title' => 'FR-15 Multiple TIQ and dealer responses',
                'sn' => 'sr12',
                'step' => $furtherStep,
                'priority' => $mustId,
                'statement' => 'The system shall allow multiple TIQ responses and multiple dealer responses on an open inquiry. Consecutive messages from the same party remain on the thread without requiring a status change. A party change (TIQ → Dealer or Dealer → TIQ) updates status per the transition rules. The correspondence loop may repeat until Closed.',
                'trigger' => 'When TIQ or a Dealer posts another message on an open inquiry',
                'ac' => "- Multiple TIQ messages are stored on the same ticket\n- Multiple dealer messages are stored on the same ticket\n- Status follows FR-04 when the responding party changes\n- Either Field or Planning may post TIQ responses",
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
     * @param  array<string, StakeholderNeed>  $sns
     */
    protected function seedNonFunctionalRequirements(
        Project $project,
        int $mustId,
        int $shouldId,
        int $agreedId,
        array $sns,
    ): void {
        $defs = [
            [
                'titles' => ['NFR-01 Role-based access without type isolation', 'NFR-01: Security & Data Isolation'],
                'title' => 'NFR-01 Role-based access without type isolation',
                'sn' => 'sr10',
                'category' => NfrCategory::SECURITY,
                'priority' => $mustId,
                'description' => 'The system shall enforce data isolation at the database level. Dealers are limited to their Branch ID. TIQ Admin/Parts Management have full cross-regional visibility. Parts Field and Parts Planning share all inquiries. Enquiry type and main type shall not be used as access-control attributes.',
                'ac' => "- Dealer users see only their Branch ID\n- Field and Planning can open any enquiry type\n- Type filters do not hide TIQ staff tickets",
            ],
            [
                'titles' => ['NFR-02 Hub / Support / Services entry', 'NFR-02: Modular Architecture (Hub / Support / Services)'],
                'title' => 'NFR-02 Hub / Support / Services entry',
                'sn' => 'sr11',
                'category' => NfrCategory::MAINTAINABILITY,
                'priority' => $mustId,
                'description' => 'The new system shall be reachable from the Hub. The Hub shall provide a shortcut that takes the user to Dealer Parts Inquiry through either the Support system or the Services system. DPI shall be implemented as another ticket type within Support and/or Services.',
                'ac' => "- Hub exposes a DPI shortcut\n- Users can open DPI via Support or via Services",
            ],
            [
                'titles' => ['NFR-03 Dashboard latency', 'NFR-03: Performance'],
                'title' => 'NFR-03 Dashboard latency',
                'sn' => 'sr06',
                'category' => NfrCategory::PERFORMANCE,
                'priority' => $shouldId,
                'description' => 'Dashboard widgets and filtered ticket lists shall load in under 3 seconds under normal concurrent operational load (all current Parts users).',
                'ac' => "- Filtered lists load in under 3 seconds for current Parts users\n- KPI widgets load in under 3 seconds under the same load",
            ],
            [
                'titles' => ['NFR-04 Immutable audit logging', 'NFR-04: Audit Logging'],
                'title' => 'NFR-04 Immutable audit logging',
                'sn' => 'sr03',
                'category' => NfrCategory::COMPLIANCE,
                'priority' => $mustId,
                'description' => 'The system shall maintain an immutable log of all user actions related to ticket state changes, including the User ID, Timestamp, and Action Performed. This log must be exportable only by Authorized Management.',
                'ac' => "- State-change actions store User ID, Timestamp, and Action\n- Only Authorized Management can export the log",
            ],
        ];

        $keepIds = [];
        foreach ($defs as $def) {
            $nfr = NonFunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereIn('title', $def['titles'])
                ->first();

            if ($nfr === null) {
                $nfr = new NonFunctionalRequirement(['project_id' => $project->id]);
            }

            $nfr->fill([
                'title' => $def['title'],
                'stakeholder_need_id' => $sns[$def['sn']]->id,
                'change_request_id' => null,
                'category' => $def['category'],
                'description' => $def['description'],
                'acceptance_criteria' => $def['ac'],
                'priority_id' => $def['priority'],
                'status_id' => $agreedId,
            ])->save();

            $keepIds[] = $nfr->id;
        }

        NonFunctionalRequirement::query()
            ->where('project_id', $project->id)
            ->whereNotIn('id', $keepIds)
            ->each(fn (NonFunctionalRequirement $nfr) => $nfr->delete());
    }

    protected function alignScope(Project $project): void
    {
        $renames = [
            'DPI as a Support system ticket type' => [
                'title' => 'Hub shortcut into Support or Services',
                'description' => 'Users open DPI from the Hub via either the Support system or the Services system. DPI is a ticket type in Support and/or Services.',
                'direction' => ScopeItemDirection::IN,
            ],
        ];

        foreach ($renames as $oldTitle => $attrs) {
            $item = ScopeItem::query()
                ->where('project_id', $project->id)
                ->where('title', $oldTitle)
                ->first();
            if ($item) {
                $item->fill($attrs)->save();
            }
        }

        $ensure = [
            [
                'title' => 'Parts Planning and Field shared inquiry workspace',
                'description' => 'Both TIQ parts teams can view, respond to, and close any enquiry type. Type is classification only.',
                'direction' => ScopeItemDirection::IN,
            ],
            [
                'title' => 'Enquiry type catalog with automatic Planning/Field main type',
                'description' => 'Dealers select EPC, Parts Availability, Ordering, ETA, or another configured type. Main type is assigned automatically.',
                'direction' => ScopeItemDirection::IN,
            ],
            [
                'title' => 'Multi-round TIQ and dealer responses on one ticket',
                'description' => 'The BPD loop allows multiple TIQ and dealer messages before close.',
                'direction' => ScopeItemDirection::IN,
            ],
        ];

        foreach ($ensure as $row) {
            ScopeItem::query()->updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                [
                    'description' => $row['description'],
                    'direction' => $row['direction'],
                ],
            );
        }
    }

    /**
     * @param  list<string>  $titles
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertStakeholderNeed(Project $project, array $titles, array $attributes): StakeholderNeed
    {
        $need = StakeholderNeed::query()
            ->where('project_id', $project->id)
            ->whereIn('title', $titles)
            ->first();

        if ($need === null) {
            $need = new StakeholderNeed(['project_id' => $project->id]);
        }

        $need->fill($attributes)->save();

        return $need->fresh();
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
