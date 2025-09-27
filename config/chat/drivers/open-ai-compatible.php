<?php

return [
    'driver_class' => \LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleCompletionsDriver::class,
    'config' => [
        'endpoint_uri' => env('OAIC_INFERENCE_URI', '/v1/chat/completions'),
        'api_key' => env('OAIC_API_KEY', null),
    ]
];
