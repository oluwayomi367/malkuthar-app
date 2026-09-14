<?php

namespace App\Support;

/**
 * Source unique des trois catégories de produits MALKUTHAR, partagée par
 * le méga-menu « Produits » (navbar) et l'accordéon de la page d'accueil.
 */
class Catalog
{
    /**
     * @return array<int, array{type: string, label: string, short: string, text: string, icon: string, image: string, color: string}>
     */
    public static function categories(): array
    {
        return [
            [
                'type' => 'digital',
                'label' => 'Produits digitaux',
                'short' => 'Ebooks · formations · méditations audio',
                'text' => "Vos écrits, vos cours et vos méditations guidées, livrés à l'instant dès le paiement confirmé — sans stock ni expédition.",
                'icon' => '<path d="M10 3v10m0 0l-3.5-3.5M10 13l3.5-3.5M4 15v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1"/>',
                'image' => 'images/categories/digital.jpg',
                'color' => '#b0892f', // or
            ],
            [
                'type' => 'physical',
                'label' => 'Produits physiques',
                'short' => 'Livres · coffrets · objets',
                'text' => "Livres imprimés, coffrets et objets — gestion du stock et des variantes, expédition suivie jusqu'à la remise du colis.",
                'icon' => '<path d="M3 6l7-3 7 3v8l-7 3-7-3V6z"/><path d="M3 6l7 3 7-3M10 9v9"/>',
                'image' => 'images/categories/physical.jpg',
                'color' => '#b4633c', // terre cuite
            ],
            [
                'type' => 'service',
                'label' => 'Services',
                'short' => 'Séances · consultations · ateliers',
                'text' => "Consultations, séances et ateliers : on réserve un créneau réel selon vos disponibilités, en visioconférence ou en présentiel.",
                'icon' => '<rect x="3.5" y="4.5" width="13" height="12" rx="2"/><path d="M3.5 8h13M7 3v3M13 3v3"/>',
                'image' => 'images/categories/service.jpg',
                'color' => '#5f7355', // sauge
            ],
        ];
    }
}
