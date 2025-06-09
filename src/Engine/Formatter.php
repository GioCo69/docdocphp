<?php
namespace DocDoc\Engine;

use InvalidArgumentException;
/**
 * Helper Class Formatter
 * 
 * Si tratta di una classe che incapsula la Log, un altra Helper Class
 * per restituire funzionalità di formattazione complesse della stringa
 * nel messaggio di Log
 * 
 * Ad esempio, la classe Log si limita a esporre una lista di emojis,
 * questa classe implementa un metodo che riconosce TAG espliciti, come:
 * ```php
 * $tag_info = "[info] Questo è un messaggio di informazione generica";
 * $message = Formatter::parseEmojiMessage($tag_info);
 * ```
 * e li sostituisce, mandando a video il risultato:
 * <p style="color:silver;background:black"><br>
 * $> test_message.php "Esempio messaggio pulito [clean]"<br>
 * <span style="color:yellow">Esempio messaggio pulito 🧹</span><br>
 * $> <br><br>
 * </p>
 * 
 * Un altro esempio più complesso è la sprintf che con un codice del genere:
 * ```php
 * $message = $argv[1] ?: "[WARNING] Esempio messaggio pulito [clean] da %s";
 * echo "Stringa: \"{$message}\"\n";
 * echo Formatter::sprintf($message, "GioCo69!") . "\n";
 * ```
 * produce un effetto di questo genere:
 * <p style="color:silver;background:black"><br>
 * $> test_message.php<br>
 * <span style="color:yellow">⚠️ Esempio messaggio pulito 🧹 da GioCo69!</span><br>
 * $> <br><br>
 * </p>
 * 
 * 
 */
class Formatter extends Log {

    /**
     * Analizza e sostituisce i tag emoji all'interno di una stringa di messaggio.
     *
     * Questo metodo cerca all'interno della stringa tutti i tag racchiusi tra parentesi quadre,
     * come ad esempio [info], [warning], [clean], e li sostituisce automaticamente con l'emoji
     * corrispondente definita nell'array statico $emojis della classe Log.
     * 
     * La funzione utilizza una espressione regolare per individuare i tag e una callback per
     * effettuare la sostituzione. Se il tag trovato corrisponde a una chiave valida nell'array
     * delle emoji, viene sostituito con l'emoji relativa, eventualmente aggiungendo uno spazio
     * extra se richiesto dalla configurazione (ad esempio per alcune emoji che necessitano di
     * maggiore separazione visiva).
     * 
     * Se il tag non viene riconosciuto, viene lasciato invariato.
     * 
     * Esempio di utilizzo:
     * <code>
     * $messaggio = "[info] Operazione completata [clean]";
     * echo Formatter::parseEmojiMessage($messaggio);
     * // Output: "ℹ️ Operazione completata 🧹"
     * </code>
     *
     * @param string $message La stringa di input contenente eventuali tag emoji.
     * @return string La stringa risultante con i tag emoji sostituiti dalle relative emoji.
     */
    public static function parseEmojiMessage(string $message): string {
        // Costruisce la regex dinamicamente in base alle chiavi
        $keys = array_keys(self::$emojis);
        $escaped = array_map('preg_quote', $keys);
        $regex = '/\[\s*(' . implode('|', $escaped) . ')\s*\]/';

        // Sostituisce ogni match con l’emoji corrispondente
        $message = preg_replace_callback($regex, function ($matches) {
            $key = trim($matches[1]);
            $s = (in_array($key, parent::$more_space) ? "  " : " "); 
            return (parent::$emojis[$key] ?? $matches[0]) . $s;
        }, $message);

        return $message;
    }

    /**
     * Override Log::message method
     * 
     * Questa funzione aggiunge la parseEmojiMessage method e poi chiama la Log::message(...)
     * Diversamente dalla Formatter::sprinf non accetta placeholder standard tipo "%s"
     * ma solo personalizzati per le emoji riconociute tipo "[clean]", per l'elenco 
     * vedere la classe Log
     * 
     * **Vedi**: {@see \DocDoc\Engine\Log Helper Log Class}
     * 
     * @param string $message
     * @param mixed $level
     * @return void
     */
    public static function message(string $message, ?string $level = 'info'): void {
        $level = $level ?? 'info';
        $message = self::string_message($message, $level);
        echo self::parseEmojiMessage($message) . "\n";

        // die();
    }

    /**
     * Metodo sprintf
     * 
     * Funziona in modo simile all'sprintf php:
     * - supporta tutti i placeholder sprintf standard (%s, %d, %f, %x, ecc.).
     * - Cast impliciti accettati solo se PHP li accetterebbe normalmente (es. '123' per %d va bene, ma 'abc' no)
     * - non supporta %1$s, %2$d ecc. con indicizzazione posizionale
     * 
     * Il seguente codice php:
     * ```php
     * $message = $argv[1] ?: "[WARNING] Esempio messaggio pulito [clean] da %s";
     * echo "Stringa: \"{$message}\"\n";
     * echo Formatter::sprintf($message, "GioCo69!") . "\n";
     * ```
     * produce un effetto di questo genere:
     * <p style="color:silver;background:black"><br>
     * $> test_message.php<br>
     * <span style="color:yellow">⚠️ Esempio messaggio pulito 🧹 da GioCo69!</span><br>
     * $> <br><br>
     * </p>
     * 
     * @param string $format
     * @param mixed[] $args
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function sprintf(string $format, mixed ...$args): string {
        // Trova tutti i placeholder nella stringa
        preg_match_all('/%([bcdeEfFgGosuxX])/i', $format, $matches);

        $expectedCount = count($matches[0]);
        $actualCount = count($args);

        // Troppi pochi argomenti
        if ($actualCount < $expectedCount) {
            $message = parent::string_message("[FATAL] Troppi pochi argomenti per sprintf: attesi $expectedCount, ricevuti $actualCount.");
            throw new InvalidArgumentException($message);
        }

        // Verifica del tipo per ogni placeholder
        foreach ($matches[1] as $index => $type) {
            $arg = $args[$index];
            $ok = match (strtolower($type)) {
                'b', 'd', 'u', 'o', 'x' => is_int($arg), // numerici interi
                'e', 'f', 'g'           => is_numeric($arg), // float o numeri
                's', 'c'                => true, // qualsiasi cosa castabile a stringa
                default                 => false
            };

            if (!$ok) {
                $message = parent::string_message("[FATAL] Tipo incompatibile per placeholder %$type alla posizione $index: fornito " . gettype($arg));
                throw new InvalidArgumentException($message);
            }
        }

        // Usa solo gli argomenti necessari
        $args = array_slice($args, 0, $expectedCount);
        // echo "--------\n";
        // var_dump($format);
        // var_dump($args);
        // Restituisce la stringa formattata
        $message = vsprintf($format, $args);
        // echo "--------\n";
        // Restituisce la stringa con le emoji se ci sono
        $message = Formatter::parseEmojiMessage($message);
        // restituisce la stringa per gli avvisi di Log se ci sono
        return Formatter::string_message($message);
    }
}