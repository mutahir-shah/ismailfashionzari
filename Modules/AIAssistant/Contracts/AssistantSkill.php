<?php

namespace Modules\AIAssistant\Contracts;

use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;

interface AssistantSkill
{
    public function key(): string;
    public function name(): string;
    public function description(): string;
    public function examples(): array;
    public function canHandle(AssistantMessageData $message): bool;
    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData;
}
