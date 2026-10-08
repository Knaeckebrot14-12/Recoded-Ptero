<?php

return [
    'daemon_connection_failed' => 'Wystąpił wyjątek podczas komunikacji z daemonem, skutkujący kodem odpowiedzi HTTP/:code. Wyjątek został zapisany w dzienniku.',
    'node' => [
        'servers_attached' => 'Aby usunąć węzeł, nie może on mieć powiązanych serwerów.',
        'daemon_off_config_updated' => 'Konfiguracja daemona została zaktualizowana, ale wystąpił błąd podczas automatycznej aktualizacji pliku konfiguracyjnego na daemonie. Musisz ręcznie zaktualizować plik konfiguracyjny daemona (config.yml), aby zastosować te zmiany.',
    ],
    'allocations' => [
        'server_using' => 'Do tej alokacji jest obecnie przypisany serwer. Alokację można usunąć tylko wtedy, gdy nie ma przypisanego serwera.',
        'too_many_ports' => 'Dodawanie więcej niż 1000 portów w jednym zakresie naraz nie jest obsługiwane.',
        'invalid_mapping' => 'Mapowanie podane dla :port było nieprawidłowe i nie mogło zostać przetworzone.',
        'cidr_out_of_range' => 'Notacja CIDR dopuszcza tylko maski od /25 do /32.',
        'port_out_of_range' => 'Porty w alokacji muszą być większe niż 1024 i nie większe niż 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Nest z powiązanymi aktywnymi serwerami nie może zostać usunięty z panelu.',
        'egg' => [
            'delete_has_servers' => 'Egg z powiązanymi aktywnymi serwerami nie może zostać usunięty z panelu.',
            'invalid_copy_id' => 'Egg wybrany do skopiowania skryptu nie istnieje lub sam kopiuje skrypt.',
            'must_be_child' => 'Dyrektywa „Kopiuj ustawienia z” dla tego Egg musi być opcją podrzędną wybranego Nest.',
            'has_children' => 'Ten Egg jest rodzicem jednego lub więcej innych Egg. Usuń te Egg przed usunięciem tego.',
        ],
        'variables' => [
            'env_not_unique' => 'Zmienna środowiskowa :name musi być unikalna dla tego Egg.',
            'reserved_name' => 'Zmienna środowiskowa :name jest chroniona i nie można jej przypisać do zmiennej.',
            'bad_validation_rule' => 'Reguła walidacji „:rule” nie jest prawidłową regułą dla tej aplikacji.',
        ],
        'importer' => [
            'json_error' => 'Wystąpił błąd podczas analizy pliku JSON: :error.',
            'file_error' => 'Podany plik JSON był nieprawidłowy.',
            'invalid_json_provided' => 'Podany plik JSON nie ma rozpoznawalnego formatu.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Edytowanie własnego konta podużytkownika jest niedozwolone.',
        'user_is_owner' => 'Nie możesz dodać właściciela serwera jako podużytkownika tego serwera.',
        'subuser_exists' => 'Użytkownik o tym adresie e-mail jest już przypisany jako podużytkownik tego serwera.',
    ],
    'databases' => [
        'delete_has_databases' => 'Nie można usunąć serwera hosta baz danych, który ma powiązane aktywne bazy danych.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'Maksymalny odstęp dla zadania łańcuchowego to 15 minut.',
    ],
    'locations' => [
        'has_nodes' => 'Nie można usunąć lokalizacji, która ma powiązane aktywne węzły.',
    ],
    'users' => [
        'node_revocation_failed' => 'Nie udało się unieważnić kluczy na <a href=":link">Węźle #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Nie znaleziono węzła spełniającego wymagania określone dla automatycznego wdrożenia.',
        'no_viable_allocations' => 'Nie znaleziono alokacji spełniających wymagania automatycznego wdrożenia.',
        'no_placement' => 'Żaden węzeł nie może teraz przyjąć tego serwera (wystarczająco wolnej pamięci i dysku, wolny port, online i bez trybu konserwacji). Nic nie zostało utworzone.',
    ],
    'api' => [
        'resource_not_found' => 'Żądany zasób nie istnieje na tym serwerze.',
    ],
    'server' => [
        'cpu_above_node' => 'Serwer może dostać maksymalnie :max% CPU na nodzie :node (:threads wątków CPU, po 100%). :cpu% to więcej, niż ma node.',
        'memory_above_node' => 'Serwer może dostać maksymalnie :max MiB pamięci na nodzie :node. :memory MiB to więcej, niż ma node.',
        'disk_above_node' => 'Serwer może dostać maksymalnie :max MiB miejsca na dysku na nodzie :node. :disk MiB to więcej, niż ma node.',
    ],
];
