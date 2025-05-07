<?php
// test-mercure.php
require __DIR__.'/vendor/autoload.php';

$hub = new \Symfony\Component\Mercure\Hub(
    'http://localhost:3000/.well-known/mercure',
    '!ChangeThisMercureHubJWTSecretKey!'
);

$update = new \Symfony\Component\Mercure\Update(
    '/test/1',
    json_encode(['status' => 'OK'])
);

try {
    $hub->publish($update);
    echo "Publication réussie!";
} catch (\Exception $e) {
    echo "Échec: ".$e->getMessage();
}