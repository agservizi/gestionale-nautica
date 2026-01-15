<?php
/**
 * NautikaPro
 * Funzioni CRUD e utility
 */

require_once __DIR__ . '/../config/config.php';

// ============================================
// UTILITA' CODICE FISCALE / PATENTE
// ============================================

function normalizeCodiceFiscale($cf) {
    $cf = strtoupper(trim($cf ?? ''));
    return $cf !== '' ? $cf : null;
}

function getBirthDateFromCodiceFiscale($cf) {
    $cf = normalizeCodiceFiscale($cf);
    if (!$cf || strlen($cf) !== 16) {
        return null;
    }

    $year = (int)substr($cf, 6, 2);
    $monthChar = substr($cf, 8, 1);
    $dayRaw = (int)substr($cf, 9, 2);

    $monthMap = [
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
        'E' => 5,
        'H' => 6,
        'L' => 7,
        'M' => 8,
        'P' => 9,
        'R' => 10,
        'S' => 11,
        'T' => 12,
    ];

    if (!isset($monthMap[$monthChar])) {
        return null;
    }

    $day = $dayRaw > 40 ? $dayRaw - 40 : $dayRaw;
    if ($day < 1 || $day > 31) {
        return null;
    }

    $currentYear = (int)date('Y');
    $currentYY = $currentYear % 100;
    $century = ($year <= $currentYY) ? 2000 : 1900;
    $fullYear = $century + $year;

    try {
        return new DateTime(sprintf('%04d-%02d-%02d', $fullYear, $monthMap[$monthChar], $day));
    } catch (Exception $e) {
        return null;
    }
}

function calculatePatenteScadenza(DateTime $birthDate, DateTime $conseguimentoDate) {
    $age = $birthDate->diff($conseguimentoDate);
    $years = (int)$age->y;
    $validYears = $years >= 60 ? 5 : 10;
    $scadenza = clone $conseguimentoDate;
    $scadenza->modify('+' . $validYears . ' years');
    return $scadenza->format('Y-m-d');
}

// ============================================
// CLIENTI - CRUD
// ============================================

