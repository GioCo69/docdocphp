<?php

namespace DocDoc\Engine;

use DocDoc\Base;
use DocDoc\Engine\Lang;
use DocDoc\Engine\Log;
use RecursiveIteratorIterator;

class MarkdownDocGenerator extends Base
{
    private string $base; // project base directory (from CFG && Base.php)
    private string $inputDir;
    private string $outputDir;
    private bool $isDebug = false;
    private string $template;
    private array $msg;
    private string $sortOrder;
    private array $topLevelMenu;

    /**
     * Costruisce una nuova istanza di MarkdownDocGenerator.
     *
     * Inizializza il generatore di documentazione Markdown specificando la directory di input contenente i file .md
     * e la directory di output dove verranno generati i file HTML. Permette inoltre di abilitare la modalità verbose
     * per il debug, di personalizzare i messaggi di log e di impostare l'ordinamento (ascendente o discendente)
     * dei file e delle directory nel menu laterale.
     *
     * @param string $inputDir   Percorso della directory sorgente contenente i file Markdown da processare.
     * @param string $outputDir  Percorso della directory di destinazione dove verranno creati i file HTML.
     * @param bool   $verbose    Se true, abilita la modalità dettagliata di log per il debug.
     * @param array  $msg        Array opzionale di messaggi personalizzati per log e notifiche.
     * @param string $sortOrder  Ordine di visualizzazione di file e cartelle nel menu: 'asc' (default) o 'desc'.
     */
    public function __construct(string $inputDir, string $outputDir, bool $verbose = false, array $msg = [], string $sortOrder = 'asc')
    {
        $this->base = $base = parent::getProjectRoot();
        // $this->log("[INFO] Project Root: " . $base);
        // $this->log("[INFO] Project DIR: " . parent::getProjectDir());
        $this->log('[INFO][build] INIT Markdown Generator');


        // Recupera tutti i messaggi in lingua per il log && debug
        $this->msg = $msg ?: Lang::messages();
        // recupera il path sorgente dove dovrebbero trovarsi i file MD
        $this->inputDir = realpath($inputDir) ?: $inputDir;
        // recupera il path destinatario dove andranno a dinire il file HTML generati
        $this->outputDir = $outputDir;
        // imposta l'ordine con cui generare i menu dai file MD
        $this->sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'asc';
        $this->topLevelMenu = [];
        // imposta il livello di log dei messagi (-v || DOCDOC_VERBOSE equivalgono all'impostazione $this->isDebug)
        $this->isDebug = $verbose;
        if (defined('DOCDOC_VERBOSE') && DOCDOC_VERBOSE === true) {
            $this->isDebug = true;
        }

        // imposta il template file
        $layout_file = $base . '/layout/layout.html';
        if (file_exists($layout_file)) {
            $this->template = file_get_contents($layout_file);
        } else {
            $this->log("[FATAL] layout missed: {$layout_file}");
            exit(1);
        }
    }

    /**
     * Avvia il processo principale di generazione della documentazione Markdown.
     *
     * Questo metodo coordina l'intero flusso di lavoro per la creazione della documentazione:
     * - Scansiona ricorsivamente la directory di input alla ricerca di file Markdown (.md).
     * - Genera la barra laterale di navigazione (sidebar) in formato HTML, riflettendo la struttura delle cartelle e dei file.
     * - Per ciascun file Markdown trovato:
     *   - Converte il contenuto in HTML utilizzando Pandoc.
     *   - Inserisce automaticamente il menu nella pagina Home, se rilevato il relativo placeholder.
     *   - Prepara e inserisce l'indice dei contenuti (TOC) personalizzato.
     *   - Applica il template HTML globale e salva il risultato nella directory di output.
     * - Copia il file di stile CSS necessario per la corretta visualizzazione.
     * - Registra messaggi di log per ogni fase significativa del processo.
     *
     * Questo metodo non restituisce alcun valore.
     *
     * @return void
     */
    public function run(): void
    {
        $base = $this->base;

        $this->log($this->msg['start'], $this->inputDir, $this->outputDir);
        $sidebar = $this->generateSidebarHTML();

        foreach ($this->getMarkdownFiles($this->inputDir) as $file) {
            $this->generateHTML($file, $sidebar);
        }

        $style_file = $base . '/layout/style.css';
        if (file_exists($style_file)) {
            copy($style_file, $this->outputDir . '/style.css');
        } else {
            $this->log("[WARNING] missing style: $style_file");
        }
        $this->log($this->msg['done']);
    }

