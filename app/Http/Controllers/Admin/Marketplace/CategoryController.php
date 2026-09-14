<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\CategoryRequest;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * MARKETPLACE — gestion de l'arbre des catégories du catalogue.
 */
class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $categories = MarketplaceCategory::query()
            ->withCount('fields')
            ->orderBy('path')
            ->get();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = MarketplaceCategory::with('fields')->find($request->query('edit'));
        }

        return view('admin.marketplace.categories.index', [
            'categories' => $categories,
            'tree' => $this->tree($categories),
            'editing' => $editing,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = MarketplaceCategory::create($request->forModel());

        $this->audit->record($request->user(), 'marketplace.category.created', $category, [
            'name' => $category->name,
        ]);

        return redirect()
            ->route('admin.marketplace.categories.index', ['edit' => $category->id])
            ->with('success', "Catégorie « {$category->name} » créée.");
    }

    public function update(CategoryRequest $request, MarketplaceCategory $category): RedirectResponse
    {
        $data = $request->forModel();

        // Anti-cycle : le nouveau parent ne peut pas être la catégorie
        // elle-même ni l'un de ses descendants.
        if ($data['parent_id'] !== null) {
            $descendantIds = MarketplaceCategory::query()->inSubtree($category)->pluck('id')->all();
            if (in_array($data['parent_id'], $descendantIds, true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Impossible de rattacher une catégorie sous elle-même ou l\'un de ses descendants.',
                ]);
            }
        }

        $category->update($data);

        $this->audit->record($request->user(), 'marketplace.category.updated', $category);

        return redirect()
            ->route('admin.marketplace.categories.index', ['edit' => $category->id])
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Request $request, MarketplaceCategory $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Retirez d\'abord les sous-catégories.');
        }

        // Phase 3 : refuser aussi si des offres y sont rattachées.
        if (class_exists(MarketplaceOffer::class)
            && MarketplaceOffer::where('category_id', $category->id)->exists()) {
            return back()->with('error', 'Des offres sont rattachées à cette catégorie.');
        }

        $category->delete();
        $this->audit->record($request->user(), 'marketplace.category.deleted', $category, ['name' => $category->name]);

        return redirect()->route('admin.marketplace.categories.index')->with('success', 'Catégorie supprimée.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'exists:marketplace_categories,id'],
            'order.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['order'] as $row) {
            MarketplaceCategory::where('id', $row['id'])->update(['position' => $row['position']]);
        }

        return back()->with('success', 'Ordre enregistré.');
    }

    /**
     * @param  Collection<int, MarketplaceCategory>  $flat
     * @return array<int, array{category: MarketplaceCategory, depth: int}>
     */
    private function tree($flat): array
    {
        $byParent = $flat->groupBy('parent_id');
        $out = [];

        $walk = function ($parentId) use (&$walk, $byParent, &$out) {
            foreach ($byParent->get($parentId, collect())->sortBy([['position', 'asc'], ['name', 'asc']]) as $node) {
                $out[] = ['category' => $node, 'depth' => $node->depth];
                $walk($node->id);
            }
        };
        $walk(null);

        return $out;
    }
}
