#!/usr/bin/env php
<?php //+ RUNNER SCRIPT PHP

//-- INIT
require __DIR__ . '/../vendor/autoload.php';
use DocDoc\Engine\MarkdownDocGenerator;
use DocDoc\Engine\Lang;
use DocDoc\Engine\Log;

/**
 * wrapper per metodo Log::message
 * @param string $message
 * @param string $level
 * @return void
 */
function wlog(string $message, string $level = 'info'): void {
    Log::message($message, $level);
}

//-- SHORT PARAM
$shortopts = "i:";   // Required value
$shortopts .= "o:";  // Required value
$shortopts .= "vh";  // Optional value
$shortopts .= "l:";  // Required value

//-- LONG PARAM
$longopts = array(
    "input:",     // Required value
    "output:",    // Required value
    "verbose::",  // Optional value
    "help",       // No value (set or no)
    "lang:"       // No value (set or no)
);

//-- GET ARGUMENTS
$options = getopt($shortopts, $longopts) ?? [];

// --- LOAD CLI PARAM
$input = $options['input'] ?? $options['i'] ?? 'tests';
$output = $options['output'] ?? $options['o'] ?? 'docs/output';
$verbose = isset($options['verbose']) || isset($options['v']);
if ($verbose) {
    echo "➡️  PARAMETER:\n";
    echo "   input  = $input\n";
    echo "   output = $output\n";
    echo "   verbose= " . ($verbose ? 'true' : 'false') . "\n\n";
}

//-- LANG
$lang = $options['lang'] ?? $options['l'] ?? 'it';
if (!in_array($lang, ['it', 'en'])) {
    $str = "❌ Language '{$lang}' not supported.";
    $lvl = "error";
    wlog(sprintf($str, $lang) . "\n", $lvl);
    exit(1);
}

//-- LOAD LANG MESSAGES
$messages = Lang::messages($lang);

// --- HELP
if (isset($options['h']) || isset($options['help'])) {
    echo $messages['help'] . "\n";
    exit(0);
}

// --- CHECK IF EXIST INP DIR
if (!is_dir($input)) {
    wlog(sprintf($messages['no_input_dir']['str'], $input) . "\n", $messages['no_input_dir']['lvl']);
    exit(1);
}

// --- SEARCH MD
// check se contiene almeno un file .md
$hasMd = false;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input));
foreach ($rii as $file) {
    if (!$file->isDir() && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
        $hasMd = true;
        break;
    }
}
if (!$hasMd) {
    wlog(sprintf($messages['no_md_found']['str'], $input) . "\n", $messages['no_md_found']['lvl']);
    exit(1);
}

//+ CALL MD GEN
$generator = new MarkdownDocGenerator($input, $output, $verbose, $messages);
$generator->run();
