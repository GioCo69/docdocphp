<?php
namespace DocDoc\Engine;

/**
 * Classe Log 
 * 
 * Fornisce funzionalità avanzate per la gestione e la formattazione dei messaggi di log
 * all'interno dell'applicazione. 
 * 
 * Estende la classe {@see \DocDoc\Engine\Lang}, ereditando così
 * eventuali metodi di internazionalizzazione o gestione delle lingue.
 *
 * Le principali caratteristiche della classe Log includono:
 * - Supporto per diversi livelli di log (info, success, warning, error, fatal, debug, ecc.)
 * - Associazione di colori e emoji ai messaggi in base al livello, per una migliore leggibilità in CLI
 * - Parsing automatico del livello di log direttamente dal messaggio, se specificato nel formato [LEVEL]
 * - Metodi statici per stampare o restituire messaggi formattati in modo coerente
 *
 * Questa classe è pensata per essere utilizzata in contesti CLI o ambienti dove la leggibilità dei log
 * è fondamentale per il debugging e il monitoraggio dell'applicazione.
 */
class Log extends Lang
{
    /**
     * Array associativo che definisce i codici colore ANSI per ciascun livello di log.
     * 
     * Ogni chiave rappresenta un livello di log (ad esempio 'info', 'success', 'warning', ecc.),
     * mentre il valore associato è la sequenza di escape ANSI utilizzata per colorare il testo
     * nel terminale CLI. Questo permette di migliorare la leggibilità dei messaggi di log,
     * associando un colore specifico a ciascun tipo di evento o severità.
     * 
     * La chiave 'reset' serve per ripristinare il colore di default dopo la stampa del messaggio.
     * 
     * Esempio di utilizzo:
     *   echo self::$colors['warning'] . "Attenzione!" . self::$colors['reset'];
     *
     * @var array
     */
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

    /**
     * Array associativo che definisce le emoji utilizzate per ciascun livello o tipo di messaggio di log.
     *
     * Ogni chiave rappresenta un livello di log (ad esempio 'info', 'success', 'warning', ecc.) o una tipologia
     * specifica di evento (come 'file', 'folder', 'add', ecc.), mentre il valore associato è l'emoji che verrà
     * visualizzata accanto al messaggio nel terminale CLI.
     *
     * L'utilizzo delle emoji migliora la leggibilità e l'immediatezza dei log, consentendo di identificare
     * rapidamente la natura del messaggio grazie a simboli visivi intuitivi.
     *
     * Esempio di utilizzo:
     *   echo self::$emojis['warning'] . " Attenzione: operazione rischiosa!";
     *
     * @var array
     */
    public static array $emojis = [
        'none' => "",
        'info' => "📋",
        'success' => "✔️",
        'warning' => "⚠️",
        'error' => "❌",
        'debug' => "⚙️",
        'fatal' => "💥",
        'clean' => "🧹",
        'index' => "📘",
        'file' => "📄",
        'uknow' => "👻",
        'add' => "➕",
        'build' => "🛠️",
        'folder' => "📂",
        'return' => "↪️",
    ];

    /**
     * Array che elenca i livelli di log o le tipologie di messaggio 
     * per cui si desidera aggiungere uno spazio extra dopo l'emoji.
     *
     * Questo array viene utilizzato per migliorare la leggibilità dei messaggi di log nel terminale CLI,
     * aggiungendo uno spazio aggiuntivo tra l'emoji e il testo del messaggio per i livelli specificati.
     * 
     * Ad esempio, per i livelli 'success', 'warning', 'debug', 'build' e 'return', l'emoji sarà seguita da due spazi,
     * rendendo il messaggio più chiaro e visivamente separato dagli altri elementi.
     * 
     * L'aggiunta di spazio extra è utile soprattutto quando alcune emoji risultano graficamente più strette o meno visibili,
     * garantendo così una formattazione uniforme e facilmente leggibile dei log.
     *
     * @var array
     */
    public static array $more_space = [
        'success',
        'warning',
        'debug',
        'build',
        'debug',
        'return'
    ];

