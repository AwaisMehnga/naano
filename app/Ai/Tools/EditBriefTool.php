<?php

namespace App\Ai\Tools;

use App\Ai\BriefDocument;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class EditBriefTool implements Tool
{
    public function __construct(private BriefDocument $document) {}

    public function name(): string
    {
        return 'edit_brief';
    }

    public function description(): Stringable|string
    {
        return <<<'DESC'
            Surgically update one field in the campaign brief. Always read_brief first.
            Use dotted paths: context, product, key_message, audience.tone, pains.0, angles.1.hook, etc.
            old_value must exactly match the current value (string fields as plain text; arrays as JSON).
            Prefer the smallest change. Never rewrite untouched fields. Do not create a full brief rewrite.
            DESC;
    }

    public function handle(Request $request): Stringable|string
    {
        $path = trim((string) $request->string('path', ''));
        $oldValue = (string) $request->string('old_value', '');
        $newValue = (string) $request->string('new_value', '');
        $replaceAll = (bool) $request->boolean('replace_all', false);

        try {
            return $this->document->edit($path, $oldValue, $newValue, $replaceAll);
        } catch (Throwable $exception) {
            return 'Error: '.$exception->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->required()->description(
                'Dotted path into the brief JSON.'
            ),
            'old_value' => $schema->string()->required()->description(
                'Current value at path (exact match). Arrays as JSON strings.'
            ),
            'new_value' => $schema->string()->required()->description(
                'Replacement value. Arrays as JSON strings.'
            ),
            'replace_all' => $schema->boolean()->description(
                'If true, replace every occurrence of old_value inside a string field.'
            ),
        ];
    }
}
