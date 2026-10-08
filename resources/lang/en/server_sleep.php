<?php

return [
    'title' => 'Sleep when empty',
    'description' => 'When nobody has been on this server for a while, it is stopped to free its memory. It stays in the server list, and the first player who joins starts it again (they are asked to reconnect in a moment). Works with Minecraft Java servers.',
    'enabled_label' => 'Sleep when empty',
    'minutes_label' => 'Stop after nobody was on for',
    'minutes' => ':count minutes',
    'default_note' => 'Following the panel default: :state, :minutes minutes.',
    'state_on' => 'on',
    'state_off' => 'off',
    'reset' => 'Use the panel default',
    'saved' => 'Sleep mode settings saved.',
    'unsupported' => 'The Wings version on this node does not know sleep mode yet, so the setting has no effect until Wings is updated.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eThis server is sleeping §7- join to wake it up',
        'starting' => '§eThe server was sleeping and is starting now. §7Please reconnect in about a minute.',
    ],
];
