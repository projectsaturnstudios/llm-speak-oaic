<?php

namespace LLMSpeak\OpenAICompatible\Drivers\Interaction;

use LLMSpeak\Core\Enums\ConversationRole;
use LLMSpeak\Core\DTO\Primitives\TextObject;
use LLMSpeak\Core\Collections\ChatConversation;
use LLMSpeak\Core\NeuralModels\CompletionsModel;
use LLMSpeak\Core\DTO\Primitives\ToolCallObject;
use LLMSpeak\Core\DTO\Schema\Completions\ContentMessage;
use LLMSpeak\Core\DTO\Schema\Completions\ToolCallsMessage;
use LLMSpeak\Core\DTO\Schema\Completions\ToolResultMessage;
use LLMSpeak\Core\Drivers\Interaction\ModelCompletionsDriver;
use LLMSpeak\Core\DTO\Schema\Completions\TextOnlyContentMessage;
use LLMSpeak\Core\DTO\Schema\Completions\MultiTextContentMessage;

class OpenAICompatibleCompletionsDriver extends ModelCompletionsDriver
{
    protected string $driver_name = 'open-ai-compatible';

    /**
     * @param array<ChatConversation> $input
     * @param CompletionsModel $neural_model
     * @return array
     */
    protected function generateRequestBody(array $input, CompletionsModel $neural_model): array
    {
        return array_map(function(ChatConversation|array $conversation) use($neural_model, $input) {
            $results = [
                'model'  => $neural_model->modelId(),
                'messages' => array_map(function(ContentMessage|array $message) use ($input) {
                    if(is_array($message))
                    {
                        $all_strings = true;
                        foreach($message as $msg)
                        {
                            if(!($msg instanceof TextOnlyContentMessage))
                            {
                                $all_strings = false;
                                break;
                            }
                        }

                        if($all_strings)
                        {
                            // if all messages are strings, this is a user message
                            $content = array_map(fn(TextOnlyContentMessage $msg) => (new TextObject($msg->content->toValue())) , $message);
                            return MultiTextContentMessage::from([
                                'role' => ConversationRole::USER->value,
                                'content' => $content
                            ])->toArray();
                        }


                    }
                    else
                    {
                        switch($message_class = $message::class)
                        {
                            case TextOnlyContentMessage::class:
                                /** @var TextOnlyContentMessage $message */
                                return $message->toArray();

                            case ToolCallsMessage::class:
                                /** @var ToolCallsMessage $message */
                                return [
                                    'role' => 'assistant',
                                    'tool_calls' => array_map(fn(ToolCallObject $message) => [
                                        'id' => $message->id,
                                        'type' => 'function',
                                        'function' => [
                                            'name' => $message->name,
                                            'arguments' =>json_encode(empty($message->args) ? new \stdClass() : $message->args)
                                        ]
                                    ], $message->tool_calls)
                                ];
                                break;

                            case ToolResultMessage::class:
                                /** @var ToolResultMessage $message */
                                return [
                                    'role' => 'tool',
                                    'tool_call_id' => $message->id,
                                    'content' => $message->content
                                ];

                            default:
                                dd("Support this kind of message", $message, $message_class);
                        }
                    }

                    return '';
                }, $conversation->all()),
                'max_completion_tokens' => $neural_model->maxTokens(),
                'temperature' => $neural_model->temperature(),
                'top_p' => $neural_model->topP(),
                'frequency_penalty' => $neural_model->frequencyPenalty(),
                'presence_penalty' => $neural_model->presencePenalty(),
                'seed' => $neural_model->seed(),
                'n' => $neural_model->n(),
                'stream' => $neural_model->willStreamResponse(),
            ];

            if(!empty($neural_model->getSystemInstructions()))
            {
                $system_messages = array_map(fn(string $instruction) => [
                    'role' => ConversationRole::SYSTEM->value,
                    'content' => (new TextObject($instruction))->toValue()
                ], $neural_model->getSystemInstructions());
                $results['messages'] = array_merge($system_messages, $results['messages']);
            }
            if(!empty($neural_model->getTools()))
            {
                //dd($neural_model->getTools());
                $results['tools'] = array_map(fn(array $tool_definition) => [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool_definition['name'],
                        'description' => $tool_definition['description'],
                        'parameters' => array_map(function(array $schema) use($tool_definition) {
                            if(empty($schema['properties'])) $schema['properties'] = new \stdClass();
                            return $schema;
                        }, [$tool_definition['inputSchema']])[0],
                    ]
                ], $neural_model->getTools());
            }

            $original = $neural_model->getOriginal();
            // @todo - apply non-standard options from original
            return array_filter($results, fn($value) => !is_null($value));
        }, $input);
    }

    protected function generateCompletion(array $output): array
    {

        $results = [
            'id'  => $output['id'],
            'usage' => $output['usage'],
            'created_at' => $output['created'],
            'metadata' => [
                'model' => $output['model'] ?? null,
                'object' => $output['object'] ?? null,
                'system_fingerprint' => $output['system_fingerprint'] ?? null,
                'service_tier' => $output['service_tier'] ?? null,
            ],
            'messages' => array_map(function(array $choice) {

                if(isset($choice['message']['tool_calls']) && (!empty($choice['message']['tool_calls'])))
                {
                    $metadata = [
                        'refusal' => $choice['message']['refusal'] ?? null,
                        'annotations' => $choice['message']['annotations'] ?? null,
                        'index' => $choice['index'],
                        'logprobs' => $choice['logprobs'] ?? null,
                        'finish_reason' => $choice['finish_reason'] ?? null,
                    ];

                    return (new ToolCallsMessage(
                        array_map(fn(array $tool_call) => new ToolCallObject(
                            $tool_call['function']['name'],
                            json_decode($tool_call['function']['arguments'], true),
                            $tool_call['id']
                        ), $choice['message']['tool_calls']),
                        $choice['message']['content'] ?? null ? new TextObject($choice['message']['content']) : null,
                        $metadata
                    ));
                }
                elseif(isset($choice['message']['content']))
                {
                    $metadata = [
                        'refusal' => $choice['message']['refusal'] ?? null,
                        'annotations' => $choice['message']['annotations'] ?? null,
                        'index' => $choice['index'],
                        'logprobs' => $choice['logprobs'] ?? null,
                        'finish_reason' => $choice['finish_reason'] ?? null,
                    ];
                    return (new TextOnlyContentMessage(
                        ConversationRole::ASSISTANT,
                        new TextObject($choice['message']['content'],
                        [
                            'reasoning' => $choice['message']['reasoning']  ?? null,
                        ]),
                        $metadata
                    ));
                }
                else
                {
                    dd("Support this kind of response choice", $choice);
                }
            }, $output['choices'])
        ];

        return array_values($results);
    }
}
