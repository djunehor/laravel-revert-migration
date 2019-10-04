<?php

namespace Djunehor\CherryPick\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CherryPickMigrateCommand extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'migrate:cherrypick {--f|file=} {--d|directory=} {--r|revert=}';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Migrate or revert only specific migrations using file name or directory';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */

	public function handle() {
		$directory  = $this->option( 'directory' );
		$given_file = $this->option( 'file' );
		$revert     = ! is_null( $this->option( 'revert' ) );

		if ( ! $directory && ! $given_file ) {
			$this->error( "File name or Directory is required" );

			return;
		}

		$mainPath    = database_path( 'migrations' );
		$directories = glob( $mainPath . '/' . $directory . '*', GLOB_ONLYDIR );
		$paths       = array_merge( [ $mainPath ], $directories );
		if ( $given_file ) {
			$files[0] = $mainPath . '/' . $given_file . '.php';
		} else {
			$files = glob( $paths[0] . '/*.php' );
		}

		//migration is impossible without a migrations table
		if ( Schema::hasTable( 'migrations' ) ) {
			$batch_no = DB::table( 'migrations' )->max( 'batch' );
			$this->info( 'MIGRATION STARTED' );

			foreach ( $files as $key => $file ) {
				$basename  = basename( $file );
				$file_info = pathinfo( $basename );
				$file_name = $file_info['filename'];

				if ( ! file_exists( $file ) ) {
					$this->info( "-> Migration $file not found" );
					continue;
				}

				require_once( $mainPath . '/' . $basename );
				$all_classes        = get_declared_classes();
				$lastTableClassName = end( $all_classes );

				if ( $revert ) {
					if ( $never_ran = ! DB::table( 'migrations' )->where( 'migration', $file_name )->first() ) {
						$this->info( "-> Migration $file never ran" );
						continue;
					}

					$tableClass = new $lastTableClassName();
					$tableClass->down();
					$this->info( '-> ' . $file_name . ' SUCCESSFULLY REVERTED' );
					continue;


				} else {
					if ( $already_migrated = DB::table( 'migrations' )->where( 'migration', $file_name )->first() ) {
						$this->info( "-> Migration $file already ran" );
						continue;
					}

					$tableClass = new $lastTableClassName();
					$tableClass->up();
					$this->info( '-> ' . $file_name . ' SUCCESSFULLY MIGRATED' );
					continue;

				}

			}
			$this->info( 'MIGRATION COMPLETED' );
		} else {
			$this->error( '-> MIGRATIONS TABLE NOT FOUND. PLEASE FIRST RUN "php artisan migrate" <-' );
		}
	}
}
