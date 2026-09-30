<?php

namespace Modules\AIAssistant\Tests\Unit;

use Tests\TestCase;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\WarehouseScope;
use InvalidArgumentException;

class WarehouseScopeTest extends TestCase
{
    public function test_absent_warehouse_key_returns_unrestricted()
    {
        $context = new AssistantContextData(businessContext: []);
        $scope = WarehouseScope::fromContext($context);

        $this->assertFalse($scope->isRestricted);
        $this->assertEmpty($scope->warehouseIds);
        $this->assertNull($scope->ownUserId);
    }

    public function test_valid_warehouse_ids_array()
    {
        $context = new AssistantContextData(businessContext: ['warehouse_ids' => [3, 1, 2, 2]]);
        $scope = WarehouseScope::fromContext($context);

        $this->assertTrue($scope->isRestricted);
        $this->assertEquals([1, 2, 3], $scope->warehouseIds);
    }

    public function test_explicit_empty_warehouse_ids_array()
    {
        $context = new AssistantContextData(businessContext: ['warehouse_ids' => []]);
        $scope = WarehouseScope::fromContext($context);

        $this->assertTrue($scope->isRestricted);
        $this->assertEquals([], $scope->warehouseIds);
    }

    public function test_rejects_non_array_warehouse_ids()
    {
        $this->expectException(InvalidArgumentException::class);
        $context = new AssistantContextData(businessContext: ['warehouse_ids' => '1,2,3']);
        WarehouseScope::fromContext($context);
    }

    public function test_rejects_associative_array_warehouse_ids()
    {
        $this->expectException(InvalidArgumentException::class);
        $context = new AssistantContextData(businessContext: ['warehouse_ids' => ['a' => 1, 'b' => 2]]);
        WarehouseScope::fromContext($context);
    }

    public function test_rejects_numeric_strings_floats_negatives_zero_null()
    {
        $invalidValues = [
            ['1', '2'], // numeric strings
            [1.5],      // floats
            [0],        // zero
            [-5],       // negative
            [null],     // null
            [false],    // boolean
        ];

        foreach ($invalidValues as $invalidArray) {
            try {
                $context = new AssistantContextData(businessContext: ['warehouse_ids' => $invalidArray]);
                WarehouseScope::fromContext($context);
                $this->fail("Failed to reject invalid warehouse array shape: " . json_encode($invalidArray));
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_valid_own_user_id()
    {
        $context = new AssistantContextData(businessContext: ['own_user_id' => 5]);
        $scope = WarehouseScope::fromContext($context);

        $this->assertFalse($scope->isRestricted);
        $this->assertEquals(5, $scope->ownUserId);
    }

    public function test_valid_own_user_id_with_warehouse_ids()
    {
        $context = new AssistantContextData(businessContext: ['own_user_id' => 5, 'warehouse_ids' => [1, 2]]);
        $scope = WarehouseScope::fromContext($context);

        $this->assertTrue($scope->isRestricted);
        $this->assertEquals([1, 2], $scope->warehouseIds);
        $this->assertEquals(5, $scope->ownUserId);
    }

    public function test_rejects_invalid_own_user_id()
    {
        $invalidValues = ['5', 5.5, 0, -1, null, false, [5]];

        foreach ($invalidValues as $invalid) {
            try {
                $context = new AssistantContextData(businessContext: ['own_user_id' => $invalid]);
                WarehouseScope::fromContext($context);
                $this->fail("Failed to reject invalid own_user_id: " . json_encode($invalid));
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }
}
