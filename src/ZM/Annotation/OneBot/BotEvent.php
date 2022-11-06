<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * 机器人相关事件注解
 *
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BotEvent extends AnnotationBase
{
    public ?string $type;

    public ?string $detail_type;

    public ?string $impl;

    public ?string $platform;

    public ?string $self_id;

    public ?string $sub_type;

    public function __construct(
        ?string $type = null,
        ?string $detail_type = null,
        ?string $impl = null,
        ?string $platform = null,
        ?string $self_id = null,
        ?string $sub_type = null
    ) {
        $this->type = $type;
        $this->detail_type = $detail_type;
        $this->impl = $impl;
        $this->platform = $platform;
        $this->self_id = $self_id;
        $this->sub_type = $sub_type;
    }

    public static function make(
        ?string $type = null,
        ?string $detail_type = null,
        ?string $impl = null,
        ?string $platform = null,
        ?string $self_id = null,
        ?string $sub_type = null
    ): BotEvent {
        return new static(...func_get_args());
    }
}
