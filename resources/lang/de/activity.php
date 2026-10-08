<?php

/**
 * Enthält alle Übersetzungstexte für die verschiedenen Activity-Log-Ereignisse.
 * Diese sollten nach dem Wert vor dem Doppelpunkt (:) im Ereignisnamen
 * verschachtelt sein. Ist kein Doppelpunkt vorhanden, stehen sie auf der
 * obersten Ebene.
 */
return [
    'auth' => [
        'fail' => 'Anmeldung fehlgeschlagen',
        'success' => 'Angemeldet',
        'password-reset' => 'Passwort zurückgesetzt',
        'reset-password' => 'Zurücksetzen des Passworts angefordert',
        'checkpoint' => 'Zwei-Faktor-Authentifizierung angefordert',
        'recovery-token' => 'Zwei-Faktor-Wiederherstellungscode verwendet',
        'token' => 'Zwei-Faktor-Abfrage gelöst',
        'ip-blocked' => 'Anfrage von nicht gelisteter IP-Adresse für :identifier blockiert',
        'sftp' => [
            'fail' => 'SFTP-Anmeldung fehlgeschlagen',
        ],
        'discord' => [
            'login' => 'Mit Discord angemeldet',
        ],
        'reset-password-requested' => 'Link zum Zurücksetzen des Passworts angefordert',
        'passkey' => [
            'add' => 'Passkey :name hinzugefügt',
            'remove' => 'Passkey :name entfernt',
            'login' => 'Mit Passkey :name angemeldet',
        ],
    ],
    'user' => [
        'user' => [
            'create' => 'Neuen Benutzer :email erstellt',
        ],
        'account' => [
            'email-changed' => 'E-Mail von :old zu :new geändert',
            'password-changed' => 'Passwort geändert',
            'discord-linked' => 'Discord-Account verknüpft',
            'discord-unlinked' => 'Discord getrennt',
        ],
        'api-key' => [
            'create' => 'Neuen API-Schlüssel :identifier erstellt',
            'delete' => 'API-Schlüssel :identifier gelöscht',
        ],
        'ssh-key' => [
            'create' => 'SSH-Schlüssel :fingerprint zum Konto hinzugefügt',
            'delete' => 'SSH-Schlüssel :fingerprint vom Konto entfernt',
        ],
        'two-factor' => [
            'create' => 'Zwei-Faktor-Authentifizierung aktiviert',
            'delete' => 'Zwei-Faktor-Authentifizierung deaktiviert',
        ],
    ],
    'server' => [
        'reinstall' => 'Server neu installiert',
        'console' => [
            'command' => '":command" auf dem Server ausgeführt',
        ],
        'power' => [
            'start' => 'Server gestartet',
            'stop' => 'Server gestoppt',
            'restart' => 'Server neu gestartet',
            'kill' => 'Serverprozess abgebrochen',
        ],
        'backup' => [
            'download' => 'Backup :name heruntergeladen',
            'delete' => 'Backup :name gelöscht',
            'restore' => 'Backup :name wiederhergestellt (gelöschte Dateien: :truncate)',
            'restore-complete' => 'Wiederherstellung des Backups :name abgeschlossen',
            'restore-failed' => 'Wiederherstellung des Backups :name fehlgeschlagen',
            'start' => 'Neues Backup :name gestartet',
            'complete' => 'Backup :name als abgeschlossen markiert',
            'fail' => 'Backup :name als fehlgeschlagen markiert',
            'lock' => 'Backup :name gesperrt',
            'unlock' => 'Backup :name entsperrt',
            'auto' => 'Automatische Backups auf alle :hours Stunden gestellt (0 = aus)',
        ],
        'database' => [
            'create' => 'Neue Datenbank :name erstellt',
            'rotate-password' => 'Passwort für Datenbank :name erneuert',
            'delete' => 'Datenbank :name gelöscht',
            'open-manager' => 'Datenbank :name in phpMyAdmin geöffnet',
        ],
        'file' => [
            'compress_one' => ':directory:files.0 komprimiert',
            'compress_other' => ':count Dateien in :directory komprimiert',
            'read' => 'Inhalt von :file angesehen',
            'copy' => 'Kopie von :file erstellt',
            'create-directory' => 'Verzeichnis :directory:name erstellt',
            'decompress' => ':files in :directory entpackt',
            'delete_one' => ':directory:files.0 gelöscht',
            'delete_other' => ':count Dateien in :directory gelöscht',
            'download' => ':file heruntergeladen',
            'pull' => 'Entfernte Datei von :url nach :directory heruntergeladen',
            'rename_one' => ':directory:files.0.from in :directory:files.0.to umbenannt',
            'rename_other' => ':count Dateien in :directory umbenannt',
            'write' => 'Neuen Inhalt in :file geschrieben',
            'upload' => 'Datei-Upload gestartet',
            'uploaded' => ':directory:file hochgeladen',
            'restore_one' => ':files.0 aus dem Papierkorb wiederhergestellt',
            'restore_other' => ':count Dateien aus dem Papierkorb wiederhergestellt',
            'trash' => [
                'purge' => ':count Einträge endgültig aus dem Papierkorb gelöscht',
            ],
        ],
        'sftp' => [
            'denied' => 'SFTP-Zugriff aufgrund fehlender Berechtigungen blockiert',
            'create_one' => ':files.0 erstellt',
            'create_other' => ':count neue Dateien erstellt',
            'write_one' => 'Inhalt von :files.0 geändert',
            'write_other' => 'Inhalt von :count Dateien geändert',
            'delete_one' => ':files.0 gelöscht',
            'delete_other' => ':count Dateien gelöscht',
            'create-directory_one' => 'Verzeichnis :files.0 erstellt',
            'create-directory_other' => ':count Verzeichnisse erstellt',
            'rename_one' => ':files.0.from in :files.0.to umbenannt',
            'rename_other' => ':count Dateien umbenannt oder verschoben',
        ],
        'allocation' => [
            'create' => ':allocation zum Server hinzugefügt',
            'notes' => 'Notizen für :allocation von ":old" zu ":new" geändert',
            'primary' => ':allocation als primäre Server-Allokation festgelegt',
            'delete' => 'Allokation :allocation gelöscht',
        ],
        'schedule' => [
            'create' => 'Zeitplan :name erstellt',
            'update' => 'Zeitplan :name aktualisiert',
            'execute' => 'Zeitplan :name manuell ausgeführt',
            'delete' => 'Zeitplan :name gelöscht',
        ],
        'task' => [
            'create' => 'Neue Aufgabe ":action" für Zeitplan :name erstellt',
            'update' => 'Aufgabe ":action" für Zeitplan :name aktualisiert',
            'delete' => 'Aufgabe für Zeitplan :name gelöscht',
        ],
        'settings' => [
            'rename' => 'Server von :old zu :new umbenannt',
            'description' => 'Serverbeschreibung von :old zu :new geändert',
        ],
        'startup' => [
            'edit' => 'Variable :variable von ":old" zu ":new" geändert',
            'image' => 'Docker-Image des Servers von :old zu :new aktualisiert',
        ],
        'subuser' => [
            'create' => ':email als Unterbenutzer hinzugefügt',
            'update' => 'Unterbenutzer-Berechtigungen für :email aktualisiert',
            'delete' => ':email als Unterbenutzer entfernt',
        ],
        'players' => [
            'whitelist_add' => ':target zur Whitelist hinzugefügt',
            'whitelist_remove' => ':target von der Whitelist entfernt',
            'op' => ':target zum Operator gemacht',
            'deop' => 'Operator :target entfernt',
            'ban' => ':target gebannt',
            'pardon' => ':target entbannt',
            'ban_ip' => 'IP :target gebannt',
            'pardon_ip' => 'IP :target entbannt',
            'kick' => ':target gekickt',
            'whitelist_on' => 'Whitelist eingeschaltet',
            'whitelist_off' => 'Whitelist ausgeschaltet',
        ],
        'clone' => 'Alle Dateien von :source nach :target kopiert',
        'sleep' => [
            'update' => 'Einstellungen des Schlafmodus geändert',
        ],
        'crashed' => 'Der Server ist abgestürzt',
        'software' => [
            'install' => ':type :version installiert',
        ],
        'ownership' => [
            'offer' => 'Server :to angeboten',
            'cancel' => 'Angebot zur Server-Übergabe zurückgezogen',
            'accept' => 'Server von :from an :to übergeben',
            'decline' => ':to hat den Server abgelehnt',
        ],
        'geyser' => [
            'install' => 'Geyser und Floodgate installiert (Bedrock-Spieler)',
            'uninstall' => 'Geyser und Floodgate entfernt',
        ],
        'subdomain' => [
            'set' => 'Subdomain :subdomain eingerichtet',
            'delete' => 'Subdomain entfernt',
        ],
    ],
    'meta' => [
        'system_user' => 'Systembenutzer',
        'system' => 'System',
        'using_api_key' => 'Verwendet API-Schlüssel',
        'using_sftp' => 'Verwendet SFTP',
        'clear_filters' => 'Filter zurücksetzen',
        'server_title' => 'Aktivitätsprotokoll',
        'server_empty' => 'Für diesen Server sind keine Aktivitätsprotokolle verfügbar.',
        'account_title' => 'Kontoaktivitätsprotokoll',
    ],
];
