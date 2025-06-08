# PHP Records

This library provides a `Record` base class to build records with.

## Examples

The below example shows how to create a User class that contains several properties

```php
use Withinboredom\Record;
use Withinboredom\Record\Attributes\ConstrainWith;

readonly class User extends Record {
	public string $name;
	public string $email;
	
	#[ConstrainWith(changeTogether: 'email')]
	#[ConstrainWith(changeTogether: 'name')]
	public int $id;
    
	public static function from(string $name, int $id, string $email): static {
		return self::fromArgs(name: $name, id: $id, email: $email);
	}
}
```
All you need to do is define a factory constructor, and the base class will handle the rest.
Using it is relatively simple:

```php
$user1 = User::from('Rob', 12, 'rob@example.com');
// later
$user2 = User::from('Rob', 12, 'rob@example.com');

assert($user1 === $user2); // true

$bob = $user1->with(name: 'Bob');

assert($user1 !== $bob); // true

$other = $bob->with(id: 42); // throws a LogicException
```

## Attributes

This library comes with a couple of attributes:

### Immutable

Prevents accidentally mutating a property with `with()`.
This is useful when you are manually setting the identity and want to ensure the identity remains stable.

```php
class Currency extends Record {
  public int $pennies;
  
  #[Immutable]
  public string $code;
  
  public function from(string $code, int $pennies): self {
    return self::fromArgs(code: $code, pennies: $pennies);
  }
}
```

This will prevent you from accidentally changing the code by doing `$balance->with(code: 'EUR')`.

### ConstrainWith

Requires a property changed with `with()` to also require other properties to be changed.
Note that when we say "change" here, we do not mean the value, but it must be included together.

```php
class Currency extends Record {
  public int $pennies;
  
  #[ConstrainWith('pennies')]
  public string $code;
  
  public function from(string $code, int $pennies): self {
    return self::fromArgs(code: $code, pennies: $pennies);
  }
}

$balance = Currency::from('USD', 42);
$transfer = $balance->with(code: 'EUR', pennies: 42); // constrain just requires it to be in the with(), not the value to change
```

## Advanced usages

There are several hooks you can use to either increase the performance or behaviour:

> `protected static function deriveIdentity(mixed ...$args): object|int|string|array`

Overriding this method allows you to determine how an object’s identity is derived.
The default implementation assumes you are creating a value object where the identity is the properties in the object.
This may not always be the case, so this allows you to skip fields and generate a different equality.

> `protected static function create(mixed ...$args): static`

Overriding this method allows you to change how objects are created when they do not already exist in the interned map.
It is not recommended to change this.

