<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Integration');
uses(RefreshDatabase::class)->in('Integration', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

require __DIR__.'/Feature/PlatformAdmin/Helpers.php';
require __DIR__.'/Unit/Application/Engagement/Helpers.php';
