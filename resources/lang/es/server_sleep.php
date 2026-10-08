<?php

return [
    'title' => 'Suspender cuando esté vacío',
    'description' => 'Cuando nadie ha estado en este servidor durante un tiempo, se detiene para liberar su memoria. Sigue apareciendo en la lista de servidores y el primer jugador que se une lo vuelve a iniciar (se le pide que se reconecte en un momento). Funciona con servidores de Minecraft Java.',
    'enabled_label' => 'Suspender cuando esté vacío',
    'minutes_label' => 'Detener cuando nadie haya estado conectado durante',
    'minutes' => ':count minutos',
    'default_note' => 'Se aplica el valor predeterminado del panel: :state, :minutes minutos.',
    'state_on' => 'activado',
    'state_off' => 'desactivado',
    'reset' => 'Usar el valor predeterminado del panel',
    'saved' => 'Ajustes del modo suspensión guardados.',
    'unsupported' => 'La versión de Wings de este nodo aún no conoce el modo suspensión, así que el ajuste no tendrá efecto hasta que se actualice Wings.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eEste servidor está durmiendo §7- únete para despertarlo',
        'starting' => '§eEl servidor estaba durmiendo y arranca ahora. §7Vuelve a conectarte en aproximadamente un minuto.',
    ],
];
