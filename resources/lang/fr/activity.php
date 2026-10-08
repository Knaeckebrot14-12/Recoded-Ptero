<?php

/**
 * Contains all of the translation strings for different activity log
 * events. These should be keyed by the value in front of the colon (:)
 * in the event name. If there is no colon present, they should live at
 * the top level.
 */
return [
    'auth' => [
        'fail' => 'Échec de connexion',
        'success' => 'Connecté',
        'password-reset' => 'Mot de passe réinitialisé',
        'reset-password' => 'Réinitialisation du mot de passe demandée',
        'checkpoint' => 'Authentification à deux facteurs demandée',
        'recovery-token' => 'Jeton de récupération à deux facteurs utilisé',
        'token' => 'Défi à deux facteurs résolu',
        'ip-blocked' => 'Requête bloquée depuis une adresse IP non autorisée pour :identifier',
        'sftp' => [
            'fail' => 'Échec de connexion SFTP',
        ],
        'discord' => [
            'login' => 'Connecté avec Discord',
        ],
        'reset-password-requested' => 'A demandé un lien de réinitialisation du mot de passe',
        'passkey' => [
            'add' => 'Clé d\'accès :name ajoutée',
            'remove' => 'Clé d\'accès :name supprimée',
            'login' => 'Connecté avec la clé d\'accès :name',
        ],
    ],
    'user' => [
        'user' => [
            'create' => 'Nouvel utilisateur :email créé',
        ],
        'account' => [
            'email-changed' => 'Adresse e-mail changée de :old à :new',
            'password-changed' => 'Mot de passe modifié',
            'discord-linked' => 'Compte Discord lié',
            'discord-unlinked' => 'Discord dissocié',
        ],
        'api-key' => [
            'create' => 'Nouvelle clé API :identifier créée',
            'delete' => 'Clé API :identifier supprimée',
        ],
        'ssh-key' => [
            'create' => 'Clé SSH :fingerprint ajoutée au compte',
            'delete' => 'Clé SSH :fingerprint retirée du compte',
        ],
        'two-factor' => [
            'create' => 'Authentification à deux facteurs activée',
            'delete' => 'Authentification à deux facteurs désactivée',
        ],
    ],
    'server' => [
        'reinstall' => 'Serveur réinstallé',
        'console' => [
            'command' => '« :command » exécuté sur le serveur',
        ],
        'power' => [
            'start' => 'Serveur démarré',
            'stop' => 'Serveur arrêté',
            'restart' => 'Serveur redémarré',
            'kill' => 'Processus du serveur tué',
        ],
        'backup' => [
            'download' => 'Sauvegarde :name téléchargée',
            'delete' => 'Sauvegarde :name supprimée',
            'restore' => 'Sauvegarde :name restaurée (fichiers supprimés : :truncate)',
            'restore-complete' => 'Restauration de la sauvegarde :name terminée',
            'restore-failed' => 'Échec de la restauration de la sauvegarde :name',
            'start' => 'Nouvelle sauvegarde :name démarrée',
            'complete' => 'Sauvegarde :name marquée comme terminée',
            'fail' => 'Sauvegarde :name marquée comme échouée',
            'lock' => 'Sauvegarde :name verrouillée',
            'unlock' => 'Sauvegarde :name déverrouillée',
            'auto' => 'Sauvegardes automatiques réglées toutes les :hours heures (0 = désactivées)',
        ],
        'database' => [
            'create' => 'Nouvelle base de données :name créée',
            'rotate-password' => 'Mot de passe renouvelé pour la base de données :name',
            'delete' => 'Base de données :name supprimée',
            'open-manager' => 'Base de données :name ouverte dans phpMyAdmin',
        ],
        'file' => [
            'compress_one' => ':directory:files.0 compressé',
            'compress_other' => ':count fichiers compressés dans :directory',
            'read' => 'Contenu de :file consulté',
            'copy' => 'Copie de :file créée',
            'create-directory' => 'Répertoire :directory:name créé',
            'decompress' => ':files décompressés dans :directory',
            'delete_one' => ':directory:files.0 supprimé',
            'delete_other' => ':count fichiers supprimés dans :directory',
            'download' => ':file téléchargé',
            'pull' => 'Fichier distant téléchargé depuis :url vers :directory',
            'rename_one' => ':directory:files.0.from renommé en :directory:files.0.to',
            'rename_other' => ':count fichiers renommés dans :directory',
            'write' => 'Nouveau contenu écrit dans :file',
            'upload' => 'Début d\'un envoi de fichier',
            'uploaded' => ':directory:file envoyé',
            'restore_one' => ':files.0 restauré depuis la corbeille',
            'restore_other' => ':count fichiers restaurés depuis la corbeille',
            'trash' => [
                'purge' => ':count éléments supprimés définitivement de la corbeille',
            ],
        ],
        'sftp' => [
            'denied' => 'Accès SFTP bloqué en raison des permissions',
            'create_one' => ':files.0 créé',
            'create_other' => ':count nouveaux fichiers créés',
            'write_one' => 'Contenu de :files.0 modifié',
            'write_other' => 'Contenu de :count fichiers modifié',
            'delete_one' => ':files.0 supprimé',
            'delete_other' => ':count fichiers supprimés',
            'create-directory_one' => 'Répertoire :files.0 créé',
            'create-directory_other' => ':count répertoires créés',
            'rename_one' => ':files.0.from renommé en :files.0.to',
            'rename_other' => ':count fichiers renommés ou déplacés',
        ],
        'allocation' => [
            'create' => ':allocation ajoutée au serveur',
            'notes' => 'Notes de :allocation modifiées de « :old » à « :new »',
            'primary' => ':allocation définie comme allocation principale du serveur',
            'delete' => 'Allocation :allocation supprimée',
        ],
        'schedule' => [
            'create' => 'Planification :name créée',
            'update' => 'Planification :name mise à jour',
            'execute' => 'Planification :name exécutée manuellement',
            'delete' => 'Planification :name supprimée',
        ],
        'task' => [
            'create' => 'Nouvelle tâche « :action » créée pour la planification :name',
            'update' => 'Tâche « :action » mise à jour pour la planification :name',
            'delete' => 'Tâche supprimée pour la planification :name',
        ],
        'settings' => [
            'rename' => 'Serveur renommé de :old en :new',
            'description' => 'Description du serveur modifiée de :old à :new',
        ],
        'startup' => [
            'edit' => 'Variable :variable modifiée de « :old » à « :new »',
            'image' => 'Image Docker du serveur mise à jour de :old à :new',
        ],
        'subuser' => [
            'create' => ':email ajouté comme sous-utilisateur',
            'update' => 'Permissions du sous-utilisateur :email mises à jour',
            'delete' => ':email retiré des sous-utilisateurs',
        ],
        'players' => [
            'whitelist_add' => ':target ajouté à la liste blanche',
            'whitelist_remove' => ':target retiré de la liste blanche',
            'op' => ':target rendu opérateur',
            'deop' => 'Opérateur :target retiré',
            'ban' => ':target banni',
            'pardon' => ':target débanni',
            'ban_ip' => 'IP :target bannie',
            'pardon_ip' => 'IP :target débannie',
            'kick' => ':target expulsé',
            'whitelist_on' => 'Liste blanche activée',
            'whitelist_off' => 'Liste blanche désactivée',
        ],
        'clone' => 'Tous les fichiers de :source copiés vers :target',
        'sleep' => [
            'update' => 'Paramètres du mode veille modifiés',
        ],
        'crashed' => 'Le serveur a planté',
        'software' => [
            'install' => ':type :version installé',
        ],
        'ownership' => [
            'offer' => 'Serveur proposé à :to',
            'cancel' => 'Offre de don du serveur retirée',
            'accept' => 'Serveur transmis de :from à :to',
            'decline' => ':to a refusé le serveur',
        ],
        'geyser' => [
            'install' => 'Geyser et Floodgate installés (joueurs Bedrock)',
            'uninstall' => 'Geyser et Floodgate supprimés',
        ],
        'subdomain' => [
            'set' => 'Sous-domaine :subdomain défini',
            'delete' => 'Sous-domaine supprimé',
        ],
    ],
    'meta' => [
        'system_user' => 'Utilisateur système',
        'system' => 'Système',
        'using_api_key' => 'Via une clé API',
        'using_sftp' => 'Via SFTP',
        'clear_filters' => 'Effacer les filtres',
        'server_title' => 'Journal d\'activité',
        'server_empty' => 'Aucun journal d\'activité disponible pour ce serveur.',
        'account_title' => 'Journal d\'activité du compte',
    ],
];
