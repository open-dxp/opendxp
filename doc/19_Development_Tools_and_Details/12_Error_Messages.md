# Error Messages

An error message helps its reader to find the cause of an error. Developers find error messages in the log. Editors
see them in the admin interface whenever a request fails. Console commands print them in the terminal.

## Structure

A message has two or three sentences:

1. The first sentence names the subject and what failed.
2. The second sentence names the reason.
3. An optional third sentence says what the reader can do.

```php
throw new Exception(sprintf(
    '%s cannot be saved. Its parent %d does not exist.',
    Element\Service::getMessageSubject($this),
    $this->getParentId()
));
```

The message reads:

```text
Object /products/shoe (ID 123) cannot be saved. Its parent 4711 does not exist.
```

## Rules

- Write full English sentences. Every sentence ends with a period.
- Build the message with `sprintf()`.
- Put a value in double quotes when the caller passed it, for example a key or an option: `The key "a/b" is invalid.`
- Write IDs, paths and numbers without quotes.
- Name a limit together with the actual value: `The full path has 812 characters. The limit is 765.`
- Name an unexpected type with `get_debug_type()`: `The value must be an array. A string was given.`

## Name the subject

| Subject                     | Form                                    | Example                                                   |
|-----------------------------|-----------------------------------------|-----------------------------------------------------------|
| Element with an ID          | `Element\Service::getMessageSubject()`  | `Object /products/shoe (ID 123)`                          |
| New element without an ID   | `Element\Service::getMessageSubject()`  | `New object "shoe"`                                       |
| Class definition            | Name and ID of the class                | `Class Product (ID prod)`                                 |
| Field of a class            | Name of the field and name of the class | `Field price of class Product`                            |
| Configuration               | Full path of the key                    | `The option opendxp.assets.thumbnails.max_scaling_factor` |
| Option of a console command | Name of the option with dashes          | `The option --thumbnails`                                 |

## Keep out of a message

- Passwords, tokens, API keys and session IDs
- SQL statements and their parameters
- Whole objects and arrays
- Personal data such as names or email addresses. An ID identifies a user or a customer.
- In a permission error, the ID or the path of an element. The user may not be allowed to see it. The log holds this
  context instead.

## Context in the log

Every log entry carries the current user, the route of the request and the console command. See
[Logging](./07_Logging.md#context-of-a-log-entry). A message does not repeat this context.

An exception message is plain text, so `sprintf()` builds it. A log call follows PSR-3. It keeps the message as a
template with placeholders and passes the values as context. Log tools group entries by the template and index the
values as fields:

```php
$this->logger->error('Object {id} cannot be indexed. The index {index} does not exist.', [
    'id' => $object->getId(),
    'index' => $indexName,
]);
```

## Exception classes

When you change a message, keep the class of the exception. Projects catch exceptions by their class. New code throws
the most specific class that fits:

| Error                                 | Class                                              |
|---------------------------------------|----------------------------------------------------|
| The caller passed an invalid argument | `InvalidArgumentException`                         |
| An element holds invalid data         | `OpenDxp\Model\Element\ValidationException`        |
| A path is already taken               | `OpenDxp\Model\Element\DuplicateFullPathException` |
| The code reaches an impossible state  | `LogicException`                                   |
