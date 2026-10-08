<?php

return [
    'daemon_connection_failed' => 'Si è verificata un\'eccezione durante la comunicazione con il daemon, con codice di risposta HTTP/:code. L\'eccezione è stata registrata.',
    'node' => [
        'servers_attached' => 'Un nodo non deve avere server collegati per poter essere eliminato.',
        'daemon_off_config_updated' => 'La configurazione del daemon è stata aggiornata, ma si è verificato un errore durante l\'aggiornamento automatico del file di configurazione sul daemon. Dovrai aggiornare manualmente il file di configurazione (config.yml) del daemon per applicare queste modifiche.',
    ],
    'allocations' => [
        'server_using' => 'Un server è attualmente assegnato a questa allocazione. Un\'allocazione può essere eliminata solo se non ha server assegnati.',
        'too_many_ports' => 'Aggiungere più di 1000 porte in un unico intervallo non è supportato.',
        'invalid_mapping' => 'La mappatura fornita per :port non era valida e non ha potuto essere elaborata.',
        'cidr_out_of_range' => 'La notazione CIDR consente solo maschere tra /25 e /32.',
        'port_out_of_range' => 'Le porte di un\'allocazione devono essere maggiori di 1024 e minori o uguali a 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Un Nest con server attivi collegati non può essere eliminato dal pannello.',
        'egg' => [
            'delete_has_servers' => 'Un Egg con server attivi collegati non può essere eliminato dal pannello.',
            'invalid_copy_id' => 'L\'Egg selezionato per copiare uno script non esiste oppure copia a sua volta uno script.',
            'must_be_child' => 'La direttiva "Copia impostazioni da" di questo Egg deve essere un\'opzione figlia del Nest selezionato.',
            'has_children' => 'Questo Egg è genitore di uno o più altri Egg. Elimina quegli Egg prima di eliminare questo.',
        ],
        'variables' => [
            'env_not_unique' => 'La variabile d\'ambiente :name deve essere univoca per questo Egg.',
            'reserved_name' => 'La variabile d\'ambiente :name è protetta e non può essere assegnata a una variabile.',
            'bad_validation_rule' => 'La regola di validazione ":rule" non è valida per questa applicazione.',
        ],
        'importer' => [
            'json_error' => 'Si è verificato un errore durante l\'analisi del file JSON: :error.',
            'file_error' => 'Il file JSON fornito non era valido.',
            'invalid_json_provided' => 'Il file JSON fornito non è in un formato riconoscibile.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Non è consentito modificare il proprio account di sotto-utente.',
        'user_is_owner' => 'Non puoi aggiungere il proprietario del server come sotto-utente di questo server.',
        'subuser_exists' => 'Un utente con quell\'indirizzo email è già assegnato come sotto-utente di questo server.',
    ],
    'databases' => [
        'delete_has_databases' => 'Impossibile eliminare un host di database con database attivi collegati.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'L\'intervallo massimo per un\'attività concatenata è di 15 minuti.',
    ],
    'locations' => [
        'has_nodes' => 'Impossibile eliminare una posizione con nodi attivi collegati.',
    ],
    'users' => [
        'node_revocation_failed' => 'Impossibile revocare le chiavi su <a href=":link">Nodo #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Non è stato trovato alcun nodo che soddisfi i requisiti specificati per la distribuzione automatica.',
        'no_viable_allocations' => 'Non è stata trovata alcuna allocazione che soddisfi i requisiti per la distribuzione automatica.',
        'no_placement' => 'Al momento nessun nodo può ospitare questo server (memoria e disco liberi sufficienti, una porta libera, online e non in manutenzione). Non è stato creato nulla.',
    ],
    'api' => [
        'resource_not_found' => 'La risorsa richiesta non esiste su questo server.',
    ],
    'server' => [
        'cpu_above_node' => 'Un server può ottenere al massimo :max% di CPU sul nodo :node (:threads thread CPU, 100% ciascuno). :cpu% è più di quanto il nodo abbia.',
        'memory_above_node' => 'Un server può ottenere al massimo :max MiB di memoria sul nodo :node. :memory MiB è più di quanto il nodo abbia.',
        'disk_above_node' => 'Un server può ottenere al massimo :max MiB di spazio su disco sul nodo :node. :disk MiB è più di quanto il nodo abbia.',
    ],
];
