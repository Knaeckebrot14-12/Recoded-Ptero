<?php

return [
    'title' => 'Diesen Server kopieren',
    'description' => 'Kopiert alle Dateien dieses Servers in einen anderen deiner Server vom gleichen Typ. Dessen bisherige Dateien kommen in seinen Papierkorb (:hours Stunden wiederherstellbar), wenn genug Platz ist, sonst werden sie gelöscht. Beide Server müssen gestoppt sein.',
    'target_label' => 'Kopieren nach',
    'choose' => 'Server auswählen',
    'start' => 'Dateien kopieren',
    'confirm_title' => 'Dateien von :target ersetzen?',
    'confirm_body' => 'Alle Dateien von :target werden durch eine Kopie der Dateien dieses Servers ersetzt. Das kann ein paar Minuten dauern.',
    'running' => 'Kopiere… bisher :files Dateien.',
    'done' => 'Fertig: :files Dateien wurden nach :target kopiert.',
    'done_kept' => 'Die bisherigen Dateien liegen in seinem Papierkorb.',
    'done_deleted' => 'Die bisherigen Dateien wurden gelöscht (nicht genug Platz, um sie aufzubewahren).',
    'failed' => 'Das Kopieren ist fehlgeschlagen: :error',
    'unsupported' => 'Wings auf diesem Node ist zu alt zum Kopieren (benötigt :version oder neuer).',
    'reasons' => [
        'other_node' => 'auf einem anderen Node',
        'no_permission' => 'keine Berechtigung',
        'unavailable' => 'gerade nicht verfügbar',
    ],
    'errors' => [
        'no_permission' => 'Du darfst die Dateien dieses Servers nicht lesen.',
        'not_allowed' => 'Dieser Server kann nicht das Ziel sein.',
        'other_node' => 'Kopieren geht nur in Server auf demselben Node.',
        'unavailable' => 'Einer der Server ist gesperrt, wird installiert oder wiederhergestellt.',
        'wings_too_old' => 'Wings auf diesem Node ist zu alt zum Kopieren (benötigt :version oder neuer).',
    ],
];
