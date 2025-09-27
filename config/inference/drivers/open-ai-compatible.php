<?php

return [
    'driver_class' => \LLMSpeak\OpenAICompatible\Drivers\Interaction\OpenAICompatibleInferenceDriver::class,
    'config' => [
        'endpoint_uri' => env('OAIC_INFERENCE_URI', '/v1/completions'),
        'api_key' => env('OAIC_API_KEY', null),
    ]
];
