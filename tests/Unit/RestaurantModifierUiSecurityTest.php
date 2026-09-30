<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RestaurantModifierUiSecurityTest extends TestCase
{
    public function test_pos_modifier_names_are_escaped_before_html_rendering(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/backend/sale/pos.blade.php'
        );

        $this->assertStringContainsString('const escapeModifierText = function(value)', $source);
        $this->assertStringContainsString('const groupName = escapeModifierText(group.name)', $source);
        $this->assertStringContainsString('const modifierName = escapeModifierText(mod.name)', $source);
        $this->assertStringNotContainsString(
            'append(`<br><small class="text-muted">Incl: ${selectedProductNames}</small>`)',
            $source
        );
    }

    public function test_product_tags_use_text_nodes_instead_of_untrusted_inner_html(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) .
            '/Modules/Restaurant/Resources/views/backend/modifier_group/index.blade.php'
        );

        $this->assertStringContainsString("label.textContent = String(item.text || '')", $source);
        $this->assertStringContainsString("remove.textContent = '×'", $source);
        $this->assertStringNotContainsString(
            'tag.innerHTML = \'<span title="\' + item.text',
            $source
        );
        $this->assertStringContainsString(
            "@include('backend.layout.partials.datatable_js')",
            $source
        );
    }

    public function test_product_forms_only_expose_current_restaurant_routing_fields(): void
    {
        $root = dirname(__DIR__, 2);
        $create = file_get_contents($root . '/resources/views/backend/product/create.blade.php');
        $edit = file_get_contents($root . '/resources/views/backend/product/edit.blade.php');

        foreach ([$create, $edit] as $source) {
            $this->assertStringContainsString('name="kitchen_id"', $source);
            $this->assertStringContainsString('name="menu_type[]"', $source);
            $this->assertStringNotContainsString('name="extras"', $source);
            $this->assertStringNotContainsString('name="is_addon"', $source);
            $this->assertStringNotContainsString('id="search_addons"', $source);
        }
    }

    public function test_new_sale_flows_gate_modifier_inputs_on_modifier_groups(): void
    {
        $root = dirname(__DIR__, 2);
        $create = file_get_contents($root . '/resources/views/backend/sale/create.blade.php');
        $pos = file_get_contents($root . '/resources/views/backend/sale/pos.blade.php');

        $this->assertStringNotContainsString('if(data.extras)', $create);
        $this->assertStringNotContainsString('if (data.extras)', $pos);
        $this->assertStringContainsString('if(data.modifiers && data.modifiers.length)', $create);
        $this->assertStringContainsString('if (data.modifiers && data.modifiers.length)', $pos);
    }
}