function getClienti($search = '', $limit = null, $offset = 0) {
    $db = getDB();
    $sql = "SELECT * FROM clienti WHERE 1=1";
    $params = [];
    
    if(!empty($search)) {
        $sql .= " AND (nome LIKE ? OR cognome LIKE ? OR telefono LIKE ? OR email LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    $sql .= " ORDER BY cognome, nome";
    
    if($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getClienteById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createCliente($data) {
    $db = getDB();
    $codiceFiscale = normalizeCodiceFiscale($data['codice_fiscale'] ?? null);
    $scadenzaPatente = $data['data_scadenza_patente'] ?? null;

    if (!empty($data['data_conseguimento_patente']) && $codiceFiscale) {
        $birthDate = getBirthDateFromCodiceFiscale($codiceFiscale);
        if ($birthDate) {
            $conseguimentoDate = new DateTime($data['data_conseguimento_patente']);
            if ($conseguimentoDate >= $birthDate) {
                $scadenzaPatente = calculatePatenteScadenza($birthDate, $conseguimentoDate);
            }
        }
    }
    $stmt = $db->prepare("
        INSERT INTO clienti (nome, cognome, telefono, email, codice_fiscale, tipo_pratica, numero_patente, data_conseguimento_patente, data_scadenza_patente, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['nome'],
        $data['cognome'],
        $data['telefono'] ?? null,
        $data['email'] ?? null,
        $codiceFiscale,
        $data['tipo_pratica'] ?? 'Altro',
        $data['numero_patente'] ?? null,
        $data['data_conseguimento_patente'] ?? null,
        $scadenzaPatente,
        $data['note'] ?? null
    ]);
    return $db->lastInsertId();
}

function updateCliente($id, $data) {
    $db = getDB();
    $codiceFiscale = normalizeCodiceFiscale($data['codice_fiscale'] ?? null);
    $scadenzaPatente = $data['data_scadenza_patente'] ?? null;

    if (!empty($data['data_conseguimento_patente']) && $codiceFiscale) {
        $birthDate = getBirthDateFromCodiceFiscale($codiceFiscale);
        if ($birthDate) {
            $conseguimentoDate = new DateTime($data['data_conseguimento_patente']);
            if ($conseguimentoDate >= $birthDate) {
                $scadenzaPatente = calculatePatenteScadenza($birthDate, $conseguimentoDate);
            }
        }
    }
    $stmt = $db->prepare("
        UPDATE clienti 
        SET nome = ?, cognome = ?, telefono = ?, email = ?, codice_fiscale = ?, tipo_pratica = ?, numero_patente = ?, data_conseguimento_patente = ?, data_scadenza_patente = ?, note = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['nome'],
        $data['cognome'],
        $data['telefono'] ?? null,
        $data['email'] ?? null,
        $codiceFiscale,
        $data['tipo_pratica'] ?? 'Altro',
        $data['numero_patente'] ?? null,
        $data['data_conseguimento_patente'] ?? null,
        $scadenzaPatente,
        $data['note'] ?? null,
        $id
    ]);
}

function deleteCliente($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM clienti WHERE id = ?");
    return $stmt->execute([$id]);
}

function countClienti() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as count FROM clienti");
    return $stmt->fetch()['count'];
}

// ============================================
// PRATICHE - CRUD
// ============================================

function getPratiche($filters = []) {
    $db = getDB();
    $sql = "SELECT p.*, 
            CONCAT(c.cognome, ' ', c.nome) as cliente_nome,
            c.telefono as cliente_telefono
            FROM pratiche p
            INNER JOIN clienti c ON p.cliente_id = c.id
            WHERE 1=1";
    $params = [];
    
    if(!empty($filters['cliente_id'])) {
        $sql .= " AND p.cliente_id = ?";
        $params[] = $filters['cliente_id'];
    }
    
    if(!empty($filters['stato'])) {
        $sql .= " AND p.stato = ?";
        $params[] = $filters['stato'];
    }
    
    if(!empty($filters['tipo_pratica'])) {
        $sql .= " AND p.tipo_pratica = ?";
        $params[] = $filters['tipo_pratica'];
    }
    
    if(!empty($filters['anno'])) {
        $sql .= " AND YEAR(p.data_apertura) = ?";
        $params[] = $filters['anno'];
    }
    
    if(!empty($filters['mese'])) {
        $sql .= " AND MONTH(p.data_apertura) = ?";
        $params[] = $filters['mese'];
    }
    
    $sql .= " ORDER BY p.data_apertura DESC, p.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAltroSottocategorie() {
        $db = getDB();
        $stmt = $db->query("SELECT DISTINCT tipo_altro_dettaglio
                                                FROM pratiche
                                                WHERE tipo_pratica = 'Altro'
                                                    AND tipo_altro_dettaglio IS NOT NULL
                                                    AND tipo_altro_dettaglio <> ''
                                                ORDER BY tipo_altro_dettaglio ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getPraticaById($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, 
        CONCAT(c.cognome, ' ', c.nome) as cliente_nome,
        c.telefono as cliente_telefono,
        c.email as cliente_email
        FROM pratiche p
        INNER JOIN clienti c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createPratica($data) {
    $db = getDB();
    $tipoPratica = $data['tipo_pratica'] ?? null;
    if ($tipoPratica === null || $tipoPratica === '') {
        $cliente = getClienteById($data['cliente_id'] ?? 0);
        $tipoPratica = $cliente['tipo_pratica'] ?? 'Altro';
    }
    $stmt = $db->prepare("
        INSERT INTO pratiche (
            cliente_id, data_apertura, stato, tipo_pratica, tipo_altro_dettaglio,
            totale_previsto, data_esame, esito_esame, data_conseguimento, numero_patente,
            allegati, data_richiesta_rinnovo, data_completamento_rinnovo, note_rinnovo,
            motivo_duplicato, motivo_duplicato_dettaglio, data_richiesta_duplicato,
            data_chiusura_duplicato, note
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['cliente_id'],
        $data['data_apertura'],
        $data['stato'] ?? 'Aperta',
        $tipoPratica,
        $data['tipo_altro_dettaglio'] ?? null,
        $data['totale_previsto'] ?? 0,
        $data['data_esame'] ?? null,
        $data['esito_esame'] ?? null,
        $data['data_conseguimento'] ?? null,
        $data['numero_patente'] ?? null,
        $data['allegati'] ?? null,
        $data['data_richiesta_rinnovo'] ?? null,
        $data['data_completamento_rinnovo'] ?? null,
        $data['note_rinnovo'] ?? null,
        $data['motivo_duplicato'] ?? null,
        $data['motivo_duplicato_dettaglio'] ?? null,
        $data['data_richiesta_duplicato'] ?? null,
        $data['data_chiusura_duplicato'] ?? null,
        $data['note'] ?? null
    ]);
    return $db->lastInsertId();
}

function updatePratica($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE pratiche SET
            data_apertura = ?, stato = ?, tipo_pratica = ?, tipo_altro_dettaglio = ?,
            totale_previsto = ?, data_esame = ?, esito_esame = ?, data_conseguimento = ?,
            numero_patente = ?, allegati = ?, data_richiesta_rinnovo = ?,
            data_completamento_rinnovo = ?, note_rinnovo = ?, motivo_duplicato = ?,
            motivo_duplicato_dettaglio = ?, data_richiesta_duplicato = ?,
            data_chiusura_duplicato = ?, note = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['data_apertura'],
        $data['stato'],
        $data['tipo_pratica'],
        $data['tipo_altro_dettaglio'] ?? null,
        $data['totale_previsto'],
        $data['data_esame'] ?? null,
        $data['esito_esame'] ?? null,
        $data['data_conseguimento'] ?? null,
        $data['numero_patente'] ?? null,
        $data['allegati'] ?? null,
        $data['data_richiesta_rinnovo'] ?? null,
        $data['data_completamento_rinnovo'] ?? null,
        $data['note_rinnovo'] ?? null,
        $data['motivo_duplicato'] ?? null,
        $data['motivo_duplicato_dettaglio'] ?? null,
        $data['data_richiesta_duplicato'] ?? null,
        $data['data_chiusura_duplicato'] ?? null,
        $data['note'] ?? null,
        $id
    ]);
}

function deletePratica($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM pratiche WHERE id = ?");
    return $stmt->execute([$id]);
}

function countPratiche($filters = []) {
    $db = getDB();
    $sql = "SELECT COUNT(*) as count FROM pratiche WHERE 1=1";
    $params = [];
    
    if(!empty($filters['stato'])) {
        $sql .= " AND stato = ?";
        $params[] = $filters['stato'];
    }
    
    if(!empty($filters['anno'])) {
        $sql .= " AND YEAR(data_apertura) = ?";
        $params[] = $filters['anno'];
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['count'];
}

// ============================================
// PAGAMENTI - CRUD
// ============================================

function getPagamenti($filters = []) {
    $db = getDB();
    $sql = "SELECT pg.*, 
            CONCAT(c.cognome, ' ', c.nome) as cliente_nome,
            p.tipo_pratica
            FROM pagamenti pg
            INNER JOIN clienti c ON pg.cliente_id = c.id
            INNER JOIN pratiche p ON pg.pratica_id = p.id
            WHERE 1=1";
    $params = [];
    
    if(!empty($filters['pratica_id'])) {
        $sql .= " AND pg.pratica_id = ?";
        $params[] = $filters['pratica_id'];
    }
    
    if(!empty($filters['cliente_id'])) {
        $sql .= " AND pg.cliente_id = ?";
        $params[] = $filters['cliente_id'];
    }
    
    if(!empty($filters['metodo_pagamento'])) {
        $sql .= " AND pg.metodo_pagamento = ?";
        $params[] = $filters['metodo_pagamento'];
    }
    
    if(!empty($filters['anno'])) {
        $sql .= " AND YEAR(pg.data_pagamento) = ?";
        $params[] = $filters['anno'];
    }
    
    if(!empty($filters['mese'])) {
        $sql .= " AND MONTH(pg.data_pagamento) = ?";
        $params[] = $filters['mese'];
    }
    
    $sql .= " ORDER BY pg.data_pagamento DESC, pg.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPagamentoById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM pagamenti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createPagamento($data) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO pagamenti (pratica_id, cliente_id, tipo_pagamento, importo, metodo_pagamento, data_pagamento, note)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['pratica_id'],
        $data['cliente_id'],
        $data['tipo_pagamento'],
        $data['importo'],
        $data['metodo_pagamento'],
        $data['data_pagamento'],
        $data['note'] ?? null
    ]);
    return $db->lastInsertId();
}

function updatePagamento($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE pagamenti 
        SET tipo_pagamento = ?, importo = ?, metodo_pagamento = ?, data_pagamento = ?, note = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['tipo_pagamento'],
        $data['importo'],
        $data['metodo_pagamento'],
        $data['data_pagamento'],
        $data['note'] ?? null,
        $id
    ]);
}

