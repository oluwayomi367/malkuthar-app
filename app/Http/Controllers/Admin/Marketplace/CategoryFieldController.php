<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\CategoryFieldRequest;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * MARKETPLACE — champs personnalisés rattachés à une catégorie.
 */
class CategoryFieldController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(CategoryFieldRequest $request, MarketplaceCategory $category): RedirectResponse
    {
        $field = $category->fields()->create($request->forModel());

        $this->audit->record($request->user(), 'marketplace.category_field.created', $field, [
            'category' => $category->name,
            'key' => $field->key,
        ]);

        return redirect()
            ->route('admin.marketplace.categories.index', ['edit' => $category->id])
            ->with('success', "Champ « {$field->label} » ajouté.");
    }

    public function update(
        CategoryFieldRequest $request,
        MarketplaceCategory $category,
        MarketplaceCategoryField $field,
    ): RedirectResponse {
        abort_unless($field->category_id === $category->id, 404);

        $field->update($request->forModel());
        $this->audit->record($request->user(), 'marketplace.category_field.updated', $field);

        return redirect()
            ->route('admin.marketplace.categories.index', ['edit' => $category->id])
            ->with('success', 'Champ mis à jour.');
    }

    public function destroy(
        Request $request,
        MarketplaceCategory $category,
        MarketplaceCategoryField $field,
    ): RedirectResponse {
        abort_unless($field->category_id === $category->id, 404);

        $field->delete();
        $this->audit->record($request->user(), 'marketplace.category_field.deleted', $field, ['key' => $field->key]);

        return redirect()
            ->route('admin.marketplace.categories.index', ['edit' => $category->id])
            ->with('success', 'Champ supprimé.');
    }
}
