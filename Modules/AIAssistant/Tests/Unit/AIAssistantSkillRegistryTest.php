<?php

namespace Modules\AIAssistant\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\AIAssistant\Services\SkillRegistry;
use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use InvalidArgumentException;
use RuntimeException;

class AIAssistantSkillRegistryTest extends TestCase
{
    private function createFakeSkill(string $key, bool|callable $handles): AssistantSkill
    {
        return new class($key, $handles) implements AssistantSkill {
            public function __construct(private string $key, private mixed $handles) {}

            public function key(): string { return $this->key; }
            public function name(): string { return $this->key . ' name'; }
            public function description(): string { return 'fake desc'; }
            public function examples(): array { return []; }

            public function canHandle(AssistantMessageData $message): bool
            {
                if (is_callable($this->handles)) {
                    return ($this->handles)($message);
                }
                return $this->handles;
            }

            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
            {
                return new AssistantResponseData(textSummary: 'handled');
            }
        };
    }

    public function test_register_and_get_skill()
    {
        $registry = new SkillRegistry();
        $skill = $this->createFakeSkill('test.skill', true);

        $registry->register($skill);

        $this->assertSame($skill, $registry->get('test.skill'));
        $this->assertNull($registry->get('unknown.skill'));
    }

    public function test_registration_preserves_deterministic_order()
    {
        $registry = new SkillRegistry();
        $skill1 = $this->createFakeSkill('skill.one', false);
        $skill2 = $this->createFakeSkill('skill.two', false);

        $registry->register($skill1);
        $registry->register($skill2);

        $all = $registry->all();
        
        $this->assertCount(2, $all);
        $this->assertSame($skill1, $all[0]);
        $this->assertSame($skill2, $all[1]);
    }

    public function test_register_rejects_duplicate_keys()
    {
        $registry = new SkillRegistry();
        $skill1 = $this->createFakeSkill('test.skill', true);
        $skill2 = $this->createFakeSkill('test.skill', false);

        $registry->register($skill1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Skill with key 'test.skill' is already registered.");
        
        $registry->register($skill2);
    }

    public function test_resolve_returns_first_matching_skill()
    {
        $registry = new SkillRegistry();
        
        $skill1 = $this->createFakeSkill('skill.one', false);
        $skill2 = $this->createFakeSkill('skill.two', true);
        $skill3 = $this->createFakeSkill('skill.three', true); // Would also match, but order dictates skill2 wins

        $registry->register($skill1);
        $registry->register($skill2);
        $registry->register($skill3);

        $message = new AssistantMessageData(role: 'user', content: 'test');
        $resolved = $registry->resolve($message);

        $this->assertSame($skill2, $resolved);
    }

    public function test_resolve_returns_null_when_no_match()
    {
        $registry = new SkillRegistry();
        
        $skill1 = $this->createFakeSkill('skill.one', false);
        $skill2 = $this->createFakeSkill('skill.two', false);

        $registry->register($skill1);
        $registry->register($skill2);

        $message = new AssistantMessageData(role: 'user', content: 'test');
        $resolved = $registry->resolve($message);

        $this->assertNull($resolved);
    }

    public function test_can_handle_exceptions_bubble_up()
    {
        $registry = new SkillRegistry();
        
        $crashingSkill = $this->createFakeSkill('skill.crash', function () {
            throw new RuntimeException("Crash during intent resolution");
        });

        $registry->register($crashingSkill);

        $message = new AssistantMessageData(role: 'user', content: 'test');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Crash during intent resolution");
        
        $registry->resolve($message);
    }

    public function test_registry_enumeration_cannot_mutate_state()
    {
        $registry = new SkillRegistry();
        $skill1 = $this->createFakeSkill('skill.one', false);
        $registry->register($skill1);

        $all = $registry->all();
        // Attempting to alter the returned array
        $all[] = $this->createFakeSkill('skill.two', false);
        
        // Internal state should remain unmodified
        $this->assertCount(1, $registry->all());
        $this->assertSame($skill1, $registry->all()[0]);
    }
}
