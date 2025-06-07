#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';
use DocDoc\Engine\Formatter;

$message = $argv[1] ?: "[WARNING] Esempio messaggio pulito [clean] da %s";
echo "Stringa: \"{$message}\"\n";
// $message = Formatter::parseEmojiMessage($message) . "\n";
// Formatter::message($message);
echo Formatter::sprintf($message, "GioCo69!") . "\n";

