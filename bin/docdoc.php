#!/usr/bin/env php
<?php
// SCRIPT PHP
require __DIR__ . '/../vendor/autoload.php';
use DocDoc\Engine\MarkdownDocGenerator;

// Script example.php
$shortopts = "i:";
$shortopts .= "o:";  // Required value
$shortopts .= "vh";  // Required value
// $shortopts .= "abc"; // These options do not accept values

$longopts = array(
    "input:",     // Required value
    "output:",    // Required value
    "verbose::",  // Optional value
    "help"        // No value
);

// prende i parametri da linea di comando
$options = getopt($shortopts, $longopts) ?? [];

// --- HELP
if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
DocDoc - Generatore HTML da Markdown compatibile con phpDocumentor

USO:
  ./bin/docdoc.php --input=DIR --output=DIR [--verbose]

OPZIONI:
  -i, --input       Cartella di input contenente i file .md (default: tests)
  -o, --output      Cartella di output dove salvare gli .html (default: docs/output)
  -v, --verbose     Attiva log dettagliato (debug) a video
  -h, --help        Mostra questo help e termina

ESEMPIO:
  ./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose

HELP;
    exit(0);
}

// --- PARAMETRI CLI
$input = $options['input'] ?? $options['i'] ?? 'tests';
$output = $options['output'] ?? $options['o'] ?? 'docs/output';
$verbose = isset($options['verbose']) || isset($options['v']);

if ($verbose) {
    echo "➡️  Parametri:\n";
    echo "   input  = $input\n";
    echo "   output = $output\n";
    echo "   verbose= " . ($verbose ? 'true' : 'false') . "\n\n";
}

// controlla che il direttorio sorgente esista
if (!is_dir($input)) {
    fwrite(STDERR, "\033[31m❌ Errore: la directory di input '$input' non esiste.\033[0m\n");
    exit(1);
}
$generator = new MarkdownDocGenerator($input, $output, $verbose);
$generator->run();
