<?php
namespace DocDoc\Engine;

class Log
{
    public static function message(string|array $message, string $level = 'info'): void
    {
        $colors = [
            'info' => "\033[36m",     // cyan
            'success' => "\033[32m",  // green
            'warning' => "\033[33m",  // yellow
            'error' => "\033[31m",    // red
            'debug' => "\033[90m",    // gray
            'reset' => "\033[0m",
            'fatal' => "\033[1;31m"   // rosso intenso (bold red)
        ];
        $level = $level ?: 'info';
        if (is_array($message)) {
            if (count($message) == 2 && isset($message['str']) && isset($message['lvl'])) {
                $level = $message['lvl'];
                $message = $message['str'];
            } else {
                echo "{$colors['fatal']}❌ FATAL: Log arguments miss ?! \nMessage:{$colors['reset']}\n";
                print_r($message);
                exit(1);
            }
        }


        $color = $colors[$level] ?? '';
        $reset = $colors['reset'];

        echo "{$color}{$message}{$reset}\n";
    }


}
