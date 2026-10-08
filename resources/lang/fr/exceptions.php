<?php

return [
    'daemon_connection_failed' => 'Une exception s\'est produite lors de la communication avec le daemon, avec un code de réponse HTTP/:code. Cette exception a été consignée.',
    'node' => [
        'servers_attached' => 'Un node ne doit avoir aucun serveur associé pour pouvoir être supprimé.',
        'daemon_off_config_updated' => 'La configuration du daemon a été mise à jour, mais une erreur est survenue lors de la mise à jour automatique du fichier de configuration sur le daemon. Vous devrez mettre à jour manuellement le fichier de configuration (config.yml) du daemon pour appliquer ces modifications.',
    ],
    'allocations' => [
        'server_using' => 'Un serveur est actuellement assigné à cette allocation. Une allocation ne peut être supprimée que si aucun serveur ne lui est assigné.',
        'too_many_ports' => 'Ajouter plus de 1000 ports dans une seule plage à la fois n\'est pas pris en charge.',
        'invalid_mapping' => 'Le mappage fourni pour :port n\'était pas valide et n\'a pas pu être traité.',
        'cidr_out_of_range' => 'La notation CIDR n\'autorise que des masques compris entre /25 et /32.',
        'port_out_of_range' => 'Les ports d\'une allocation doivent être supérieurs à 1024 et inférieurs ou égaux à 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Un Nest auquel sont rattachés des serveurs actifs ne peut pas être supprimé du panel.',
        'egg' => [
            'delete_has_servers' => 'Un Egg auquel sont rattachés des serveurs actifs ne peut pas être supprimé du panel.',
            'invalid_copy_id' => 'L\'Egg sélectionné pour copier un script n\'existe pas ou copie lui-même un script.',
            'must_be_child' => 'La directive « Copier les paramètres depuis » de cet Egg doit être une option enfant du Nest sélectionné.',
            'has_children' => 'Cet Egg est le parent d\'un ou plusieurs autres Eggs. Veuillez supprimer ces Eggs avant de supprimer celui-ci.',
        ],
        'variables' => [
            'env_not_unique' => 'La variable d\'environnement :name doit être unique pour cet Egg.',
            'reserved_name' => 'La variable d\'environnement :name est protégée et ne peut pas être assignée à une variable.',
            'bad_validation_rule' => 'La règle de validation « :rule » n\'est pas une règle valide pour cette application.',
        ],
        'importer' => [
            'json_error' => 'Une erreur s\'est produite lors de l\'analyse du fichier JSON : :error.',
            'file_error' => 'Le fichier JSON fourni n\'était pas valide.',
            'invalid_json_provided' => 'Le fichier JSON fourni n\'est pas dans un format reconnaissable.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'La modification de votre propre compte de sous-utilisateur n\'est pas autorisée.',
        'user_is_owner' => 'Vous ne pouvez pas ajouter le propriétaire du serveur comme sous-utilisateur de ce serveur.',
        'subuser_exists' => 'Un utilisateur avec cette adresse e-mail est déjà assigné comme sous-utilisateur de ce serveur.',
    ],
    'databases' => [
        'delete_has_databases' => 'Impossible de supprimer un serveur hôte de bases de données auquel des bases de données actives sont liées.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'L\'intervalle maximal d\'une tâche chaînée est de 15 minutes.',
    ],
    'locations' => [
        'has_nodes' => 'Impossible de supprimer un emplacement auquel des nodes actifs sont rattachés.',
    ],
    'users' => [
        'node_revocation_failed' => 'Échec de la révocation des clés sur <a href=":link">Node #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Aucun node répondant aux exigences spécifiées pour le déploiement automatique n\'a été trouvé.',
        'no_viable_allocations' => 'Aucune allocation répondant aux exigences du déploiement automatique n\'a été trouvée.',
        'no_placement' => 'Aucun node ne peut accueillir ce serveur pour le moment (assez de mémoire et d\'espace disque libres, un port libre, en ligne et hors maintenance). Rien n\'a été créé.',
    ],
    'api' => [
        'resource_not_found' => 'La ressource demandée n\'existe pas sur ce serveur.',
    ],
    'server' => [
        'cpu_above_node' => 'Un serveur peut obtenir au maximum :max% de CPU sur le node :node (:threads threads CPU, 100% chacun). :cpu% dépasse ce que le node possède.',
        'memory_above_node' => 'Un serveur peut obtenir au maximum :max Mio de mémoire sur le node :node. :memory Mio dépasse ce que le node possède.',
        'disk_above_node' => 'Un serveur peut obtenir au maximum :max Mio d\'espace disque sur le node :node. :disk Mio dépasse ce que le node possède.',
    ],
];
