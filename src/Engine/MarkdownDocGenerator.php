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

    public function __construct(string $inputDir, string $outputDir, bool $verbose = false, array $msg = [])
    {
        $this->msg = $msg ?: Lang::messages();
        $this->log("RUN - inpDir: {$inputDir}, outDir: {$outputDir}, Verbose: " . ($verbose ? 'true' : 'false'), 'info');

        $this->inputDir = realpath($inputDir) ?: $inputDir;
        $this->outputDir = $outputDir;
        $this->verbose = $verbose;

        $this->template = file_get_contents(__DIR__ . '/../../layout/layout.html');
    }

    public function run(): void
    {
        $this->log(
            sprintf(
                $this->msg['start']['str'],
                $this->inputDir,
                $this->outputDir
            ),
            $this->msg['start']['lvl']
        );

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
        // $relPath = substr($mdPath, strlen($this->inputDir) + 1);
        // $htmlPath = preg_replace('/\.md$/', '.html', $relPath);

        $relativeWithEmoji = substr($mdPath, strlen($this->inputDir) + 1);
        $relativePathClean = $this->removeEmoji($relativeWithEmoji);
        $htmlPath = preg_replace('/\.md$/', '.html', $relativePathClean);
        $htmlFullPath = $this->outputDir . '/' . $htmlPath;

        if (!is_dir(dirname($htmlFullPath))) {
            mkdir(dirname($htmlFullPath), 0777, true);
        }

        $title = pathinfo($mdPath, PATHINFO_FILENAME);

        // Usa GitHub-flavored markdown con pandoc
        $body = shell_exec("pandoc --from=gfm --to=html5 \"$mdPath\"");

        // TOC generata separatamente
        $toc = shell_exec("pandoc --from=gfm --to=html5 --toc --toc-depth=3 \"$mdPath\"");

        $html = str_replace('$body$', $body, $this->template);
        $html = str_replace('$toc_custom$', $toc, $html);
        $html = str_replace('<!-- $sidebar$ -->', $sidebar, $html);

        $depth = substr_count($htmlPath, '/');
        $relativeBase = str_repeat('../', $depth);
        //$html = str_replace('<base href="../">', '<base href="' . $relativeBase . '">', $html);

        file_put_contents($htmlFullPath, $html);

        $this->log(
            sprintf(
                $this->msg['file_generated']['str'],
                $htmlPath
            ),
            $this->msg['file_generated']['lvl']
        );
    }

    private function generateSidebarHTML(): string
    {
        $virtualBase = basename($this->outputDir);
        return $this->buildSidebarTree($this->inputDir, '', $virtualBase);
    }

    private function buildSidebarTree(string $baseDir, string $relativePath, string $urlPrefix): string
    {
        $fullPath = rtrim($baseDir . DIRECTORY_SEPARATOR . $relativePath, '/');
        $entries = scandir($fullPath);
        if (!$entries)
            return '';

        $dirs = [];
        $files = [];

        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..', '.git', 'vendor', 'layout', 'layout-pandoc']))
                continue;
            $entryPath = $fullPath . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($entryPath)) {
                if ($this->directoryContainsMarkdown($entryPath)) {
                    $dirs[] = $entry;
                }
            } elseif (pathinfo($entry, PATHINFO_EXTENSION) === 'md') {
                $files[] = $entry;
            }
        }

        $html = "<ul class='phpdocumentor-list'>\n";


        foreach ($dirs as $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $html .= "  <li><strong>$label</strong>\n";
            $html .= $this->buildSidebarTree($baseDir, $entryRelPath, $urlPrefix);
            $html .= "  </li>\n";
        }

        // Mappa dei nomi "puliti" => file con emoji (preferiti)
        $cleanedFiles = [];

        foreach ($files as $entry) {
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $clean = $this->removeEmoji($label);

            // Se non esiste ancora o questo ha emoji e quello precedente no → preferisci questo
            if (
                !isset($cleanedFiles[$clean]) ||
                $this->startsWithEmoji($label)
            ) {
                $cleanedFiles[$clean] = $entry;
            }
        }

        // Ora generi solo da quelli selezionati
        foreach ($cleanedFiles as $clean => $entry) {
            $entryRelPath = ltrim($relativePath . '/' . $entry, '/');
            $label = pathinfo($entry, PATHINFO_FILENAME);
            $cleanRelPath = $this->removeEmoji($entryRelPath);
            $htmlPath = preg_replace('/\.md$/', '.html', $cleanRelPath);
            $href = $urlPrefix . '/' . $htmlPath;

            $html .= "  <li><a href=\"$href\">$label</a></li>\n";
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



    private function log(string|array $message, string $level = 'info'): void
    {
        if ($level === 'debug' && !$this->verbose) {
            return;
        }
        Log::message($message, $level);
    }
}