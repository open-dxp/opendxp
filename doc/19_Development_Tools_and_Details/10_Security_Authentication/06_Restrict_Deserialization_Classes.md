# Restrict Deserialization Classes

Some places in OpenDXP call PHP's `unserialize()` on data the reading code doesn't fully control the shape of, 
for example an object stored via `TmpStore` or the admin session's security token.

Reconstructing an arbitrary class from such data is a known PHP security risk (object injection),
so these places restrict which classes `unserialize()` is allowed to reconstruct.

The allowed classes are configurable per scope under `opendxp.serialization`:

```yaml
opendxp:
    serialization:
        authentication:
            allowed_classes:
                App\Security\MyCustomToken: true
        tmp_store:
            allowed_classes:
                App\Model\MyStoredObject: true
```

A project only ever adds entries here. OpenDXP's own built-in classes for each scope stay in
place regardless of what a project configures, so a project can't accidentally remove them.

## Scopes

| Scope          | Config key       | Used for                                                                                                                                                                                             |
|----------------|------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Authentication | `authentication` | The admin session's security token (`\OpenDxp\Tool\Authentication`). Add a class here if you register a custom authenticator or two-factor provider for the admin firewall with its own token class. |
| TmpStore       | `tmp_store`      | Objects stored via `\OpenDxp\Model\Tool\TmpStore::add()`/`set()`. Add a class here if you store an object, not just scalars or arrays, through TmpStore.                                             |

## In code

`\OpenDxp\Tool\Serialize::unserializeWithScope()` reads a scope's configured classes and
restricts `unserialize()` to them:

```php
use OpenDxp\Tool\SerializationScope;
use OpenDxp\Tool\Serialize;

$value = Serialize::unserializeWithScope(SerializationScope::TmpStore, $data);
```

Use this whenever new code needs to deserialize data it doesn't fully control the shape of,
instead of calling `unserialize()` or `Serialize::unserialize()` directly.

If the data contains a class that isn't allowed, anywhere in it, `unserializeWithScope()`
returns `null` rather than a value with a broken class hidden a few properties deep in it.
