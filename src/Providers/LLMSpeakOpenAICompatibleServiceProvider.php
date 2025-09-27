<?php

namespace LLMSpeak\OpenAICompatible\Providers;

use LLMSpeak\Core\Support\Facades\AIInference;
use LLMSpeak\Core\Support\Facades\AIEmbeddings;
use LLMSpeak\Core\Support\Facades\AICompletions;
use ProjectSaturnStudios\LaravelDesignPatterns\Providers\BaseServiceProvider;
use LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleInferenceDriver;
use LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleEmbeddingsDriver;
use LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleCompletionsDriver;

class LLMSpeakOpenAICompatibleServiceProvider extends BaseServiceProvider
{
    protected array $config = [
        'vector-embeddings.drivers.open-ai-compatible' => __DIR__ . '/../../config/embeddings/drivers/open-ai-compatible.php',
        'inferencing.drivers.open-ai-compatible' => __DIR__ . '/../../config/inference/drivers/open-ai-compatible.php',
        'chat-completions.drivers.open-ai-compatible' => __DIR__ . '/../../config/chat/drivers/open-ai-compatible.php',

    ];
    protected array $publishable_config = [
        [
            'key' => 'vector-embeddings.drivers.open-ai-compatible',
            'file_path' => 'embeddings/drivers/open-ai-compatible.php',
            'groups' => ['llms', 'llms.ve', 'llms.ve.oaic']
        ],
        [
            'key' => 'inferencing.drivers.open-ai-compatible',
            'file_path' => 'inferencing/drivers/open-ai-compatible.php',
            'groups' => ['llms', 'llms.mi', 'llms.mi.oaic']
        ],
        [
            'key' => 'chat-completions.drivers.open-ai-compatible',
            'file_path' => 'chat-completions/drivers/open-ai-compatible.php',
            'groups' => ['llms', 'llms.cc', 'llms.cc.oaic']
        ],
    ];

    protected function mainBooted(): void
    {
        AIInference::extend('open-ai-compatible', fn() => new OpenAICompatibleInferenceDriver);
        AIEmbeddings::extend('open-ai-compatible', fn() => new OpenAICompatibleEmbeddingsDriver);
        AICompletions::extend('open-ai-compatible', fn() => new OpenAICompatibleCompletionsDriver);

    }

}
