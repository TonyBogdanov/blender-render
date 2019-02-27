<?php

$log = isset( $_GET['log'] ) ? $_GET['log'] : null;

if ( ! isset( $log ) ) {

    $logs = array_reduce( glob( __DIR__ . '/sheepit/cache/log-*.log' ), function ( array $logs, string $path ) {

        $name = substr( pathinfo( $path, PATHINFO_FILENAME ), 4 );
        $date = date( 'd.m.Y - H:i:s', (int) $name );

        $logs[ $date ] = $name;

        return $logs;

    }, [] );

    ksort( $logs );

    echo json_encode( $logs );
    exit;

}
