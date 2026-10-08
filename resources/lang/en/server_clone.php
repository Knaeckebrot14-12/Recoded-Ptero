<?php

return [
    'title' => 'Copy this server',
    'description' => 'Copies all files of this server into another of your servers of the same type. Its current files go into its trash (restorable for :hours hours) when there is enough room, otherwise they are deleted. Both servers must be stopped.',
    'target_label' => 'Copy into',
    'choose' => 'Choose a server',
    'start' => 'Copy files',
    'confirm_title' => 'Replace the files of :target?',
    'confirm_body' => 'All files of :target are replaced by a copy of this server\'s files. This can take a few minutes.',
    'running' => 'Copying… :files files so far.',
    'done' => 'Done: :files files were copied into :target.',
    'done_kept' => 'Its previous files are in its trash.',
    'done_deleted' => 'Its previous files were deleted (not enough room to keep them).',
    'failed' => 'The copy failed: :error',
    'unsupported' => 'This node\'s Wings is too old for copying (needs :version or newer).',
    'reasons' => [
        'other_node' => 'on another node',
        'no_permission' => 'no permission',
        'unavailable' => 'unavailable right now',
    ],
    'errors' => [
        'no_permission' => 'You may not read the files of this server.',
        'not_allowed' => 'This server can\'t be the target.',
        'other_node' => 'Only servers on the same node can be copied into.',
        'unavailable' => 'One of the servers is suspended, installing or restoring.',
        'wings_too_old' => 'This node\'s Wings is too old for copying (needs :version or newer).',
    ],
];
