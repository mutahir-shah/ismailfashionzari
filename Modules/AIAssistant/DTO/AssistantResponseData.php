<?php

namespace Modules\AIAssistant\DTO;

use InvalidArgumentException;

final readonly class AssistantResponseData
{
    use ValidatesJsonSafety;

    public function __construct(
        public string $textSummary,
        public string $responseType = 'text',
        public array $cards = [],
        public array $table = [],
        public array $links = [],
        public array $warnings = [],
        public array $errors = [],
        public array $metadata = []
    ) {
        $validResponseTypes = ['text', 'card', 'table', 'chart', 'error'];
        if (!in_array($this->responseType, $validResponseTypes, true)) {
            throw new InvalidArgumentException("Invalid response type: {$this->responseType}. Must be one of: " . implode(', ', $validResponseTypes));
        }

        // Validate cards: list entries with required non-empty string title and a JSON-safe value
        if (!empty($this->cards) && !array_is_list($this->cards)) {
            throw new InvalidArgumentException("Invalid cards: must be a list.");
        }
        foreach ($this->cards as $index => $card) {
            if (!is_array($card)) {
                throw new InvalidArgumentException("Invalid card at index {$index}: must be an array.");
            }
            if (!isset($card['title']) || !is_string($card['title']) || trim($card['title']) === '') {
                throw new InvalidArgumentException("Invalid card at index {$index}: title must be a non-empty string.");
            }
            if (!array_key_exists('value', $card)) {
                throw new InvalidArgumentException("Invalid card at index {$index}: value is required.");
            }
        }

        // Validate table: empty or an associative structure with string columns and array rows;
        if (!empty($this->table)) {
            if (!isset($this->table['columns']) || !is_array($this->table['columns'])) {
                throw new InvalidArgumentException("Invalid table: missing or invalid columns array.");
            }
            if (!array_is_list($this->table['columns'])) {
                throw new InvalidArgumentException("Invalid table columns: must be a list.");
            }
            $colCount = count($this->table['columns']);
            foreach ($this->table['columns'] as $colIndex => $column) {
                if (!is_string($column)) {
                    throw new InvalidArgumentException("Invalid table column at index {$colIndex}: must be a string.");
                }
            }
            if (!isset($this->table['rows']) || !is_array($this->table['rows'])) {
                throw new InvalidArgumentException("Invalid table: missing or invalid rows array.");
            }
            if (!array_is_list($this->table['rows'])) {
                throw new InvalidArgumentException("Invalid table rows: must be a list.");
            }
            foreach ($this->table['rows'] as $rowIndex => $row) {
                if (!is_array($row) || !array_is_list($row)) {
                    throw new InvalidArgumentException("Invalid table row at index {$rowIndex}: must be a list.");
                }
                if (count($row) !== $colCount) {
                    throw new InvalidArgumentException("Invalid table row at index {$rowIndex}: row width must equal column count.");
                }
            }
        }

        // Validate links: list entries with required non-empty string label and URL/route value
        if (!empty($this->links) && !array_is_list($this->links)) {
            throw new InvalidArgumentException("Invalid links: must be a list.");
        }
        foreach ($this->links as $index => $link) {
            if (!is_array($link)) {
                throw new InvalidArgumentException("Invalid link at index {$index}: must be an array.");
            }
            if (!isset($link['label']) || !is_string($link['label']) || trim($link['label']) === '') {
                throw new InvalidArgumentException("Invalid link at index {$index}: label must be a non-empty string.");
            }
            if (!isset($link['url']) || !is_string($link['url']) || trim($link['url']) === '') {
                throw new InvalidArgumentException("Invalid link at index {$index}: url must be a non-empty string.");
            }
        }

        // Validate warnings and errors: lists of strings
        if (!empty($this->warnings) && !array_is_list($this->warnings)) {
            throw new InvalidArgumentException("Invalid warnings: must be a list.");
        }
        if (!empty($this->errors) && !array_is_list($this->errors)) {
            throw new InvalidArgumentException("Invalid errors: must be a list.");
        }
        foreach (['warnings' => $this->warnings, 'errors' => $this->errors] as $type => $list) {
            foreach ($list as $index => $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException("Invalid {$type} at index {$index}: must be a string.");
                }
            }
        }

        self::ensureJsonPayloadSafe($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'text_summary' => $this->textSummary,
            'response_type' => $this->responseType,
            'cards' => $this->cards,
            'table' => $this->table,
            'links' => $this->links,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
        ];
    }
}