function deletePagamento($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM pagamenti WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// AGENDA GUIDE - CRUD
// ============================================

function getAgendaGuide($filters = []) {
    $db = getDB();
    $sql = "SELECT ag.*, 
            CONCAT(c.cognome, ' ', c.nome) as cliente_nome,
            c.telefono as cliente_telefono
            FROM agenda_guide ag
            INNER JOIN clienti c ON ag.cliente_id = c.id
            WHERE 1=1";
    $params = [];
    
    if(!empty($filters['data'])) {
        $sql .= " AND ag.data_guida = ?";
        $params[] = $filters['data'];
    }
    
    if(!empty($filters['data_inizio']) && !empty($filters['data_fine'])) {
        $sql .= " AND ag.data_guida BETWEEN ? AND ?";
        $params[] = $filters['data_inizio'];
        $params[] = $filters['data_fine'];
    }
    
    if(!empty($filters['cliente_id'])) {
        $sql .= " AND ag.cliente_id = ?";
        $params[] = $filters['cliente_id'];
    }
    
    $sql .= " ORDER BY ag.data_guida, ag.orario_inizio";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAgendaById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM agenda_guide WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createAgenda($data) {
    $db = getDB();
    $praticaId = isset($data['pratica_id']) && $data['pratica_id'] !== ''
        ? (int)$data['pratica_id']
        : null;
    $tipoLezione = isset($data['tipo_lezione']) && $data['tipo_lezione'] !== ''
        ? $data['tipo_lezione']
        : null;
    $note = isset($data['note']) && $data['note'] !== ''
        ? $data['note']
        : null;
    $stmt = $db->prepare("
        INSERT INTO agenda_guide (cliente_id, pratica_id, data_guida, orario_inizio, orario_fine, istruttore, tipo_lezione, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['cliente_id'],
        $praticaId,
        $data['data_guida'],
        $data['orario_inizio'],
        $data['orario_fine'],
        $data['istruttore'] ?? null,
        $tipoLezione,
        $note
    ]);
    return $db->lastInsertId();
}

function updateAgenda($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE agenda_guide 
        SET data_guida = ?, orario_inizio = ?, orario_fine = ?, istruttore = ?, tipo_lezione = ?, note = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['data_guida'],
        $data['orario_inizio'],
        $data['orario_fine'],
        $data['istruttore'] ?? null,
        $data['tipo_lezione'] ?? null,
        $data['note'] ?? null,
        $id
    ]);
}

