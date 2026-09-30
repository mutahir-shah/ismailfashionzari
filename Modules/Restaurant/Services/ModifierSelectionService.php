<?php

namespace Modules\Restaurant\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Restaurant\Entities\Modifier;
use Modules\Restaurant\Entities\ProductModifierGroup;
use Modules\Restaurant\Entities\ProductModifierGroupModifier;

class ModifierSelectionService
{
    public function resolve(int $productId, ?string $payload): array
    {
        if ($payload === null || trim($payload) === '') {
            $submitted = [];
        } else {
            $submitted = json_decode($payload, true);
            if (!is_array($submitted) || json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'topping_product' => __('Invalid modifier selection payload.'),
                ]);
            }
        }

        $modifierIds = array_map(
            fn ($item) => is_array($item) ? (int) ($item['id'] ?? 0) : 0,
            $submitted
        );

        if (in_array(0, $modifierIds, true) || count($modifierIds) !== count(array_unique($modifierIds))) {
            throw ValidationException::withMessages([
                'topping_product' => __('Modifier selections must contain unique, valid modifier IDs.'),
            ]);
        }

        $assignments = ProductModifierGroup::with(['modifierGroup'])
            ->where('product_id', $productId)
            ->get()
            ->filter(fn ($assignment) => $assignment->modifierGroup && $assignment->modifierGroup->is_active);

        $modifiers = Modifier::whereIn('id', $modifierIds)
            ->where('is_active', 1)
            ->get()
            ->keyBy('id');

        if ($modifiers->count() !== count($modifierIds)) {
            throw ValidationException::withMessages([
                'topping_product' => __('One or more selected modifiers are invalid or inactive.'),
            ]);
        }

        $assignmentsByGroup = $assignments->keyBy('modifier_group_id');
        $selectedByGroup = $modifiers->groupBy('modifier_group_id');

        foreach ($selectedByGroup as $groupId => $selected) {
            if (!$assignmentsByGroup->has($groupId)) {
                throw ValidationException::withMessages([
                    'topping_product' => __('A selected modifier is not assigned to this product.'),
                ]);
            }
        }

        foreach ($assignments as $assignment) {
            $group = $assignment->modifierGroup;
            $count = $selectedByGroup->get($group->id, collect())->count();
            $min = (int) $assignment->effectiveMinSelection();
            $max = (int) $assignment->effectiveMaxSelection();
            $required = $assignment->effectiveIsRequired();

            if (($required && $count < max(1, $min)) || $count < $min || $count > $max) {
                throw ValidationException::withMessages([
                    'topping_product' => __(
                        'The :group modifier group requires between :min and :max selections.',
                        ['group' => $group->name, 'min' => $required ? max(1, $min) : $min, 'max' => $max]
                    ),
                ]);
            }

            if ($group->selection_type === 'single' && $count > 1) {
                throw ValidationException::withMessages([
                    'topping_product' => __('The :group modifier group allows only one selection.', ['group' => $group->name]),
                ]);
            }
        }

        $pricing = ProductModifierGroupModifier::where('product_id', $productId)
            ->whereIn('modifier_id', $modifierIds)
            ->get()
            ->keyBy('modifier_id');

        $snapshots = $modifierIds
            ? $this->snapshots($modifierIds, $modifiers, $assignmentsByGroup, $pricing)
            : [];

        foreach ($submitted as $index => $item) {
            if (!is_array($item) || !array_key_exists('price', $item)) {
                throw ValidationException::withMessages([
                    'topping_product' => __('Modifier pricing is missing. Please reload the product and try again.'),
                ]);
            }

            $submittedPrice = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
            if ($submittedPrice === false ||
                abs((float) $submittedPrice - $snapshots[$index]['price_adjustment']) > 0.0001) {
                throw ValidationException::withMessages([
                    'topping_product' => __('Modifier pricing changed. Please reload the product and try again.'),
                ]);
            }
        }

        return $snapshots;
    }

    private function snapshots(
        array $modifierIds,
        Collection $modifiers,
        Collection $assignments,
        Collection $pricing
    ): array {
        return array_map(function (int $modifierId) use ($modifiers, $assignments, $pricing) {
            $modifier = $modifiers->get($modifierId);
            $group = $assignments->get($modifier->modifier_group_id)->modifierGroup;
            $config = $pricing->get($modifierId);

            if ($config && !$config->is_active) {
                throw ValidationException::withMessages([
                    'topping_product' => __('The :modifier modifier is unavailable for this product.', [
                        'modifier' => $modifier->name,
                    ]),
                ]);
            }

            return [
                'modifier_group_id' => $group->id,
                'modifier_id' => $modifier->id,
                'modifier_group_name' => $group->name,
                'modifier_name' => $modifier->name,
                'price_adjustment' => (float) ($config->price_adjustment ?? $modifier->price_adjustment),
                'product_list' => $config->product_list ?? null,
                'qty_list' => $config->qty_list ?? null,
            ];
        }, $modifierIds);
    }
}
