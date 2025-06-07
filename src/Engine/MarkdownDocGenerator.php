<?php

namespace DocDoc\Engine;
use DocDoc\Engine\Lang;
use DocDoc\Engine\Log;
use RecursiveIteratorIterator;

class MarkdownDocGenerator
{
    private string $inputDir;
    private string $outputDir;
    private bool $verbose;
    private string $template;
    private array $msg;
    private string $sortOrder; // oppure 'desc'
    private array $topLevelMenu;


    public function __construct(string $inputDir, string $outputDir, bool $verbose = false, array $msg = [], string $sortOrder = 'asc')
    {
        Formatter::message("[INFO] INIT MD Generator [build]");
        $this->msg = $msg ?: Lang::messages();
        //$this->log("RUN - inpDir: {$inputDir}, outDir: {$outputDir}, Verbose: " . ($verbose ? 'true' : 'false'), 'info');

        $this->inputDir = realpath($inputDir) ?: $inputDir;
        $this->outputDir = $outputDir;
        $this->verbose = $verbose;
        $this->sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'asc';
        $this->topLevelMenu = [];

        $this->template = file_get_contents(__DIR__ . '/../../layout/layout.html');
    }

    public function run(): void
    {

        $this->log($this->msg['start'], $this->inputDir, $this->outputDir);

        $sidebar = $this->generateSidebarHTML();

        foreach ($this->getMarkdownFiles($this->inputDir) as $file) {
            $this->generateHTML($file, $sidebar);
        }

        $styleSrc = __DIR__ . '/../../layout/style.css';
        $styleDst = $this->outputDir . '/style.css';
        copy($styleSrc, $styleDst);

        $this->log($this->msg['done'], 'success');
    }

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

    private function generateHTML(string $mdPath, string $sidebar): void
    {
        $relativeWithEmoji = substr($mdPath, strlen($this->inputDir) + 1);
        $relativePathClean = $this->removeEmoji($relativeWithEmoji);
        $htmlPath = preg_replace('/\.md$/', '.html', $relativePathClean);
        $htmlFullPath = $this->outputDir . '/' . $htmlPath;

        if (!is_dir(dirname($htmlFullPath))) {
            mkdir(dirname($htmlFullPath), 0777, true);
        }

        $title = pathinfo($mdPath, PATHINFO_FILENAME);
        $markdownContent = file_get_contents($mdPath);

        $useTempFile = false;

        if (strtolower(substr($title, -4)) === 'home') {
            $this->log("🛠️  Home.md rilevato, sostituisco menu dinamico...", 'info');
            $menuMarkdown = $this->generateMenuMarkdown();
            $this->log("📋 Menu Markdown generato:\n$menuMarkdown", 'debug');

            if (str_contains($markdownContent, '<!-- $menu_auto$ -->')) {
                $markdownContent = str_replace('<!-- $menu_auto$ -->', $menuMarkdown, $markdownContent);
                $useTempFile = true;
            } else {
                $this->log("⚠️  Placeholder <!-- \$menu_auto\$ --> NON trovato in Home.md", 'warning');
            }
        }

        if ($useTempFile) {
            // Salva contenuto modificato in file temporaneo
            $tmpMdPath = tempnam(sys_get_temp_dir(), 'docdoc_');
            file_put_contents($tmpMdPath, $markdownContent);
            $this->log("📄 File temporaneo creato: $tmpMdPath", 'debug');
            $mdToUse = $tmpMdPath;
        } else {
            $mdToUse = $mdPath;
        }

        // Conversione HTML con Pandoc
        $this->log("⚙️  Invocazione Pandoc su: $mdToUse", 'debug');
        $body = shell_exec("pandoc --from=gfm --to=html5 \"$mdToUse\"");

        $tocFull = shell_exec("pandoc --from=gfm --to=html5 --toc --toc-depth=3 \"$mdToUse\"");
        $this->log("📘 TOC HTML grezzo:\n" . substr($tocFull, 0, 500) . "...", 'debug'); // solo in parte per non inondare

        if ($useTempFile) {
            unlink($mdToUse);
            $this->log("🧹 File temporaneo eliminato: $mdToUse", 'debug');
        }

        // Estrai solo la TOC
        preg_match('/<nav id="TOC".*?<\/nav>/s', $tocFull, $matches);
        $toc = $matches[0] ?? '';

        // Generazione path relativi per link nella TOC
        $relativeHtmlPath = str_replace('\\', '/', $htmlPath);
        $baseDirName = basename($this->outputDir);
        $finalHrefPrefix = $baseDirName . '/' . $relativeHtmlPath;

        $toc = preg_replace(
            '/href="#([^"]+)"/',
            'href="' . $finalHrefPrefix . '#$1"',
            $toc
        );

        // Inserimento nel template
        $html = str_replace('$body$', $body, $this->template);
        $html = str_replace('$toc_custom$', $toc, $html);
        $html = str_replace('<!-- $sidebar$ -->', $sidebar, $html);

        $depth = substr_count($htmlPath, '/');
        $relativeBase = str_repeat('../', $depth);
        $html = str_replace('<base href="../">', '<base href="../' . $relativeBase . '">', $html);

        // Salvataggio output finale
        file_put_contents($htmlFullPath, $html);

        // $this->log(
        //     sprintf($this->msg['file_generated']['str'], $htmlPath),
        //     $this->msg['file_generated']['lvl']
        // );
        // echo "htmlPath: $htmlPath\n";
        $this->log($this->msg['file_generated'], $htmlPath);
    }


