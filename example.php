<?php
require_once __DIR__ . '/vendor/autoload.php';

$geevee = new GeeVee\GeeVeeAPI("YOUR_EMAIL_ADDRESS@gmail.com", "YOUR_PASSWORD");

//send SMS
$geevee->sendSMS('1234561234', 'Hello there');

//get all messages
$all = $geevee->getAllMessages();

//get only messages form certain number
$from_number = $geevee->getMessagesFrom('1234561234');

//get only messages from multiple numbers
$numbers = array('1234561234', '5555555555');
$from_numbers = $geevee->getMessagesFrom($numbers);

//Mark a message as read
$geevee->markAsRead($message_id);

//delete message
$geevee->delete($message_id);
