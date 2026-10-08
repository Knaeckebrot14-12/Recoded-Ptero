<?php

return [
    'title' => 'Deze server kopiëren',
    'description' => 'Kopieert alle bestanden van deze server naar een andere server van jou van hetzelfde type. De huidige bestanden daarvan gaan naar de prullenbak (:hours uur terug te zetten) als er genoeg ruimte is, anders worden ze verwijderd. Beide servers moeten gestopt zijn.',
    'target_label' => 'Kopiëren naar',
    'choose' => 'Kies een server',
    'start' => 'Bestanden kopiëren',
    'confirm_title' => 'Bestanden van :target vervangen?',
    'confirm_body' => 'Alle bestanden van :target worden vervangen door een kopie van de bestanden van deze server. Dit kan enkele minuten duren.',
    'running' => 'Bezig met kopiëren… tot nu toe :files bestanden.',
    'done' => 'Klaar: :files bestanden zijn naar :target gekopieerd.',
    'done_kept' => 'De vorige bestanden staan in de prullenbak daarvan.',
    'done_deleted' => 'De vorige bestanden zijn verwijderd (te weinig ruimte om ze te bewaren).',
    'failed' => 'Kopiëren is mislukt: :error',
    'unsupported' => 'Wings op deze node is te oud om te kopiëren (vereist :version of nieuwer).',
    'reasons' => [
        'other_node' => 'op een andere node',
        'no_permission' => 'geen toestemming',
        'unavailable' => 'nu niet beschikbaar',
    ],
    'errors' => [
        'no_permission' => 'Je mag de bestanden van deze server niet lezen.',
        'not_allowed' => 'Deze server kan niet het doel zijn.',
        'other_node' => 'Kopiëren kan alleen naar servers op dezelfde node.',
        'unavailable' => 'Een van de servers is opgeschort, wordt geïnstalleerd of hersteld.',
        'wings_too_old' => 'Wings op deze node is te oud om te kopiëren (vereist :version of nieuwer).',
    ],
];
