<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stakeholder type moves from four combined onion layers to one specific type per option.
     * Precedence: default-role key, the old role text kept in notes, then the stakeholder name.
     * A row that had a layer but cannot be placed becomes "other"; rows with no type stay empty.
     */
    public function up(): void
    {
        $byOldRole = [
            'business analyst' => 'business_analyst',
            'implementation subject matter expert' => 'implementation_sme',
            'sponsor' => 'sponsor',
            'domain subject matter expert' => 'domain_sme',
            'end user' => 'end_user',
            'operation' => 'operational_support',
            'operational support, domain subject matter expert' => 'operational_support',
        ];

        $byName = [
            'dx team' => 'implementation_sme', 'omar san' => 'sponsor', 'top management' => 'executive',
            'shift software' => 'supplier', 'delaer uselect teams' => 'customer',
            'parts field agent' => 'end_user', 'parts field team' => 'end_user',
            'parts procurement lead' => 'domain_sme', 'parts planning team' => 'operational_support',
        ];

        foreach (DB::table('stakeholders')->get(['id', 'name', 'type', 'system_key', 'notes']) as $row) {
            if ($row->type === null || $row->type === '') {
                continue;
            }

            $oldRole = null;
            if (preg_match('/^Role: (.+)$/mi', (string) $row->notes, $m)) {
                $oldRole = strtolower(trim($m[1]));
            }

            $type = $row->system_key
                ?: ($byOldRole[$oldRole] ?? null)
                ?: ($byName[strtolower(trim($row->name))] ?? null)
                ?: 'other';

            DB::table('stakeholders')->where('id', $row->id)->update(['type' => $type]);
        }
    }

    public function down(): void
    {
        // Data normalisation only.
    }
};
