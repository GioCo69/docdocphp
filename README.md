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
./bin/docdoc.php --input=mydoc.wiki --output=docs/mydoc.wiki --verbose -s asc
```

## TODO

- manca da rivedere la generazione del menu nel file Home.md, prende solo il primo livello

## Help
```bash
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
```

## Requisiti

- PHP ≥ 7.4
- pandoc installato e nel PATH
- Composer
- documentazione del tuo progetto generata con phpDocumentor 

## Setup

```bash
composer install
```

## 🧪 Test RUN

```bash
./bin/docdoc.php -h
```