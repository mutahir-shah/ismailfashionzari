<?php

namespace Modules\AIAssistant\DTO;

final readonly class AssistantContextData
{
    use ValidatesJsonSafety;

    public function __construct(
        public ?string $tenantId = null,
        public ?int $userId = null,
        public array $businessContext = [],
        public array $systemContext = []
    ) {
        self::ensureJsonPayloadSafe($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'business_context' => $this->businessContext,
            'system_context' => $this->systemContext,
        ];
    }
}
