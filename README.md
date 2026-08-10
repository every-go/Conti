# Conti Famiglia

App web per tracciare le spese personali/familiari, con login, conti annuali e
sotto-conti a livelli infiniti (es. `2022 → Sardegna → Trasporti`).

Stack: **PHP 8.2 (puro) + MySQL 8 + Tailwind CSS (via CDN)**, containerizzata con Docker
per lo sviluppo in locale.

## Struttura del progetto

```
mazzaretto-conti/
├── docker-compose.yml       Orchestrazione dei 3 servizi (web, db, phpmyadmin)
├── .env                     Credenziali usate da docker-compose (NON versionare)
├── .env.example              Esempio di .env da copiare/personalizzare
├── docker/php/Dockerfile     Immagine PHP + Apache con estensione pdo_mysql
├── db/schema.sql             Schema del database, eseguito al primo avvio del container MySQL
└── src/                      Tutto il codice PHP (montato in /var/www/html)
    ├── index.php              Redirect a login o dashboard
    ├── login.php / register.php / logout.php
    ├── dashboard.php          Vista d'insieme: conti per anno + totale globale
    ├── conto.php              Vista di un singolo conto: voci, sotto-conti, totale ricorsivo
    ├── crea_conto.php         Crea un conto principale (anno) o un sotto-conto
    ├── inserisci.php          Form di inserimento nuovo record
    ├── modifica.php           Modifica/elimina un record esistente
    ├── storico.php            Storico filtrabile per conto, categoria, tipo, intervallo date
    └── includes/
        ├── db.php              Connessione PDO al database
        ├── bootstrap.php        Avvia sessione + carica db.php e functions.php
        ├── auth.php              requireLogin()
        ├── functions.php         Formattazione importi, calcoli ricorsivi sui conti, controlli di sicurezza
        ├── header.php / footer.php   Layout comune (nav + Tailwind)
```

## Come funziona lo schema dati

- **utenti**: `id`, `username` (UNIQUE), `password` (hashata con `password_hash`, mai in chiaro)
- **conti**: `id`, `nome`, `anno` (valorizzato solo se è un conto principale), `user_id`,
  `parent_conto_id` (NULL = conto principale, altrimenti sotto-conto — supporta livelli infiniti)
- **record**: `id`, `conto_id`, `nome` (categoria, es. "Cibo"), `soldi` (negativo se costo),
  `tipo` (ENUM `Contanti`/`Bancomat`, obbligatorio), `data`, `note` (opzionale)

I totali (per categoria, per conto, globali) **non sono mai salvati**: si calcolano sempre al
volo con `SUM()` sulla tabella `record`, quindi sono sempre coerenti anche modificando o
eliminando un record. Un conto (es. "2022") mostra automaticamente anche il totale dei suoi
sotto-conti (es. "Sardegna"), sommato ricorsivamente.

## Avvio in locale con Docker

Serve solo **Docker** e **Docker Compose** installati.

```bash
# 1. (opzionale) personalizza le password in .env, controllare il .env.example per avere un esempio di .env
# 2. avvia i container
docker compose up -d --build

# 3. apri il sito
#    http://localhost:8080

# 4. (opzionale) apri phpMyAdmin per vedere il database a occhio
#    http://localhost:8081   (utente/password presi da .env)
```

Al primo avvio, MySQL esegue automaticamente `db/schema.sql` e crea le tabelle. Registra il
primo utente da `http://localhost:8080/register.php`: viene creato in automatico anche il
conto per l'anno corrente.

Le modifiche ai file dentro `src/` sono visibili subito ricaricando la pagina (nessun rebuild
necessario: la cartella è montata come volume).

### Comandi utili

```bash
docker compose down        # ferma i container, MANTIENE i dati del database
docker compose down -v     # ferma i container e CANCELLA anche il database
docker compose logs -f web # log del servizio PHP
```