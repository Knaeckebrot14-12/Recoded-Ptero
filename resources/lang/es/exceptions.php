<?php

return [
    'daemon_connection_failed' => 'Se produjo una excepción al intentar comunicarse con el daemon, con el código de respuesta HTTP/:code. Esta excepción se ha registrado.',
    'node' => [
        'servers_attached' => 'Un nodo no debe tener servidores vinculados para poder eliminarse.',
        'daemon_off_config_updated' => 'La configuración del daemon se ha actualizado, pero se produjo un error al intentar actualizar automáticamente el archivo de configuración en el daemon. Deberás actualizar manualmente el archivo de configuración (config.yml) del daemon para aplicar estos cambios.',
    ],
    'allocations' => [
        'server_using' => 'Actualmente hay un servidor asignado a esta asignación. Una asignación solo se puede eliminar si no tiene ningún servidor asignado.',
        'too_many_ports' => 'No se admite añadir más de 1000 puertos en un solo rango a la vez.',
        'invalid_mapping' => 'El mapeo proporcionado para :port no era válido y no se pudo procesar.',
        'cidr_out_of_range' => 'La notación CIDR solo permite máscaras entre /25 y /32.',
        'port_out_of_range' => 'Los puertos de una asignación deben ser mayores que 1024 y menores o iguales que 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'No se puede eliminar del panel un Nest que tiene servidores activos asociados.',
        'egg' => [
            'delete_has_servers' => 'No se puede eliminar del panel un Egg que tiene servidores activos asociados.',
            'invalid_copy_id' => 'El Egg seleccionado para copiar un script no existe o él mismo copia un script.',
            'must_be_child' => 'La directiva «Copiar ajustes de» de este Egg debe ser una opción hija del Nest seleccionado.',
            'has_children' => 'Este Egg es padre de uno o más Eggs. Elimina esos Eggs antes de eliminar este.',
        ],
        'variables' => [
            'env_not_unique' => 'La variable de entorno :name debe ser única en este Egg.',
            'reserved_name' => 'La variable de entorno :name está protegida y no se puede asignar a una variable.',
            'bad_validation_rule' => 'La regla de validación «:rule» no es una regla válida para esta aplicación.',
        ],
        'importer' => [
            'json_error' => 'Se produjo un error al intentar analizar el archivo JSON: :error.',
            'file_error' => 'El archivo JSON proporcionado no era válido.',
            'invalid_json_provided' => 'El archivo JSON proporcionado no tiene un formato reconocible.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'No se permite editar tu propia cuenta de subusuario.',
        'user_is_owner' => 'No puedes añadir al propietario del servidor como subusuario de este servidor.',
        'subuser_exists' => 'Un usuario con esa dirección de correo ya está asignado como subusuario de este servidor.',
    ],
    'databases' => [
        'delete_has_databases' => 'No se puede eliminar un servidor host de bases de datos que tiene bases de datos activas vinculadas.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'El intervalo máximo para una tarea encadenada es de 15 minutos.',
    ],
    'locations' => [
        'has_nodes' => 'No se puede eliminar una ubicación que tiene nodos activos asociados.',
    ],
    'users' => [
        'node_revocation_failed' => 'No se pudieron revocar las claves en <a href=":link">Nodo #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'No se encontraron nodos que cumplan los requisitos especificados para el despliegue automático.',
        'no_viable_allocations' => 'No se encontraron asignaciones que cumplan los requisitos del despliegue automático.',
        'no_placement' => 'Ningún nodo puede alojar este servidor ahora mismo (suficiente memoria y disco libres, un puerto libre, en línea y sin mantenimiento). No se ha creado nada.',
    ],
    'api' => [
        'resource_not_found' => 'El recurso solicitado no existe en este servidor.',
    ],
    'server' => [
        'cpu_above_node' => 'Un servidor puede recibir como máximo :max% de CPU en el node :node (:threads hilos de CPU, 100% cada uno). :cpu% es más de lo que tiene el node.',
        'memory_above_node' => 'Un servidor puede recibir como máximo :max MiB de memoria en el node :node. :memory MiB es más de lo que tiene el node.',
        'disk_above_node' => 'Un servidor puede recibir como máximo :max MiB de disco en el node :node. :disk MiB es más de lo que tiene el node.',
    ],
];