    private function generateMenuMarkdown(): string
    {
        $menu = '';

        foreach ($this->topLevelMenu as $value) {
            // if(is_array($href)) {
            //     continue;
            // }
            // list($label, $href) = $value;


            $menu .= "- [{$value['label']}]({$value['href']})\n";
            // $this->log("Menu: $menu", 'info');
        }

        return $menu;
    }

    private function generateSidebarHTML(): string
    {
        $virtualBase = basename($this->outputDir);
        return $this->buildSidebarTree($this->inputDir, '', $virtualBase);
    }

    private function buildSidebarTree(string $baseDir, string $relativePath, string $urlPrefix): string
    {
        $fullPath = rtrim($baseDir . DIRECTORY_SEPARATOR . $relativePath, '/');
        $this->log("🔍 Scanning: $fullPath", 'debug');
        $entries = scandir($fullPath);
        if (!$entries) {
            return '';
        }

        $dirs = [];
        $files = [];
        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..', '.git', 'vendor', 'layout', 'layout-pandoc'])) {
                continue;
            }

            $entryPath = $fullPath . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($entryPath)) {
                if ($this->directoryContainsMarkdown($entryPath)) {
                    $dirs[] = $entry;
                    $this->log("📁 DIR trovata: $entry", 'debug');
                }
            } elseif (pathinfo($entry, PATHINFO_EXTENSION) === 'md') {
                $files[] = $entry;
                $this->log("📄 FILE md trovato: $entry", 'debug');
            }
        }

        // Ordinamento configurabile
        $sortFlags = SORT_NATURAL | SORT_FLAG_CASE;
        if ($this->sortOrder === 'desc') {
            rsort($dirs, $sortFlags);
            rsort($files, $sortFlags);
        } else {
            sort($dirs, $sortFlags);
            sort($files, $sortFlags);
        }
        $this->log("📑 Dir ordinati: " . implode(', ', $dirs), 'debug');
        $this->log("📝 File ordinati: " . implode(', ', $files), 'debug');

        $html = "<ul class='phpdocumentor-list'>\n";

        foreach ($dirs as $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);

            $html .= "  <li><a href=\"\">$label</a></li><li>\n";
            $html .= $this->buildSidebarTree($baseDir, $entryRelPath, $urlPrefix);
            $html .= "  </li>\n";
        }

        // Mappa dei nomi "puliti" => file con emoji (preferiti)
        $cleanedFiles = [];
        foreach ($files as $entry) {
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $clean = $this->removeEmoji($label);

            if (
                !isset($cleanedFiles[$clean]) ||
                $this->startsWithEmoji($label)
            ) {
                $cleanedFiles[$clean] = $entry;
            }
        }

        foreach ($cleanedFiles as $clean => $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $cleanRelPath = $this->removeEmoji($entryRelPath);
            $htmlPath = preg_replace('/\.md$/', '.html', $cleanRelPath);
            $href = $urlPrefix . '/' . $htmlPath;

            $this->log("🔗 Genero link: label='$label' → href='$href'", 'debug');
            $html .= "  <li><a href=\"$href\">$label</a></li>\n";

            // Se siamo nel root, salviamo anche per il menu dinamico
            if ($relativePath === '') {
                $this->topLevelMenu[] = [
                    'label' => $label,
                    'href' => $href
                ];
                $this->log("[add] Aggiunto a topLevelMenu: label='$label', href='$href'", 'debug');
            }
        }

        $html .= "</ul>\n";
        return $html;
    }

    private function startsWithEmoji(string $text): bool
    {
        return (bool) preg_match('/^\p{So}/u', $text);
    }

    private function removeEmoji(string $text): string
    {
        $parts = explode('/', $text);
        $clean = array_map(function ($part) {
            // Rimuove emoji + variation selectors + caratteri invisibili Unicode
            return preg_replace('/^[\p{So}\p{Sk}\p{Cn}\p{Zs}\x{FE0F}\x{200D}\p{Cf}]+/u', '', $part);
        }, $parts);
        return implode('/', $clean);
    }



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

    public function validatePlaceholderCount(string $format, array $args): bool
    {
        // Rimuove '%%' (escape di % letterale)
        $cleanFormat = preg_replace('/%%/', '', $format);

        // Trova tutti i placeholder sprintf validi
        preg_match_all('/(?<!%)%(?:\d+\$)?[bcdeEfFgGosuxX]/', $cleanFormat, $matches);

        $expected = count($matches[0]);
        $provided = count($args);

        return $expected === $provided;
    }

    private function log(string|array $message, mixed ...$args): void
    {
        $level = $args[0] ?? 'info';
        $level = is_string($level) ? $level : 'info';
        $color = Log::$colors[$level] ?? Log::$colors['info'];
        $args = $args ?? [];

        if ($level === 'debug' && !$this->verbose) {
            return;
        }

        if(in_array($level, array_keys(Log::$colors))) {
            Formatter::message($message, $level);
            return;
        }
        //echo "msg: $message\n";
        if($this->validatePlaceholderCount($message, $args)) {

            echo Formatter::sprintf($message, ...$args);
        }

        
    }

}