    /**
     * Restituisce un array contenente i percorsi completi di tutti i file Markdown (.md)
     * presenti nella directory specificata e nelle sue sottodirectory.
     *
     * Questo metodo esegue una scansione ricorsiva della directory di input fornita,
     * individuando tutti i file che terminano con estensione ".md". I percorsi restituiti
     * sono assoluti e possono essere utilizzati per successive operazioni di conversione
     * o generazione della documentazione.
     *
     * @param string $dir Percorso della directory di partenza da cui iniziare la scansione ricorsiva.
     * @return array      Array di stringhe contenente i percorsi completi dei file Markdown trovati.
     */
    private function getMarkdownFiles(string $dir): array
    {
        $rii = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];

        foreach ($rii as $file) {
            if (!$file->isDir() && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Pulisce e normalizza un segmento di percorso per renderlo sicuro e compatibile con i nomi di file e URL.
     *
     * Questo metodo rimuove eventuali caratteri speciali, simboli Unicode (come emoji), caratteri di controllo,
     * spazi e altri simboli non validi all'inizio del segmento. Successivamente, traslittera i caratteri UTF-8
     * in ASCII quando possibile, sostituendo quelli non convertibili. Infine, sostituisce tutti i caratteri
     * non alfanumerici, trattini, underscore e punti con un trattino, e rimuove eventuali trattini iniziali o finali.
     *
     * @param string $segment Segmento di percorso da sanificare (ad esempio, nome di file o directory).
     * @return string Segmento sanificato, pronto per essere utilizzato in percorsi di file o URL.
     */
    private function sanitizePathSegment(string $segment): string
    {
        $segment = preg_replace('/^[\p{So}\p{Sk}\p{Cn}\p{Zs}\x{FE0F}\x{200D}\p{Cf}]+/u', '', $segment);
        $segment = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $segment);
        $segment = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $segment);
        return trim($segment, '-');
    }

