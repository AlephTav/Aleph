# Aleph\Core\Exception #

## General information ##

|||
| --- | --- |
| **Inheritance** | Exception |
| **Child classes** | no |
| **Interfaces** | no |
| **Source** | lib/core/exception.php |

Class **Aleph\Core\Exception** is used to generate typed exceptions within the framework and provides methods to easily identify the type of error that occurred on its token.

## Public non-static methods ##

### **__construct()**

```php
public void __construct(string|object $class, string $token, mixed $var1, mixed $var2, ... )
```

||||
| --- | --- | --- |
| **$class** | string, object | a class that contains error token. |
| **$token** | string | an error token, or equivalently a constant name of an error template that determined in some class. |
| **$var1**, **$var2**, ... | mixed | parameters of error token. |

The class constructor. Generates an error message on her token. Under the token error means some string constant whose value is the error pattern. The constant can be defined inside some class or can be separately defined outside any class.

Template error message can contain embedded variables in format of PHP function **sprintf()**. In the formation of the final error message, these variables will be replaced with the appropriate values​​. Substitution takes place from left to right.

Depending on the value of the constructor parameters, there are three main cases of the formation of the error message:
- Error token is a simple string that is not a constant of any class. In this case the first parameter of the constructor is empty string or FALSE. For example:

    ```php
    // It'll display 'Simple error: 1, 2';
    throw new Aleph\Core\Exception(false, 'Simple error: %s, %s', 1, 2);
    ```
-   Error token is a constant of some class. Exception is thrown within this class. Example:
    ```php
    class A
    {
      const ERR_1 = '%s error template%s';

      public function test()
      {
        throw new Aleph\Core\Exception($this, 'ERR_1', 'Test', '!');
      }
    }

    // It'll display 'Test error template! (Token: A::ERR_1)'
    (new A)->test();
    ```
-   Error token is a constant of some class. Exception is thrown outside of this class. Example:
    ```php
    class A
    {
      const ERR_1 = '%s error template%s';
    }

    // Throws error 'Test error template! (Token: A::ERR_1)'
    throw new Aleph\Core\Exception('A', 'ERR_1', 'Test', '!');

    // It's possible to write more compactly (with the same result):
    throw new Aleph\Core\Exception('A::ERR_1', 'Test', '!');
    ```

### **getClass()**

```php
public string getClass()
```

Returns name of class which contains error token. If error token does not belong some class then the method returns empty string.

### **getToken()**

```php
public string getToken()
```

Returns error token.

### **getInfo()**

```php
public array getInfo()
```

Analyse an exception and returns detailed information about it. Analysis is performed using method **Aleph::analyzeException()**.

## Protected non-static properties ##

### **class**

```php
protected string $class
```

Stores name of class which contains error token. If error token does not belong to any class then property value is empty string.

### **token**

```php
protected string $token
```

Contains error token.