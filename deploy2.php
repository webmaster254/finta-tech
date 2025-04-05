<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'https://github.com/webmaster254/fanikisha-microfinance.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('154.41.228.213')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/microfinance');

// Hooks

after('deploy:failed', 'deploy:unlock');
