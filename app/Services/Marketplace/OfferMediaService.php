<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Support\MarketplaceSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — gestion des visuels d'une offre (image principale + galerie
 * + images de variantes). Les fichiers sont déplacés vers
 * `public/uploads/marketplace/offers/` et le chemin relatif est renvoyé.
 *
 * Le traitement des fichiers se fait AVANT OfferWriter : la révision d'une
 * offre ne transporte que des chemins, jamais des uploads.
 */
class OfferMediaService
{
    private const DIR = 'uploads/marketplace/offers';

    /**
     * Applique les changements d'images à un tableau de données d'offre.
     *
     * @param  array<string, mixed>  $data
     * @param  array{main_image?: ?UploadedFile, gallery?: array<int, UploadedFile>, gallery_remove?: array<int, string>, remove_main_image?: bool}  $files
     * @return array<string, mixed>
     */
    public function apply(?MarketplaceOffer $offer, array $data, array $files): array
    {
        $gallery = $offer?->gallery ?? [];

        // Retraits de la galerie.
        foreach ($files['gallery_remove'] ?? [] as $path) {
            $gallery = array_values(array_filter($gallery, fn ($p) => $p !== $path));
            $this->delete($path);
        }

        // Ajouts à la galerie (borne max).
        $max = MarketplaceSettings::maxGalleryImages();
        foreach (array_slice($files['gallery'] ?? [], 0, max(0, $max - count($gallery))) as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $gallery[] = $this->store($file);
            }
        }

        // Image principale.
        $main = $offer?->main_image_path;
        if (! empty($files['remove_main_image'])) {
            $this->delete($main);
            $main = null;
        }
        if (($files['main_image'] ?? null) instanceof UploadedFile && $files['main_image']->isValid()) {
            $this->delete($main);
            $main = $this->store($files['main_image']);
        }

        // À défaut d'image principale, promeut la 1re de la galerie.
        if ($main === null && $gallery !== []) {
            $main = $gallery[0];
        }

        $data['main_image_path'] = $main;
        $data['gallery'] = array_values($gallery);

        return $data;
    }

    public function storeVariantImage(?UploadedFile $file): ?string
    {
        return $file instanceof UploadedFile && $file->isValid() ? $this->store($file) : null;
    }

    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR) && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    private function store(UploadedFile $file): string
    {
        $dir = public_path(self::DIR);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $name = Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($dir, $name);

        return self::DIR.'/'.$name;
    }
}
