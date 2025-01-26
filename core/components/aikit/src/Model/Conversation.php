<?php
namespace modmore\AIKit\Model;

use xPDO\xPDO;

/**
 * Class Conversation
 *
 * @property string $title
 * @property int $started_by
 * @property int $started_on
 * @property int $last_message_on
 * @property int $prompt_token_count
 * @property int $response_token_count
 *
 * @property \modmore\AIKit\Model\Message[] $Messages
 *
 * @package modmore\AIKit\Model
 */
class Conversation extends \modmore\AIKit\Model\BaseObject
{
}