function deleteAgenda($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM agenda_guide WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// SPESE - CRUD
// ============================================

function getSpese($filters = []) {
    $db = getDB();
    $sql = "SELECT * FROM spese WHERE 1=1";
    $params = [];
    
    if(!empty($filters['categoria'])) {
        $sql .= " AND categoria = ?";
        $params[] = $filters['categoria'];
    }
    
    if(!empty($filters['anno'])) {
        $sql .= " AND YEAR(data_spesa) = ?";
        $params[] = $filters['anno'];
    }
    
    if(!empty($filters['mese'])) {
        $sql .= " AND MONTH(data_spesa) = ?";
        $params[] = $filters['mese'];
    }
    
    $sql .= " ORDER BY data_spesa DESC, id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSpesaById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM spese WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createSpesa($data) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO spese (data_spesa, categoria, categoria_altro, importo, descrizione)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['data_spesa'],
        $data['categoria'],
        $data['categoria_altro'] ?? null,
        $data['importo'],
        $data['descrizione'] ?? null
    ]);
    return $db->lastInsertId();
}

function updateSpesa($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE spese 
        SET data_spesa = ?, categoria = ?, categoria_altro = ?, importo = ?, descrizione = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['data_spesa'],
        $data['categoria'],
        $data['categoria_altro'] ?? null,
        $data['importo'],
        $data['descrizione'] ?? null,
        $id
    ]);
}

