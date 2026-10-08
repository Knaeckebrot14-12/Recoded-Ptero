<?php

return [
    'title' => 'Suspender quando vazio',
    'description' => 'Quando ninguém esteve neste servidor por algum tempo, ele é parado para libertar a memória. Continua na lista de servidores e o primeiro jogador que entrar volta a iniciá-lo (é-lhe pedido que se ligue novamente daqui a pouco). Funciona com servidores Minecraft Java.',
    'enabled_label' => 'Suspender quando vazio',
    'minutes_label' => 'Parar quando ninguém esteve ligado durante',
    'minutes' => ':count minutos',
    'default_note' => 'Aplica-se o padrão do painel: :state, :minutes minutos.',
    'state_on' => 'ativado',
    'state_off' => 'desativado',
    'reset' => 'Usar o padrão do painel',
    'saved' => 'Definições do modo de suspensão guardadas.',
    'unsupported' => 'A versão do Wings neste nó ainda não conhece o modo de suspensão, por isso a definição só tem efeito depois de atualizar o Wings.',
    // Shown to Minecraft players: in their server list and when they join a sleeping server.
    'minecraft' => [
        'motd' => '§6zZz §eEste servidor está a dormir §7- entra para o acordar',
        'starting' => '§eO servidor estava a dormir e está a iniciar agora. §7Volta a ligar-te daqui a cerca de um minuto.',
    ],
];
