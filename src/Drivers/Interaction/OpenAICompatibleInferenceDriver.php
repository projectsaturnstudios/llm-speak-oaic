<?php

namespace LLMSpeak\OpenAICompatible\Drivers\Interaction;

use LLMSpeak\Core\DTO\Primitives\TextObject;
use LLMSpeak\Core\NeuralModels\InferenceModel;
use LLMSpeak\Core\NeuralModels\EmbeddingsModel;
use LLMSpeak\Core\Drivers\Interaction\ModelInferenceDriver;

class OpenAICompatibleInferenceDriver extends ModelInferenceDriver
{
    protected string $driver_name = 'open-ai-compatible';

    /**
     * @param array<string> $input
     * @param EmbeddingsModel $neural_model
     * @return array
     */
    protected function generateRequestBody(array|string $input, InferenceModel $neural_model): array
    {
        return array_map(function(string|array $text) use($neural_model) {
            $results = [
                'model'  => $neural_model->modelId(),
                'prompt' => $text,
                'max_tokens' => $neural_model->maxTokens(),
                'temperature' => $neural_model->temperature(),
                'top_p' => $neural_model->topP(),
                'frequency_penalty' => $neural_model->frequencyPenalty(),
                'presence_penalty' => $neural_model->presencePenalty(),
                'seed' => $neural_model->seed(),
                'n' => $neural_model->n(),
                'stream' => $neural_model->willStreamResponse(),
            ];

            $original = $neural_model->getOriginal();
            // @todo - apply non-standard options from original

            return array_filter($results, fn($value) => !is_null($value));
        }, $input);
    }

    protected function generateInference(array $output): array
    {
        $results = [
            'id'  => $output['id'],
            'usage' => $output['usage'],
            'created_at' => $output['created'],
            'metadata' => [
                'model' => $output['model'] ?? null,
                'object' => $output['object'] ?? null,
                'system_fingerprint' => $output['system_fingerprint'] ?? null,
            ],
            'messages' => array_map(fn(array $choice) => new TextObject(...[
                'text' => $choice['text'],
                'metadata' => [
                    'index' => $choice['index'],
                    'logprobs' => $choice['logprobs'] ?? null,
                    'finish_reason' => $choice['finish_reason'] ?? null,
                ]
            ]), $output['choices'])
        ];

        return array_values($results);
    }
}
