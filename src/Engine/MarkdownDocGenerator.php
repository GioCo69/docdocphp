<?php

namespace DocDoc\Engine;

class MarkdownDocGenerator
{
    private string $inputDir;
    private string $outputDir;
    private bool $verbose;
    private string $template;

    public function __construct(string $inputDir, string $outputDir, bool $verbose = false)
    {
        $this->log("RUN - inpDir: {$inputDir}, outDir: {$outputDir}, Verbose: " . ($verbose ? 'true' : 'false'));

        $this->inputDir = realpath($inputDir) ?: $inputDir;
        $this->outputDir = $outputDir;
        $this->verbose = $verbose;

        $this->template = file_get_contents(__DIR__ . '/../../layout/layout.html');
    }

    public function run(): void
    {
        $this->log("📂 Inizio generazione da '{$this->inputDir}' a '{$this->outputDir}'", 'info');

        $sidebar = $this->generateSidebarHTML();

        foreach ($this->getMarkdownFiles($this->inputDir) as $file) {
            $this->generateHTML($file, $sidebar);
        }

        $styleSrc = __DIR__ . '/../../layout/style.css';
        $styleDst = $this->outputDir . '/style.css';
        copy($styleSrc, $styleDst);

        $this->log("✅ Generazione completata.", 'success');
    }

    private function getMarkdownFiles(string $dir): array
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        $hasMd = false;
        foreach ($rii as $file) {
            if (!$file->isDir() && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                $hasMd = true;
                break;
            }
        }

        if (!$hasMd) {
            fwrite(STDERR, "\033[33m⚠️  Avviso: la cartella {$this->inputDir} non contiene file Markdown (.md).\033[0m\n");
            exit(1);
        }

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
        $relPath = substr($mdPath, strlen($this->inputDir) + 1);
        $htmlPath = preg_replace('/\.md$/', '.html', $relPath);
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

        file_put_contents($htmlFullPath, $html);

        $this->log("↪️  Generato: $htmlPath", 'debug');
    }

    private function generateSidebarHTML(): string
    {
        $html = "<ul class='phpdocumentor-list'>\n";
        foreach ($this->getMarkdownFiles($this->inputDir) as $file) {
            $relPath = substr($file, strlen($this->inputDir) + 1);
            $htmlPath = preg_replace('/\.md$/', '.html', $relPath);
            $title = pathinfo($file, PATHINFO_FILENAME);
            $html .= "  <li><a href=\"$htmlPath\">$title</a></li>\n";
        }
        $html .= "</ul>\n";
        return $html;
    }

    private function log(string $message, string $level = 'info'): void
    {
        $colors = [
            'info' => "\033[36m",     // cyan
            'success' => "\033[32m",  // green
            'warning' => "\033[33m",  // yellow
            'error' => "\033[31m",    // red
            'debug' => "\033[90m",    // gray
            'reset' => "\033[0m"
        ];

        if ($level === 'debug' && !$this->verbose) {
            return;
        }

        $color = $colors[$level] ?? '';
        $reset = $colors['reset'];

        echo "{$color}{$message}{$reset}\n";
    }
}