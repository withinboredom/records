<?php

namespace Withinboredom\Records;

use Closure;
use WeakMap;
use WeakReference;

abstract readonly class Record
{
	private object|int|float|string $id;

	/**
	 * All records must have a private constructor and should never be created except through factories
	 */
	final protected function __construct() {}

	protected static function fromClosure(object|int|string|float $id, Closure $builder): static
	{
		$records = &static::getRecords();
		$type = static::class;
		$record = null;

		if(is_object($id)) {
			$records[$type] ??= new WeakMap();
			if(!($records[$type] instanceof WeakMap)) {
				throw new \RuntimeException('cannot mix object and non-object ids in the same record type');
			}
		} else {
			$records[$type] ??= [];
		}

		$reference = $records[$type][$id] ??= WeakReference::create(($record = $builder()));
		$record ??= $reference->get();
		if (!isset($record->id)) {
			$record->id = $id;
		}
		return $record;
	}

	private static function &getRecords(): array
	{
		static $records = [];
		return $records;
	}

	/*
	 * called once all records are collected for this instance
	 */
	public function __destruct()
	{
		$records = &static::getRecords();
		$type = static::class;
		unset($records[$type][$this->id]);
		if(empty($records[$type])) {
			unset($records[$type]);
		}
	}
}