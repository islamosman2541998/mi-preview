<?php

namespace App\Ai;

/** Structured JSON generation by one AI provider (Claude, Gemini). Tests use a fake. */
interface TextGenerator
{
    /** Short key stored on runs ("claude", "gemini"). */
    public function key(): string;

    /** Name shown in the admin, including the model. */
    public function label(): string;

    public function configured(): bool;

    /**
     * Whether to send a schema narrowed to the section types of the request.
     * Claude caches one stable schema; Gemini limits schema complexity.
     */
    public function narrowSchema(): bool;

    /**
     * @param  array  $schema  JSON schema the output must match
     * @param  callable(int $charsSoFar, bool $writing): void|null  $onProgress  called while streaming
     *
     * @throws AiException with an Arabic, user-facing message
     */
    public function generate(string $system, string $user, array $schema, ?callable $onProgress = null): GenerationResult;
}
