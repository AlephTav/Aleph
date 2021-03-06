# Aleph\Cache\Cache #

## Общая информация ##

|||
| --- | --- |
| **Наследование** | нет |
| **Дочерние классы** | Aleph\Cache\File, Aleph\Cache\Memory, Aleph\Cache\APC, Aleph\Cache\PHPRedis, Aleph\Cache\Redis, Aleph\Cache\Session |
| **Интерфейсы** | Countable |
| **Файл** | lib/Cache/Cache.php |

Класс **Cache** является базовым классом всех классов фрэймворка используемых для кэширования данных. **Cache** предоставляет методы для работы с кэшируемыми данными объединёнными в группы, а также фабричный метод для получения экземпляра кэширующего класса заданного типа.

```php
// Получить экземпляр класса для работы с файловым кэшем.
$cache = Cache::getInstance('file');
// Сохранить 'some data' в кэше на  10 секунд под идентификатором 'key'.
$cache->set('key', 'some data', 10);
// Прочитать данные из кэша ассоциированные с идентификатором 'key'.
$data = $cache->get('key');
// Удалить данные из кэша по их идентификатору.
$cache->remove('key');
// Проверить что данные в кэше не существуют или время их жизни истекло.
var_dump($cache->isExpired('key'));
// Создать три записи в кэше и добавить их в группу с именем 'foo'.
$cache->set('k1', 'v1', 5, 'foo');
$cache->set('k2', 'v2', 5, 'foo');
$cache->set('k3', 'v3', 5, 'foo');
// Получить все данные из группы 'foo'.
$data = $cache->getByGroup('foo');
// Удалить все данные из группы 'foo'.
$cache->cleanByGroup('foo');
``` 

## Общедоступные статические методы ##

### **getInstance()**

```php
public static Aleph\Cache\Cache getInstance(string $type = null, array $params = null)
```

||||
| --- | --- | --- |
| **$type** | string | тип класса кэширования. |
| **$params** | array | параметры конструктора заданного класса. |

Возвращает объект заданного класса кэширования. Вторым параметром метода является список параметров конструктора класса кэширования. Ниже приведены параметры конструктора в зависимости от типа кэша:

1. Тип **File**:
   - string **directory** - директория кэширования. Если такой директории не существует, фрэймворк попытается создать её.
