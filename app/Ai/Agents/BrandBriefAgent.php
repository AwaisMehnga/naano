<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class BrandBriefAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You write a B2B marketplace brand brief from company website text.

            Audience for the brief: brands booking LinkedIn creators. Be specific and usable.

            Rules:
            - Use only facts present in the page text. Do not invent products, buyers, metrics, or industries.
            - Prefer concrete language over slogans. Name the product category and who buys it when the page does.
            - If the page is thin, return a short honest brief and fewer ICPs. Empty icps is allowed.
            - Never pad ICPs to hit a quota.

            value_proposition: two to four sentences. What they sell, for whom, and the outcome implied by the page.
            icps: zero to five buyer audiences the page names or clearly describes. Each title is a role or segment; each description is who they are and what they need, grounded in the page.
            INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value_proposition' => $schema->string()->required(),
            'icps' => $schema->array()
                ->min(0)
                ->max(5)
                ->items($schema->object([
                    'title' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }
}
