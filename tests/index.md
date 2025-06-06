---
title: Documentazione Interna
author: Giorgio Codazzi
inject_id: sviluppo-web
date: 2025-04-21
subtitle: Manuale per i collaboratori
keywords: [standard, VSCode, docker, azienda]
lang: it
toc: false
toc-title: Indice dei contenuti
repository: https://github.com/ProgettoilSeme/social-economy
version: 1.0
---

# Benvenuto

Questa è una documentazione interna per collaboratori che si occupano dello sviluppo web aziendale.

***Per tornare alla documentazione*** [della libreria](/docs/index.html).

Nella presente è raccolta l'organizzazione, gli strumenti usati, le tecnologie e gli standard oltre a un breve riassunto di quanto ottenuto con ***questo primo progetto pilota*** e quindi ***questa documentazione***.

Alla data attuale (aprile 2025) vicini alla prima fase di rilascio in beta, usiamo una piattaforma di sviluppo in remoto connessa alla rete aziendale, costituita da un mini-PC con OS Proxmox (linux) e diverse macchine Debian 12 virtuali, ognuna delle quali monta una soluzione Docker gestita via Portainer.io che insieme costituiscono una  "*mini-webfarm*" per lo sviluppo: un docker-host infra, un docker-host web e alcuni docker host dedicati allo sviluppo per ogni ruolo, uno per il frontend e uno per il backend del sito.

Abbiamo quindi due ruoli: un grafico esperto in JS e un tecnico WordPress PHP + MySQL.

Lo sviluppo utilizza VSCode come IDE, X-Debug e PHP come linguaggi e LAMP Docker dedicati al ruolo. Tuttavia l'ambiente ha richiesto nel tempo e con le necessità diversi "*adattamenti*". 

Attualmente il backend poggia integralmente su una libreria di classi PHP 8.3.x raccolta sotto un unico plugin e un unico package (SEA) estendibile a piacere di WordPress che risponde alle esigenze del frontend via AJAX e con chiamate dirette (per l'inizializzazione delle pagine).

Le chiamate dirette sono interfacciate tramite un unica classe di swtich "*LibraryFrontEnd*" che gestisce le chiamate ai metodi tramite fuzioni di wrapping, ripartendole verso le sottoclassi di progetto dedicate. Quelle via AJAX (editig dei dati) sono chiamate intese alla modifica dei dati che agiscono tramite callback registrate in WP e via fetch POST JS dal frontend.

La piattaforma di sviluppo IDE è connessa poi a un repository Git dove tutto il sito è salvato. Gli aggiornamenti del codice (PUSH) avvengono tramite accordo tra i differenti ruoli, riducendo al minimo i conflitti (MERGE && PULL). In generale si procede in questo modo: mano a mano che i servizi del sito aggiunti nel plugin vengono implementati e attivati, il codice migra verso frontend perché siano preparate le interfaccie che li sfruttano, quindi avviene un adeguamento di "*fallback*" (ricaduta) per il debugging con il LAMP backend e si procede allo scambio fino al raggiungimento di una condizione stabile.

Le modifiche al database (dato che i LAMP sono distinti) che non conviene adeguare con interventi manuali o di altra natura ma che occorrono per l'allineamento, vengono condivise tramite backup UpDraft Plus, un plugin di WordPress.

Alla data attuale abbiamo un DB con un installazione WP Standard e alcune tabelle proprietarie.

Per ogni altro approfondimento consultare le sezioni dedicate.



---

# Home

Main [Standard di codifica](index.html)