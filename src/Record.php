<?php

namespace Withinboredom\Records;

use WeakMap;
use WeakReference;

abstract readonly class Record
{
	private object|int|string|array $id;
	private bool $isArray;

	/**
	 * All records must have a private constructor and should never be created except through factories
	 */
	final protected function __construct() {}

	protected static function fromArgs(mixed ...$args): static
	{
		$id = static::deriveIdentity(...$args);

		return self::fromClosure($id, function () use ($args) {
			return static::create(...$args);
		});
	}

	abstract protected static function deriveIdentity(mixed ...$args): object|int|string|array;

	protected static function fromClosure(object|string|int|array $id, \Closure $create): static
	{
		$records = &static::getRecords();
		$type = static::class;
		$record = null;

		$isArray = is_array($id);
		if (is_array($id)) {
			$id = self::getArrayId($id);
		}

		if (is_object($id)) {
			$records[$type] ??= new WeakMap();
			if (!($records[$type] instanceof WeakMap)) {
				throw new \RuntimeException('cannot mix object and non-object ids in the same record type');
			}
		} else {
			$records[$type] ??= [];
		}

		$reference = $records[$type][$id] ??= WeakReference::create(($record = $create()));
		$record ??= $reference->get();
		if (!isset($record->id)) {
			$record->id = $id;
			$record->isArray = $isArray;
		}
		return $record;
	}

	private static function &getRecords(): array
	{
		static $records = [];
		return $records;
	}

	private static function getArrayId(array|int $id, bool $delete = false): int
	{
		static $ids = [];
		static $freelist = [];
		if (is_int($id)) {
			if ($delete) {
				$freelist[] = $id;
			}
			return $id;
		}
		$match = null;
		foreach ($ids as $i => $arr) {
			if ($arr === $id) {
				$match = $i;
				if ($delete) {
					$freelist[] = $i;
				}
				break;
			}
		}
		if ($match === null) {
			$nextId = array_pop($freelist);
			if ($nextId === null) {
				$nextId = count($ids);
				$ids[] = $id;
			} else {
				$ids[$nextId] = $id;
			}
			return $nextId;
		}
		return $match;
	}

	abstract protected static function create(mixed ...$args): static;

	public function with(mixed ...$args): static
	{
		$id = static::deriveIdentity(...$args);
		$record = self::fromClosure($id, function () use ($args, $id) {
			$r = new \ReflectionClass(static::class);
			$clone = $r->newInstanceWithoutConstructor();
			if($isArray = is_array($id)) {
				$id = self::getArrayId($id);
			}

			$clone->id = $id;
			$clone->isArray = $isArray;

			foreach ($r->getProperties() as $rprop) {
				$field = $rprop->getName();
				if (array_key_exists($field, $args)) {
					$rprop->setValue($clone, $args[$field]);
				} elseif ($rprop->isInitialized($this)) {
					$rprop->setValue($clone, $rprop->getValue($this));
				}
			}
			return $clone;
		});

		return $record;
	}

	/*
	 * called once all records are collected for this instance
	 */

	public function __destruct()
	{
		$records = &static::getRecords();
		$type = static::class;
		if (isset($this->id)) {
			unset($records[$type][$this->id]);
			if($this->isArray) {
				self::getArrayId($this->id, true);
			}
		}
		if (empty($records[$type])) {
			unset($records[$type]);
		}
	}

	final public function __clone(): void
	{
		throw new \LogicException('do not clone records');
	}

	final public function __unserialize(array $data): void
	{
		throw new \LogicException('do not unserialize records');
	}
}