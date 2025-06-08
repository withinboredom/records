<?php

use Withinboredom\Records\Record;

readonly class MoneyTesting extends Record {
	public int $pennies;

	public static function from(int $pennies): self {
		return self::fromArgs($pennies, $pennies);
	}

	protected static function create(...$args): static
	{
		$m = new self();
		$m->pennies = $args[0] ?? $args['pennies'];
		return $m;
	}

	protected static function deriveIdentity(...$args): object|int|string
	{
		return $args[0] ?? $args['pennies'];
	}
}

readonly class MoneyTesting2 extends Record {
	public int $pennies;
	public static function from(int $pennies): self {
		return self::fromArgs($pennies);
	}

	protected static function create(...$args): static
	{
		$m = new self();
		$m->pennies = $args[0] ?? $args['pennies'];
		return $m;
	}

	protected static function deriveIdentity(...$args): object|int|string
	{
		return $args[0] ?? $args['pennies'];
	}
}

readonly class CurrencyTesting1 extends Record {
	public MoneyTesting $money;
	public string $code;

	public static function from(MoneyTesting $money, string $code): self {
		return self::fromArgs($money, $code);
	}

	protected static function create(...$args): static
	{
		$m = new self();
		$m->money = $args[0] ?? $args['money'];
		$m->code = $args[1] ?? $args['code'];
		return $m;
	}

	protected static function deriveIdentity(...$args): object|int|string
	{
		return $args[0] ?? $args['money'];
	}
}

it('can create an arbitrary record', function() {
	$m1 = MoneyTesting::from(100);
	$m2 = MoneyTesting::from(100);
	expect($m1)->toBe($m2);
});

it('will not mix up records', function () {
	$m1 = MoneyTesting::from(100);
	$m2 = MoneyTesting2::from(100);
	expect($m1)->not->toBe($m2);
});

it('can use an object as an id', function() {
	$x = function() {
		$m = MoneyTesting::from(100);
		$c = CurrencyTesting1::from($m, 'USD');
		$c2 = CurrencyTesting1::from($m, 'USD');
		expect($c)->toBe($c2);
		return $c;
	};

	$y = $x();
	$z = $x();
	expect($y)->toBe($z);
});

it('does not allow cloning', function() {
	$m = MoneyTesting::from(10);
	expect(fn() => clone $m)->toThrow(LogicException::class);
});

it('does not allow unserialization', function() {
	$m = MoneyTesting::from(10);
	expect(fn() => unserialize(serialize($m)))->toThrow(LogicException::class);
});

readonly class UserTest extends Record {
	public string $name;
	public int $id;
	public string $email;

	protected static function create(...$args): static
	{
		return new self()->with(...$args);
	}

	protected static function deriveIdentity(...$args): array
	{
		return $args;
	}

	public static function from(string $name, int $id, string $email): static {
		return self::fromArgs(name: $name, id: $id, email: $email);
	}
}

it('can use with() on a record', function() {
	$user = UserTest::from('bob', 1, '<EMAIL>');
	$other = $user->with(email: "other-email");
	expect($user)->not->toBe($other);
	expect($user->email)->toBe('<EMAIL>');
	expect($other->email)->toBe('other-email');
});