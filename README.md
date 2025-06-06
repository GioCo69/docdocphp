# docdocphp

Generatore HTML da file Markdown in stile phpDocumentor.  
Supporta input ricorsivo, layout personalizzato, CLI con opzioni e log a colori.

## Uso rapido

```bash
./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose
```

## Help
```bash
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

```

## Requisiti

- PHP ≥ 7.4
- pandoc installato e nel PATH
- Composer

## Setup

```bash
composer install
```

## 🧪 Test RUN

```bash
composer install
```