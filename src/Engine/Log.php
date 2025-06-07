<?php
namespace DocDoc\Engine;

class Log 
{
    public static array $colors = [
        'none' => "",
        'info' => "\033[36m",     // cyan
        'success' => "\033[32m",  // green
        'warning' => "\033[33m",  // yellow
        'error' => "\033[31m",    // red
        'fatal' => "\033[1;31m",  // rosso intenso (bold red)
        'debug' => "\033[90m",    // gray
        'reset' => "\033[0m",
    ];

    public static array $emojis = [
        'none'    => "",
        'info'    => "📋",
        'success' => "✔️",
        'warning' => "⚠️",
        'error'   => "❌",
        'debug'   => "⚙️",
        'fatal'   => "💥",
        'clean'   => "🧹",
        'index'   => "📘",
        'file'    => "📄",
        'uknow'   => "👻",
        'add'     => "➕",
        'build'   => "🛠️",
        'folder'  => "📂",
        'return'  => "↪️",

    ];
    public static array $more_space = [
        'success', 'warning', 'debug'
    ];

    public static function parseLogMessage(string $message, string &$level = '', string &$color = ''): string|false
    {
        // Prepara lista dei livelli da $colors
        $colors = self::$colors;
        $level = $level ?: 'info';
        $color = $color ?: $colors[$level];

        $keys = array_keys($colors);
        $upperKeys = array_map('strtoupper', $keys);
        $regexLevels = implode('|', array_map('preg_quote', $upperKeys));

        // Regex dinamica basata su chiavi di $colors
        $regex = '/^\s*\[\s*(' . $regexLevels . ')\s*\]\s*|\s*\[\s*(' . $regexLevels . ')\s*\]\s*$/i';

        if (preg_match($regex, $message, $matches)) {
            // Prende la parola catturata (gruppo 1 o 2)
            $matched = $matches[1] ?: $matches[2];
            $level = strtolower($matched);
            $color = $colors[$level] ?? $colors['info'];
            // Rimuove solo il tag [LEVEL] dai bordi
            $cleaned = preg_replace($regex, '', $message);
            return $cleaned;
        }

        // Nessuna corrispondenza trovata
        $level = $level ?: 'info';
        $color = $colors[$level];
        return false;
    }

    public static function message(string|array $message, ?string $level = 'info'): void {
        echo self::string_message($message, $level);
    }

    /**
     * Questo metodo accetta una stringa come messaggio da formattare
     * 
     * Ci sono diversi modi per specificare un livello per questo messaggio di log nel primo parametro,
     * può essere definito come elemento di un vettore:
     * $messaggio = [
     *      'lvl' => 'warning',
     *      'msg' => 'Questo è un esempio di messaggio'
     * ]
     * 
     * Oppure può essere nella stringa:
     * $messaggio = "[ WARNING ] Questo è un esempio di messaggio"
     * 
     * Oppure possiamo separare il messaggio dal livello passandolo come secondo parametro.
     * 
     * In ogni caso avremo in restituzione per il precedente esempio, una stringa del genere dal prompt CLI:
     * <p style="color:yellow;background:black"><br>
     * $> test_message.php "[ WARNING ] Questo è un esempio di messaggio"
     * ⚠️ Questo è un esempio di messaggio<br>
     * $><br>
     * </p>
     * colorato di giallo
     * 
     * 
     * @param string|array $message
     * @param mixed $level
     * @return string
     */
    public static function string_message(string|array $message, ?string $level = 'none'): string
    {
        $colors = self::$colors;
        $level = $level ?: 'none';
        $color = $colors[$level] ?? $colors['none'];
        $colors = self::$colors;
        $emojis = self::$emojis;

        // è stato specificato un livello che non esiste ?
        if (!in_array($level, array_keys($colors))) {
            echo "{$colors['fatal']}{$emojis['fatal']} FATAL: Level unkonw ! \Level:{$level}\n";
            print_r($message);
            exit(1);
        }
        // se il messaggio è un array allora deve essere composto 
        // da un elemento 'lvl' e un elemento 'str'
        if (is_array($message)) {
            $allowed_keys = ['lvl', 'str', 'level', 'string', 'msg', 'message'];
            $message_keys = array_keys($message);
            // Verifica che tutte le chiavi di $message siano ammesse
            $unknown_keys = array_diff($message_keys, $allowed_keys);
            if (!empty($unknown_keys)) {
                $keys = implode(',',array_keys($message));
                echo "{$colors['fatal']}{$emojis['fatal']} FATAL: Message key(s) unkonw ! \nKey(s):{$keys}\n";
                print_r($message);
                echo "{$colors['reset']}\n";
                exit(1);
            }
            if (count($message) == 2 && isset($message['str']) && isset($message['lvl'])) {
                $level = $message['lvl'] ?? $message['level'];
                $message = $message['str'] ?? $message['string'] ?? $message['msg'] ?? $message['message'];
            } else {
                echo "{$colors['fatal']}{$emojis['fatal']} FATAL: Log arguments miss ?! \nMessage:{$colors['reset']}\n";
                print_r($message);
                exit(1);
            }
        } else {
            // non è un array allora può essere che il livello sia nel messaggio
            $parsed_message = self::parseLogMessage($message, $level, $color);
            $message = $parsed_message ?: $message;
            //  echo "lvl: $level\n";
        }
// echo "lvl: {$level}\n";
        $level = $level ?: 'info';
        $color = $color ?: $colors[$level] ?? '';
        $reset = $colors['reset'];

        $emoji = ($emojis[$level] ?? '') . (in_array($level, self::$more_space) ? "  " : " ");
        // echo "emj: [$emoji]\n";
        return "{$color}{$emoji}{$message}{$reset}\n";
    }


}
