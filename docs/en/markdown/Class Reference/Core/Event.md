# Aleph\Core\Event #

## General information ##

|||
| --- | --- | --- |
| **Inheritance** | no |
| **Child classes** | no |
| **Interfaces** | no |
| **Source** | lib/Core/Event.php |

A simple implementation of a design pattern "Observer" which allows to connect one or more delegates from some event and then calling all the delegates that bound to a specific event.

## Public static methods ##

### **listen()**

```php
public static void listen(string $event, mixed $listener, integer $priority = null, boolean $once = false)
```

||||
| --- | --- | --- |
| **$event** | string | event name. |
| **$listener** | mixed | a delegate that associated with the given event. |
| **$priority** |integer | priority of the delegate. |
| **$once** | boolean | if equals TRUE then the delegate will be invoked only once and thereafter it will be unbound from the event. |

Binds a delegate with the event. Allows to set priority of the delegate. Delegates with a higher priority will be called in the first place.

### **once()**

```php
public static void once(string $event, mixed $listener, integer $priority = null)
```

||||
| --- | --- | --- |
| **$event** | string | event name |
| **$listener** | mixed | a delegate that associated with the given event. |
| **$priority** | intege | priority of the delegate. |

Binds a delegate with the event. The delegate is called only once and thereafter it will be removed from the event.

### **listeners()**

```php
public static integer listeners(string $event = null)
```

||||
| --- | --- | --- |
| **$event** | string | event name. |

Returns the number of delegates bound to the specified event. If the event is not specified, the method returns the total number of delegates for each event.

### **remove()**

```php
public static void remove(string $event = null, mixed $listener = null)
```

||||
| --- | --- | --- |
| **$event** | string | event name. |
| **$listener** | mixed | a delegate that associated with the given event. |

Removes the specified delegate from the specified event. If the individual delegate is not defined, the method deletes all of the delegates from the event. If the event is not set, the method will remove all the delegates from all events.

### **fire()**

```php
public static void fire(string $event, array $args = [])
```

||||
| --- | --- | --- |
| **$event** | string | event name. |
| **$args** | array | array of arguments that will be passed to all delegates of the event. |

Consistently, in according to their priorities, invokes delegates of the event. As the first argument, each delegate receives the name of the event. If any of the delegates will return FALSE, then it will lead to the termination of calls to all subsequent delegates.
