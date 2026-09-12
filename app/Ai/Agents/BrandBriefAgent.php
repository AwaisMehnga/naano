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
        return 'You write a short B2B brand brief from a company website. Stay factual. Do not invent product claims.';
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value_proposition' => $schema->string()->required(),
            'icp_1_title' => $schema->string()->required(),
            'icp_1_description' => $schema->string()->required(),
            'icp_2_title' => $schema->string()->required(),
            'icp_2_description' => $schema->string()->required(),
            'icp_3_title' => $schema->string()->required(),
            'icp_3_description' => $schema->string()->required(),
        ];
    }
}
