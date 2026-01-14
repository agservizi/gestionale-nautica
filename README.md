# NautikaPro

Sistema gestionale professionale per Scuola Nautica realizzato con PHP puro, JavaScript Vanilla e Bootstrap 5.

## 📋 Caratteristiche Principali

- **Anagrafica Clienti** con storico completo
- **Gestione Pratiche** con calendario e campi dinamici per tipo
- **Pagamenti Immediati** durante apertura pratica
- **Stato Economico Pratiche** (totale/pagato/residuo)
- **Agenda Guide** con calendario giornaliero
- **Gestione Spese** per categoria
- **Report Economici** con entrate/uscite/saldo

## 🚀 Installazione

### 1. Requisiti
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

### 2. Setup Database

```bash
mysql -u root -p
```

Importa lo schema:
```bash
mysql -u root -p < database_schema.sql
```

### 3. Configurazione

Modifica `config/config.php` con le tue credenziali database:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'tuapassword');
define('DB_NAME', 'gestionale_nautica');
```

### 4. Avvio

Copia i file nella directory del web server (es: `/var/www/html/` o htdocs)

Accedi tramite browser:
```
http://localhost/gestionale_nautica/
```

## 📁 Struttura Progetto

```
/config          - Configurazione e connessione DB
/includes        - Header, sidebar, footer, functions
/pages          - Tutte le pagine dell'applicazione
/assets
  /css          - Stili custom Bootstrap
  /js           - JavaScript vanilla
  /icons        - Icone
```

## 🎨 UI & UX

- **Sidebar collapsibile** per navigazione rapida
- **Palette colori professionale**: Blu navy, Azzurro, Oro, Grigio
- **Tabelle responsive** con filtri
- **Modali** per inserimento/modifica dati
- **Badge** colorati per stati pratiche ed economici
- **Dashboard** con KPI e statistiche

## 📊 Moduli Principali

### Dashboard
Statistiche anno corrente, pratiche, entrate, uscite, saldo

### Clienti
CRUD completo + storico pratiche/pagamenti/guide

### Pratiche
- Gestione completa con calendario
- Campi dinamici per tipo (Patente/Rinnovo/Duplicato/Altro)
- Stato pratica ed economico
- Pagamento immediato all'apertura

### Pagamenti
Storico pagamenti con filtri per anno/mese/metodo

### Agenda Guide
Calendario giornaliero 08:00-18:00 (Lun-Sab)

### Spese
Gestione uscite per categoria (Vincenzo, Luigi, Affitto, Benzina, Altro)

### Report
Report economici con grafici e possibilità di export (PDF/Excel)

## 🔐 Sicurezza

- **Prepared Statements** per tutte le query
- **PDO** con gestione errori
- **Validazione input** lato server e client
- **Escape HTML** per prevenire XSS

## 💡 Note per lo Sviluppo

- PHP OOP per gestione database (Singleton pattern)
- JavaScript Vanilla (no jQuery)
- Bootstrap 5.3.2 completamente customizzato
- Trigger MySQL per aggiornamento automatico totale pagato

## 📱 Mobile (Capacitor)

È presente una wrapper Capacitor in mobile/ per rendere l'app usabile come mobile app.
Imposta l'URL del server in mobile/capacitor.config.json (server.url) prima di compilare.

## 📞 Supporto

Sistema pronto per uso reale in ufficio.

Per customizzazioni contatta lo sviluppatore.

---

**Versione**: 1.0.0  
**Anno**: 2025+  
**Licenza**: Proprietario
