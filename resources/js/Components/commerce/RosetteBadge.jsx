/** Pastille de remise — sobre, texte + couleur (plus de rosette décorative). */
export default function RosetteBadge({ discount, className = '' }) {
    if (!discount || discount <= 0) return null;

    return (
        <span className={['inline-flex items-center rounded-full bg-rust px-2.5 py-0.5 text-xs font-semibold text-white', className].filter(Boolean).join(' ')}>
            <span className="sr-only">Remise de </span>−{discount} %
        </span>
    );
}