    /**
     * Pulisce e normalizza un percorso completo, rendendolo sicuro per l'utilizzo come parte di un URL o di un nome file.
     *
     * Questo metodo suddivide il percorso fornito in segmenti utilizzando lo slash come separatore,
     * quindi applica la funzione sanitizePathSegment() a ciascun segmento per rimuovere caratteri speciali,
     * simboli non validi e traslitterare eventuali caratteri non ASCII. I segmenti sanificati vengono poi
     * ricomposti in un percorso unico, separato da slash. Il risultato è un percorso privo di caratteri
     * problematici, adatto per essere utilizzato come parte di URL, nomi di file o riferimenti interni.
     *
     * @param string $path Percorso completo da sanificare (può contenere sottocartelle e nomi di file).
     * @return string Percorso sanificato, pronto per l'uso in URL o file system.
     */
    private function sanitizePath(string $path): string
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        return implode('/', array_map([$this, 'sanitizePathSegment'], $parts));
    }

    /**
     * Genera il file HTML a partire da un file Markdown specificato.
     *
     * Questo metodo esegue le seguenti operazioni:
     * - Calcola il percorso relativo del file Markdown rispetto alla directory di input e lo sanifica per l'utilizzo come percorso di output.
     * - Crea la struttura di directory necessaria nella cartella di output, se non già presente.
     * - Legge il contenuto del file Markdown e, se il file rappresenta la Home (il cui nome termina con "home"), inserisce automaticamente il menu generato nel punto indicato dal placeholder.
     * - Prepara un header YAML per Pandoc, se non già presente, per impostare il titolo della pagina.
     * - Se necessario, crea un file temporaneo Markdown con le modifiche apportate.
     * - Converte il Markdown in HTML utilizzando Pandoc tramite shell_exec.
     * - Genera l'indice dei contenuti (TOC) personalizzato tramite Pandoc e lo adatta per i link interni.
     * - Applica il template HTML globale, sostituendo i placeholder con il corpo della pagina, la sidebar e l'indice dei contenuti.
     * - Calcola il percorso relativo per il tag <base> in modo che i link funzionino correttamente anche in sottocartelle.
     * - Salva il file HTML generato nella directory di output.
     * - Registra messaggi di log per ogni fase significativa del processo.
     *
     * @param string $mdPath   Percorso completo del file Markdown da convertire.
     * @param string $sidebar  HTML della sidebar di navigazione da inserire nel template.
     * @return void
     */
    private function generateHTML(string $mdPath, string $sidebar): void
    {
        $relativeOriginal = substr($mdPath, strlen($this->inputDir) + 1);
        $relativeClean = $this->sanitizePath($relativeOriginal);
        $htmlPath = preg_replace('/\.md$/', '.html', $relativeClean);
        $htmlFullPath = $this->outputDir . '/' . $htmlPath;

        if (!is_dir(dirname($htmlFullPath))) {
            mkdir(dirname($htmlFullPath), 0777, true);
        }

        $title = pathinfo($mdPath, PATHINFO_FILENAME);
        $markdownContent = file_get_contents($mdPath);
        $useTempFile = false;

        // Iniezione automatica menu in Home
        if (strtolower(substr($title, -4)) === 'home') {
            $this->log($this->msg['home_detected']);
            $menuMarkdown = $this->generateMenuMarkdown();
            $this->debug($this->msg['menu_generated'], $menuMarkdown);

            if (str_contains($markdownContent, '<!-- $menu_auto$ -->')) {
                $markdownContent = str_replace('<!-- $menu_auto$ -->', $menuMarkdown, $markdownContent);
                $useTempFile = true;
            } else {
                $this->log($this->msg['menu_placeholder_missing']);
            }
        }

        // Inserisci header YAML per Pandoc e crea file temporaneo
        $yamlHeader = $this->prependYamlHeader($title, $markdownContent);
        if ($yamlHeader !== null) {
            $markdownContent = $yamlHeader;
            $useTempFile = true;
        }

        $mdToUse = $useTempFile
            ? $this->createTempMarkdown($markdownContent)
            : $mdPath;

        $this->debug($this->msg['pandoc_invoked'], $mdToUse);
        $body = shell_exec("pandoc --from=gfm --to=html5 \"$mdToUse\"");

        $tocFull = shell_exec("pandoc --from=gfm --to=html5 --standalone --toc --toc-depth=3 \"$mdToUse\"");
        $this->debug($this->msg['toc_raw'], substr($tocFull, 0, 500));

        if ($useTempFile) {
            unlink($mdToUse);
            $this->debug($this->msg['temp_removed'], $mdToUse);
        }

        preg_match('/<nav[^>]*id="TOC"[^>]*>(.*?)<\/nav>/s', $tocFull, $matches);
        $toc = $matches[1] ?? '';

        $relativeHtmlPath = str_replace('\\', '/', $htmlPath);
        $baseDirName = basename($this->outputDir);
        $finalHrefPrefix = $baseDirName . '/' . $relativeHtmlPath;
        $toc = preg_replace('/href="#([^"]+)"/', 'href="' . $finalHrefPrefix . '#$1"', $toc);

        $html = str_replace(
            ['$body$', '$toc_custom$', '<!-- $sidebar$ -->'],
            [$body, $toc, $sidebar],
            $this->template
        );

        $depth = substr_count($htmlPath, '/');
        $relativeBase = str_repeat('../', $depth);
        $html = str_replace('<base href="../">', '<base href="../' . $relativeBase . '">', $html);

        file_put_contents($htmlFullPath, $html);
        $this->log($this->msg['file_generated'], $htmlPath);
    }

    /**
     * Genera un documento in formato Markdown utilizzando il titolo e il contenuto forniti.
     *
     * Questa funzione prende in ingresso un titolo e un contenuto testuale, 
     * li formatta secondo la sintassi Markdown e restituisce la stringa risultante.
     * Se i parametri non sono validi, può restituire null.
     *
     * @param string $title   Il titolo del documento Markdown.
     * @param string $content Il contenuto principale del documento in formato testo.
     * @return string|null    La stringa Markdown generata o null in caso di errore.
     */
    private function prependYamlHeader(string $title, string $content): ?string
    {
        if (preg_match('/^---\s*\ntitle:/', $content)) {
            return null;
        }

        return "---\ntitle: \"$title\"\n---\n\n" . $content;
    }

    /**
     * Crea un file temporaneo Markdown con il contenuto specificato.
     *
     * Questo metodo genera un file temporaneo nella directory di sistema utilizzando la funzione tempnam(),
     * scrive al suo interno il contenuto Markdown fornito e restituisce il percorso completo del file creato.
     * Il file temporaneo viene utilizzato per operazioni intermedie, come l'iniezione di header YAML o la modifica
     * dinamica del contenuto prima della conversione tramite Pandoc. Dopo l'utilizzo, il file temporaneo deve essere eliminato.
     *
     * @param string $content Il contenuto Markdown da scrivere nel file temporaneo.
     * @return string Il percorso completo del file temporaneo creato.
     */
    private function createTempMarkdown(string $content): string
    {
        $tmpMdPath = tempnam(sys_get_temp_dir(), 'docdoc_');
        file_put_contents($tmpMdPath, $content);
        $this->debug($this->msg['temp_created'], $tmpMdPath);
        return $tmpMdPath;
    }

    /**
     * Genera il menu principale in formato Markdown.
     *
     * Questo metodo costruisce un elenco Markdown dei link ai file Markdown di primo livello
     * presenti nella directory principale del progetto. Ogni voce del menu viene generata come
     * elemento di lista Markdown, con il testo del link corrispondente al nome del file (senza estensione)
     * e l'URL che punta al relativo file HTML generato. Il menu risultante può essere inserito
     * automaticamente nella pagina Home o in altre sezioni della documentazione, facilitando la navigazione
     * tra i documenti principali.
     *
     * @return string Una stringa contenente il menu in formato Markdown pronto per essere inserito nei documenti.
     */
    private function generateMenuMarkdown(): string
    {
        $menu = '';
        foreach ($this->topLevelMenu as $value) {
            $menu .= "- [{$value['label']}]({$value['href']})\n";
        }
        return $menu;
    }

    /**
     * Questa classe si occupa della generazione automatica della documentazione
     * in formato Markdown per il progetto. Analizza il codice sorgente, estrae
     * le informazioni rilevanti come classi, metodi e proprietà, e produce file
     * di documentazione leggibili e strutturati secondo lo standard Markdown.
     *
     * Utilizzare questa classe per mantenere aggiornata la documentazione tecnica
     * del progetto, facilitando la comprensione e la manutenzione del codice da parte
     * di sviluppatori e collaboratori.
     *
     * Funzionalità principali:
     * - Analisi del codice PHP per l'estrazione di commenti e firme delle funzioni.
     * - Generazione di file Markdown organizzati per moduli o componenti.
     * - Supporto per l'integrazione con strumenti di documentazione esterni.
     */
    private function generateSidebarHTML(): string
    {
        return $this->buildSidebarTree($this->inputDir, '', basename($this->outputDir));
    }

    /**
     * Costruisce la struttura ad albero per la sidebar di navigazione della documentazione.
     *
     * Questa funzione esplora ricorsivamente la directory di base specificata, generando una rappresentazione ad albero
     * dei file e delle cartelle Markdown trovati. Ogni nodo dell'albero rappresenta una voce della sidebar, con i relativi
     * percorsi e collegamenti URL generati a partire dal prefisso fornito. Il risultato è una stringa che rappresenta
     * l'intera struttura della sidebar, pronta per essere utilizzata nell'interfaccia utente della documentazione.
     *
     * @param string $baseDir       Il percorso assoluto della directory di partenza da cui generare la struttura.
     * @param string $relativePath  Il percorso relativo corrente rispetto alla directory di base, utilizzato per la ricorsione.
     * @param string $urlPrefix     Il prefisso da anteporre agli URL generati per ciascun elemento della sidebar.
     * @return string               Una stringa che rappresenta la struttura ad albero della sidebar in formato Markdown o HTML.
     */
    private function buildSidebarTree(string $baseDir, string $relativePath, string $urlPrefix): string
    {
        $fullPath = rtrim($baseDir . DIRECTORY_SEPARATOR . $relativePath, '/');
        $this->debug($this->msg['scanning'], $fullPath);

        $entries = scandir($fullPath);
        if (!$entries)
            return '';

        $dirs = [];
        $files = [];

        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..', '.git', 'vendor', 'layout', 'layout-pandoc']))
                continue;

            $entryPath = $fullPath . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($entryPath) && $this->directoryContainsMarkdown($entryPath)) {
                $dirs[] = $entry;
                $this->debug($this->msg['dir_found'], $entry);
            }

            if (is_file($entryPath) && pathinfo($entry, PATHINFO_EXTENSION) === 'md') {
                $files[] = $entry;
                $this->debug($this->msg['md_found'], $entry);
            }
        }

        $sortFlags = SORT_NATURAL | SORT_FLAG_CASE;
        ($this->sortOrder === 'desc') ? rsort($dirs, $sortFlags) : sort($dirs, $sortFlags);
        ($this->sortOrder === 'desc') ? rsort($files, $sortFlags) : sort($files, $sortFlags);

        $this->debug($this->msg['dirs_sorted'], implode(', ', $dirs));
        $this->debug($this->msg['files_sorted'], implode(', ', $files));

        $html = "<ul class='phpdocumentor-list'>\n";

        foreach ($dirs as $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $html .= "  <li><a href=\"\">$label</a></li><li>\n";
            $html .= $this->buildSidebarTree($baseDir, $entryRelPath, $urlPrefix);
            $html .= "  </li>\n";
        }

        $cleanedFiles = [];
        foreach ($files as $entry) {
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $clean = $this->removeEmoji($label);
            if (!isset($cleanedFiles[$clean]) || $this->startsWithEmoji($label)) {
                $cleanedFiles[$clean] = $entry;
            }
        }

        foreach ($cleanedFiles as $clean => $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $cleanRelPath = $this->sanitizePath($entryRelPath);
            $htmlPath = preg_replace('/\.md$/', '.html', $cleanRelPath);
            $href = $urlPrefix . '/' . $htmlPath;

            $this->debug($this->msg['link_generated'], $label, $href);
            $html .= "  <li><a href=\"$href\">$label</a></li>\n";

            if ($relativePath === '') {
                $this->topLevelMenu[] = ['label' => $label, 'href' => $href];
                $this->debug($this->msg['menu_added'], $label, $href);
            }
        }

        $html .= "</ul>\n";
        return $html;
    }

    /**
     * Verifica se una stringa inizia con un'emoji Unicode.
     *
     * Questo metodo controlla se il primo carattere della stringa fornita
     * appartiene alla categoria Unicode "Symbol, Other" (\p{So}), che include
     * la maggior parte delle emoji e simboli grafici. È utile per distinguere
     * file o cartelle che iniziano con emoji, ad esempio per gestire ordinamenti
     * o visualizzazioni personalizzate nella documentazione.
     *
     * @param string $text La stringa da analizzare.
     * @return bool Restituisce true se la stringa inizia con un'emoji, false altrimenti.
     */
    private function startsWithEmoji(string $text): bool
    {
        return (bool) preg_match('/^\p{So}/u', $text);
    }

    /**
     * Rimuove eventuali emoji e simboli Unicode dall'inizio di ciascun segmento di una stringa di percorso.
     *
     * Questo metodo prende in ingresso una stringa (tipicamente un nome di file o percorso) e,
     * per ogni segmento separato da slash ('/'), elimina tutti i caratteri che appartengono alle categorie Unicode
     * relative a simboli grafici, emoji, caratteri di controllo e spazi all'inizio del segmento.
     * È utile per normalizzare i nomi di file o directory che iniziano con emoji o simboli,
     * garantendo una maggiore compatibilità nei riferimenti e nei link generati dalla documentazione.
     *
     * @param string $text La stringa da cui rimuovere emoji e simboli all'inizio di ogni segmento.
     * @return string La stringa risultante, priva di emoji e simboli iniziali in ciascun segmento.
     */
    private function removeEmoji(string $text): string
    {
        return implode('/', array_map(
            fn($part) => preg_replace('/^[\p{So}\p{Sk}\p{Cn}\p{Zs}\x{FE0F}\x{200D}\p{Cf}]+/u', '', $part),
            explode('/', $text)
        ));
    }

    /**
     * Verifica se una directory (inclusi i suoi sottolivelli) contiene almeno un file Markdown (.md).
     *
     * Questo metodo esegue una scansione ricorsiva della directory specificata, esplorando tutte le sottocartelle,
     * e restituisce true non appena trova almeno un file con estensione ".md". Se non viene trovato alcun file Markdown,
     * restituisce false. È utile per determinare se una cartella debba essere inclusa nella generazione della sidebar
     * o nella struttura della documentazione, evitando di mostrare directory vuote o irrilevanti.
     *
     * @param string $dir Percorso assoluto della directory da analizzare.
     * @return bool Restituisce true se viene trovato almeno un file Markdown, false altrimenti.
     */
    private function directoryContainsMarkdown(string $dir): bool
    {
        $rii = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($rii as $file) {
            if (!$file->isDir() && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                return true;
            }
        }
        return false;
    }

    /**
     * Registra un messaggio di log per il sistema di generazione della documentazione Markdown.
     *
     * Questo metodo consente di tracciare eventi, errori o informazioni utili durante il processo
     * di generazione della documentazione, facilitando il debug e il monitoraggio delle operazioni.
     *
     * @param string $message Il messaggio da registrare, può essere una stringa singola o un array di messaggi.
     * @param mixed[] $args Argomenti opzionali che possono essere utilizzati per formattare il messaggio di log.
     * @return void
     */
    private function log(string $message, mixed ...$args): void
    {
        $level = $args[0] ?? 'info';
        $level = is_string($level) ? $level : 'info';

        if (in_array($level, array_keys(Log::$colors))) {
            Formatter::message($message, $level);
            return;
        }

        if ($this->validatePlaceholderCount($message, $args)) {
            echo Formatter::sprintf($message, ...$args);
        }
    }

    /**
     * Registra un messaggio di debug durante la generazione della documentazione.
     *
     * Questo metodo consente di tracciare informazioni dettagliate utili per il debugging,
     * ma solo se la modalità debug è abilitata. I messaggi di debug vengono inoltrati al sistema
     * di log interno, permettendo di monitorare lo stato, i dati intermedi e le operazioni svolte
     * dal generatore di documentazione. È particolarmente utile per identificare problemi,
     * verificare il flusso di esecuzione e analizzare i valori delle variabili durante lo sviluppo
     * o la manutenzione del codice.
     *
     * @param string $message Il messaggio di debug da registrare, può essere una stringa o un array di messaggi.
     * @param mixed[] $args Argomenti opzionali per la formattazione del messaggio di debug.
     * @return void
     */
    private function debug(string $message, mixed ...$args): void
    {
        if (!$this->isDebug) {
            return;
        }
        $this->log($message, ...$args);
    }

    /**
     * Verifica la corrispondenza tra il numero di placeholder di formato presenti nella stringa
     * e il numero di argomenti forniti.
     *
     * Questo metodo analizza la stringa di formato specificata, contando i placeholder di tipo printf
     * (ad esempio %s, %d, %f, ecc.), escludendo le sequenze di escape (%%), e confronta il totale
     * con il numero di argomenti effettivamente passati. Restituisce true se il conteggio corrisponde,
     * false altrimenti. È utile per prevenire errori di formattazione nei messaggi di log o output,
     * garantendo che ogni placeholder abbia un valore associato.
     *
     * @param string $format La stringa di formato che può contenere placeholder printf.
     * @param array $args    Gli argomenti da associare ai placeholder della stringa di formato.
     * @return bool          Restituisce true se il numero di placeholder e di argomenti coincide, false altrimenti.
     */
    public function validatePlaceholderCount(string $format, array $args): bool
    {
        $cleanFormat = preg_replace('/%%/', '', $format);
        preg_match_all('/(?<!%)%(?:\d+\$)?[bcdeEfFgGosuxX]/', $cleanFormat, $matches);
        return count($matches[0]) === count($args);
    }
}
