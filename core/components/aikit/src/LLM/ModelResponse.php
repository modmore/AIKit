<?php

namespace modmore\AIKit\LLM;

class ModelResponse
{
    public const FINISH_REASON_ERROR = 'error';
    public const FINISH_REASON_STOP = 'stop';
    public const FINISH_REASON_LENGTH = 'length';
    public const FINISH_REASON_CONTENT_FILTER = 'content_filter';
    public const FINISH_REASON_TOOL_CALLS = 'tool_calls';

    private array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getRawResponse(): array
    {
        return $this->response;
    }

    public function getFinishReason(): string
    {
        if (!empty($this->response['error']['message'])) {
            return self::FINISH_REASON_ERROR;
        }
        return $this->response['choices'][0]['finish_reason'] ?? self::FINISH_REASON_STOP;
    }

    public function getResponseText(): string
    {
        return $this->response['choices'][0]['message']['content'] ?? $this->response['error']['message'] ?? '';
    }

    public function getPromptTokens(): int
    {
        return $this->response['usage']['prompt_tokens'] ?? 0;
    }

    public function getResponseTokens(): int
    {
        return $this->response['usage']['completion_tokens'] ?? 0;
    }

    public function getMessageId()
    {
        return $this->response['id'] ?? ''; // @todo header?
    }

    public function getToolCalls(): array
    {
        return $this->response['choices'][0]['message']['tool_calls'] ?? [];
    }
}
