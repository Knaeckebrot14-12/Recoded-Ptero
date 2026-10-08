<?php

return [
    'daemon_connection_failed' => 'Bei der Kommunikation mit dem Daemon ist ein Fehler aufgetreten, der zu einem HTTP/:code-Statuscode geführt hat. Dieser Fehler wurde protokolliert.',
    'node' => [
        'servers_attached' => 'Eine Node darf keine verknüpften Server haben, um gelöscht werden zu können.',
        'daemon_off_config_updated' => 'Die Daemon-Konfiguration wurde aktualisiert, allerdings ist beim automatischen Aktualisieren der Konfigurationsdatei auf dem Daemon ein Fehler aufgetreten. Du musst die Konfigurationsdatei (config.yml) des Daemons manuell aktualisieren, damit diese Änderungen wirksam werden.',
    ],
    'allocations' => [
        'server_using' => 'Dieser Allokation ist derzeit ein Server zugewiesen. Eine Allokation kann nur gelöscht werden, wenn ihr aktuell kein Server zugewiesen ist.',
        'too_many_ports' => 'Das Hinzufügen von mehr als 1000 Ports in einem einzelnen Bereich auf einmal wird nicht unterstützt.',
        'invalid_mapping' => 'Die für :port angegebene Zuordnung war ungültig und konnte nicht verarbeitet werden.',
        'cidr_out_of_range' => 'Die CIDR-Notation erlaubt nur Masken zwischen /25 und /32.',
        'port_out_of_range' => 'Ports in einer Allokation müssen größer als 1024 und kleiner oder gleich 65535 sein.',
    ],
    'nest' => [
        'delete_has_servers' => 'Ein Nest mit aktiven angehängten Servern kann nicht aus dem Panel gelöscht werden.',
        'egg' => [
            'delete_has_servers' => 'Ein Egg mit aktiven angehängten Servern kann nicht aus dem Panel gelöscht werden.',
            'invalid_copy_id' => 'Das für das Kopieren eines Skripts ausgewählte Egg existiert entweder nicht oder kopiert selbst ein Skript.',
            'must_be_child' => 'Die Anweisung "Einstellungen kopieren von" für dieses Egg muss eine Unteroption des ausgewählten Nests sein.',
            'has_children' => 'Dieses Egg ist übergeordnet für ein oder mehrere andere Eggs. Bitte lösche zuerst diese Eggs, bevor du dieses Egg löschst.',
        ],
        'variables' => [
            'env_not_unique' => 'Die Umgebungsvariable :name muss innerhalb dieses Eggs eindeutig sein.',
            'reserved_name' => 'Die Umgebungsvariable :name ist geschützt und kann keiner Variable zugewiesen werden.',
            'bad_validation_rule' => 'Die Validierungsregel ":rule" ist für diese Anwendung keine gültige Regel.',
        ],
        'importer' => [
            'json_error' => 'Beim Verarbeiten der JSON-Datei ist ein Fehler aufgetreten: :error.',
            'file_error' => 'Die angegebene JSON-Datei war ungültig.',
            'invalid_json_provided' => 'Die angegebene JSON-Datei liegt nicht in einem erkennbaren Format vor.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Das Bearbeiten deines eigenen Unterbenutzer-Kontos ist nicht erlaubt.',
        'user_is_owner' => 'Du kannst den Server-Besitzer nicht als Unterbenutzer für diesen Server hinzufügen.',
        'subuser_exists' => 'Ein Benutzer mit dieser E-Mail-Adresse ist diesem Server bereits als Unterbenutzer zugewiesen.',
    ],
    'databases' => [
        'delete_has_databases' => 'Ein Datenbank-Host-Server mit aktiven verknüpften Datenbanken kann nicht gelöscht werden.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'Das maximale Intervall für eine verkettete Aufgabe beträgt 15 Minuten.',
    ],
    'locations' => [
        'has_nodes' => 'Ein Standort mit aktiven angehängten Nodes kann nicht gelöscht werden.',
    ],
    'users' => [
        'node_revocation_failed' => 'Schlüssel auf <a href=":link">Node #:node</a> konnten nicht widerrufen werden. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Es konnten keine Nodes gefunden werden, die die Anforderungen für die automatische Bereitstellung erfüllen.',
        'no_viable_allocations' => 'Es konnten keine Allokationen gefunden werden, die die Anforderungen für die automatische Bereitstellung erfüllen.',
        'no_placement' => 'Derzeit kann kein Node diesen Server aufnehmen (genug freier Arbeitsspeicher und Speicherplatz, ein freier Port, online und nicht in Wartung). Es wurde nichts erstellt.',
    ],
    'api' => [
        'resource_not_found' => 'Die angeforderte Ressource existiert auf diesem Server nicht.',
    ],
    'server' => [
        'cpu_above_node' => 'Ein Server kann auf dem Node :node höchstens :max% CPU bekommen (:threads CPU-Threads, je 100%). :cpu% ist mehr, als der Node hat.',
        'memory_above_node' => 'Ein Server kann auf dem Node :node höchstens :max MiB Arbeitsspeicher bekommen. :memory MiB ist mehr, als der Node hat.',
        'disk_above_node' => 'Ein Server kann auf dem Node :node höchstens :max MiB Speicherplatz bekommen. :disk MiB ist mehr, als der Node hat.',
    ],
];
