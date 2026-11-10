<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Influence and interest become Low / Medium / High dropdowns. Map the old free text
     * onto those codes; anything that does not fit is moved into notes so nothing is lost.
     */
    public function up(): void
    {
        $map = ['low' => 'low', 'medium' => 'medium', 'high' => 'high', 'medium/high' => 'high'];

        foreach (DB::table('stakeholders')->get(['id', 'influence', 'interest', 'notes']) as $row) {
            $update = [];
            $notes = $row->notes;

            foreach (['influence', 'interest'] as $field) {
                $value = $row->{$field};
                if ($value === null || $value === '') {
                    continue;
                }
                $key = strtolower(trim($value));
                if (isset($map[$key])) {
                    $update[$field] = $map[$key];
                    continue;
                }
                $update[$field] = null;
                $notes = trim(($notes ? $notes."\n" : '').ucfirst($field).': '.$value);
            }

            if ($update !== []) {
                $update['notes'] = $notes;
                DB::table('stakeholders')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Data normalisation only; original free text is not restored.
    }
};
