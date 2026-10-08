<?php

return [
    'daemon_connection_failed' => 'Er is een uitzondering opgetreden bij het communiceren met de daemon, met HTTP/:code als responscode. Deze uitzondering is vastgelegd.',
    'node' => [
        'servers_attached' => 'Een node mag geen gekoppelde servers hebben om verwijderd te kunnen worden.',
        'daemon_off_config_updated' => 'De daemonconfiguratie is bijgewerkt, maar er is een fout opgetreden bij het automatisch bijwerken van het configuratiebestand op de daemon. Je moet het configuratiebestand (config.yml) van de daemon handmatig bijwerken om deze wijzigingen toe te passen.',
    ],
    'allocations' => [
        'server_using' => 'Er is momenteel een server aan deze allocatie toegewezen. Een allocatie kan alleen worden verwijderd als er geen server aan is toegewezen.',
        'too_many_ports' => 'Meer dan 1000 poorten tegelijk in één bereik toevoegen wordt niet ondersteund.',
        'invalid_mapping' => 'De opgegeven toewijzing voor :port was ongeldig en kon niet worden verwerkt.',
        'cidr_out_of_range' => 'CIDR-notatie staat alleen maskers tussen /25 en /32 toe.',
        'port_out_of_range' => 'Poorten in een allocatie moeten groter zijn dan 1024 en kleiner dan of gelijk aan 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Een Nest met actieve servers kan niet uit het paneel worden verwijderd.',
        'egg' => [
            'delete_has_servers' => 'Een Egg met actieve servers kan niet uit het paneel worden verwijderd.',
            'invalid_copy_id' => 'Het Egg dat is geselecteerd om een script van te kopiëren bestaat niet of kopieert zelf een script.',
            'must_be_child' => 'De richtlijn "Instellingen kopiëren van" van dit Egg moet een onderliggende optie van het geselecteerde Nest zijn.',
            'has_children' => 'Dit Egg is de ouder van een of meer andere Eggs. Verwijder die Eggs voordat je dit Egg verwijdert.',
        ],
        'variables' => [
            'env_not_unique' => 'De omgevingsvariabele :name moet uniek zijn voor dit Egg.',
            'reserved_name' => 'De omgevingsvariabele :name is beschermd en kan niet aan een variabele worden toegewezen.',
            'bad_validation_rule' => 'De validatieregel ":rule" is geen geldige regel voor deze toepassing.',
        ],
        'importer' => [
            'json_error' => 'Er is een fout opgetreden bij het verwerken van het JSON-bestand: :error.',
            'file_error' => 'Het opgegeven JSON-bestand was ongeldig.',
            'invalid_json_provided' => 'Het opgegeven JSON-bestand heeft geen herkenbaar formaat.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Het bewerken van je eigen subgebruikersaccount is niet toegestaan.',
        'user_is_owner' => 'Je kunt de servereigenaar niet als subgebruiker van deze server toevoegen.',
        'subuser_exists' => 'Een gebruiker met dat e-mailadres is al als subgebruiker aan deze server toegewezen.',
    ],
    'databases' => [
        'delete_has_databases' => 'Een databasehostserver met gekoppelde actieve databases kan niet worden verwijderd.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'Het maximale interval voor een geketende taak is 15 minuten.',
    ],
    'locations' => [
        'has_nodes' => 'Een locatie met gekoppelde actieve nodes kan niet worden verwijderd.',
    ],
    'users' => [
        'node_revocation_failed' => 'Sleutels intrekken op <a href=":link">Node #:node</a> is mislukt. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Er is geen node gevonden die voldoet aan de opgegeven eisen voor automatische implementatie.',
        'no_viable_allocations' => 'Er is geen allocatie gevonden die voldoet aan de eisen voor automatische implementatie.',
        'no_placement' => 'Er is op dit moment geen node die deze server kan opnemen (genoeg vrij geheugen en schijfruimte, een vrije poort, online en niet in onderhoud). Er is niets aangemaakt.',
    ],
    'api' => [
        'resource_not_found' => 'De opgevraagde resource bestaat niet op deze server.',
    ],
    'server' => [
        'cpu_above_node' => 'Een server kan op node :node maximaal :max% CPU krijgen (:threads CPU-threads, elk 100%). :cpu% is meer dan de node heeft.',
        'memory_above_node' => 'Een server kan op node :node maximaal :max MiB geheugen krijgen. :memory MiB is meer dan de node heeft.',
        'disk_above_node' => 'Een server kan op node :node maximaal :max MiB schijfruimte krijgen. :disk MiB is meer dan de node heeft.',
    ],
];
