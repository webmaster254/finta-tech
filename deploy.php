<?php
namespace Deployer;

require 'recipe/laravel.php';

// Configuration
set('repository', 'git@github.com:webmaster254/fanikisha-microfinance.git');
set('default_stage', 'production');
set('keep_releases', 3);

// Hosts
host('fanikishamicrofinancebank.com')

    ->set('remote_user', 'root')
    ->set('deploy_path', '/home/fanikishamicrofinancebank.com/test/');

// Tasks
task('deploy:update_code', function () {
    upload(__DIR__ . '/.env.production', '{{release_path}}/.env');
});
// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
// Deployment flow
desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'deploy:symlink',
    'deploy:cleanup',
    'deploy:success',
]);


