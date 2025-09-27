<?php

return [
    'driver_class' => \LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleEmbeddingsDriver::class,
    'config' => [
        'endpoint_uri' => env('OAIC_EMBED_URI', '/v1/embeddings'),
        'api_key' => env('OAIC_API_KEY', null),
    ]
];
