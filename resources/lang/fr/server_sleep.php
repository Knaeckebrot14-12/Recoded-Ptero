<?php

return [
    'title' => 'Veille quand vide',
    'description' => 'Quand personne n\'est allé sur ce serveur pendant un moment, il est arrêté pour libérer sa mémoire. Il reste dans la liste des serveurs, et le premier joueur qui le rejoint le redémarre (il est invité à se reconnecter dans un instant). Fonctionne avec les serveurs Minecraft Java.',
    'enabled_label' => 'Veille quand vide',
    'minutes_label' => 'Arrêter quand personne n\'était connecté depuis',
    'minutes' => ':count minutes',
    'default_note' => 'La valeur par défaut du panel s\'applique : :state, :minutes minutes.',
    'state_on' => 'activée',
    'state_off' => 'désactivée',
    'reset' => 'Utiliser la valeur par défaut du panel',
    'saved' => 'Paramètres du mode veille enregistrés.',
    'unsupported' => 'La version de Wings de ce node ne connaît pas encore le mode veille ; le réglage n\'aura d\'effet qu\'après la mise à jour de Wings.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eCe serveur est en veille §7- rejoignez-le pour le réveiller',
        'starting' => '§eLe serveur était en veille et démarre maintenant. §7Reconnectez-vous dans environ une minute.',
    ],
];
