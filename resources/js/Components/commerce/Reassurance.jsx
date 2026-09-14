const DELIVERY = {
    digital: 'Livraison digitale immédiate après paiement',
    physical: 'Expédition suivie, numéro de suivi communiqué',
    service: 'Lien de visioconférence ou lieu confirmé à la réservation',
    default: 'Livraison digitale immédiate · expédition suivie pour le physique',
};

export default function Reassurance({ variant = 'default', className = '' }) {
    const items = [
        ['Paiement sécurisé', 'FedaPay · CinetPay — Mobile Money & carte'],
        ['Livraison', DELIVERY[variant] ?? DELIVERY.default],
        ['Support', 'Une équipe joignable en cas de besoin'],
    ];

    return (
        <ul className={['grid gap-4 sm:grid-cols-3', className].filter(Boolean).join(' ')}>
            {items.map(([title, text]) => (
                <li key={title} className="flex gap-3">
                    <span className="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-accent/30 bg-accent/10 text-accent">
                        <svg className="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M3 8.5l3 3 7-8" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <p className="text-[13px] font-semibold text-ink">{title}</p>
                        <p className="text-[13px] text-ink-2">{text}</p>
                    </div>
                </li>
            ))}
        </ul>
    );
}
