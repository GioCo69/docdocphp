<?php
namespace DocDoc\Engine;
use Symfony\Component\Yaml\Yaml;

/**
 * Classe Lang
 *
 * Questa classe gestisce la localizzazione e la traduzione delle stringhe all'interno dell'applicazione.
 * Fornisce metodi per recuperare le traduzioni in base alla lingua selezionata e permette di aggiungere nuove lingue o aggiornare le traduzioni esistenti.
 * È fondamentale per supportare applicazioni multilingua e migliorare l'esperienza utente internazionale.
 */
class Lang
{
  // Valore di default e attuale
  private static string $currentLang = 'it';

  private static array $cache = [];
  
  /**
   * Inizializza la lingua dell'applicazione seguendo una gerarchia di priorità
   * 
   * 1. Se viene passato un parametro $lang, questo ha la precedenza e viene utilizzato come lingua corrente (solo 'it' o 'en' sono accettati).
   * 2. In assenza di parametro, viene cercata una configurazione in un file config.php (se esiste).
   * 3. Se non esiste il file di configurazione, viene controllata la presenza della costante DOCDOC_LANG.
   * 4. In ultima istanza, viene verificata la variabile di ambiente DOCDOC_LANG.
   * Se nessuna delle opzioni precedenti è valida, la lingua predefinita sarà 'it'.
   *
   * @param mixed $lang Lingua da impostare (opzionale, accetta solo 'it' o 'en')
   * @return void
   */
  public static function init(?string $lang = 'it'): void
  {
    // Se passato come parametro accetta ignora tutte le altre impostazioni
    if (isset($lang)) {
      $lang = $lang ?? 'it';
      $lang = in_array($lang, ['it', 'en']) ? $lang : 'it';
      self::setLang($lang);
      return;
    }
    // 1. File di configurazione (richiesto una sola volta)
    $cfgPath = __DIR__ . '/../../config.php'; // Adatta il path se necessario
    if (file_exists($cfgPath)) {
      require_once $cfgPath;
      return;
    }
    // 2. Costante di configurazione
    if (defined('DOCDOC_LANG')) {
      self::$currentLang = constant('DOCDOC_LANG');
      self::logOverride("Configurazione", self::$currentLang);
      return;
    }
    // 3. Variabile di ambiente
    $envLang = getenv('DOCDOC_LANG');
    if ($envLang !== false) {
      self::$currentLang = $envLang;
      self::logOverride("Variabile ambiente", self::$currentLang);
      return;
    }
  }

  /**
   * Imposta manualmente la lingua dell'applicazione.
   * 
   * Questo metodo consente di forzare la lingua corrente, ad esempio quando viene specificata
   * da riga di comando o da uno script esterno. L'impostazione tramite questo metodo ha la precedenza
   * su tutte le altre fonti di configurazione (file di configurazione, costanti, variabili di ambiente).
   * È possibile utilizzare solo le lingue supportate ('it' o 'en').
   *
   * @param string $lang La lingua da impostare ('it' o 'en')
   * @return void
   */
  public static function setLang(string $lang): void
  {
    self::$currentLang = $lang;
    self::logOverride("CLI", $lang);
  }

  /**
   * Recupera tutti i messaggi localizzati disponibili per l'applicazione.
   *
   * Questo metodo restituisce un array contenente le stringhe di testo tradotte
   * in base alla lingua corrente dell'utente o alle impostazioni di localizzazione.
   * Può essere utilizzato per visualizzare messaggi, etichette, notifiche e altri
   * elementi dell'interfaccia utente in modo multilingue.
   *
   * @return array Un array associativo di messaggi localizzati, dove la chiave rappresenta
   *               l'identificatore del messaggio e il valore la traduzione corrispondente.
   */
  public static function messages(bool $help = false): array|string
  {
    static $messagesCache = null;

    $lang = self::$currentLang ?? 'it';
    $langDir = defined('PROJECT_ROOT') ? PROJECT_ROOT . '/lang' : dirname(__DIR__, 2) . '/lang';

    if ($help) {
      $helpFile = $langDir . '/help.yaml';
      if (!file_exists($helpFile)) {
        echo "[fatal] File help.yaml non trovato in $langDir\n";
        exit(1);
      }

      $allHelp = Yaml::parseFile($helpFile);
      if (!isset($allHelp[$lang])) {
        echo "[fatal] Nessuna sezione '$lang' trovata in help.yaml\n";
        exit(1);
      }

      return $allHelp[$lang];
    }

    // Carica e memorizza il file dei messaggi solo una volta
    if ($messagesCache === null) {
      $messagesFile = $langDir . '/messages.yaml';
      if (!file_exists($messagesFile)) {
        echo "[fatal] File messages.yaml non trovato in $langDir\n";
        exit(1);
      }

      $parsed = Yaml::parseFile($messagesFile);
      if (!is_array($parsed)) {
        echo "[fatal] Il contenuto di messages.yaml non è valido\n";
        exit(1);
      }

      $messagesCache = $parsed;
    }

    // Estrai i messaggi nella lingua corrente
    $localized = [];
    foreach ($messagesCache as $key => $langs) {
      if (!isset($langs[$lang])) {
        echo "[warning] Chiave '$key' non ha una traduzione per '$lang'\n";
        continue;
      }
      $localized[$key] = $langs[$lang];
    }

    return $localized;
  }

  /**
   * Restituisce la lingua attualmente selezionata o impostata nel sistema.
   *
   * Questo metodo viene utilizzato per ottenere il codice della lingua attiva,
   * che può essere utilizzato per la localizzazione dell'interfaccia utente,
   * la traduzione dei contenuti o la gestione delle preferenze linguistiche
   * dell'utente all'interno dell'applicazione.
   *
   * @return string Il codice della lingua attualmente selezionata (ad esempio 'it', 'en', ecc.).
   */
  /**
   * Summary of getLang
   * @return string
   */
  public static function getLang(): string
  {
    return self::$currentLang;
  }

  /**
   * Registra e visualizza un messaggio di override della lingua.
   *
   * Questo metodo viene utilizzato per tracciare e notificare la fonte che ha determinato
   * la modifica della lingua corrente dell'applicazione. Può essere utile per il debug
   * o per comprendere quale configurazione (parametro CLI, file di configurazione, costante,
   * variabile di ambiente) ha avuto la precedenza nell'impostazione della lingua.
   *
   * @param string $source La fonte che ha causato l'override della lingua (es. "CLI", "Configurazione", "Variabile ambiente").
   * @param string $lang Il codice della lingua che è stata impostata (ad esempio 'it' o 'en').
   * @return void
   */
  private static function logOverride(string $source, string $lang): void
  {
    echo "[config] Lingua impostata da $source: '$lang'\n";
  }
}
