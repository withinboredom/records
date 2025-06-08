<?php

use Withinboredom\Records\Record;

readonly class MoneyTesting extends Record {
	public int $pennies;

	public static function from(int $pennies): self {
		return self::fromClosure($pennies, static function() use ($pennies) {
			$m = new self();
			$m->pennies = $pennies;
			return $m;
		});
	}
}

readonly class MoneyTesting2 extends Record {
	public int $pennies;
	public static function from(int $pennies): self {
		return self::fromClosure($pennies, static function() use ($pennies) {
			$m = new self();
			$m->pennies = $pennies;
			return $m;
		});
	}
}

readonly class CurrencyTesting1 extends Record {
	public MoneyTesting $money;
	public string $code;

	public static function from(MoneyTesting $money, string $code): self {
		return self::fromClosure($money, static function() use ($money, $code) {
			$m = new self();
			$m->money = $money;
			$m->code = $code;
			return $m;
		});
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