function deleteSpesa($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM spese WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// STATISTICHE E REPORT
// ============================================

function getStatisticheDashboard($anno = null) {
    if(!$anno) $anno = date('Y');
    $db = getDB();
    
    // Totale clienti
    $totClienti = countClienti();
    
    // Pratiche anno corrente
    $totPratiche = countPratiche(['anno' => $anno]);
    $praticheAperte = countPratiche(['stato' => 'Aperta', 'anno' => $anno]);
    $praticheInCorso = countPratiche(['stato' => 'In corso', 'anno' => $anno]);
    $praticheCompletate = countPratiche(['stato' => 'Completata', 'anno' => $anno]);
    
    // Entrate anno corrente
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(importo), 0) as totale 
        FROM pagamenti 
        WHERE YEAR(data_pagamento) = ?
    ");
    $stmt->execute([$anno]);
    $entrateAnno = $stmt->fetch()['totale'];
    
    // Uscite anno corrente
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(importo), 0) as totale 
        FROM spese 
        WHERE YEAR(data_spesa) = ?
    ");
    $stmt->execute([$anno]);
    $usciteAnno = $stmt->fetch()['totale'];
    
    // Utile/Perdita
    $saldo = $entrateAnno - $usciteAnno;
    
    return [
        'totale_clienti' => $totClienti,
        'totale_pratiche' => $totPratiche,
        'pratiche_aperte' => $praticheAperte,
        'pratiche_in_corso' => $praticheInCorso,
        'pratiche_completate' => $praticheCompletate,
        'entrate_anno' => $entrateAnno,
        'uscite_anno' => $usciteAnno,
        'saldo_anno' => $saldo,
        'anno' => $anno
    ];
}