    /**
     * Analizza un messaggio di log per rilevare ed estrarre il livello di log specificato tra parentesi quadre,
     * come ad esempio "[WARNING] Messaggio di esempio". Se viene rilevato un livello valido all'inizio o alla fine
     * del messaggio, aggiorna i parametri $level e $color in base al livello trovato e restituisce il messaggio
     * privato del tag [LEVEL]. Se non viene trovato alcun livello, restituisce false e lascia invariati i parametri.
     *
     * Questo metodo consente di specificare dinamicamente il livello di log direttamente all'interno del messaggio,
     * facilitando la scrittura di log più leggibili e strutturati, senza dover sempre passare il livello come parametro separato.
     * Il livello viene riconosciuto in modo case-insensitive e può essere posizionato sia all'inizio che alla fine della stringa.
     *
     * @param string $message Il messaggio di log da analizzare, eventualmente contenente il livello tra parentesi quadre.
     * @param string $level   Variabile passata per riferimento che verrà aggiornata con il livello rilevato (se presente).
     * @param string $color   Variabile passata per riferimento che verrà aggiornata con il colore associato al livello rilevato.
     * @return string|false   Restituisce il messaggio senza il tag [LEVEL] se il livello viene trovato, altrimenti false.
     */
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

    /**
     * Stampa un messaggio di log formattato in base al livello specificato.
     *
     * Questo metodo accetta una stringa di messaggio e un livello di log opzionale (ad esempio 'info', 'warning', 'error', ecc.),
     * e stampa direttamente a schermo il messaggio formattato con il colore e l'emoji associati al livello scelto.
     * 
     * Se il livello non viene specificato, viene utilizzato 'info' come valore predefinito.
     * È possibile anche specificare il livello direttamente all'interno del messaggio utilizzando la sintassi [LEVEL] 
     * (ad esempio "[WARNING] Attenzione: operazione rischiosa"), in tal caso il livello verrà rilevato automaticamente.
     * 
     * Questo metodo è utile per visualizzare rapidamente messaggi di log leggibili e strutturati in ambienti CLI,
     * facilitando il debugging e il monitoraggio dell'applicazione.
     *
     * @param string $message Il messaggio da stampare.
     * @param mixed $level    Il livello di log (opzionale). Può essere una stringa come 'info', 'warning', ecc.
     * @return void
     */
    public static function message(string $message, ?string $level = 'info'): void
    {
        echo self::string_message($message, $level);
    }

    /**
     * Questo metodo accetta una stringa come messaggio 
     * e restituisce una sttringa colorata e con emojy a seconda del livello
     * 
     * Per specificare il livello, basta inserirlo nel messaggo in questo modo:
     * ```php
     * $messaggio = "[ WARNING ] Questo è un esempio di messaggio"
     * ```
     * 
     * Oppure possiamo separare il messaggio dal livello passandolo come secondo parametro.
     * 
     * In ogni caso avremo in restituzione per il precedente esempio, una stringa del genere dal prompt CLI:
     * <p style="color:yellow;background:black"><br>
     * $> test_message.php "[ WARNING ] Questo è un esempio di messaggio"
     * ⚠️ Questo è un esempio di messaggio<br>
     * $><br>
     * </p>
     * 
     * Il colore dipenderà dal livello.
     * Se è necessario usare una stringa parametrizzata, consultare la
     * {@see \DocDoc\Engine\Formatter::sprintf sptinf della classe Formatter}
     * 
     * 
     * @param string $message
     * @param mixed $level
     * @return string
     */
    public static function string_message(string $message, ?string $level = 'none'): string
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
            echo "\n";
            exit(1);
        }

        // ricava l'eventuale livello dal messaggio
        $parsed_message = self::parseLogMessage($message, $level, $color);
        $message = $parsed_message ?: $message;

        $level = $level ?: 'info';
        $color = $color ?: $colors[$level] ?? '';
        $reset = $colors['reset'];

        $emoji = ($emojis[$level] ?? '') . (in_array($level, self::$more_space) ? "  " : " ");
        // echo "emj: [$emoji]\n";
        return "{$color}{$emoji}{$message}{$reset}\n";
    }


}
