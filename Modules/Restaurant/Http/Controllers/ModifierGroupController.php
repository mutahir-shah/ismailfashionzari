<?php

namespace Modules\Restaurant\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Restaurant\Entities\ModifierGroup;
use Modules\Restaurant\Entities\Modifier;
use Modules\Restaurant\Entities\ProductModifierGroup;
use Modules\Restaurant\Entities\ProductModifierGroupModifier;
use App\Models\Product;
use Modules\Restaurant\Entities\Kitchens;
use DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ModifierGroupController extends Controller
{
    // ── Modifier Groups ──────────────────────────────────────────────────────

    public function index()
    {
        return view('restaurant::backend.modifier_group.index');
    }

    /**
     * AJAX: DataTable data source.
     */
    public function getData()
    {
        $groups = ModifierGroup::withCount('modifiers')
            ->with(['modifiers' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($g) => [
                'id'              => $g->id,
                'name'            => $g->name,
                'selection_type'  => $g->selection_type,
                'min_selection'   => $g->min_selection,
                'max_selection'   => $g->max_selection,
                'is_required'     => $g->is_required,
                'modifiers_count' => $g->modifiers_count,
                'sort_order'      => $g->sort_order,
                'is_active'       => $g->is_active,
            ]);

        return response()->json(['data' => $groups]);
    }

    /**
     * AJAX: Product search for the tag-select in modals.
     */
    public function productSearch(Request $request)
    {
        $q = trim($request->input('q', ''));

        $products = Product::where('is_active', 1)
            ->when($q, fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%");
            }))
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => $p->name . ($p->code ? ' (' . $p->code . ')' : ''),
            ]);

        return response()->json(['results' => $products]);
    }

    /**
     * AJAX: Create group + inline modifiers + initial product assignments in one transaction.
     */
    public function store(Request $request)
    {
        $this->validateGroup($request);
        $modifiers = $this->decodeArray($request, 'modifiers');
        $this->validateModifierPayload($modifiers);
        $productIds = $this->decodeArray($request, 'product_ids');
        $request->merge(['product_ids_array' => $productIds]);
        $request->validate([
            'product_ids_array'   => 'array',
            'product_ids_array.*' => 'integer|distinct|exists:products,id',
        ]);

        DB::transaction(function () use ($request, $modifiers, $productIds) {
            $group = ModifierGroup::create([
                'name'           => $request->name,
                'selection_type' => $request->selection_type,
                'min_selection'  => $request->min_selection,
                'max_selection'  => $request->max_selection,
                'is_required'    => $request->boolean('is_required'),
                'sort_order'     => $request->sort_order ?? 0,
                'is_active'      => 1,
            ]);

            $this->syncModifiers($group->id, $modifiers);
            $this->syncProductIds($group->id, $productIds);
        });

        return response()->json(['success' => true, 'message' => __('db.Modifier group created successfully.')]);
    }

    /**
     * AJAX: Return group + modifiers + assigned product IDs for the edit modal.
     */
    public function edit($id)
    {
        $group = ModifierGroup::with(['modifiers' => fn($q) => $q->orderBy('sort_order')])->findOrFail($id);

        $assignedProducts = ProductModifierGroup::where('modifier_group_id', $id)
            ->join('products', 'products.id', '=', 'product_modifier_groups.product_id')
            ->select('products.id', 'products.name', 'products.code')
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => $p->name . ($p->code ? ' (' . $p->code . ')' : ''),
            ]);

        return response()->json(array_merge($group->toArray(), [
            'assigned_products' => $assignedProducts,
        ]));
    }

    /**
     * AJAX: Update group + sync modifiers + sync product assignments in one transaction.
     */
    public function update(Request $request)
    {
        $this->validateGroup($request, true);
        $modifiers = $this->decodeArray($request, 'modifiers');
        $this->validateModifierPayload($modifiers);
        $productIds = $this->decodeArray($request, 'product_ids');
        $request->merge(['product_ids_array' => $productIds]);
        $request->validate([
            'id'             => 'required|exists:modifier_groups,id',
            'product_ids_array'   => 'array',
            'product_ids_array.*' => 'integer|distinct|exists:products,id',
        ]);
        $submittedIds = array_values(array_filter(array_map(
            fn ($modifier) => isset($modifier['id']) ? (int) $modifier['id'] : null,
            $modifiers
        )));
        $validIds = Modifier::where('modifier_group_id', $request->id)
            ->whereIn('id', $submittedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (array_diff($submittedIds, $validIds)) {
            throw ValidationException::withMessages([
                'modifiers' => __('One or more modifiers do not belong to this modifier group.'),
            ]);
        }

        DB::transaction(function () use ($request, $modifiers, $productIds) {
            $group = ModifierGroup::findOrFail($request->id);
            $group->update([
                'name'           => $request->name,
                'selection_type' => $request->selection_type,
                'min_selection'  => $request->min_selection,
                'max_selection'  => $request->max_selection,
                'is_required'    => $request->boolean('is_required'),
                'sort_order'     => $request->sort_order ?? $group->sort_order,
                'is_active'      => $request->boolean('is_active', true),
            ]);

            $this->syncModifiers($group->id, $modifiers, true);
            $this->syncProductIds($group->id, $productIds);
        });

        return response()->json(['success' => true, 'message' => __('db.Modifier group updated successfully.')]);
    }

    /**
     * AJAX: Delete a modifier group.
     */
    public function destroy($id)
    {
        ModifierGroup::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => __('db.Modifier group deleted.')]);
    }

    /**
     * Sync modifiers for a group. Each item: {id?, name, price_adjustment, sort_order}
     */
    private function syncModifiers(int $groupId, array $modifiers, bool $deleteRemoved = false): void
    {
        $submittedIds = array_filter(array_column($modifiers, 'id'));

        if ($deleteRemoved) {
            $q = Modifier::where('modifier_group_id', $groupId);
            if (!empty($submittedIds)) $q->whereNotIn('id', $submittedIds);
            $q->delete();
        }

        foreach ($modifiers as $i => $m) {
            $name = trim($m['name'] ?? '');
            if ($name === '') continue;

            $data = [
                'name'             => $name,
                'price_adjustment' => $m['price_adjustment'] ?? 0,
                'sort_order'       => $m['sort_order'] ?? $i,
            ];

            if (!empty($m['id'])) {
                Modifier::where('modifier_group_id', $groupId)->where('id', $m['id'])->update($data);
            } else {
                Modifier::create(array_merge($data, ['modifier_group_id' => $groupId, 'is_active' => 1]));
            }
        }
    }

    private function validateGroup(Request $request, bool $updating = false): void
    {
        $request->validate([
            'name'             => 'required|string|max:191',
            'selection_type'   => 'required|in:single,multiple',
            'min_selection'    => 'required|integer|min:0|lte:max_selection',
            'max_selection'    => 'required|integer|min:1',
            'sort_order'       => 'nullable|integer|min:0|max:65535',
            'modifiers'        => 'nullable|string',
            'product_ids'      => 'nullable|string',
        ]);

        if ($request->selection_type === 'single' && (int) $request->max_selection !== 1) {
            throw ValidationException::withMessages([
                'max_selection' => __('A single-selection modifier group must have a maximum selection of 1.'),
            ]);
        }

        if ($request->boolean('is_required') && (int) $request->min_selection < 1) {
            throw ValidationException::withMessages([
                'min_selection' => __('A required modifier group must require at least one selection.'),
            ]);
        }
    }

    private function decodeArray(Request $request, string $field): array
    {
        $value = $request->input($field, '[]');
        $decoded = json_decode($value, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                $field => __('The :attribute must contain a valid JSON array.', ['attribute' => str_replace('_', ' ', $field)]),
            ]);
        }

        return $decoded;
    }

    private function validateModifierPayload(array $modifiers): void
    {
        foreach ($modifiers as $index => $modifier) {
            if (!is_array($modifier) ||
                !isset($modifier['name']) ||
                !is_string($modifier['name']) ||
                trim($modifier['name']) === '' ||
                mb_strlen($modifier['name']) > 191 ||
                (isset($modifier['id']) && filter_var($modifier['id'], FILTER_VALIDATE_INT) === false) ||
                (isset($modifier['price_adjustment']) && !is_numeric($modifier['price_adjustment'])) ||
                (isset($modifier['sort_order']) &&
                    (filter_var($modifier['sort_order'], FILTER_VALIDATE_INT) === false ||
                     (int) $modifier['sort_order'] < 0 ||
                     (int) $modifier['sort_order'] > 65535))) {
                throw ValidationException::withMessages([
                    "modifiers.$index" => __('Each modifier must contain a valid name, ID, price, and sort order.'),
                ]);
            }
        }
    }

    /**
     * Sync product assignments for a group (add new, remove deselected, keep existing pricing rows).
     */
    private function syncProductIds(int $groupId, array $newIds): void
    {
        $currentIds = ProductModifierGroup::where('modifier_group_id', $groupId)
            ->pluck('product_id')->toArray();

        // Remove deselected products (keep their per-product modifier pricing for recovery)
        $toRemove = array_diff($currentIds, $newIds);
        if (!empty($toRemove)) {
            ProductModifierGroup::where('modifier_group_id', $groupId)
                ->whereIn('product_id', $toRemove)->delete();
        }

        // Add newly selected products
        foreach (array_diff($newIds, $currentIds) as $productId) {
            ProductModifierGroup::firstOrCreate([
                'product_id'        => $productId,
                'modifier_group_id' => $groupId,
            ], ['sort_order' => 0]);
        }
    }

    // ── Modifiers (dedicated management page for large lists) ────────────────

    public function modifiers($groupId)
    {
        $group     = ModifierGroup::with(['modifiers' => fn($q) => $q->orderBy('sort_order')])->findOrFail($groupId);
        $modifiers = $group->modifiers;
        return view('restaurant::backend.modifier_group.modifiers', compact('group', 'modifiers'));
    }

    public function storeModifier(Request $request, $groupId)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'price_adjustment' => 'nullable|numeric',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);
        ModifierGroup::findOrFail($groupId);
        $modifier = Modifier::create([
            'modifier_group_id' => $groupId,
            'name'              => $request->name,
            'price_adjustment'  => $request->price_adjustment ?? 0,
            'sort_order'        => $request->sort_order ?? 0,
            'is_active'         => 1,
        ]);
        return response()->json(['success' => true, 'message' => __('db.Modifier added successfully.'), 'modifier' => $modifier]);
    }

    public function storeModifierAjax(Request $request, $groupId)
    {
        return $this->storeModifier($request, $groupId);
    }

    public function editModifier($groupId, $modifierId)
    {
        return response()->json(Modifier::where('modifier_group_id', $groupId)->findOrFail($modifierId));
    }

    public function updateModifier(Request $request, $groupId)
    {
        $request->validate([
            'id' => [
                'required',
                Rule::exists('modifiers', 'id')->where('modifier_group_id', $groupId),
            ],
            'name' => 'required|string|max:191',
            'price_adjustment' => 'nullable|numeric',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);
        $modifier = Modifier::where('modifier_group_id', $groupId)->findOrFail($request->id);
        $modifier->update([
            'name'             => $request->name,
            'price_adjustment' => $request->price_adjustment ?? $modifier->price_adjustment,
            'sort_order'       => $request->sort_order ?? $modifier->sort_order,
            'is_active'        => $request->boolean('is_active', true),
        ]);
        return response()->json(['success' => true, 'message' => __('db.Modifier updated successfully.')]);
    }

    public function destroyModifier($groupId, $modifierId)
    {
        Modifier::where('modifier_group_id', $groupId)->findOrFail($modifierId)->delete();
        return response()->json(['success' => true, 'message' => __('db.Modifier deleted.')]);
    }

    // ── Product Assignment (advanced — per-product price override) ────────────

    public function products($groupId)
    {
        $group       = ModifierGroup::with('modifiers')->findOrFail($groupId);
        $products    = Product::where('is_active', 1)->select('id', 'name', 'code', 'type')->orderBy('name')->get();
        $assigned    = ProductModifierGroup::where('modifier_group_id', $groupId)->pluck('product_id')->toArray();
        $pricingRows = ProductModifierGroupModifier::where('modifier_group_id', $groupId)->get()->groupBy('product_id');

        return view('restaurant::backend.modifier_group.products', compact('group', 'products', 'assigned', 'pricingRows'));
    }

    public function assignProduct(Request $request)
    {
        $request->validate([
            'product_id'        => 'required|exists:products,id',
            'modifier_group_id' => 'required|exists:modifier_groups,id',
            'sort_order'        => 'nullable|integer|min:0|max:65535',
            'modifiers'         => 'nullable|array',
            'modifiers.*'       => 'array',
            'modifiers.*.price_adjustment' => 'nullable|numeric',
            'modifiers.*.sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $groupId = (int) $request->modifier_group_id;
        $submittedModifierIds = array_map('intval', array_keys($request->input('modifiers', [])));
        $validModifierIds = Modifier::where('modifier_group_id', $groupId)
            ->whereIn('id', $submittedModifierIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (array_diff($submittedModifierIds, $validModifierIds)) {
            throw ValidationException::withMessages([
                'modifiers' => __('One or more modifiers do not belong to the selected modifier group.'),
            ]);
        }

        DB::transaction(function () use ($request, $submittedModifierIds) {
            $productId = $request->product_id;
            $groupId   = $request->modifier_group_id;

            ProductModifierGroup::updateOrCreate(
                ['product_id' => $productId, 'modifier_group_id' => $groupId],
                ['sort_order' => $request->sort_order ?? 0]
            );

            foreach ($request->input('modifiers', []) as $modifierId => $data) {
                ProductModifierGroupModifier::updateOrCreate(
                    ['product_id' => $productId, 'modifier_group_id' => $groupId, 'modifier_id' => $modifierId],
                    [
                        'price_adjustment' => $data['price_adjustment'] ?? 0,
                        'product_list'     => $data['product_list'] ?? null,
                        'qty_list'         => $data['qty_list'] ?? null,
                        'variant_list'     => $data['variant_list'] ?? null,
                        'wastage_percent'  => $data['wastage_percent'] ?? null,
                        'is_active'        => isset($data['is_active']) ? 1 : 0,
                        'sort_order'       => $data['sort_order'] ?? 0,
                    ]
                );
            }

            ProductModifierGroupModifier::where('product_id', $productId)
                ->where('modifier_group_id', $groupId)
                ->when(
                    !empty($submittedModifierIds),
                    fn ($query) => $query->whereNotIn('modifier_id', $submittedModifierIds)
                )
                ->when(
                    empty($submittedModifierIds),
                    fn ($query) => $query
                )
                ->delete();
        });

        return response()->json(['success' => true, 'message' => __('db.Product modifier assignment saved successfully.')]);
    }

    public function unassignProduct(Request $request)
    {
        $request->validate([
            'product_id'        => 'required|exists:products,id',
            'modifier_group_id' => 'required|exists:modifier_groups,id',
        ]);

        DB::transaction(function () use ($request) {
            ProductModifierGroup::where('product_id', $request->product_id)
                ->where('modifier_group_id', $request->modifier_group_id)->delete();
            ProductModifierGroupModifier::where('product_id', $request->product_id)
                ->where('modifier_group_id', $request->modifier_group_id)->delete();
        });

        return response()->json(['success' => true, 'message' => __('db.Product unassigned from modifier group.')]);
    }

    public function getProductModifierConfig(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'modifier_group_id' => 'required|exists:modifier_groups,id',
        ]);
        $productId = $request->product_id;
        $groupId   = $request->modifier_group_id;

        $group = ModifierGroup::with(['modifiers' => fn($q) => $q->where('is_active', 1)->orderBy('sort_order')])->findOrFail($groupId);

        return response()->json([
            'group'      => $group,
            'pricing'    => ProductModifierGroupModifier::where('product_id', $productId)->where('modifier_group_id', $groupId)->get()->keyBy('modifier_id'),
            'assignment' => ProductModifierGroup::where('product_id', $productId)->where('modifier_group_id', $groupId)->first(),
            'product'    => Product::select('id', 'name', 'code')->findOrFail($productId),
        ]);
    }
}
