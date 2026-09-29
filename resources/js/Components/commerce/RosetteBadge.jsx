export default function RosetteBadge({ discount, className = '' }) {
    if (!discount || discount <= 0) return null;

    return (
        <div className={`relative inline-flex items-center justify-center w-11 h-11 drop-shadow-sm select-none transition-transform group-hover:scale-105 ${className}`}>
            <svg viewBox="0 0 48 48" className="absolute inset-0 w-full h-full text-rust fill-current">
                {/* Scalloped rosette path */}
                <path d="M24 2l3.4 3.7 4.9-1.2 2.1 4.5 4.8 1.4.6 5 4.1 2.8-1 4.9 3 4-2.4 4.4 1.7 4.7-3.7 3.4 0.3 5-4.7 2.1-1.2 4.9-5 0.6-2.6 4.3-4.9-1-3.9 3.2-4.4-2.5-4.6 1.9-3.5-3.6-4.9 1.1-2.2-4.5-4.8-1.3-.7-5-4.1-2.8 1-4.9-3-4 2.4-4.5-1.7-4.6 3.7-3.5-.3-5 4.7-2.1 1.2-4.9 5-.6 2.6-4.3 4.9 1 3.9-3.2 4.4 2.5 4.6-1.9z" />
            </svg>
            <span className="relative z-10 text-[11px] font-black tracking-tight text-white leading-none drop-shadow-xs">
                −{discount}%
            </span>
        </div>
    );
}
