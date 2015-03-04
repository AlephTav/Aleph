# Aleph\Cache\File #

## General information ##

|||
| --- | --- |
| **Inheritance** | Aleph\Cache\Cache |
| **Child classes** | no |
| **Interfaces** | Countable |
| **Source** | lib/Cache/File.php |

Class for caching data based on file system. The caching data is serialized and placed in a separate file, which is located in a specified folder (directory cache). Because file system does not automatically delete expired cache files, the class overrides method **gc()** of the parent class that allows to remove expired cached data.

## Public static methods ##

### **isAvailable()**

```php
public static boolean isAvailable()
```

Returns always TRUE because of permanent availability of file system.

## Public nonstatic properties ##

### **directoryMode**

```php
public integer $directoryMode = 0777
```

Permissions for newly created cache directory.

### **fileMode**

```php
public integer $fileMode = 0666
```

Permissions for newly created cache files.

## Public nonstatic methods ##

### **getDirectory()**

```php
public string getDirectory()
```

Returns the current cache directory.

### **setDirectory()**

```php
public void setDirectory(string $path = null)
```

||||
| --- | --- | --- |
| **$path** | string | cache directory. |

Sets the directory to store cache files. If this directory does not exist, the framework will attempt to create it.

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