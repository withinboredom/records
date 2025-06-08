<?php

namespace Withinboredom\Record\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class Immutable implements RecordAttribute {}