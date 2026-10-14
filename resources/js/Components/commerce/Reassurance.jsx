const DELIVERY = {
    digital: 'Livraison digitale immédiate après paiement',
    physical: 'Expédition suivie, numéro de suivi communiqué',
    service: 'Lien de visioconférence ou lieu confirmé à la réservation',
    training: 'Accès au contenu ouvert après paiement',
    default: 'Livraison digitale immédiate · expédition suivie pour le physique',
};

const ICONS = {
    pay: 'M3 7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5zM3 10h18M7 14.5h3',
    delivery: 'M3 6.5h11v9H3zM14 9.5h3.5l2.5 3v3h-6M7 18.5a1.5 1.5 0 1 0 0-.01M17 18.5a1.5 1.5 0 1 0 0-.01',
    support: 'M4.5 6.5A1.5 1.5 0 0 1 6 5h12a1.5 1.5 0 0 1 1.5 1.5v8A1.5 1.5 0 0 1 18 16h-7l-4 3.5V16H6a1.5 1.5 0 0 1-1.5-1.5z',
};

/**
 * Trois lignes de réassurance, icônes en trait.
 * `layout="row"` (défaut) : trois colonnes dès sm. `layout="stack"` : liste compacte (bloc d'achat).
 */
export default function Reassurance({ variant = 'default', layout = 'row', items: custom = null, className = '' }) {
    const items = custom ?? [
        ['pay', 'Paiement sécurisé', 'FedaPay · CinetPay — Mobile Money et carte'],
        ['delivery', 'Livraison', DELIVERY[variant] ?? DELIVERY.default],
        ['support', 'Support', 'Une équipe joignable en cas de besoin'],
    ];

    const stack = layout === 'stack';

    return (
        <ul className={[stack ? 'space-y-3' : 'grid gap-5 sm:grid-cols-3', className].filter(Boolean).join(' ')}>
            {items.map(([icon, title, text]) => (
                <li key={title} className="flex gap-3">
                    <svg className="mt-0.5 h-5 w-5 shrink-0 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <path d={ICONS[icon] ?? ICONS.support} />
                    </svg>
                    <p className="text-sm leading-snug text-ink-2">
                        <span className="font-semibold text-ink">{title}</span>
                        {stack ? ' — ' : <br />}
                        {text}
                    </p>
                </li>
            ))}
        </ul>
    );
}
