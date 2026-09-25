<?php

namespace App\Ai\Tools;

use App\Ai\BriefDocument;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class ReadBriefTool implements Tool
{
    public function __construct(private BriefDocument $document) {}

    public function name(): string
    {
        return 'read_brief';
    }

    public function description(): Stringable|string
    {
        return <<<'DESC'
            Read the current campaign brief JSON before editing.
            Optionally pass a dotted path (e.g. key_message, audience.tone, angles.0.hook) to read one field.
            Always call this before edit_brief.
            DESC;
    }

    public function handle(Request $request): Stringable|string
    {
        $path = $request->string('path');
        $path = is_string($path) && trim($path) !== '' ? trim($path) : null;

        try {
            $value = $this->document->read($path);
        } catch (Throwable $exception) {
            return 'Error: '.$exception->getMessage();
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->description(
                'Optional dotted path into the brief. Omit to read the full brief.'
            ),
        ];
    }
}
