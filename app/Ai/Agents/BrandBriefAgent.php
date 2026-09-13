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
            You write a short B2B brand brief from a company website page.

            Use only facts present in the page text. Do not invent product claims, buyers, or industries.
            value_proposition: two to four sentences that paraphrase what the company sells and for whom.
            icps: only buyer audiences the page names or clearly describes. Return as many as the page supports, from zero to five. Do not pad to a quota. If the page describes one customer, return one ICP.
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