function getReportEconomico($anno = null, $mese = null, $tipoPratica = null, $metodoPagamento = null) {
    if(!$anno) $anno = date('Y');
    $db = getDB();
    
    // Entrate
    $sqlEntrate = "SELECT 
        COALESCE(SUM(importo), 0) as totale,
        metodo_pagamento,
        COUNT(*) as numero_transazioni
        FROM pagamenti pg
        INNER JOIN pratiche p ON pg.pratica_id = p.id
        WHERE YEAR(pg.data_pagamento) = ?";
    $params = [$anno];
    
    if($mese) {
        $sqlEntrate .= " AND MONTH(pg.data_pagamento) = ?";
        $params[] = $mese;
    }

    if($metodoPagamento) {
        $sqlEntrate .= " AND pg.metodo_pagamento = ?";
        $params[] = $metodoPagamento;
    }

    if($tipoPratica) {
        $sqlEntrate .= " AND p.tipo_pratica = ?";
        $params[] = $tipoPratica;
    }
    
    $sqlEntrate .= " GROUP BY metodo_pagamento";
    $stmt = $db->prepare($sqlEntrate);
    $stmt->execute($params);
    $entrate = $stmt->fetchAll();
    
    // Uscite
    $sqlUscite = "SELECT 
        COALESCE(SUM(importo), 0) as totale,
        categoria,
        COUNT(*) as numero_spese
        FROM spese 
        WHERE YEAR(data_spesa) = ?";
    $paramsUscite = [$anno];
    
    if($mese) {
        $sqlUscite .= " AND MONTH(data_spesa) = ?";
        $paramsUscite[] = $mese;
    }
    
    $sqlUscite .= " GROUP BY categoria";
    $stmt = $db->prepare($sqlUscite);
    $stmt->execute($paramsUscite);
    $uscite = $stmt->fetchAll();
    
    // Totali
    $totEntrate = array_sum(array_column($entrate, 'totale'));
    $totUscite = array_sum(array_column($uscite, 'totale'));
    
    return [
        'anno' => $anno,
        'mese' => $mese,
        'tipo_pratica' => $tipoPratica,
        'metodo_pagamento' => $metodoPagamento,
        'entrate' => $entrate,
        'uscite' => $uscite,
        'totale_entrate' => $totEntrate,
        'totale_uscite' => $totUscite,
        'saldo' => $totEntrate - $totUscite
    ];
}

function getEntrateMensili($anno, $metodoPagamento = null, $tipoPratica = null) {
    $db = getDB();
    $sql = "SELECT MONTH(pg.data_pagamento) as mese, COALESCE(SUM(pg.importo),0) as totale
            FROM pagamenti pg
            INNER JOIN pratiche p ON pg.pratica_id = p.id
            WHERE YEAR(pg.data_pagamento) = ?";
    $params = [$anno];

    if ($metodoPagamento) {
        $sql .= " AND pg.metodo_pagamento = ?";
        $params[] = $metodoPagamento;
    }

    if ($tipoPratica) {
        $sql .= " AND p.tipo_pratica = ?";
        $params[] = $tipoPratica;
    }

    $sql .= " GROUP BY MONTH(pg.data_pagamento)";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $data = array_fill(1, 12, 0);
    foreach ($rows as $r) {
        $data[(int)$r['mese']] = (float)$r['totale'];
    }
    return $data;
}

