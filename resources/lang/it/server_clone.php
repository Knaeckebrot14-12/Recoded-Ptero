<?php

return [
    'title' => 'Copia questo server',
    'description' => 'Copia tutti i file di questo server in un altro dei tuoi server dello stesso tipo. I suoi file attuali finiscono nel suo cestino (ripristinabili per :hours ore) se c\'è abbastanza spazio, altrimenti vengono eliminati. Entrambi i server devono essere fermi.',
    'target_label' => 'Copia in',
    'choose' => 'Scegli un server',
    'start' => 'Copia file',
    'confirm_title' => 'Sostituire i file di :target?',
    'confirm_body' => 'Tutti i file di :target vengono sostituiti da una copia dei file di questo server. Può richiedere qualche minuto.',
    'running' => 'Copia in corso… finora :files file.',
    'done' => 'Fatto: :files file copiati in :target.',
    'done_kept' => 'I suoi file precedenti sono nel suo cestino.',
    'done_deleted' => 'I suoi file precedenti sono stati eliminati (spazio insufficiente per conservarli).',
    'failed' => 'La copia non è riuscita: :error',
    'unsupported' => 'Wings su questo nodo è troppo vecchio per copiare (serve :version o più recente).',
    'reasons' => [
        'other_node' => 'su un altro nodo',
        'no_permission' => 'nessun permesso',
        'unavailable' => 'al momento non disponibile',
    ],
    'errors' => [
        'no_permission' => 'Non puoi leggere i file di questo server.',
        'not_allowed' => 'Questo server non può essere la destinazione.',
        'other_node' => 'Si può copiare solo in server sullo stesso nodo.',
        'unavailable' => 'Uno dei server è sospeso, in installazione o in ripristino.',
        'wings_too_old' => 'Wings su questo nodo è troppo vecchio per copiare (serve :version o più recente).',
    ],
];
