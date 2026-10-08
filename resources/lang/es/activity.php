<?php

/**
 * Contains all of the translation strings for different activity log
 * events. These should be keyed by the value in front of the colon (:)
 * in the event name. If there is no colon present, they should live at
 * the top level.
 */
return [
    'auth' => [
        'fail' => 'Inicio de sesión fallido',
        'success' => 'Sesión iniciada',
        'password-reset' => 'Contraseña restablecida',
        'reset-password' => 'Restablecimiento de contraseña solicitado',
        'checkpoint' => 'Autenticación de dos factores solicitada',
        'recovery-token' => 'Token de recuperación de dos factores utilizado',
        'token' => 'Desafío de dos factores resuelto',
        'ip-blocked' => 'Solicitud bloqueada desde una dirección IP no incluida en la lista para :identifier',
        'sftp' => [
            'fail' => 'Inicio de sesión SFTP fallido',
        ],
        'discord' => [
            'login' => 'Inició sesión con Discord',
        ],
        'reset-password-requested' => 'Solicitó un enlace para restablecer la contraseña',
        'passkey' => [
            'add' => 'Añadió la passkey :name',
            'remove' => 'Eliminó la passkey :name',
            'login' => 'Inició sesión con la passkey :name',
        ],
    ],
    'user' => [
        'user' => [
            'create' => 'Creó un nuevo usuario :email',
        ],
        'account' => [
            'email-changed' => 'Cambió el correo de :old a :new',
            'password-changed' => 'Cambió la contraseña',
            'discord-linked' => 'Vinculó una cuenta de Discord',
            'discord-unlinked' => 'Desvinculó Discord',
        ],
        'api-key' => [
            'create' => 'Creó la nueva clave de API :identifier',
            'delete' => 'Eliminó la clave de API :identifier',
        ],
        'ssh-key' => [
            'create' => 'Añadió la clave SSH :fingerprint a la cuenta',
            'delete' => 'Eliminó la clave SSH :fingerprint de la cuenta',
        ],
        'two-factor' => [
            'create' => 'Activó la autenticación de dos factores',
            'delete' => 'Desactivó la autenticación de dos factores',
        ],
    ],
    'server' => [
        'reinstall' => 'Reinstaló el servidor',
        'console' => [
            'command' => 'Ejecutó «:command» en el servidor',
        ],
        'power' => [
            'start' => 'Inició el servidor',
            'stop' => 'Detuvo el servidor',
            'restart' => 'Reinició el servidor',
            'kill' => 'Mató el proceso del servidor',
        ],
        'backup' => [
            'download' => 'Descargó la copia de seguridad :name',
            'delete' => 'Eliminó la copia de seguridad :name',
            'restore' => 'Restauró la copia de seguridad :name (archivos eliminados: :truncate)',
            'restore-complete' => 'Completó la restauración de la copia de seguridad :name',
            'restore-failed' => 'No se pudo completar la restauración de la copia de seguridad :name',
            'start' => 'Inició una nueva copia de seguridad :name',
            'complete' => 'Marcó la copia de seguridad :name como completada',
            'fail' => 'Marcó la copia de seguridad :name como fallida',
            'lock' => 'Bloqueó la copia de seguridad :name',
            'unlock' => 'Desbloqueó la copia de seguridad :name',
            'auto' => 'Configuró copias automáticas cada :hours horas (0 = desactivadas)',
        ],
        'database' => [
            'create' => 'Creó la nueva base de datos :name',
            'rotate-password' => 'Rotó la contraseña de la base de datos :name',
            'delete' => 'Eliminó la base de datos :name',
            'open-manager' => 'Abrió la base de datos :name en phpMyAdmin',
        ],
        'file' => [
            'compress_one' => 'Comprimió :directory:files.0',
            'compress_other' => 'Comprimió :count archivos en :directory',
            'read' => 'Vio el contenido de :file',
            'copy' => 'Creó una copia de :file',
            'create-directory' => 'Creó el directorio :directory:name',
            'decompress' => 'Descomprimió :files en :directory',
            'delete_one' => 'Eliminó :directory:files.0',
            'delete_other' => 'Eliminó :count archivos en :directory',
            'download' => 'Descargó :file',
            'pull' => 'Descargó un archivo remoto de :url a :directory',
            'rename_one' => 'Renombró :directory:files.0.from a :directory:files.0.to',
            'rename_other' => 'Renombró :count archivos en :directory',
            'write' => 'Escribió nuevo contenido en :file',
            'upload' => 'Inició una subida de archivo',
            'uploaded' => 'Subió :directory:file',
            'restore_one' => ':files.0 restaurado desde la papelera',
            'restore_other' => ':count archivos restaurados desde la papelera',
            'trash' => [
                'purge' => ':count elementos eliminados definitivamente de la papelera',
            ],
        ],
        'sftp' => [
            'denied' => 'Bloqueó el acceso SFTP por permisos',
            'create_one' => 'Creó :files.0',
            'create_other' => 'Creó :count archivos nuevos',
            'write_one' => 'Modificó el contenido de :files.0',
            'write_other' => 'Modificó el contenido de :count archivos',
            'delete_one' => 'Eliminó :files.0',
            'delete_other' => 'Eliminó :count archivos',
            'create-directory_one' => 'Creó el directorio :files.0',
            'create-directory_other' => 'Creó :count directorios',
            'rename_one' => 'Renombró :files.0.from a :files.0.to',
            'rename_other' => 'Renombró o movió :count archivos',
        ],
        'allocation' => [
            'create' => 'Añadió :allocation al servidor',
            'notes' => 'Actualizó las notas de :allocation de «:old» a «:new»',
            'primary' => 'Estableció :allocation como la asignación principal del servidor',
            'delete' => 'Eliminó la asignación :allocation',
        ],
        'schedule' => [
            'create' => 'Creó la programación :name',
            'update' => 'Actualizó la programación :name',
            'execute' => 'Ejecutó manualmente la programación :name',
            'delete' => 'Eliminó la programación :name',
        ],
        'task' => [
            'create' => 'Creó una nueva tarea «:action» para la programación :name',
            'update' => 'Actualizó la tarea «:action» de la programación :name',
            'delete' => 'Eliminó una tarea de la programación :name',
        ],
        'settings' => [
            'rename' => 'Renombró el servidor de :old a :new',
            'description' => 'Cambió la descripción del servidor de :old a :new',
        ],
        'startup' => [
            'edit' => 'Cambió la variable :variable de «:old» a «:new»',
            'image' => 'Actualizó la imagen de Docker del servidor de :old a :new',
        ],
        'subuser' => [
            'create' => 'Añadió a :email como subusuario',
            'update' => 'Actualizó los permisos del subusuario :email',
            'delete' => 'Eliminó a :email como subusuario',
        ],
        'players' => [
            'whitelist_add' => 'Añadió a :target a la lista blanca',
            'whitelist_remove' => 'Quitó a :target de la lista blanca',
            'op' => 'Hizo operador a :target',
            'deop' => 'Quitó el operador a :target',
            'ban' => 'Baneó a :target',
            'pardon' => 'Desbaneó a :target',
            'ban_ip' => 'Baneó la IP :target',
            'pardon_ip' => 'Desbaneó la IP :target',
            'kick' => 'Expulsó a :target',
            'whitelist_on' => 'Activó la lista blanca',
            'whitelist_off' => 'Desactivó la lista blanca',
        ],
        'clone' => 'Todos los archivos de :source copiados a :target',
        'sleep' => [
            'update' => 'Ajustes del modo suspensión cambiados',
        ],
        'crashed' => 'El servidor se cayó',
        'software' => [
            'install' => 'Instaló :type :version',
        ],
        'ownership' => [
            'offer' => 'Servidor ofrecido a :to',
            'cancel' => 'Oferta de dar el servidor retirada',
            'accept' => 'Servidor entregado de :from a :to',
            'decline' => ':to rechazó el servidor',
        ],
        'geyser' => [
            'install' => 'Geyser y Floodgate instalados (jugadores Bedrock)',
            'uninstall' => 'Geyser y Floodgate quitados',
        ],
        'subdomain' => [
            'set' => 'Configuró el subdominio :subdomain',
            'delete' => 'Eliminó el subdominio',
        ],
    ],
    'meta' => [
        'system_user' => 'Usuario del sistema',
        'system' => 'Sistema',
        'using_api_key' => 'Usando clave de API',
        'using_sftp' => 'Usando SFTP',
        'clear_filters' => 'Borrar filtros',
        'server_title' => 'Registro de actividad',
        'server_empty' => 'No hay registros de actividad disponibles para este servidor.',
        'account_title' => 'Registro de actividad de la cuenta',
    ],
];
