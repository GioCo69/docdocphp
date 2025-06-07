# docdocphp

Generatore HTML da file Markdown in stile phpDocumentor.  
Supporta input ricorsivo, layout personalizzato, CLI con opzioni e log a colori.

Pensato come estensione della documentazione generata da phpDocumentor con tema default
Testato con phpDocumentor ver. 3.7

==ATTENZIONE==
Dato che il layout viene preso direttamente da quello della documentazione già generata
da phpDocumentor i file HTML devono trovarsi in un sottodirettorio della cartella
di quella documentazione


## Uso rapido

```bash
./bin/docdoc.php --input=docSEA.wiki --output=docs/docSEA.wiki --verbose
```

## TODO

- [x] la riga 93 (ora commentata) in MarkdownDocGenerator non gestisce correttamente il base path per il template
- la formattazione della prima voce di menu che ha sottocartelle, rimane visivamente diversa dalle altre (uno schifo)
- il base path del primo gruppo di voci corrisponde a <base href="../../../">
- la TOC è un disastro, non contiene affatto i capitoli e i sottocapitoli del contenuto
- è possibile che la ripetizione della voce Home sia rimasta nelle pagine diverse da quelle in home

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