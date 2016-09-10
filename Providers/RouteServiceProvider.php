<?php

namespace App\Modules\Enroll\Providers;

use Caffeinated\Modules\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * The controller namespace for the module.
	 *
	 * @var string|null
	 */
	protected $namespace = 'App\Modules\Enroll\Http\Controllers';

	/**
	 * Define your module's route model bindings, pattern filters, etc.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function boot(Router $router)
	{
		parent::boot($router);

		// 导入路由依赖的命名中间件
        $router->middleware('enroll.auth', \App\Modules\Enroll\Http\Middleware\Authenticate::class);
        $router->middleware('enroll.hold', \App\Modules\Enroll\Http\Middleware\HoldRequestStatus::class);
        $router->middleware('enroll.guest', \App\Modules\Enroll\Http\Middleware\RedirectIfAuthenticated::class);
        $router->middleware('enroll.setup', \App\Modules\Enroll\Http\Middleware\SetupCircuitByYear::class);
	}

	/**
	 * Define the routes for the module.
	 *
	 * @param  \Illuminate\Routing\Router $router
	 * @return void
	 */
	public function map(Router $router)
	{
		$router->group([
			'namespace'  => $this->namespace,
			'middleware' => ['web']
		], function($router) {
			require (config('modules.path').'/Enroll/Http/routes.php');
		});
	}
}
