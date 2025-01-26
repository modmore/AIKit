<?php
namespace modmore\AIKit\Model;

use xPDO\xPDO;

/**
 * Class Message
 *
 * @property int $conversation
 * @property string $user_role
 * @property int $user
 * @property string $content
 * @property int $delivered_on
 * @property int $prompt_token_count
 * @property int $response_token_count
 *
 * @package modmore\AIKit\Model
 */
class Message extends \modmore\AIKit\Model\BaseObject
{
}
