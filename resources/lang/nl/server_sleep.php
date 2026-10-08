<?php

return [
    'title' => 'Slapen als leeg',
    'description' => 'Als er een tijdje niemand op deze server is geweest, wordt hij gestopt om geheugen vrij te maken. Hij blijft in de serverlijst staan en de eerste speler die meedoet, start hem weer (die wordt gevraagd zo opnieuw te verbinden). Werkt met Minecraft Java-servers.',
    'enabled_label' => 'Slapen als leeg',
    'minutes_label' => 'Stoppen als er zo lang niemand op was',
    'minutes' => ':count minuten',
    'default_note' => 'De standaard van het paneel geldt: :state, :minutes minuten.',
    'state_on' => 'aan',
    'state_off' => 'uit',
    'reset' => 'Standaard van het paneel gebruiken',
    'saved' => 'Instellingen voor de slaapstand opgeslagen.',
    'unsupported' => 'De Wings-versie op deze node kent de slaapstand nog niet, dus de instelling werkt pas na een Wings-update.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eDeze server slaapt §7- doe mee om hem wakker te maken',
        'starting' => '§eDe server sliep en start nu op. §7Verbind over ongeveer een minuut opnieuw.',
    ],
];
