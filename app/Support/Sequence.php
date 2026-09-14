<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Générateur de compteurs atomiques (table `sequences`).
 *
 * Utilisé pour l'identifiant affilié séquentiel (§32). L'incrément est
 * protégé par un verrou pessimiste : deux activations d'affilié
 * simultanées ne peuvent pas obtenir le même numéro.
 */
class Sequence
{
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $row = DB::table('sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('sequences')->insert([
                    'key' => $key,
                    'value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = (int) $row->value + 1;

            DB::table('sequences')
                ->where('key', $key)
                ->update(['value' => $next, 'updated_at' => now()]);

            return $next;
        });
    }
}
