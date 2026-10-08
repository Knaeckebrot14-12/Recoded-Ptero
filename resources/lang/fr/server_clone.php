<?php

return [
    'title' => 'Copier ce serveur',
    'description' => 'Copie tous les fichiers de ce serveur dans un autre de vos serveurs du même type. Ses fichiers actuels vont dans sa corbeille (restaurables pendant :hours heures) s\'il y a assez de place, sinon ils sont supprimés. Les deux serveurs doivent être arrêtés.',
    'target_label' => 'Copier vers',
    'choose' => 'Choisir un serveur',
    'start' => 'Copier les fichiers',
    'confirm_title' => 'Remplacer les fichiers de :target ?',
    'confirm_body' => 'Tous les fichiers de :target sont remplacés par une copie des fichiers de ce serveur. Cela peut prendre quelques minutes.',
    'running' => 'Copie en cours… :files fichiers jusqu\'ici.',
    'done' => 'Terminé : :files fichiers copiés vers :target.',
    'done_kept' => 'Ses fichiers précédents sont dans sa corbeille.',
    'done_deleted' => 'Ses fichiers précédents ont été supprimés (pas assez de place pour les garder).',
    'failed' => 'La copie a échoué : :error',
    'unsupported' => 'Wings sur ce node est trop ancien pour copier (nécessite :version ou plus récent).',
    'reasons' => [
        'other_node' => 'sur un autre node',
        'no_permission' => 'aucune autorisation',
        'unavailable' => 'indisponible pour le moment',
    ],
    'errors' => [
        'no_permission' => 'Vous ne pouvez pas lire les fichiers de ce serveur.',
        'not_allowed' => 'Ce serveur ne peut pas être la cible.',
        'other_node' => 'La copie n\'est possible que vers des serveurs du même node.',
        'unavailable' => 'L\'un des serveurs est suspendu, en installation ou en restauration.',
        'wings_too_old' => 'Wings sur ce node est trop ancien pour copier (nécessite :version ou plus récent).',
    ],
];
