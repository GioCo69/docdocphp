<?php
namespace DocDoc\Engine;

class Lang
{
    public static function messages(string $lang = 'it'): array
    {
        $en = [
            'start' => "[folder] Starting generation from '%s' to '%s'",
            'done' => "Generation complete.",
            'file_generated' => "[return] Generated: %s",
            'no_input_dir' => "[ERROR] Input directory '%s' does not exist.",
            'no_md_found' => "[WARNING] Input directory '%s' contains no Markdown (.md) files.",
            'help' => <<<EOT
DocDoc - Markdown to HTML Generator with phpDocumentor-compatible layout

USAGE:
  ./bin/docdoc.php --input=DIR --output=DIR [--verbose] [--lang=en]

OPTIONS:
  -i, --input       Input directory with .md files (default: tests)
  -o, --output      Output directory for HTML (default: docs/output)
  -v, --verbose     Enable detailed log (debug)
  -l, --lang        Language (it/en) (default: it)
  -s, --sort        = asc|desc, Sort menu items (default: asc)
  -h, --help        Show this help message

EXAMPLE:
  ./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose --lang=en
EOT
        ];

        $it = [
            'start' => "[folder] Inizio generazione da '%s' a '%s'",
            'done' => "Generazione completata",
            'file_generated' => "[return]  Generato: %s",
            'no_input_dir' => "[ERROR] La directory di input '%s' non esiste.",
            'no_md_found' => "[WARNING] Folder '%s' does not contain Markdown (.md) file",
            'help' => <<<EOT
DocDoc - Generatore HTML da Markdown compatibile con phpDocumentor

USO:
  ./bin/docdoc.php --input=DIR --output=DIR [--verbose] [--lang=it]

OPZIONI:
  -i, --input       Cartella di input con file .md (default: tests)
  -o, --output      Cartella di output per HTML (default: docs/output)
  -v, --verbose     Attiva log dettagliato (debug)
  -l, --lang        Lingua (it/en) (default: it)
  -s, --sort        = asc|desc, Ordina le voci di menu (default: asc)
  -h, --help        Mostra questo messaggio di aiuto

ESEMPIO:
  ./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose --lang=it
EOT
        ];

        return $lang === 'en' ? $en : $it;
    }
}
