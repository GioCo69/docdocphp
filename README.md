# docdocphp

Generatore HTML da file Markdown in stile phpDocumentor.  
Supporta input ricorsivo, layout personalizzato, CLI con opzioni e log a colori.

Pensato come estensione della documentazione generata da phpDocumentor con tema default
Testato con phpDocumentor ver. 3.7

Pensato per generare documentazione MD della wiki GitHub in modo da averla identica
come fosse una estensione delle documentazione di phpDocumentor ma descrittiva del progetto.

⚠️ ATTENZIONE !

Dato che il layout viene preso direttamente da quello della documentazione già generata
da phpDocumentor i file HTML devono trovarsi in un sottodirettorio della cartella
di quella documentazione.

## Uso rapido

```bash
./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose
```

## TODO

- la TOC è da rivedere (si è rotta)
- è stata rifatta la gestione del log CLI (linea di comando) - da testare
- GitHub non gestisce i secondo livello dei menu: li portaimo tutti "flat" 
- da testare bene la gestione delle emoji

## Help
```bash
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
  ./bin/docdoc.php --input=doc.wiki --output=docs/doc.wiki --verbose --lang=en

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