2. Type **Memory**:
   - array **servers** - массив конфигурационных параметров для memcache сервера. Для более подробной информации смотрите [http://php.net/manual/ru/memcache.addserver.php](http://php.net/manual/ru/memcache.addserver.php)
   - boolean **compress** - определяет нужно ли сжимать данные перед помещением их в кэш. Значение по умолчанию TRUE.
3. Type **APC**. Не содержит параметров.
4. Type **PHPRedis**:
   - **host** - хост или путь к сокету для подключения к redis. Значение по умолчанию 127.0.0.1
   - **port** - порт соединения, опционально. Значение по умолчанию 6379.
   - **timeout** - допустимое время соединения, опционально.
   - **password** - пароль для аутентификации на сервере, опционально.
   - **database** - номер используемой базы данных, опционально.
5. Type **Redis**. Содержит такие же параметры как и **PHPRedis**.
6. Type **Session**. Не содержит параметров.

Если тип кэша не задан, то метод попытается прочитать тип кэша и его параметры из конфигурационного файла из секции **cache**. Если такая секция не определяет какой-либо кэш или тип кэша не задан, то типом кэша по умолчанию станет файловый кэш.

### **isAvailable()**

```php
public static boolean isAvailable()
```

Возвращает TRUE, если кэш заданного типа доступен для использования и FALSE в противном случае.


## Общедоступные нестатические методы ##

### **set()**

```php
abstract public void set(string $key, mixed $content, integer $expire, string $group = null)
```

||||
| --- | --- | --- |
| **$key** | string | уникальный идентификатор кэшируемых данных. |
| **$content** | mixed | данные для кэширования. |
| **$expire** | integer | время жизни кэша в секундах. |
| **$group** | string | имя группы. |

Сохраняет данные в кэше по их уникальному идентификатору.

### **get()**

```php
abstract public mixed get(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | уникальный идентификатор кэшируемых данных. |

Извлекает из кэша данные по их идентификатору.

### **remove()**

```php
abstract public void remove(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | уникальный идентификатор кэшируемых данных. |

Удаляет данные из кэша по их идентификатору.

### **isExpired()**

```php
abstract public boolean isExpired(string $key)
```

||||
| --- | --- | --- |
| **$key** | string | уникальный идентификатор кэшируемых данных. |

Возвращает TRUE если время жизни кэша истекло и FALSE в противном случае.

### **clean()**

```php
abstract public void clean()
```

Удаляет все данные из кэша.

### **gc()**

```php
public void gc(float $probability = 100)
```

||||
| --- | --- | --- |
| **$probability** | float | вероятность вызова метода в процентах. |

Сборщик мусора. Нормализует хранилище ключей для групп.

### **count()**

```php
public integer count()
```

Возвращает количество ключей кэшируемых данных добавленных в группы. Этот метод часть интерфейса **Countable**. Это означает что вы можете применять функцию **count()** к объекту кэша.

### **getVault()**

```php
public array getVault()
```

Возвращает массив ключей кэшируемых данных объединённых в группы. Формат возвращаемого массива:

```
[
 'имя группы' => ['ключ' => время жизни кэша, 'ключ' => время жизни кэша, ... ],
 'имя гурппы' => ['ключ' => время жизни кэша, 'ключ' => время жизни кэша, ... ],
  ...
]
```

### **getVaultLifeTime()**

```php
public integer getVaultLifeTime()
```

Возвращает время жизни кэша (в секундах) хранилища ключей для групп.

### **setVaultLifeTime()**

```php
public integer setVaultLifeTime(integer $vaultLifeTime)
```

||||
| --- | --- | --- |
| **$vaultLifeTime** | integer | время жизни кэша хранилища ключей для групп. |

Устанавливает время жизни кэша хранилища ключей для групп.

### **normalizeVault()**

```php
public void normalizeVault()
```

Удаляет ключи для просроченных данных из хранилища ключей.

### **getByGroup()**

```php
public array getByGroup(string $group)
```

||||
| --- | --- | --- |
| **$group** | string | название группы. |

Возвращает данные сохранённые в кэше для заданной группы по имени этой группы. Пример:

```php
// Пишем данные в кэш для группы 'my group'.
foreach ($i = 1; $i <= 5; $i++) $cache->set('key' . $i, $i, 100, 'my group');
// Извлекаем данные для группы 'my group'.
print_r($cache->getByGroup('my group'));
// В результате получим массив:
// ['key1' => 1, 'key2' => 2, 'key3' => 3, 'key4' => 4, 'key5' => 5];
```

### **cleanByGroup()**

```php
public void cleanByGroup(string $group)
```

||||
| --- | --- | --- |
| **$group** | string | название группы. |

Удаляет группу кэшируемых данных из кэша по её имени.

## Защищённые нестатические методы ##

### **vaultLifeTime**

```php
protected integer $vaultLifeTime = 31536000
```

Время жизни кэша хранилища ключей для групп. Время жизни задаётся в секундах.


## Защищённые нестатические методы ##

### **saveKeyToVault()**

```php
protected void saveKeyToVault(string $key, integer $expire, string $group)
```

||||
| --- | --- | --- |
| **$key** | string | уникальный идентификатор кэшируемых данных. |
| **$expire** | integer | время жизни кэша. |
| **$group** | string | название группы. |

Сохраняет идентификатор кэшируемых данных в хранилище ключей для групп. Этот метод должен вызываться в соотвествующей рееализации абстрактного метода **set()**.

> Если **$group** равен NULL, то **$key** не будет добавлен в хранилище ключей.

### **normalizeExpire()**

```php
protected integer normalizeExpire(integer $expire)
```

||||
| --- | --- | --- |
| **$expire** | integer | время жизни кэша в секундах. |

Нормализует значение времени жизни кэша.

### **normalizeVault()**

```php
protected void normalizeVault()
```

Удаляет ключи просроченных данных из хранилища ключей.