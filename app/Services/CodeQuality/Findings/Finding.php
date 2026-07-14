<?php

namespace App\Services\CodeQuality\Findings;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Finding
{
    /**
     * @param  list<string>  $docs
     * @param  list<string>  $relatedNotes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $checkId,
        public readonly string $source,
        public readonly string $severity,
        public readonly string $confidence,
        public readonly string $category,
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $file = null,
        public readonly ?int $line = null,
        public readonly ?int $endLine = null,
        public readonly ?string $symbol = null,
        public readonly ?string $evidence = null,
        public readonly ?string $recommendation = null,
        public readonly array $docs = [],
        public readonly ?string $dedupeKey = null,
        public readonly bool $blocking = false,
        public readonly array $relatedNotes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $fallbackCheckId, string $fallbackSource): self
    {
        return new self(
            id: (string) ($data['id'] ?? 'cqf_'.Str::ulid()),
            checkId: (string) ($data['check_id'] ?? $fallbackCheckId),
            source: (string) ($data['source'] ?? $fallbackSource),
            severity: (string) ($data['severity'] ?? 'warning'),
            confidence: (string) ($data['confidence'] ?? 'medium'),
            category: (string) ($data['category'] ?? 'general'),
            title: (string) ($data['title'] ?? 'Code quality finding'),
            description: (string) ($data['description'] ?? ''),
            file: Arr::has($data, 'file') && $data['file'] !== null ? (string) $data['file'] : null,
            line: Arr::has($data, 'line') && $data['line'] !== null ? (int) $data['line'] : null,
            endLine: Arr::has($data, 'end_line') && $data['end_line'] !== null ? (int) $data['end_line'] : null,
            symbol: Arr::has($data, 'symbol') && $data['symbol'] !== null ? (string) $data['symbol'] : null,
            evidence: Arr::has($data, 'evidence') && $data['evidence'] !== null ? (string) $data['evidence'] : null,
            recommendation: Arr::has($data, 'recommendation') && $data['recommendation'] !== null ? (string) $data['recommendation'] : null,
            docs: array_values(array_map('strval', Arr::wrap($data['docs'] ?? []))),
            dedupeKey: Arr::has($data, 'dedupe_key') && $data['dedupe_key'] !== null ? (string) $data['dedupe_key'] : null,
            blocking: (bool) ($data['blocking'] ?? false),
            relatedNotes: array_values(array_map('strval', Arr::wrap($data['related_notes'] ?? []))),
        );
    }

    public function withBlocking(bool $blocking): self
    {
        return new self(
            id: $this->id,
            checkId: $this->checkId,
            source: $this->source,
            severity: $this->severity,
            confidence: $this->confidence,
            category: $this->category,
            title: $this->title,
            description: $this->description,
            file: $this->file,
            line: $this->line,
            endLine: $this->endLine,
            symbol: $this->symbol,
            evidence: $this->evidence,
            recommendation: $this->recommendation,
            docs: $this->docs,
            dedupeKey: $this->dedupeKey,
            blocking: $blocking,
            relatedNotes: $this->relatedNotes,
        );
    }

    public function withRelatedNote(string $note): self
    {
        return new self(
            id: $this->id,
            checkId: $this->checkId,
            source: $this->source,
            severity: $this->severity,
            confidence: $this->confidence,
            category: $this->category,
            title: $this->title,
            description: $this->description,
            file: $this->file,
            line: $this->line,
            endLine: $this->endLine,
            symbol: $this->symbol,
            evidence: $this->evidence,
            recommendation: $this->recommendation,
            docs: $this->docs,
            dedupeKey: $this->dedupeKey,
            blocking: $this->blocking,
            relatedNotes: [...$this->relatedNotes, $note],
        );
    }

    public function dedupeIdentity(): string
    {
        if ($this->dedupeKey !== null && $this->dedupeKey !== '') {
            return $this->dedupeKey;
        }

        if ($this->file !== null && $this->line !== null) {
            return implode(':', [
                $this->checkId,
                $this->file,
                (string) $this->line,
                Str::slug($this->title),
            ]);
        }

        return implode(':', [
            $this->checkId,
            Str::slug($this->title),
            (string) $this->symbol,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'check_id' => $this->checkId,
            'source' => $this->source,
            'severity' => $this->severity,
            'confidence' => $this->confidence,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'file' => $this->file,
            'line' => $this->line,
            'end_line' => $this->endLine,
            'symbol' => $this->symbol,
            'evidence' => $this->evidence,
            'recommendation' => $this->recommendation,
            'docs' => $this->docs,
            'dedupe_key' => $this->dedupeKey ?? $this->dedupeIdentity(),
            'blocking' => $this->blocking,
            'related_notes' => $this->relatedNotes,
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
