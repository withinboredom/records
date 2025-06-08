# PHP Records

This library provides a `Record` base class to build records with.

## Examples

The below example shows how to create a User class that contains several properties

```php
use Withinboredom\Record;

readonly class User extends Record {
	public string $name;
	public int $id;
	public string $email;
    
	public static function from(string $name, int $id, string $email): static {
		return self::fromArgs(name: $name, id: $id, email: $email);
	}
}
```
All you need to do is define a factory constructor, and the base class will handle the rest.

## Advanced usages

There are several hooks you can use to either increase the performance or 