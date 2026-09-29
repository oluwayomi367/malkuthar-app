const COLOR_HEX_MAP = {
    noir: '#1c1917',
    black: '#1c1917',
    blanc: '#ffffff',
    white: '#ffffff',
    marron: '#78350f',
    brown: '#78350f',
    havane: '#78350f',
    doré: '#ca8a04',
    dore: '#ca8a04',
    or: '#ca8a04',
    gold: '#ca8a04',
    argent: '#94a3b8',
    silver: '#94a3b8',
    gris: '#64748b',
    gray: '#64748b',
    grey: '#64748b',
    bleu: '#1e3a8a',
    blue: '#1e3a8a',
    'bleu nuit': '#0f172a',
    'bleu ciel': '#38bdf8',
    rouge: '#b91c1c',
    red: '#b91c1c',
    vert: '#15803d',
    green: '#15803d',
    olive: '#65a30d',
    jaune: '#eab308',
    yellow: '#eab308',
    orange: '#ea580c',
    rose: '#f43f5e',
    pink: '#f43f5e',
    violet: '#7c3aed',
    purple: '#7c3aed',
    bordeaux: '#881337',
    crème: '#fef3c7',
    creme: '#fef3c7',
    cream: '#fef3c7',
    beige: '#f5f5dc',
    terracotta: '#991b1b',
    ambre: '#d97706',
    cuir: '#854d0e',
};

export function getColorHex(colorName) {
    if (!colorName || typeof colorName !== 'string') return '#78716c';

    const normalized = colorName.trim().toLowerCase();
    if (normalized.startsWith('#')) return normalized;
    if (normalized.startsWith('rgb')) return normalized;

    return COLOR_HEX_MAP[normalized] ?? '#78716c';
}
