<?php

return [
    'title' => 'Sospendi quando vuoto',
    'description' => 'Quando nessuno è stato su questo server per un po\', viene fermato per liberare la memoria. Resta nell\'elenco dei server e il primo giocatore che entra lo riavvia (gli viene chiesto di riconnettersi tra un attimo). Funziona con i server Minecraft Java.',
    'enabled_label' => 'Sospendi quando vuoto',
    'minutes_label' => 'Ferma quando nessuno è stato connesso per',
    'minutes' => ':count minuti',
    'default_note' => 'Vale l\'impostazione predefinita del pannello: :state, :minutes minuti.',
    'state_on' => 'attiva',
    'state_off' => 'disattiva',
    'reset' => 'Usa l\'impostazione predefinita del pannello',
    'saved' => 'Impostazioni della modalità sospensione salvate.',
    'unsupported' => 'La versione di Wings di questo nodo non conosce ancora la modalità sospensione, quindi l\'impostazione non ha effetto finché Wings non viene aggiornato.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eQuesto server sta dormendo §7- entra per svegliarlo',
        'starting' => '§eIl server dormiva e ora si sta avviando. §7Riconnettiti tra circa un minuto.',
    ],
];
