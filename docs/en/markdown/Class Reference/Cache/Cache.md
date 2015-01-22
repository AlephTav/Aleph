# Aleph\Cache\Cache #

## General information ##

|||
| --- | --- |
| **Inheritance** | no |
| **Child classes** | Aleph\Cache\File, Aleph\Cache\Memory, Aleph\Cache\APC, Aleph\Cache\PHPRedis, Aleph\Cache\Redis, Aleph\Cache\Session |
| **Interfaces** | Countable |
| **Source** | lib/Cache/Cache.php |

Class **Cache** is the base class of all framework's classes, which are used for caching data. Provides methods for working with groups of cached data, as well as factory method to get an instance of the cache of a given type.

```php
// Gets an instance of the file cache.
$cache = Cache::getInstance('file');
// Stores 'some data' in cache during 10 seconds with identifier 'key'.
$cache->set('key', 'some data', 10);
// Reads data.
$data = $cache->get('key');
// Removes cache by its identifier.
$cache->remove('key');
// Checks that the data has been deleted.
var_dump($cache->isExpired('key'));
// Creates three cache blocks that combined to one group 'foo'
$cache->set('k1', 'v1', 5, 'foo');
$cache->set('k2', 'v2', 5, 'foo');
$cache->set('k3', 'v3', 5, 'foo');
// Gets all data of group 'foo'.
$data = $cache->getByGroup('foo');
// Removes all group data.
$cache->cleanByGroup('foo');
``` 

## Public static methods ##

### **getInstance()**

```php
public static Aleph\Cache\Cache getInstance(string $type = null, array $params = null)
```

||||
| --- | --- | --- |
| **$type** | string | type of class for caching. |
| **$params** | array | cache parameters. |

Returns an instance of the given cache class. The second parameter of the method is arguments of the class constructor. Below there is a list of constructor parameters that depends on the cache type:

1. Type **File**:
   - string **directory** - cache directory. If such directory does not exist the framework will try to create it.
2. Type **Memory**:
   - array **servers** - an array of configuration parameters for the memcache servers. To get more information see [http://php.net/manual/ru/memcache.addserver.php](http://php.net/manual/ru/memcache.addserver.php)
   - boolean **compress** - determines whether the data is compressed before being placed in the cache. The default value is TRUE.
3. Type **APC**. It has no config parameters.
4. Type **PHPRedis**:
   - string **host** - host or path to a unix domain socket for a redis connection. The default value is 127.0.0.1
   - integer **port** - port for a connection, optional. The default value is 6379.
   - integer **timeout** - the connection timeout in seconds, optional.
   - string **password** - password for server authentication, optional.
   - integer **database** - number of the redis database to use, optional.
5. Type **Redis**. It has the same parameters as **PHPRedis**.
6. Type **Session**. It has no config parameters.

If the cache type is not specified, the method will attempt to read the cache type and its parameters from a configuration file from section **cache**. If such section does not define some cache or cache type is not specified, the type of cache will be file cache.

### **isAvailable()**

```php
public static boolean isAvailable()
```

Returns TRUE, if the cache of the appropriate type is available for use, and FALSE otherwise.


## Public nonstatic methods ##

### **set()**

```php
abstract public void set(string $key, mixed $content, integer $expire, string $group = null)
```

||||
| --- | --- | --- |
| **$key** | string | unique identifier of caching data. |
| **$content** | mixed | data to cache. |
| **$expire** | integer | cache lifetime in seconds. |
| **$group** | string | cache group name. |

Stores data in cache by their unique identifier.

### **get()**

```php
abstract public mixed get(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | unique identifier of caching data. |

Returns previously stored data by their identifier.

### **remove()**

```php
abstract public void remove(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | unique identifier of caching data. |

Removes data from cache by their identifier.

### **isExpired()**

```php
abstract public boolean isExpired(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | unique identifier of caching data. |

Returns TRUE if cache is expired and FALSE otherwise.

### **clean()**

```php
abstract public void clean()
```

Removes all data from cache.

### **gc()**

```php
public void gc(float $probability = 100)
```

||||
| --- | --- | --- |
| **$probability** | float | probability of method call in percent. |

Garabage collector. Removes all expired cache data and normalizes the vault of group keys.

### **count()**

```php
public integer count()
```

Returns number of group keys. This method is part of interface **Countable**. This means that you can apply function **count()** to the cache object.

### **getVault()**

```php
public array getVault()
```

Returns key array of all data that combined to groups. Format of this array:

```
[
 'group name' => ['key' => lifetime, 'key' => lifetime, ... ],
 'group name' => ['key' => lifetime, 'key' => lifetime, ... ],
  ...
]
```

### **getVaultLifeTime()**

```php
public integer getVaultLifeTime()
```

Returns lifetime (in seconds) of cache vault.

### **setVaultLifeTime()**

```php
public integer setVaultLifeTime(integer $vaultLifeTime)
```

||||
| --- | --- | --- |
| **$vaultLifeTime** | integer | cache vault lifetime in seconds. |

Sets cache vault lifetime in seconds.

### **normalizeVault()**

```php
public void normalizeVault()
```

Removes keys of the expired data from the key vault.

### **getByGroup()**

```php
public array getByGroup(string $group)
```

||||
| --- | --- | --- |
| **$group** | string | cache group name. |

Returns an array of data stored in the cache under the same specified group. Example:

```php
// Write the data in the cache, the group of 'my group'
foreach ($i = 1; $i <= 5; $i++) $cache->set('key' . $i, $i, 100, 'my group');
// Gets array of cached data of group 'my group'
print_r($cache->getByGroup('my group'));
// Displays an array like
// ['key1' => 1, 'key2' => 2, 'key3' => 3, 'key4' => 4, 'key5' => 5];
```

### **cleanByGroup()**

```php
public void cleanByGroup(string $group)
```

||||
| --- | --- | --- |
| **$group** | string | cache group name. |

Removes group of cached data by its name.

## Protected nonstatic properties ##

### **vaultLifeTime**

```php
protected integer $vaultLifeTime = 31536000
```

The lifetime of the cache IDs of cached data. Lifetime is defined in seconds.


## Protected nonstatic methods ##

### **saveKeyToVault()**

```php
protected void saveKeyToVault(string $key, integer $expire, string $group)
```

||||
| --- | --- | --- |
| **$key** | string | unique identifier of caching data. |
| **$expire** | integer | cache lifetime. |
| **$group** | string | cache group name. |

Stores the identifier of the cache in the vault of identifiers. This method should be called at the appropriate implementation of the abstract method **set()**.

> If **$group** is equal NULL, **$key** won't be stored in the vault.

### normalizeExpire()

```php
protected integer normalizeExpire(integer $expire)
```

||||
| --- | --- | --- |
| **$expire** | integer | the cache expiration time in seconds. |

Normalizes the cache expiration time.

### normalizeVault()

```php
protected void normalizeVault()
```

Removes expired data from the cache vault.