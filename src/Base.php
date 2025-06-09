<?php

namespace DocDoc;

abstract class Base
{
    /** @var string|null Percorso assoluto alla root del progetto */
    protected static ?string $projectRoot = null;
    protected static ?string $projectDir = null;

    /**
     * Inizializza la costante PROJECT_ROOT e carica config.php una sola volta.
     */
    protected static function init(): void
    {
        if (self::$projectRoot !== null) {
            return; // già inizializzato
        }

        $baseDirName = basename(__DIR__, 1);
        $baseDir = dirname(__DIR__, 1); // risale alla root-del-progetto
        $configPath = $baseDir . '/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;

            if (defined('PROJECT_ROOT')) {
                self::$projectRoot = PROJECT_ROOT;
            } else {
                // fallback se la define non è stata fatta per errore
                self::$projectRoot = $baseDir;
            }

            if (defined('PROJECT_DIR')) {
                self::$projectDir = PROJECT_DIR;
            } else {
                // fallback se la define non è stata fatta per errore
                self::$projectDir = $baseDirName;
            }
        } else {
            // fallback estremo
            self::$projectDir = $baseDirName;
            self::$projectRoot = $baseDir;
        }

        // Base.php è in src/Base.php


    }

    /**
     * Ritorna il percorso assoluto alla root del progetto
     */
    public static function getProjectRoot(): string
    {
        self::init();
        return self::$projectRoot;
    }

    /**
     * Ritorna il percorso assoluto alla root del progetto
     */
    public static function getProjectDir(): string
    {
        self::init();
        return self::$projectDir;
    }
}
