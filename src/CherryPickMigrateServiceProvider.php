<?php

namespace Djunehor\CherryPick;

use Illuminate\Support\ServiceProvider;

class CherryPickMigrateServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->commands( [
			\Djunehor\CherryPickMigrate\Commands\CherryPickMigrateCommand::class,
		] );
	}
}
