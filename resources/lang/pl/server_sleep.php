<?php

return [
    'title' => 'Usypiaj, gdy pusty',
    'description' => 'Gdy przez jakiś czas nikogo nie było na tym serwerze, zostaje on zatrzymany, aby zwolnić pamięć. Pozostaje na liście serwerów, a pierwszy gracz, który dołączy, uruchamia go ponownie (zostaje poproszony o ponowne połączenie za chwilę). Działa z serwerami Minecraft Java.',
    'enabled_label' => 'Usypiaj, gdy pusty',
    'minutes_label' => 'Zatrzymaj, gdy nikogo nie było przez',
    'minutes' => ':count min',
    'default_note' => 'Obowiązuje ustawienie domyślne panelu: :state, :minutes min.',
    'state_on' => 'włączone',
    'state_off' => 'wyłączone',
    'reset' => 'Użyj ustawienia domyślnego panelu',
    'saved' => 'Zapisano ustawienia trybu uśpienia.',
    'unsupported' => 'Wersja Wings na tym węźle nie zna jeszcze trybu uśpienia, więc ustawienie zadziała dopiero po aktualizacji Wings.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eTen serwer śpi §7- dołącz, aby go obudzić',
        'starting' => '§eSerwer spał i właśnie się uruchamia. §7Połącz się ponownie za około minutę.',
    ],
];
