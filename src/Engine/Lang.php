<?php
namespace DocDoc\Engine;

class Lang
{
    public static function messages(string $lang = 'it'): array
    {
        $en = [
            'start' => [ 'str' => "📂 Starting generation from '%s' to '%s'", 'lvl' => 'info'],
            'done' => [ 'str' => "✅ Generation complete.", 'lvl' => 'info'],
            'file_generated' => [ 'str' => "↪️  Generated: %s", 'lvl' => 'info'],
            'no_input_dir' => [ 'str' => "❌ Error: input directory '%s' does not exist.", 'lvl' => 'error'],
            'no_md_found' => [ 'str' => "⚠️ Warning: input directory '%s' contains no Markdown (.md) files.", 'lvl' => 'warning'],
            'help' => <<<EOT
DocDoc - Markdown to HTML Generator with phpDocumentor-compatible layout

USAGE:
  ./bin/docdoc.php --input=DIR --output=DIR [--verbose] [--lang=en]

OPTIONS:
  -i, --input       Input directory with .md files (default: tests)
  -o, --output      Output directory for HTML (default: docs/output)
  -v, --verbose     Enable detailed log (debug)
  -l, --lang        Language (it/en) [default: it]
  -h, --help        Show this help message

EXAMPLE:
  ./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose --lang=en
EOT
        ];

        $it = [
            'start' => [ 'str' => "📂 Inizio generazione da '%s' a '%s'", 'lvl' => 'info'],
            'done' => [ 'str' => "✅ Generazione completata.", 'lvl' => 'info'],
            'file_generated' => [ 'str' => "↪️  Generato: %s", 'lvl' => 'info'],
            'no_input_dir' => [ 'str' => "❌ Errore: la directory di input '%s' non esiste.", 'lvl' => 'error'],
            'no_md_found' => [ 'str' => "⚠️ Avviso: la cartella '%s' non contiene file Markdown (.md).", 'lvl' => 'warning'],
            'help' => <<<EOT
DocDoc - Generatore HTML da Markdown compatibile con phpDocumentor

USO:
  ./bin/docdoc.php --input=DIR --output=DIR [--verbose] [--lang=it]

OPZIONI:
  -i, --input       Cartella di input con file .md (default: tests)
  -o, --output      Cartella di output per HTML (default: docs/output)
  -v, --verbose     Attiva log dettagliato (debug)
  -l, --lang        Lingua (it/en) [default: it]
  -h, --help        Mostra questo messaggio di aiuto

ESEMPIO:
  ./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose --lang=it
EOT
        ];

        return $lang === 'en' ? $en : $it;
    }
}
