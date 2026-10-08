<?php

return [
    'daemon_connection_failed' => 'Ocorreu uma exceção ao tentar se comunicar com o daemon, resultando em um código de resposta HTTP/:code. Esta exceção foi registrada.',
    'node' => [
        'servers_attached' => 'Um nó não pode ter servidores vinculados para poder ser excluído.',
        'daemon_off_config_updated' => 'A configuração do daemon foi atualizada, mas ocorreu um erro ao tentar atualizar automaticamente o arquivo de configuração no daemon. Você precisará atualizar manualmente o arquivo de configuração (config.yml) do daemon para aplicar estas alterações.',
    ],
    'allocations' => [
        'server_using' => 'Há um servidor atribuído a esta alocação no momento. Uma alocação só pode ser excluída se nenhum servidor estiver atribuído a ela.',
        'too_many_ports' => 'Adicionar mais de 1000 portas em um único intervalo de uma vez não é suportado.',
        'invalid_mapping' => 'O mapeamento fornecido para :port era inválido e não pôde ser processado.',
        'cidr_out_of_range' => 'A notação CIDR só permite máscaras entre /25 e /32.',
        'port_out_of_range' => 'As portas de uma alocação devem ser maiores que 1024 e menores ou iguais a 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Um Nest com servidores ativos vinculados não pode ser excluído do painel.',
        'egg' => [
            'delete_has_servers' => 'Um Egg com servidores ativos vinculados não pode ser excluído do painel.',
            'invalid_copy_id' => 'O Egg selecionado para copiar um script não existe ou ele mesmo copia um script.',
            'must_be_child' => 'A diretiva "Copiar configurações de" deste Egg deve ser uma opção filha do Nest selecionado.',
            'has_children' => 'Este Egg é pai de um ou mais outros Eggs. Exclua esses Eggs antes de excluir este.',
        ],
        'variables' => [
            'env_not_unique' => 'A variável de ambiente :name deve ser única neste Egg.',
            'reserved_name' => 'A variável de ambiente :name é protegida e não pode ser atribuída a uma variável.',
            'bad_validation_rule' => 'A regra de validação ":rule" não é uma regra válida para esta aplicação.',
        ],
        'importer' => [
            'json_error' => 'Ocorreu um erro ao tentar analisar o arquivo JSON: :error.',
            'file_error' => 'O arquivo JSON fornecido não era válido.',
            'invalid_json_provided' => 'O arquivo JSON fornecido não está em um formato reconhecível.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Não é permitido editar sua própria conta de subusuário.',
        'user_is_owner' => 'Você não pode adicionar o proprietário do servidor como subusuário deste servidor.',
        'subuser_exists' => 'Um usuário com esse endereço de e-mail já está atribuído como subusuário deste servidor.',
    ],
    'databases' => [
        'delete_has_databases' => 'Não é possível excluir um servidor host de banco de dados que tenha bancos de dados ativos vinculados.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'O intervalo máximo para uma tarefa encadeada é de 15 minutos.',
    ],
    'locations' => [
        'has_nodes' => 'Não é possível excluir uma localização que tenha nós ativos vinculados.',
    ],
    'users' => [
        'node_revocation_failed' => 'Falha ao revogar as chaves no <a href=":link">Nó #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Nenhum nó que satisfaça os requisitos especificados para a implantação automática foi encontrado.',
        'no_viable_allocations' => 'Nenhuma alocação que satisfaça os requisitos da implantação automática foi encontrada.',
        'no_placement' => 'Nenhum nó pode receber este servidor neste momento (memória e disco livres suficientes, uma porta livre, online e fora de manutenção). Nada foi criado.',
    ],
    'api' => [
        'resource_not_found' => 'O recurso solicitado não existe neste servidor.',
    ],
    'server' => [
        'cpu_above_node' => 'Um servidor pode receber no máximo :max% de CPU no node :node (:threads threads de CPU, 100% cada). :cpu% é mais do que o node tem.',
        'memory_above_node' => 'Um servidor pode receber no máximo :max MiB de memória no node :node. :memory MiB é mais do que o node tem.',
        'disk_above_node' => 'Um servidor pode receber no máximo :max MiB de disco no node :node. :disk MiB é mais do que o node tem.',
    ],
];
