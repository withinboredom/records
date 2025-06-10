<?php

namespace Withinboredom;

use WeakMap;
use WeakReference;
use Withinboredom\Record\Attributes\ConstrainWith;
use Withinboredom\Record\Attributes\Immutable;
use Withinboredom\Record\Attributes\RecordAttribute;

abstract readonly class Record
{
	/**
	 * @var object|int|string|array|float The record's identifier
	 */
	private object|int|string|array|float $id;

	/**
	 * All records must have a private constructor and should never be created except through factories
	 */
	final protected function __construct() {}

	/**
	 * Create a new or interned record from the given arguments
	 *
	 * @param mixed ...$args The arguments to create the type
	 * @return static The record
	 */
	final protected static function fromArgs(mixed ...$args): static
	{
		$id = static::deriveIdentity(...$args);

		return self::fromClosure($id, function () use ($args) {
			return static::create(...$args);
		});
	}

	/**
	 * Given the arguments, deriveIdentity MUST return a stable identity.
	 *
	 * @param mixed ...$args The arguments used to derive the identity
	 * @return object|int|string|array|float An identity
	 */
	protected static function deriveIdentity(mixed ...$args): object|int|string|array|float
	{
		return $args;
	}

	/**
	 * Creates a record with the given ID and calls the $create closure if it is not yet interned.
	 *
	 * @param object|string|int|array|float $id The identity of the record.
	 * @param \Closure $create Called to create the record if it does not exist.
	 * @return static The record.
	 */
	final protected static function fromClosure(object|string|int|array|float $id, \Closure $create): static
	{
		$records = &static::getRecords();
		$type = static::class;
		$record = null;

		$originalId = $id;
		if (is_array($id)) {
			$id = self::getArrayId($type, $id);
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
			$record->id = $originalId;
		}
		return $record;
	}

	/**
	 * Gets the interned records for this runtime.
	 *
	 * @return array<string, WeakMap|array>
	 */
	private static function &getRecords(): array
	{
		static $records = [];
		return $records;
	}

	/**
	 * Determines an identity for a record keyed by an array.
	 *
	 * @param array|int $id The identity.
	 * @param bool $delete Whether to delete the given identity.
	 * @return int The stable identifier.
	 */
	private static function getArrayId(string $type, array|int $id, bool $delete = false): int
	{
		static $ids = [];

		if(is_int($id)) {
			$key = $id;
		} else {
			$key = array_search($id, $ids[$type] ?? [], true);
		}

		if($delete) {
			if($key !== false) {
				unset($ids[$type][$key]);
			}
			// if $key is false, we deliberately fail here, when returning false,
			// this shouldn't happen, and it would be unrecoverable anyway.
			return $key;
		}

		if ($key === false) {
			$key = count($ids[$type] ?? []);
			$ids[$type][$key] = $id;
		}

		return $key;
	}

	/**
	 * Creates a new record with the given arguments.
	 *
	 * @param mixed ...$args
	 * @return static
	 */
	protected static function create(mixed ...$args): static
	{
		return new static()->with(...$args);
	}

	final public function with(mixed ...$args): static
	{
		if (($this->id ?? false) && is_array($this->id)) {
			$id = static::deriveIdentity(...array_replace($this->id, $args));

			if (count($id) !== count($this->id)) {
				throw new \LogicException("unknown property: " . array_key_last($args));
			}
		} else {
			$id = static::deriveIdentity(...$args);
		}

		// based on crell\Evolvable
		return self::fromClosure($id, function () use ($args, $id) {
			$r = new \ReflectionClass(static::class);
			$clone = $r->newInstanceWithoutConstructor();

			$clone->id = $id;
			$changes = [];
			$checks = [];

			foreach ($r->getProperties() as $property) {
				$attributes = $property->getAttributes(RecordAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
				$field = $property->getName();
				if (array_key_exists($field, $args)) {
					// value is changing
					foreach($attributes as $attribute) {
						$a = $attribute->newInstance();
						if($a instanceof Immutable) {
							throw new \LogicException("cannot change immutable property: " . $field);
						}
						if(($a instanceof ConstrainWith) && !in_array($a->changeTogether, $changes, true)) {
							$checks[] = $a->changeTogether;
						}
					}

					$changes[] = $field;

					$property->setValue($clone, $args[$field]);
				} elseif ($property->isInitialized($this)) {
					// value is not changed
					$property->setValue($clone, $property->getValue($this));
				}
			}

			foreach($checks as $field) {
				if(!in_array($field, $changes, true)) {
					throw new \LogicException("cannot change property: " . $field . " without changing " . implode(", ", $changes));
				}
			}

			return $clone;
		});
	}

	public function __destruct()
	{
		$records = &static::getRecords();
		$type = static::class;
		if (isset($this->id)) {
			if (is_array($this->id)) {
				$id = self::getArrayId($type, $this->id, true);
				unset($records[$type][$id]);
			} else {
				unset($records[$type][$this->id]);
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