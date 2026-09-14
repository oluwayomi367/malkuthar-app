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

const TONES = {
    wait: 'border-amber/30 bg-amber/10 text-amber',
    ok: 'border-brand-green/30 bg-brand-green/10 text-brand-green',
    neut: 'border-black/12 bg-black/[0.04] text-ink-2',
    bad: 'border-rust/30 bg-rust/10 text-rust',
};

export default function StatusPill({ status }) {
    const [label, tone] = MAP[status] ?? [status ? status[0].toUpperCase() + status.slice(1) : '', 'neut'];

    return (
        <span className={['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11.5px] font-semibold', TONES[tone]].join(' ')}>
            <span className="h-1.5 w-1.5 rounded-full bg-current" />{label}
        </span>
    );
}
