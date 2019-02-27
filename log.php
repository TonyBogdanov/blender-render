<?php

$log = isset( $_GET['log'] ) ? $_GET['log'] : null;
$after = isset( $_GET['after'] ) ? (int) $_GET['after'] : 0;

if ( ! isset( $log ) ) {

    $logs = array_reduce( glob( __DIR__ . '/sheepit/cache/log-*.log' ), function ( array $logs, string $path ) {

        $name = substr( pathinfo( $path, PATHINFO_FILENAME ), 4 );
        $date = date( 'd.m.Y' ) === date( 'd.m.Y', (int) $name ) ?
            date( 'H:i:s', (int) $name ) :
            date( 'd.m.Y - H:i:s', (int) $name );

        $logs[ $date ] = $name;

        return $logs;

    }, [] );

    krsort( $logs );

    echo json_encode( [ md5( json_encode( $logs ) ), $logs ] );
    exit;

}

$result = [];
$now    = date( 'd.m.Y' );
$fp     = fopen( __DIR__ . '/sheepit/cache/log-' . basename( $log ) . '.log', 'rb' );

while ( ! feof( $fp ) ) {

    $time = (int) fread( $fp, 10 );
    if ( 0 === $time ) {

        break;

    }

    $length = (int) fread( $fp, 10 );
    if ( 0 === $length ) {

        break;

    }

    $message = fread( $fp, $length );

    if ( $time > $after ) {

        $result[] = [

            $now === date( 'd.m.Y', $time ) ? date( 'H:i:s', $time ) : date( 'd.m.Y - H:i:s', $time ),
            $message,

        ];

    }

}

fclose( $fp );

echo json_encode( [ time(), $result ] );
