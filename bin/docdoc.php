#!/usr/bin/env php
<?php //+ RUNNER SCRIPT PHP

//-- INIT
require __DIR__ . '/../vendor/autoload.php';
use DocDoc\Engine\MarkdownDocGenerator;
use DocDoc\Engine\Lang;
use DocDoc\Engine\Formatter;

/**
 * wrapper per metodo Log::message
 * @param string $message
 * @param string $level
 * @return void
 */
// function wlog(string $message, string $level = 'info'): void {
//     Log::message($message, $level);
// }

//-- SHORT PARAM
$shortopts = "i:";   // Required value
$shortopts .= "o:";  // Required value
$shortopts .= "vh"; // Optional value
$shortopts .= "l:";  // Required value
$shortopts .= "s:";  // Required value

//-- LONG PARAM
$longopts = array(
    "input:",     // Required value
    "output:",    // Required value
    "verbose::",  // Optional value
    "help",       // No value (set or no)
    "lang::",     // Optional value
    "sort::"      // Optional value
);

//-- GET ARGUMENTS
$options = getopt($shortopts, $longopts) ?? [];

// --- LOAD CLI PARAM
$input = $options['input'] ?? $options['i'] ?? 'tests';
$output = $options['output'] ?? $options['o'] ?? 'docs/output';
$verbose = isset($options['verbose']) || isset($options['v']);
$lang = $options['lang'] ?? $options['l'] ?? 'it';
if (!in_array($lang, ['it', 'en'])) {
    Formatter::message("[ERROR] Language '{$lang}' not supported");
    exit(1);
}
$sort = $options['sort'] ?? $options['s'] ?? 'asc';
if (!in_array($sort, ['asc', 'desc'])) {
    Formatter::message("[ERROR] Sort '{$sort}' not supported.");
    exit(1);
}

// Special log verbose
if ($verbose) {
    echo "➡️  PARAMETER:\n";
    echo "   input   = $input\n";
    echo "   output  = $output\n";
    echo "   verbose = " . ($verbose ? 'true' : 'false') . "\n";
    echo "   lang    = $lang\n";
    echo "   sort    = $sort\n";
}

//-- LOAD MESSAGES SERVICE
Lang::init($lang);
$messages = Lang::messages();

// --- HELP
if (isset($options['h']) || isset($options['help'])) {
    echo $messages = Lang::messages(true) . "\n";
    exit(0);
}

// --- CHECK IF EXIST INP DIR
if (!is_dir($input)) {
    echo Formatter::sprintf($messages['no_input_dir'], $input);
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
    echo Formatter::sprintf($messages['no_md_found'], $input);
    exit(1);
}

//+ CALL MD GEN
$generator = new MarkdownDocGenerator($input, $output, $verbose, $messages, $sort);
$generator->run();
