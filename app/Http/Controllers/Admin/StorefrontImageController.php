<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\StorefrontImages;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Images de la vitrine : chaque emplacement (bannières, tuiles, photos des
 * pages publiques et des pages de connexion) peut être remplacé ou remis à
 * son image d'origine. Voir App\Support\StorefrontImages.
 */
class StorefrontImageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Storefront/Images', [
            'groups' => StorefrontImages::grouped(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        abort_unless(StorefrontImages::exists($key), 404);

        $request->validate([
            'image' => ['required', 'file', 'max:5120', function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }
                $ext = strtolower($value->getClientOriginalExtension());
                if (! in_array($ext, ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'avif', 'gif'], true)) {
                    $fail('Le fichier doit être une image JPG, PNG, GIF, WebP ou AVIF.');
                }
            }],
        ], [
            'image.required' => 'Choisissez une image à envoyer.',
            'image.max' => "L'image ne doit pas dépasser 5 Mo.",
        ]);

        $previous = StorefrontImages::custom($key);
        $path = $this->storeUpload($request->file('image'));
        StorefrontImages::set($key, $path);
        StorefrontImages::deleteFile($previous);

        $this->audit->record($request->user(), 'storefront.image_updated', null, [
            'slot' => $key,
            'path' => $path,
        ], 'Image de la vitrine remplacée : '.StorefrontImages::SLOTS[$key][1], $request);

        return back()->with('success', 'Image mise à jour : '.StorefrontImages::SLOTS[$key][1].'.');
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        abort_unless(StorefrontImages::exists($key), 404);

        StorefrontImages::deleteFile(StorefrontImages::custom($key));
        StorefrontImages::set($key, null);

        $this->audit->record($request->user(), 'storefront.image_reset', null, [
            'slot' => $key,
        ], "Image de la vitrine remise à l'origine : ".StorefrontImages::SLOTS[$key][1], $request);

        return back()->with('success', "Image d'origine rétablie : ".StorefrontImages::SLOTS[$key][1].'.');
    }

    private function storeUpload(UploadedFile $file): string
    {
        $dir = public_path(trim(StorefrontImages::UPLOAD_PREFIX, '/'));
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'jpg'));
        $ext = in_array($ext, ['jpeg', 'jpe', 'jfif'], true) ? 'jpg' : $ext;
        $name = Str::uuid()->toString().'.'.$ext;
        $file->move($dir, $name);

        return StorefrontImages::UPLOAD_PREFIX.$name;
    }
}
