<?php

namespace Withinboredom\Record\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
readonly class ConstrainWith implements RecordAttribute
{
	public function __construct(public string $changeTogether) {}
}