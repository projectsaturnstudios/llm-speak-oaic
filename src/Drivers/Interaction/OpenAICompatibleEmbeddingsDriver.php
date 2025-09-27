<?php

namespace LLMSpeak\OpenAICompatible\Drivers\Interaction;

use LLMSpeak\Core\NeuralModels\EmbeddingsModel;
use LLMSpeak\Core\DTO\Primitives\VectorEmbedding;
use LLMSpeak\Core\Drivers\Interaction\ModelEmbeddingsDriver;

class OpenAICompatibleEmbeddingsDriver extends ModelEmbeddingsDriver
{
    protected string $driver_name = 'open-ai-compatible';

    /**
     * @param array<string> $input
     * @param EmbeddingsModel $neural_model
     * @return array
     */
    protected function generateRequestBody(array $input, EmbeddingsModel $neural_model): array
    {
        return array_map(function(string $text) use($neural_model) {
            $results = [
                'model' => $neural_model->modelId(),
                'input' => $text,

            ];
            $original = $neural_model->getOriginal();
            if(isset($original['dimensions'])) $results['dimensions'] = $original['dimensions'];
            if(isset($original['encoding_format'])) $results['encoding_format'] = $original['encoding_format'];
            if(isset($original['user'])) $results['user'] = $original['user'];

            return $results;
        }, $input);
    }

    protected function generateEmbeddings(array $output): array
    {
        $results = [
            'usage' => $output['usage'],
            'vectorized' => array_map(fn(array $embedding_data) =>
                (new VectorEmbedding(
                    $embedding_data['embedding'],
                    ['index' => $embedding_data['index'], 'object' => $embedding_data['object']]
                )),
                $output['data']
            )
        ];

        return array_values($results);
    }
}
