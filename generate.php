<?php

declare(strict_types=1);

namespace Ghostwriter\TnTherapists;

use Ghostwriter\TnTherapists\Model\Therapist;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Database;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

(static function () {
    /** @var null|string $path */
    $path = array_reduce(
        [
            __DIR__ . '/vendor/autoload.php',
            __DIR__ . '/../../../autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../autoload.php',
        ],
        static fn ($carry, $item) => (($carry === null) && file_exists($item)) ? $item : $carry
    );
    if ($path === null) {
        fwrite(STDERR, 'Cannot locate autoloader; please run "composer install"' . PHP_EOL);
        exit(1);
    }
    require $path;

    $filesystem = new Filesystem();

    $databasePath = './database.sqlite';
    if ($filesystem->missing($databasePath)) {
        $filesystem->put($databasePath, '');
    }

    $database = new Database();
    $database->addConnection([
        'driver'    => 'sqlite',
        'database' => 'database.sqlite',
        'prefix' => '',
    ]);
    $database->setEventDispatcher(new Dispatcher(new Container()));
    $database->setAsGlobal();
    $database->bootEloquent();

    $tableTemplate = $filesystem->get('./table.html');

    $filesystem->put(
        './README.md',
        str_replace(
            '{therapists}',
            collect([
                '> Collection of Black and African American Therapists in Nashville, TN and Therapists serving Black and African American communities.',

                PHP_EOL,
                '> Publicly available information collected to help promote Healing Ourselves and Healing Others. #BlackLivesMatter',
                PHP_EOL,
            ])->merge(
                Therapist::all()->sortBy('title')->collect()->map(static fn (Therapist $therapist) => dump(sprintf(
                    $tableTemplate,
                    $therapist->getAttribute('subtitle'),
                    $therapist->getAttribute('hash'),
                    $therapist->getAttribute('image'),
                    $therapist->getAttribute('title'),
                    $therapist->getAttribute('statement'),
                    $therapist->getAttribute('offersOnlineTherapy'),
                    $therapist->getAttribute('acceptingAppointments'),
                    $therapist->getAttribute('location'),
                    $therapist->getAttribute('contact'),
                )))
            )->join(PHP_EOL),
            $filesystem->get('./README.md.tmp')
        )
    );
})();
