const MAP = {
    pending: ['En attente', 'wait'],
    available: ['Disponible', 'ok'],
    processing: ['En traitement', 'neut'],
    paid: ['Payé', 'neut'],
    cancelled: ['Annulé', 'bad'],
    reversed: ['Repris', 'bad'],
    failed: ['Échoué', 'bad'],
    active: ['Actif', 'ok'],
    suspended: ['Suspendu', 'bad'],
    closed: ['Fermé', 'neut'],
    approved: ['Approuvé', 'ok'],
    rejected: ['Rejeté', 'bad'],
    draft: ['Brouillon', 'neut'],
    pending_review: ['En revue', 'wait'],
    changes_requested: ['À corriger', 'wait'],
    published: ['En ligne', 'ok'],
    archived: ['Archivé', 'neut'],
};

// Le libellé porte l'information ; la pastille de couleur ne fait que la doubler.
const TONES = {
    wait: ['bg-amber-tint text-amber', 'bg-amber'],
    ok: ['bg-sage-soft text-sage', 'bg-sage'],
    neut: ['bg-ground-alt text-ink-2', 'bg-ink-3'],
    bad: ['bg-rust-tint text-rust', 'bg-rust'],
};

export default function StatusPill({ status }) {
    const [label, tone] = MAP[status] ?? [status ? status[0].toUpperCase() + status.slice(1) : '', 'neut'];
    const [pill, dot] = TONES[tone] ?? TONES.neut;

    return (
        <span className={['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold', pill].join(' ')}>
            <span className={['h-1.5 w-1.5 shrink-0 rounded-full', dot].join(' ')} aria-hidden="true" />
            {label}
        </span>
    );
}
