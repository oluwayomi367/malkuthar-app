<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * AuditLogger — §68 : "Qui ? Quoi ? Quand ? Depuis quelle adresse/IP ?
 * Quelle modification ?" (ex : "Administrateur X — modification du statut
 * de l'affilié Y — 19/08/2026 14:32").
 *
 * Écrit une ligne immuable ; jamais de mise à jour d'une ligne existante.
 */
class AuditLogger
{
    public function record(
        ?User $admin,
        string $action,
        ?Model $subject = null,
        array $changes = [],
        ?string $description = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'admin_id' => $admin?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'changes' => $changes ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
