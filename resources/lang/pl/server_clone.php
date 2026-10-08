<?php

return [
    'title' => 'Skopiuj ten serwer',
    'description' => 'Kopiuje wszystkie pliki tego serwera do innego z twoich serwerów tego samego typu. Jego obecne pliki trafiają do jego kosza (do przywrócenia przez :hours godz.), jeśli jest dość miejsca, w przeciwnym razie są usuwane. Oba serwery muszą być zatrzymane.',
    'target_label' => 'Kopiuj do',
    'choose' => 'Wybierz serwer',
    'start' => 'Kopiuj pliki',
    'confirm_title' => 'Zastąpić pliki serwera :target?',
    'confirm_body' => 'Wszystkie pliki serwera :target zostaną zastąpione kopią plików tego serwera. Może to potrwać kilka minut.',
    'running' => 'Kopiowanie… dotąd :files plików.',
    'done' => 'Gotowe: skopiowano :files plików do :target.',
    'done_kept' => 'Jego poprzednie pliki są w jego koszu.',
    'done_deleted' => 'Jego poprzednie pliki zostały usunięte (za mało miejsca, by je zachować).',
    'failed' => 'Kopiowanie nie powiodło się: :error',
    'unsupported' => 'Wings na tym węźle jest za stary do kopiowania (wymaga :version lub nowszego).',
    'reasons' => [
        'other_node' => 'na innym węźle',
        'no_permission' => 'brak uprawnień',
        'unavailable' => 'obecnie niedostępny',
    ],
    'errors' => [
        'no_permission' => 'Nie możesz czytać plików tego serwera.',
        'not_allowed' => 'Ten serwer nie może być celem.',
        'other_node' => 'Kopiować można tylko do serwerów na tym samym węźle.',
        'unavailable' => 'Jeden z serwerów jest zawieszony, instalowany lub przywracany.',
        'wings_too_old' => 'Wings na tym węźle jest za stary do kopiowania (wymaga :version lub nowszego).',
    ],
];
