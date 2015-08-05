# GeeVeeAPI
Sends SMS messages to any phone number using Google Voice account


#How to use?
- Without using composer:
Include the API file src/GeeVee/GeeVeeAPI.php

-Using Composer:
```
"require": {
  "tedivm/fetch": "0.6.*"
}
```


#Examples
```php
$geevee = new GeeVee\GeeVeeAPI("YOUR_EMAIL_ADDRESS@gmail.com", "YOUR_PASSWORD");
```


Send SMS
```php
$geevee->sendSMS('1234561234', 'Hello there');
```

Get all messages
```php
$all = $geevee->getAllMessages();
```


Get only messages form certain number
```php
$from_number = $geevee->getMessagesFrom('1234561234');
```

Get only messages from multiple numbers
```php
$numbers = array('1234561234', '5555555555');
$from_numbers = $geevee->getMessagesFrom($numbers);
```

Mark a message as read
```php
$geevee->markAsRead($message_id);
```

Delete message
```php
$geevee->delete($message_id);
```
