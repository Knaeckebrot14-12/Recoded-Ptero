<?php

return [
    'title' => 'Copiar este servidor',
    'description' => 'Copia todos los archivos de este servidor en otro de tus servidores del mismo tipo. Sus archivos actuales van a su papelera (restaurables durante :hours horas) si hay espacio suficiente; si no, se eliminan. Ambos servidores deben estar detenidos.',
    'target_label' => 'Copiar en',
    'choose' => 'Elige un servidor',
    'start' => 'Copiar archivos',
    'confirm_title' => '¿Reemplazar los archivos de :target?',
    'confirm_body' => 'Todos los archivos de :target se reemplazan por una copia de los archivos de este servidor. Puede tardar unos minutos.',
    'running' => 'Copiando… :files archivos hasta ahora.',
    'done' => 'Listo: se copiaron :files archivos en :target.',
    'done_kept' => 'Sus archivos anteriores están en su papelera.',
    'done_deleted' => 'Sus archivos anteriores se eliminaron (no había espacio para conservarlos).',
    'failed' => 'La copia falló: :error',
    'unsupported' => 'Wings en este nodo es demasiado antiguo para copiar (necesita :version o posterior).',
    'reasons' => [
        'other_node' => 'en otro nodo',
        'no_permission' => 'sin permiso',
        'unavailable' => 'no disponible ahora',
    ],
    'errors' => [
        'no_permission' => 'No puedes leer los archivos de este servidor.',
        'not_allowed' => 'Este servidor no puede ser el destino.',
        'other_node' => 'Solo se puede copiar en servidores del mismo nodo.',
        'unavailable' => 'Uno de los servidores está suspendido, instalándose o restaurándose.',
        'wings_too_old' => 'Wings en este nodo es demasiado antiguo para copiar (necesita :version o posterior).',
    ],
];
