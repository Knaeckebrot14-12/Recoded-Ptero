<?php

return [
    'title' => 'Copiar este servidor',
    'description' => 'Copia todos os ficheiros deste servidor para outro dos teus servidores do mesmo tipo. Os ficheiros atuais dele vão para a reciclagem dele (restauráveis durante :hours horas) se houver espaço suficiente; caso contrário, são eliminados. Ambos os servidores têm de estar parados.',
    'target_label' => 'Copiar para',
    'choose' => 'Escolhe um servidor',
    'start' => 'Copiar ficheiros',
    'confirm_title' => 'Substituir os ficheiros de :target?',
    'confirm_body' => 'Todos os ficheiros de :target são substituídos por uma cópia dos ficheiros deste servidor. Pode demorar alguns minutos.',
    'running' => 'A copiar… :files ficheiros até agora.',
    'done' => 'Concluído: :files ficheiros copiados para :target.',
    'done_kept' => 'Os ficheiros anteriores estão na reciclagem dele.',
    'done_deleted' => 'Os ficheiros anteriores foram eliminados (sem espaço para os guardar).',
    'failed' => 'A cópia falhou: :error',
    'unsupported' => 'O Wings deste nó é demasiado antigo para copiar (requer :version ou mais recente).',
    'reasons' => [
        'other_node' => 'noutro nó',
        'no_permission' => 'sem permissão',
        'unavailable' => 'indisponível agora',
    ],
    'errors' => [
        'no_permission' => 'Não podes ler os ficheiros deste servidor.',
        'not_allowed' => 'Este servidor não pode ser o destino.',
        'other_node' => 'Só é possível copiar para servidores no mesmo nó.',
        'unavailable' => 'Um dos servidores está suspenso, a instalar ou a restaurar.',
        'wings_too_old' => 'O Wings deste nó é demasiado antigo para copiar (requer :version ou mais recente).',
    ],
];