function getUsciteMensili($anno) {
    $db = getDB();
    $stmt = $db->prepare("SELECT MONTH(data_spesa) as mese, COALESCE(SUM(importo),0) as totale
                          FROM spese WHERE YEAR(data_spesa) = ?
                          GROUP BY MONTH(data_spesa)");
    $stmt->execute([$anno]);
    $rows = $stmt->fetchAll();
    $data = array_fill(1, 12, 0);
    foreach ($rows as $r) {
        $data[(int)$r['mese']] = (float)$r['totale'];
    }
    return $data;
}

function getAgendaCountsByDateRange($startDate, $endDate) {
    $db = getDB();
    $stmt = $db->prepare("SELECT data_guida, COUNT(*) as totale
                          FROM agenda_guide
                          WHERE data_guida BETWEEN ? AND ?
                          GROUP BY data_guida");
    $stmt->execute([$startDate, $endDate]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[$row['data_guida']] = (int)$row['totale'];
    }
    return $map;
}

function getUpcomingGuide($days = 7) {
    $db = getDB();
    $stmt = $db->prepare("SELECT ag.*, CONCAT(c.cognome, ' ', c.nome) as cliente_nome
                          FROM agenda_guide ag
                          INNER JOIN clienti c ON ag.cliente_id = c.id
                          WHERE ag.data_guida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                          ORDER BY ag.data_guida ASC, ag.orario_inizio ASC
                          LIMIT 10");
    $stmt->execute([$days]);
    return $stmt->fetchAll();
}

function getNotificationSummary() {
    $db = getDB();
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM agenda_guide WHERE data_guida = ?");
    $stmt->execute([$today]);
    $todayCount = (int)$stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM agenda_guide WHERE data_guida = ?");
    $stmt->execute([$tomorrow]);
    $tomorrowCount = (int)$stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM pratiche WHERE residuo > 0");
    $scoperte = (int)$stmt->fetch()['total'];

    return [
        'today_guides' => $todayCount,
        'tomorrow_guides' => $tomorrowCount,
        'pratiche_scoperte' => $scoperte
    ];
}

// ============================================
// CONSENSI COOKIE/PRIVACY
// ============================================

function getConsentCookieName() {
    return 'nautikapro_consent';
}

function setConsentCookie($value, $days = 180) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(getConsentCookieName(), $value, [
        'expires' => time() + ($days * 86400),
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}

function getConsentValue() {
    return $_COOKIE[getConsentCookieName()] ?? null;
}

function saveConsent($value, $userId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO cookie_consents (user_id, consent_value, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $value,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function createGdprRequest($userId, $type, $details = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO gdpr_requests (user_id, request_type, details) VALUES (?, ?, ?)");
    $stmt->execute([
        $userId,
        $type,
        $details
    ]);
    return $db->lastInsertId();
}

function getGdprRequestsByUser($userId, $limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM gdpr_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function buildSqlBackup() {
    $db = getDB();
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- Backup NautikaPro\n-- " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        $row = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $create = $row['Create Table'] ?? '';
        $dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $dump .= $create . ";\n\n";

        $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_map(fn($c) => "`$c`", array_keys($rows[0]));
            $dump .= "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES\n";
            $values = [];
            foreach ($rows as $r) {
                $vals = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, array_values($r));
                $values[] = '(' . implode(',', $vals) . ')';
            }
            $dump .= implode(",\n", $values) . ";\n\n";
        }
    }
    return $dump;
}

function restoreSqlBackup($sql) {
    $db = getDB();
    $statements = [];
    $buffer = '';
    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '--')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (substr(trim($line), -1) === ';') {
            $statements[] = $buffer;
            $buffer = '';
        }
    }
    if ($buffer !== '') {
        $statements[] = $buffer;
    }

    foreach ($statements as $stmt) {
        $db->exec($stmt);
    }
}

// ============================================
// SCHEDULER INTERNO (NO CRON)
// ============================================

function initScheduledJobs() {
    $db = getDB();
    $jobs = [
        ['backup_daily', 'Backup database quotidiano', 24 * 60],
        ['audit_cleanup_weekly', 'Cleanup audit log settimanale', 7 * 24 * 60],
        ['notify_daily', 'Invio promemoria giornaliero', 24 * 60],
    ];

    foreach ($jobs as $j) {
        $stmt = $db->prepare("INSERT IGNORE INTO scheduled_jobs (job_key, description, interval_minutes, enabled)
                              VALUES (?, ?, ?, 1)");
        $stmt->execute([$j[0], $j[1], $j[2]]);
    }
}

function runScheduledJobs() {
    // Probabilità di esecuzione per richiesta (default 5%)
    $prob = (int)(getenv('JOBS_PROBABILITY') ?: 5);
    if (mt_rand(1, 100) > $prob) {
        return;
    }

    initScheduledJobs();

    $db = getDB();
    $stmt = $db->query("SELECT * FROM scheduled_jobs WHERE enabled = 1");
    $jobs = $stmt->fetchAll();

    foreach ($jobs as $job) {
        $lastRun = $job['last_run'] ? strtotime($job['last_run']) : 0;
        $interval = (int)$job['interval_minutes'] * 60;
        if (time() - $lastRun >= $interval) {
            runJobByKey($job['job_key']);
        }
    }
}

function runJobByKey($jobKey) {
    $db = getDB();
    $status = 'ok';
    $message = 'OK';

    try {
        ob_start();
        switch ($jobKey) {
            case 'backup_daily':
                require __DIR__ . '/../scripts/backup_rotate.php';
                break;
            case 'audit_cleanup_weekly':
                require __DIR__ . '/../scripts/log_rotate.php';
                break;
            case 'notify_daily':
                require __DIR__ . '/../scripts/notify_daily.php';
                break;
            default:
                throw new Exception('Job non riconosciuto');
        }
        $output = trim(ob_get_clean());
        if ($output !== '') {
            $message = $output;
        }
    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $status = 'error';
        $message = $e->getMessage();
    }

    $stmt = $db->prepare("UPDATE scheduled_jobs SET last_run = NOW(), last_status = ?, last_message = ? WHERE job_key = ?");
    $stmt->execute([$status, $message, $jobKey]);

    logAudit('run_job', 'scheduler', null, $jobKey . ':' . $status);
}

// ============================================
// UTILITY
// ============================================

function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

function formatDate($date, $format = 'd/m/Y') {
    if(empty($date)) return '-';
    return date($format, strtotime($date));
}

function getStatoEconomicoBadge($pratica) {
    $residuo = $pratica['residuo'];
    $totale = $pratica['totale_previsto'];
    $pagato = $pratica['totale_pagato'];
    
    if($totale == 0) {
        return '<span class="badge bg-secondary">Non definito</span>';
    }
    
    if($residuo <= 0) {
        return '<span class="badge bg-success">Saldato</span>';
    } else if($pagato > 0) {
        return '<span class="badge bg-warning">In acconto</span>';
    } else {
        return '<span class="badge bg-danger">Da pagare</span>';
    }
}

function getStatoPraticaBadge($stato) {
    $badges = [
        'Aperta' => 'bg-info',
        'In corso' => 'bg-primary',
        'Completata' => 'bg-success',
        'Annullata' => 'bg-danger'
    ];
    $class = $badges[$stato] ?? 'bg-secondary';
    return "<span class='badge $class'>$stato</span>";
}

// ============================================
// ALLEGATI PRATICHE
// ============================================

function getPraticaAllegati($pratica_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT pa.*, u.username as uploaded_by_name
                          FROM pratiche_allegati pa
                          LEFT JOIN utenti u ON pa.uploaded_by = u.id
                          WHERE pa.pratica_id = ?
                          ORDER BY pa.data_upload DESC");
    $stmt->execute([$pratica_id]);
    return $stmt->fetchAll();
}

function savePraticaAllegato($pratica_id, $file, $uploaded_by = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore upload file.');
    }

    $allowedTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File troppo grande (max 5MB).');
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowedTypes[$mime])) {
        throw new Exception('Formato file non consentito.');
    }

    $ext = $allowedTypes[$mime];
    $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $storedName = 'pratica_' . $pratica_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Impossibile salvare il file.');
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO pratiche_allegati
        (pratica_id, uploaded_by, filename_original, filename_stored, mime_type, file_size)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $pratica_id,
        $uploaded_by,
        $safeOriginal,
        $storedName,
        $mime,
        $file['size']
    ]);

    return $storedName;
}

function deletePraticaAllegato($allegato_id, $pratica_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT filename_stored FROM pratiche_allegati WHERE id = ? AND pratica_id = ?");
    $stmt->execute([$allegato_id, $pratica_id]);
    $file = $stmt->fetch();

    if (!$file) {
        return false;
    }

    $uploadDir = __DIR__ . '/../uploads';
    $path = $uploadDir . '/' . $file['filename_stored'];
    if (file_exists($path)) {
        unlink($path);
    }

    $del = $db->prepare("DELETE FROM pratiche_allegati WHERE id = ? AND pratica_id = ?");
    return $del->execute([$allegato_id, $pratica_id]);
}

// ============================================
// AUDIT LOG
// ============================================

function logAudit($action, $entity, $entityId = null, $details = null) {
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, entity, entity_id, details, ip_address, user_agent)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $entity, $entityId, $details, $ip, $ua]);
}

// ============================================
// CSRF
// ============================================

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function csrf_validate($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
