<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class BrowserTestCase extends BaseTestCase
{
    /**
     * Use a file-backed database so real browser requests share test state.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $databasePath = database_path(sprintf('browser-testing-%d.sqlite', getmypid()));

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        $app['config']->set('database.connections.sqlite.database', $databasePath);

        return $app;
    }
}
