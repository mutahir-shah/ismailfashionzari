<?php

namespace Modules\AIAssistant\Services;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use InvalidArgumentException;

class SkillRegistry
{
    /**
     * @var array<string, AssistantSkill>
     */
    private array $skills = [];

    /**
     * Register a new skill. Order is deterministic based on registration sequence.
     * Duplicate keys are rejected.
     *
     * @throws InvalidArgumentException
     */
    public function register(AssistantSkill $skill): void
    {
        $key = $skill->key();
        
        if (array_key_exists($key, $this->skills)) {
            throw new InvalidArgumentException("Skill with key '{$key}' is already registered.");
        }
        
        $this->skills[$key] = $skill;
    }

    /**
     * Resolves the first skill that can handle the given message.
     * Exceptions from `canHandle()` are not caught; they bubble up predictably.
     */
    public function resolve(AssistantMessageData $message): ?AssistantSkill
    {
        foreach ($this->skills as $skill) {
            if ($skill->canHandle($message)) {
                return $skill;
            }
        }
        
        return null;
    }

    /**
     * Lookup a skill by its exact key.
     */
    public function get(string $key): ?AssistantSkill
    {
        return $this->skills[$key] ?? null;
    }

    /**
     * Get all registered skills. The returned array is a copy, preventing accidental mutation.
     *
     * @return AssistantSkill[]
     */
    public function all(): array
    {
        return array_values($this->skills);
    }
}
