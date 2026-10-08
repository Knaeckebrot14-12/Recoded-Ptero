<?php

return [
    'title' => 'Schlafen, wenn leer',
    'description' => 'Wenn eine Weile niemand auf diesem Server war, wird er gestoppt und gibt seinen Arbeitsspeicher frei. Er bleibt in der Serverliste, und der erste Spieler, der beitritt, startet ihn wieder (er wird gebeten, gleich noch einmal zu verbinden). Funktioniert mit Minecraft-Java-Servern.',
    'enabled_label' => 'Schlafen, wenn leer',
    'minutes_label' => 'Stoppen, wenn so lange niemand drauf war',
    'minutes' => ':count Minuten',
    'default_note' => 'Es gilt der Standard des Panels: :state, :minutes Minuten.',
    'state_on' => 'an',
    'state_off' => 'aus',
    'reset' => 'Standard des Panels verwenden',
    'saved' => 'Einstellungen des Schlafmodus gespeichert.',
    'unsupported' => 'Die Wings-Version auf diesem Node kennt den Schlafmodus noch nicht, die Einstellung wirkt also erst nach einem Wings-Update.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eDieser Server schläft §7- tritt bei, um ihn aufzuwecken',
        'starting' => '§eDer Server hat geschlafen und startet jetzt. §7Bitte verbinde dich in etwa einer Minute erneut.',
    ],
];
