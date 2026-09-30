<?php

namespace Modules\AIAssistant\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use InvalidArgumentException;

class AIAssistantDataTest extends TestCase
{
    public function test_assistant_message_data_constructs_and_serializes_correctly()
    {
        $message = new AssistantMessageData(
            role: 'user',
            content: 'Show me sales',
            responseType: 'table',
            metadata: ['foo' => 'bar']
        );

        $this->assertEquals('user', $message->role);
        $this->assertEquals('Show me sales', $message->content);
        
        $array = $message->toArray();
        $this->assertEquals([
            'role' => 'user',
            'content' => 'Show me sales',
            'response_type' => 'table',
            'metadata' => ['foo' => 'bar'],
        ], $array);
    }

    public function test_assistant_message_data_rejects_invalid_roles()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role: admin');
        
        new AssistantMessageData(role: 'admin', content: 'test');
    }

    public function test_assistant_context_data_defaults_and_serializes()
    {
        $context = new AssistantContextData(tenantId: 't1', userId: 42);

        $this->assertEquals('t1', $context->tenantId);
        $this->assertEquals(42, $context->userId);
        $this->assertIsArray($context->businessContext);
        $this->assertEmpty($context->businessContext);

        $array = $context->toArray();
        $this->assertEquals([
            'tenant_id' => 't1',
            'user_id' => 42,
            'business_context' => [],
            'system_context' => [],
        ], $array);
    }

    public function test_assistant_response_data_constructs_and_serializes()
    {
        $response = new AssistantResponseData(
            textSummary: 'Here is the data',
            responseType: 'chart',
            cards: [['title' => 'Revenue', 'value' => 100]],
            warnings: ['Data is from yesterday']
        );

        $this->assertEquals('Here is the data', $response->textSummary);
        $this->assertEquals('chart', $response->responseType);

        $array = $response->toArray();
        $this->assertEquals('Here is the data', $array['text_summary']);
        $this->assertEquals('chart', $array['response_type']);
        $this->assertCount(1, $array['cards']);
        $this->assertCount(1, $array['warnings']);
        $this->assertEmpty($array['table']);
        $this->assertEmpty($array['links']);
        $this->assertEmpty($array['errors']);
        $this->assertEmpty($array['metadata']);
    }

    public function test_assistant_response_data_rejects_invalid_types()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response type: unknown');
        
        new AssistantResponseData(textSummary: 'test', responseType: 'unknown');
    }

    public function test_assistant_response_data_rejects_malformed_cards()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid card at index 0: title must be a non-empty string.');
        
        new AssistantResponseData(textSummary: 'test', cards: [['title' => '', 'value' => 123]]);
    }

    public function test_assistant_response_data_rejects_malformed_table()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table: missing or invalid rows array.');
        
        new AssistantResponseData(textSummary: 'test', table: ['columns' => ['A', 'B']]);
    }

    public function test_assistant_response_data_rejects_malformed_links()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid link at index 0: url must be a non-empty string.');
        
        new AssistantResponseData(textSummary: 'test', links: [['label' => 'Click Here']]);
    }

    public function test_dtos_reject_objects_for_json_safety()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data at payload.metadata.obj: objects are not allowed.');
        
        new AssistantMessageData(role: 'user', content: 'test', metadata: ['obj' => new \stdClass()]);
    }

    public function test_dtos_reject_resources_for_json_safety()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Type is not supported');
        
        $resource = fopen('php://memory', 'r');
        new AssistantContextData(systemContext: ['res' => $resource]);
    }

    public function test_dtos_reject_non_finite_floats_for_json_safety()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Inf and NaN cannot be JSON encoded');
        
        new AssistantContextData(businessContext: ['val' => log(0)]); // -INF
    }

    public function test_dtos_reject_invalid_utf8_strings()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Malformed UTF-8 characters, possibly incorrectly encoded');
        
        new AssistantMessageData(role: 'user', content: 'test', metadata: ['bad_string' => "\xc3\x28"]); // Invalid UTF-8
    }

    public function test_dtos_reject_recursive_arrays()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Recursion detected');
        
        $metadata = [];
        $metadata['recurse'] = &$metadata;
        
        new AssistantMessageData(role: 'user', content: 'test', metadata: $metadata);
    }

    public function test_assistant_response_data_enforces_actual_lists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid warnings: must be a list.');
        
        new AssistantResponseData(textSummary: 'test', warnings: ['a' => 'Warning 1']);
    }

    public function test_assistant_response_data_enforces_table_row_width()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table row at index 0: row width must equal column count.');
        
        new AssistantResponseData(
            textSummary: 'test', 
            table: [
                'columns' => ['A', 'B'],
                'rows' => [
                    ['a'] // Missing one column
                ]
            ]
        );
    }

    public function test_default_array_isolation_between_instances()
    {
        $data1 = new AssistantContextData();
        $data2 = new AssistantContextData();

        $this->assertEquals([], $data1->businessContext);
        $this->assertEquals([], $data2->businessContext);

        // PHP optimizes empty arrays in default parameters to use the same internal zval.
        // Copy-on-write guarantees isolation when manipulated. 
        // We verify that they are correctly isolated standard arrays.
        $copy1 = $data1->businessContext;
        $copy1['isolated'] = true;

        $this->assertEmpty($data2->businessContext);
        $this->assertArrayNotHasKey('isolated', $data1->businessContext); // Original is unmodified (readonly)
    }

    public function test_dtos_reject_invalid_utf8_in_message_content()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Malformed UTF-8 characters, possibly incorrectly encoded');
        
        new AssistantMessageData(role: 'user', content: "Bad \xc3\x28 content");
    }

    public function test_dtos_reject_invalid_utf8_in_response_text_or_tenant_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Malformed UTF-8 characters, possibly incorrectly encoded');
        
        new AssistantResponseData(textSummary: "Bad \xc3\x28 summary");
    }

    public function test_dtos_reject_invalid_utf8_array_keys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Malformed UTF-8 characters, possibly incorrectly encoded');
        
        new AssistantContextData(businessContext: ["\xc3\x28" => 'value']);
    }

    public function test_dtos_reject_excessive_payload_nesting()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON encoding failed for payload: Maximum stack depth exceeded');
        
        $deep = [];
        for ($i = 0; $i < 515; $i++) {
            $deep = ['nested' => $deep];
        }
        
        new AssistantMessageData(role: 'user', content: 'test', metadata: $deep);
    }

    public function test_successful_json_encode_for_each_valid_dto()
    {
        $message = new AssistantMessageData(role: 'user', content: 'hello', metadata: ['key' => 'value']);
        $context = new AssistantContextData(tenantId: 'tenant1', businessContext: ['key' => 'value']);
        $response = new AssistantResponseData(textSummary: 'summary', cards: [['title' => 'card', 'value' => 1]]);
        
        $this->assertJson(json_encode($message->toArray(), JSON_THROW_ON_ERROR));
        $this->assertJson(json_encode($context->toArray(), JSON_THROW_ON_ERROR));
        $this->assertJson(json_encode($response->toArray(), JSON_THROW_ON_ERROR));
    }
}
