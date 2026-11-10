<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stakeholder type becomes a BABOK onion-diagram layer (external / organization / unit / delivery).
     *
     * Precedence: system_key, then the old type text, then the stakeholder name. Rows that cannot be
     * placed are left empty for the BA to classify. The old role text is kept in notes.
     */
    public function up(): void
    {
        $bySystemKey = [
            'business_analyst' => 'delivery', 'project_manager' => 'delivery', 'tester' => 'delivery',
            'implementation_sme' => 'delivery', 'customer' => 'external', 'supplier' => 'external',
            'regulator' => 'external', 'sponsor' => 'organization', 'domain_sme' => 'organization',
            'end_user' => 'unit', 'operational_support' => 'unit',
        ];

        $byOldType = [
            'business analyst' => 'delivery', 'implementation subject matter expert' => 'delivery',
            'sponsor' => 'organization', 'domain subject matter expert' => 'organization',
            'end user' => 'unit', 'operation' => 'unit',
            'operational support, domain subject matter expert' => 'unit',
        ];

        $byName = [
            'dx team' => 'delivery', 'delaer uselect teams' => 'external', 'omar san' => 'organization', 'top management' => 'organization',
            'shift software' => 'external', 'brokers' => 'external',
            'parts field agent' => 'unit', 'parts procurement lead' => 'unit',
            'parts planning team' => 'unit', 'parts field team' => 'unit',
            'administration department' => 'unit', 'tiq leasing department' => 'unit',
            'preowned vehicles team' => 'unit',
        ];

        foreach (DB::table('stakeholders')->get(['id', 'name', 'type', 'system_key', 'notes']) as $row) {
            $oldType = trim((string) $row->type);
            $oldKey = strtolower($oldType);

            $layer = ($row->system_key ? ($bySystemKey[$row->system_key] ?? null) : null)
                ?? ($byOldType[$oldKey] ?? null)
                ?? ($byName[strtolower(trim($row->name))] ?? null);

            $notes = $row->notes;
            $keepOldText = $oldType !== '' && $oldKey !== 'role' && ! $row->system_key;
            if ($keepOldText && stripos((string) $notes, 'Role: '.$oldType) === false) {
                $notes = trim(($notes ? $notes."\n" : '').'Role: '.$oldType);
            }

            DB::table('stakeholders')->where('id', $row->id)->update([
                'type' => $layer,
                'notes' => $notes,
            ]);
        }
    }

    public function down(): void
    {
        // Data normalisation only; the old free-text role is preserved in notes.
    }
};
