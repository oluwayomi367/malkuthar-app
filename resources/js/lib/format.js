/** Formatage monétaire — identique à `number_format($n, 0, ',', ' ')` côté PHP. */
export function money(n) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(Number(n) || 0));
}
