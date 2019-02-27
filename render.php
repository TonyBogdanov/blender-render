<?php

define( 'DIR', str_replace( '\\', '/', __DIR__ ) . '/sheepit' );

class Log {

    protected $path;

    public function __construct() {

        $this->path = DIR . '/cache/log-' . time() . '.log';

    }

    public function write( string $message ): Log {

        echo $message;

        file_put_contents(

            $this->path,
            str_pad( time(), 10 ) .
            str_pad( strlen( $message ), 10 ) .
            $message,
            FILE_APPEND

        );

        return $this;

    }

    public function writeln( string $message ): Log {

        return $this->write( $message . PHP_EOL );

    }

}

function check_dir() {

    if ( ! is_dir( DIR ) ) {

        echo 'SheepIT dir missing, creating...' . PHP_EOL;

        if ( ! @mkdir( DIR ) ) {

            exit( 'Could not create sheepit dir.' );

        }

    } else {

        echo 'SheepIT dir OK.' . PHP_EOL;

    }

}

function check_cache_dir() {

    if ( ! is_dir( DIR . '/cache' ) ) {

        echo 'SheepIT cache dir missing, creating...' . PHP_EOL;

        if ( ! @mkdir( DIR . '/cache' ) ) {

            exit( 'Could not create SheepIT cache dir.' );

        }

    } else {

        echo 'SheepIT cache dir OK.' . PHP_EOL;

    }

}

function check_client() {

    if ( ! is_file( DIR . '/sheepit.jar' ) ) {

        echo 'SheepIT client missing, downloading...' . PHP_EOL;

        if ( ! ( $download = @file_get_contents( 'https://www.sheepit-renderfarm.com/media/applet/client-latest.php' ) ) ) {

            exit( 'Could not download client.' );

        }

        if ( ! @file_put_contents( DIR . '/sheepit.jar', $download ) ) {

            exit( 'Could not save client.' );

        }

    } else {

        echo 'SheepIT client OK.' . PHP_EOL;

    }

}

function check_config( $device, $username, $password ) {

    if ( ! is_file( DIR . '/sheepit.conf' ) ) {

        echo 'SheepIT config missing, generating...' . PHP_EOL;

        $cache = DIR . '/cache';
        $config = "cache-dir=" . $cache . PHP_EOL .
                  "priority=-19" . PHP_EOL .
                  "login=" . $username . PHP_EOL .
                  "password=" . $password . PHP_EOL;

        if ( $device ) {

            $config .= 'compute-method=GPU' . PHP_EOL;
            $config .= 'compute-gpu=' . $device . PHP_EOL;
            $config .= 'tile-size=120' . PHP_EOL;

        } else {

            $config .= 'compute-method=CPU' . PHP_EOL;
            $config .= 'tile-size=16' . PHP_EOL;

        }

        if ( ! @file_put_contents( DIR . '/sheepit.conf', $config ) ) {

            exit( 'Could not save config.' );

        }

    } else {

        echo 'SheepIT config OK.' . PHP_EOL;

    }

}

function check_composer() {

    if ( ! is_file( DIR . '/composer.json' ) || ! is_file( DIR . '/composer.lock' ) ) {

        echo 'Composer missing, installing...' . PHP_EOL;

        $composer = <<<COMPOSER
{
    "name": "tb/sheepit",
    "type": "project",
    "require": {
        "symfony/process": "^4.3@dev"
    },
    "minimum-stability": "dev"
}
COMPOSER;

        if ( ! @file_put_contents( DIR . '/composer.json', $composer ) ) {

            exit( 'Could not create composer.json.' );

        }

        $result = '';

        chdir( DIR );
        passthru( 'composer install', $result );

        if ( 0 !== $result ) {

            exit( 'Could not install composer packages.' );

        }

    } else {

        echo 'Composer OK.' . PHP_EOL;

    }

}

function get_device() {

    $process = new \Symfony\Component\Process\Process(

        [

            'java',
            '-jar', 'sheepit.jar',
            '--show-gpu',

        ],
        DIR,
        null,
        null,
        null

    );

    $process->mustRun();

    if ( preg_match( '/cuda_\d+/i', $process->getOutput(), $matches ) ) {

        return $matches[0];

    }

    return null;

}

function render( $device, Log $log ) {

    if ( $device ) {

        $log->writeln( 'Rendering with GPU.' );

    } else {

        $log->writeln( 'Rendering with CPU.' );

    }

    $process = new \Symfony\Component\Process\Process(

        [

            'java',
            '-jar', DIR . '/sheepit.jar',
            '-ui', 'text',
            '-config', DIR . '/sheepit.conf',

        ],
        DIR,
        null,
        null,
        null

    );

    $process->start();
    $process->waitUntil( function ( $type, $output ) use ( $process, $log ) {

        if ( $process::OUT === $type ) {

            $log->write( $output );

        } else {

            $log->write( '[ERR] ' . $output );

        }

        return preg_match( '/no job available\. sleeping for/i', $output );

    } );

    $process->stop();

    return true;

}

check_dir();
check_cache_dir();
check_client();
check_composer();

require_once DIR . '/vendor/autoload.php';

define( 'DEVICE', get_device() );

array_shift( $argv );

$username   = array_shift( $argv );
$password   = array_shift( $argv );

check_config( DEVICE, $username, $password );

while ( true ) {

    try {

        render( DEVICE, new Log() );

    } catch ( \Exception $e ) {}

}
