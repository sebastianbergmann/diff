<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '7.0.x-dev',
        'version' => '7.0.9999999.9999999-dev',
        'reference' => '08c7a665a2f833b935a5c92884f4a45a81cbc54c',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '7.0.x-dev',
            'version' => '7.0.9999999.9999999-dev',
            'reference' => '08c7a665a2f833b935a5c92884f4a45a81cbc54c',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'friendsofphp/php-cs-fixer' => array(
            'dev_requirement' => true,
            'replaced' => array(
                0 => 'v3.95.30',
            ),
        ),
        'php-cs-fixer/shim' => array(
            'pretty_version' => 'v3.95.30',
            'version' => '3.95.30.0',
            'reference' => '413c9705404163fa3e5f7854208c5e9ae1a4590d',
            'type' => 'application',
            'install_path' => __DIR__ . '/../php-cs-fixer/shim',
            'aliases' => array(),
            'dev_requirement' => true,
        ),
    ),
);
